<?php
require_once __DIR__ . '/../models/User.php';

class user_controller
{
    /** GET /login  (form)  |  POST /login  (authenticate) */
    public static function login(): void
    {
        $error = null;
        // Remember where the user was trying to go (set as ?next=)
        $next = self::safeNext($_REQUEST['next'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check($_POST['csrf_token'] ?? null)) {
                $error = 'Your session expired. Please try again.';
            } else {
                $email = trim($_POST['email'] ?? '');
                $pass  = $_POST['password'] ?? '';
                if (!$email || !$pass) {
                    $error = 'Please enter both email and password.';
                } else if ($user = User::authenticate($email, $pass)) {
                    self::loginUser($user);
                    redirect($next ?: 'dashboard');
                } else {
                    $error = 'Invalid email or password.';
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
                $error = 'Your session expired. Please try again.';
            } else {
                $name  = trim($_POST['name']  ?? '');
                $email = trim($_POST['email'] ?? '');
                $pass  = $_POST['password'] ?? '';
                if (!$name) {
                    $error = 'Please tell us your name.';
                } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'That email address doesn\'t look right.';
                } else if (strlen($pass) < 6) {
                    $error = 'Password must be at least 6 characters.';
                } else if (User::emailExists($email)) {
                    $error = 'An account with that email already exists.';
                } else {
                    $newId = User::create($name, $email, $pass);
                    if (!$newId) {
                        $error = 'Could not create the account. Please try again.';
                    } else {
                        $user = User::find($newId);
                        if ($user) {
                            self::loginUser($user);
                            redirect($next ?: 'dashboard');
                        }
                        $error = 'Account created — please sign in.';
                        redirect('login' . ($next ? '?next=' . urlencode($next) : ''));
                    }
                }
            }
        }
        include __DIR__ . '/../views/register.php';
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
                $error = 'Your session expired. Please try again.';
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

    public static function logout(): void
    {
        // Clear PHP session entirely so the auth.php fallback kicks in again on next request.
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']);
        }
        session_destroy();
        redirect('');
    }

    /**
     * Validate a ?next= redirect target. Accepts only relative URLs starting
     * with "/" (no protocol, no //host) so attackers can't bounce sign-ins
     * off our site to phishing pages.
     */
    private static function safeNext(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '/') return '';
        if ($raw[0] !== '/' || (isset($raw[1]) && $raw[1] === '/')) return '';
        return $raw;
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
        ];
        // Best-effort last-login stamp (column may not be migrated yet)
        try {
            global $db;
            $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
               ->execute([(int)$user['id']]);
        } catch (\Throwable $e) { /* ignore */ }
    }
}
