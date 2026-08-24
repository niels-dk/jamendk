<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../app/mailer.php';

class user_controller
{
    /** GET /login  (form)  |  POST /login  (authenticate) */
    public static function login(): void
    {
        $error  = null;
        $notice = null;
        // Set when the password was right but the address isn't confirmed —
        // the view uses it to offer a resend link.
        $unverifiedEmail = null;

        // One-shot notices handed over by register / verify / reset.
        if (!empty($_SESSION['flash'])) {
            $notice = $_SESSION['flash'];
            unset($_SESSION['flash']);
        }

        // Remember where the user was trying to go (set as ?next=)
        $next = self::safeNext($_REQUEST['next'] ?? '');
        // Arrived here from Logout with somewhere to return to.
        $signedOut = !empty($_GET['out']) && $next !== '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check($_POST['csrf_token'] ?? null)) {
                $error = t('auth.err_expired');
            } else {
                $email = trim($_POST['email'] ?? '');
                $pass  = $_POST['password'] ?? '';
                $ip    = (string)($_SERVER['REMOTE_ADDR'] ?? '');

                if (!$email || !$pass) {
                    $error = t('auth.err_both');
                }
                // Checked BEFORE authenticate(), so a throttled attacker gets
                // no password comparison and no timing signal at all. The
                // message is the same whichever limit tripped — saying which
                // would tell someone whether an address exists.
                elseif (User::loginThrottled($email, $ip)) {
                    $error = t('auth.err_throttled');
                }
                else if ($user = User::authenticate($email, $pass)) {
                    // Account blocked (e.g. the person left and an admin
                    // deactivated it during a handover).
                    if (!empty($user['deactivated_at'])) {
                        $error = t('auth.err_deactivated');
                    }
                    // Correct password, but the address was never confirmed.
                    // Refuse the session — this is what makes bot signups inert.
                    elseif (User::needsVerification($user)) {
                        $unverifiedEmail = $user['email'] ?? $email;
                        $error = t('auth.err_unverified');
                    } else {
                        // Correct password — wipe this identifier's history so a
                        // few fat-fingered attempts never count against you.
                        User::clearLoginAttempts($email);
                        self::loginUser($user);

                        // ?next= survives a sign-out, so it can belong to whoever
                        // was here BEFORE. Sign out of admin, sign back in as an
                        // ordinary user, and it would send them to /admin/... and
                        // straight into a 404. Re-check it against the account
                        // that actually signed in.
                        if ($next !== '' && !self::canVisit($next, $user)) {
                            $next = '';
                        }
                        redirect($next ?: 'dashboard');
                    }
                } else {
                    User::recordFailedLogin($email, $ip);
                    $error = t('auth.err_invalid');
                }
            }
        }
        include __DIR__ . '/../views/login.php';
    }

    /** GET /register  (form)  |  POST /register  (create + auto-login) */
    public static function register(): void
    {
        $error = null;
        $name  = '';
        $email = '';
        $next  = self::safeNext($_REQUEST['next'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check($_POST['csrf_token'] ?? null)) {
                $error = t('auth.err_expired');
            } elseif (trim($_POST['website'] ?? '') !== '') {
                // Honeypot: real users never see or fill this field.
                // Pretend success so bots don't learn they were caught.
                redirect('login');
            } else {
                $name  = trim($_POST['name']  ?? '');
                $email = trim($_POST['email'] ?? '');
                $pass  = $_POST['password'] ?? '';
                if (!$name) {
                    $error = t('auth.err_name');
                } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = t('auth.err_email');
                } else if (strlen($pass) < 6) {
                    $error = t('auth.err_short_pass');
                } else if (User::emailExists($email)) {
                    // Never confirm that an address is taken — that turns this
                    // form into an account-existence oracle. The real owner
                    // gets an email explaining what happened instead.
                    $existing = User::findByEmail($email);
                    if ($existing && !Mailer::rateLimited($email, 'reset_notice', 2, 60)) {
                        $raw = User::issueResetToken((int)$existing['id']);
                        if ($raw) {
                            Mailer::sendAlreadyRegistered($email, $existing['name'] ?? '', $raw);
                        }
                    }
                    self::flashToLogin($next);
                } else {
                    $newId = User::create($name, $email, $pass);
                    if (!$newId) {
                        $error = 'Could not create the account. Please try again.';
                    } else {
                        // No auto-login: the account stays inert until the
                        // address is confirmed.
                        $raw = User::issueVerifyToken($newId);
                        if ($raw) {
                            Mailer::sendVerification($email, $name, $raw);
                        }
                        self::flashToLogin($next);
                    }
                }
            }
        }
        include __DIR__ . '/../views/register.php';
    }

    /** Identical outcome whether the address was new or already taken. */
    private static function flashToLogin(string $next = ''): void
    {
        $_SESSION['flash'] = t('auth.check_inbox');
        redirect('login' . ($next ? '?next=' . urlencode($next) : ''));
    }

    /** GET /verify/{token} — confirm an address and burn the link. */
    public static function verifyEmail(string $token): void
    {
        $user = User::findByVerifyToken($token);
        if (!$user) {
            // Expired or already used. Offer a fresh one rather than a dead end.
            $error = 'That confirmation link has expired or was already used.';
            $pageTitle = t('auth.link_expired');
            $noSidebar = true;
            ob_start();
            include __DIR__ . '/../views/verify_resend.php';
            $content = ob_get_clean();
            include __DIR__ . '/../views/layout.php';
            return;
        }

        User::markVerified((int)$user['id']);
        // Confirming proves control of the mailbox, so sign them straight in.
        // No flash here: only /login consumes it, so setting one would leave
        // a stale "confirmed" notice to surface on a later sign-in.
        self::loginUser($user);
        redirect('dashboard');
    }

    /** GET/POST /verify-resend — send a fresh confirmation link. */
    public static function resendVerification(): void
    {
        $error = null; $notice = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check($_POST['csrf_token'] ?? null)) {
                $error = t('auth.err_expired');
            } else {
                $email = trim($_POST['email'] ?? '');
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'That email address doesn\'t look right.';
                } else {
                    $user = User::findByEmail($email);
                    // Only unverified accounts get a link; everyone sees the
                    // same message regardless.
                    if ($user && User::needsVerification($user)
                        && !Mailer::rateLimited($email, 'verify', 3, 60)) {
                        $raw = User::issueVerifyToken((int)$user['id']);
                        if ($raw) {
                            Mailer::sendVerification($email, $user['name'] ?? '', $raw);
                        }
                    }
                    $notice = 'If that address needs confirming, a new link is on its way.';
                }
            }
        }

        $pageTitle = 'Resend confirmation';
        $noSidebar = true;
        ob_start();
        include __DIR__ . '/../views/verify_resend.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** GET/POST /forgot — request a password-reset link. */
    public static function forgotPassword(): void
    {
        $error = null; $notice = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check($_POST['csrf_token'] ?? null)) {
                $error = t('auth.err_expired');
            } elseif (trim($_POST['website'] ?? '') !== '') {
                $notice = t('auth.forgot_sent');
            } else {
                $email = trim($_POST['email'] ?? '');
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'That email address doesn\'t look right.';
                } else {
                    $user = User::findByEmail($email);
                    if ($user && !Mailer::rateLimited($email, 'reset', 3, 60)) {
                        $raw = User::issueResetToken((int)$user['id']);
                        if ($raw) {
                            Mailer::sendPasswordReset($email, $user['name'] ?? '', $raw);
                        }
                    }
                    // Same answer whether or not the account exists.
                    $notice = t('auth.forgot_sent');
                }
            }
        }

        $pageTitle = 'Forgot password';
        $noSidebar = true;
        ob_start();
        include __DIR__ . '/../views/forgot.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** GET/POST /reset/{token} — choose a new password. */
    public static function resetPassword(string $token): void
    {
        $error = null;
        $user  = User::findByResetToken($token);

        if (!$user) {
            $error = 'That reset link has expired or was already used.';
            $pageTitle = 'Link expired';
            $noSidebar = true;
            ob_start();
            include __DIR__ . '/../views/reset_expired.php';
            $content = ob_get_clean();
            include __DIR__ . '/../views/layout.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check($_POST['csrf_token'] ?? null)) {
                $error = t('auth.err_expired');
            } else {
                $new     = (string)($_POST['new_password'] ?? '');
                $confirm = (string)($_POST['confirm_password'] ?? '');
                if (strlen($new) < 6) {
                    $error = t('auth.err_short_pass');
                } elseif ($new !== $confirm) {
                    $error = 'Passwords don\'t match.';
                } else {
                    User::resetPassword((int)$user['id'], $new);
                    // Force a fresh sign-in with the new password rather than
                    // handing out a session from an emailed link.
                    $_SESSION['flash'] = t('auth.reset_done');
                    redirect('login');
                }
            }
        }

        $pageTitle = 'Choose a new password';
        $noSidebar = true;
        ob_start();
        include __DIR__ . '/../views/reset.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** GET/POST /account — edit own profile (name, email, password). */
    public static function account(): void
    {
        require_login();
        global $db, $currentUserId;

        $user = User::find((int)$currentUserId);
        if (!$user) { redirect('logout'); }

        $notice = null; $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check($_POST['csrf_token'] ?? null)) {
                $error = t('auth.err_expired');
            } else {
                $action = $_POST['action'] ?? '';

                if ($action === 'profile') {
                    // Email stays read-only until we can send verification mail.
                    $name    = trim($_POST['name']         ?? '');
                    $company = trim($_POST['company']      ?? '');
                    $org     = trim($_POST['organisation'] ?? '');
                    if ($name === '') {
                        $error = 'Name cannot be empty.';
                    } else {
                        try {
                            $db->prepare('UPDATE users SET name = ?, company = ?, organisation = ? WHERE id = ?')
                               ->execute([$name, $company ?: null, $org ?: null, (int)$user['id']]);
                            $_SESSION['user']['name'] = $name;
                            $user['name'] = $name;
                            $user['company'] = $company; $user['organisation'] = $org;
                            $notice = 'Profile updated.';
                        } catch (\Throwable $e) {
                            $error = 'Could not save — has the profile-fields migration been run?';
                        }
                    }
                } elseif ($action === 'password') {
                    $current = (string)($_POST['current_password'] ?? '');
                    $new     = (string)($_POST['new_password'] ?? '');
                    $confirm = (string)($_POST['confirm_password'] ?? '');
                    if (!password_verify($current, $user['password_hash'] ?? '')) {
                        $error = 'Current password is incorrect.';
                    } elseif (strlen($new) < 6) {
                        $error = 'New password must be at least 6 characters.';
                    } elseif ($new !== $confirm) {
                        $error = 'New passwords don\'t match.';
                    } else {
                        $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                           ->execute([password_hash($new, PASSWORD_DEFAULT), (int)$user['id']]);
                        $notice = 'Password changed.';
                    }
                }
            }
        }

        // One-shot flashes from the transfer flow (request/cancel).
        if (!empty($_SESSION['flash_account']))     { $notice = $_SESSION['flash_account'];     unset($_SESSION['flash_account']); }
        if (!empty($_SESSION['flash_account_err'])) { $error  = $_SESSION['flash_account_err']; unset($_SESSION['flash_account_err']); }

        // Account handover: what I own, and any pending outgoing transfer.
        require_once __DIR__ . '/transfer.php';
        require_once __DIR__ . '/../app/transfer.php';
        $ownSummary      = AccountTransfer::summary($db, (int)$user['id']);
        $ownSummaryText  = AccountTransfer::summaryText($ownSummary);
        $pendingTransfer = transfer_controller::pendingOutgoing($db, (int)$user['id']);

        $pageTitle = 'My account';
        $noSidebar = true;
        ob_start();
        include __DIR__ . '/../views/account.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** POST /api/notifications/{id}/ack — check a dashboard notice off. */
    public static function ackNotification(string $id): void
    {
        api_require_login();
        header('Content-Type: application/json');
        global $db, $currentUserId;
        try {
            $st = $db->prepare("UPDATE notifications SET acknowledged_at = NOW()
                                 WHERE id = ? AND user_id = ? AND acknowledged_at IS NULL");
            $st->execute([(int)$id, (int)$currentUserId]);
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'notifications table missing — run the migration']);
        }
    }

    /** POST /api/shares/seen — dismiss the "new boards shared with you" notice. */
    public static function sharesSeen(): void
    {
        api_require_login();
        header('Content-Type: application/json');
        global $db, $currentUserId;
        try {
            $db->prepare('UPDATE users SET shares_seen_at = NOW() WHERE id = ?')
               ->execute([(int)$currentUserId]);
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'shares_seen_at column missing — run the migration']);
        }
    }

    /** POST /account/language  body: lang — set interface + email language. */
    public static function setLanguage(): void
    {
        // No require_login(): anonymous visitors can switch language too.
        // I18n::setForCurrentUser() writes users.lang only when there is a
        // user, and always writes the session, so both cases work unchanged.
        require_once __DIR__ . '/../app/i18n.php';

        $back = self::safeNext($_POST['next'] ?? '')
                ?: (function_exists('is_logged_in') && is_logged_in() ? '/dashboard' : '/');
        if (csrf_check($_POST['csrf_token'] ?? null)) {
            I18n::setForCurrentUser((string)($_POST['lang'] ?? ''));
        }
        redirect($back);
    }

    public static function logout(): void
    {
        // Read this BEFORE the session is destroyed and capture it in a local:
        // it has to ride in the redirect URL, because there will be no session
        // left to stash it in.
        $back = self::safeNext($_GET['next'] ?? '');

        // Clear PHP session entirely so the auth.php fallback kicks in again on next request.
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']);
        }
        session_destroy();

        // With a page to come back to, land on the sign-in form primed to
        // return there. Without one, the home page as before.
        if ($back !== '') {
            redirect('/login?next=' . urlencode($back) . '&out=1');
        }
        redirect('');
    }

    /**
     * Validate a ?next= redirect target. Accepts only relative URLs starting
     * with "/" (no protocol, no //host) so attackers can't bounce sign-ins
     * off our site to phishing pages.
     */
    /**
     * Is this destination plausible for the account that just signed in?
     *
     * Not a permission system — the page's own guard remains the authority.
     * This only stops the obvious mismatch of handing one account a
     * destination that belonged to another, which otherwise reads as a broken
     * login rather than as the access check it is.
     */
    private static function canVisit(string $path, array $user): bool
    {
        if (strncmp($path, '/admin', 6) === 0) {
            return ($user['role'] ?? 'user') === 'admin';
        }
        return true;
    }

    private static function safeNext(string $raw): string
    {
        // One definition of this rule, in app/helpers.php — duplicated
        // security checks drift apart.
        return safe_internal_path($raw);
    }

    /** Internal: write a successful sign-in into the session. */
    private static function loginUser(array $user): void
    {
        session_regenerate_id(true); // prevent session fixation
        $_SESSION['user_id']       = (int)$user['id'];
        $_SESSION['authenticated'] = true;
        $_SESSION['user']          = [
            'id'    => (int)$user['id'],
            'name'  => $user['name']  ?? '',
            'email' => $user['email'] ?? '',
            'role'  => $user['role']  ?? 'user',
            'lang'  => $user['lang']  ?? null,
        ];

        // Signing in restores the ACCOUNT's language, and overrides whatever
        // the browser was showing. Without this, a language picked while
        // logged out would sit in the session and outrank the account's own
        // setting (I18n::lang() reads the session first) — so someone whose
        // account is Danish would land in English after signing in on an
        // English page, and the next click on the picker would write that
        // English back over their stored preference.
        require_once __DIR__ . '/../app/i18n.php';
        if (I18n::isSupported($user['lang'] ?? null)) {
            $_SESSION['lang'] = $user['lang'];
        } else {
            unset($_SESSION['lang']);   // no stored preference → fall back to default
        }
        // Best-effort last-login stamp (column may not be migrated yet)
        try {
            global $db;
            $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
               ->execute([(int)$user['id']]);
        } catch (\Throwable $e) { /* ignore */ }
    }
}
