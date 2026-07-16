<?php
/**
 * Admin controller — site administration (require_admin on everything).
 * /admin/users — manage accounts: role, password reset, delete.
 */
require_once __DIR__ . '/../models/User.php';

class admin_controller
{
    /** GET /admin/users — user management page */
    public static function users(): void
    {
        require_admin();
        global $db;

        // u.* keeps this robust as profile columns get added over time
        $sql = "
            SELECT u.*,
                   (SELECT COUNT(*) FROM dream_boards d WHERE d.user_id = u.id AND d.deleted_at IS NULL) AS dreams,
                   (SELECT COUNT(*) FROM visions v      WHERE v.user_id = u.id AND v.deleted_at IS NULL) AS visions,
                   (SELECT COUNT(*) FROM mood_boards m  WHERE m.user_id = u.id AND m.deleted_at IS NULL) AS moods
              FROM users u
             ORDER BY u.id ASC
        ";
        $users = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'User management';
        $noSidebar = true;
        ob_start();
        include __DIR__ . '/../views/admin_users.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * GET /admin/pricing — shadow revenue. What the base would bill today,
     * how teams cluster across the tiers, and which free teams are one
     * person away from a paid band. The number that says when to build
     * payments at all.
     */
    public static function pricing(): void
    {
        require_admin();
        require_once __DIR__ . '/../app/pricing.php';
        global $db;

        $stats = Pricing::shadowStats($db);

        $pageTitle = 'Shadow revenue';
        $noSidebar = true;
        ob_start();
        include __DIR__ . '/../views/admin_pricing.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * GET /admin/mail — what the app has tried to send, and what failed.
     * The one place to look when someone says "I never got the email".
     */
    public static function mailLog(): void
    {
        require_admin();
        // $currentUser is read by the view to prefill the test-send address
        global $db, $currentUser;

        $rows = [];
        $stats = ['sent' => 0, 'failed' => 0];
        $migrationMissing = false;
        try {
            $rows = $db->query("SELECT * FROM mail_log
                                 ORDER BY created_at DESC, id DESC
                                 LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($db->query("SELECT status, COUNT(*) c FROM mail_log
                                  WHERE created_at > (NOW() - INTERVAL 7 DAY)
                                  GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $stats[$r['status']] = (int)$r['c'];
            }
        } catch (\Throwable $e) {
            $migrationMissing = true;
        }

        // Surface the active transport so a misconfigured driver is obvious
        $mailDriver = defined('MAIL_DRIVER') ? MAIL_DRIVER : 'mail (default — no MAIL_DRIVER set)';
        $mailFrom   = defined('MAIL_FROM') ? MAIL_FROM : (defined('MAIL_USER') ? MAIL_USER : '(auto)');

        // Config readout for debugging auth failures. The password's LENGTH is
        // shown but never its value: a length that doesn't match what you typed
        // is the tell-tale of a PHP quoting/escaping problem, and it gives that
        // answer without putting the secret on screen.
        $mailCfg = [
            'MAIL_DRIVER'    => defined('MAIL_DRIVER')    ? MAIL_DRIVER    : null,
            'MAIL_HOST'      => defined('MAIL_HOST')      ? MAIL_HOST      : null,
            'MAIL_PORT'      => defined('MAIL_PORT')      ? MAIL_PORT      : null,
            'MAIL_USER'      => defined('MAIL_USER')      ? MAIL_USER      : null,
            'MAIL_FROM'      => defined('MAIL_FROM')      ? MAIL_FROM      : null,
            'MAIL_FROM_NAME' => defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : null,
            'MAIL_SITE_HOST' => defined('MAIL_SITE_HOST') ? MAIL_SITE_HOST : null,
        ];
        $mailPassLen = defined('MAIL_PASS') ? strlen((string)MAIL_PASS) : null;

        $pageTitle = 'Mail log';
        $noSidebar = true;
        ob_start();
        include __DIR__ . '/../views/admin_mail.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * POST /admin/mail/test — send one real email to the admin, so
     * deliverability can be proven before a user ever hits Register.
     */
    public static function mailTest(): void
    {
        require_admin();
        require_once __DIR__ . '/../app/mailer.php';
        global $currentUser;

        if (!csrf_check($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_admin'] = '⚠ Your session expired. Please try again.';
            redirect('/admin/mail');
        }

        $to = trim($_POST['to'] ?? '') ?: (string)($currentUser['email'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_admin'] = '⚠ That address doesn\'t look right.';
            redirect('/admin/mail');
        }

        $html = Mailer::layout(
            'Mail is working',
            '<p style="margin:0;">If you are reading this, DreamBoard can send email:
             SMTP authenticated, and this message reached a real inbox.</p>
             <p style="margin:14px 0 0;font-size:13px;color:#5a6878;">
             Sent from the admin mail log at ' . htmlspecialchars(date('M j, Y · H:i')) . '.</p>'
        );
        $ok = Mailer::send($to, 'DreamBoard test email', $html, 'test');

        $_SESSION['flash_admin'] = $ok
            ? '✓ Test email sent to ' . $to . ' — check the inbox (and the spam folder).'
            : '⚠ Send failed. The error is in the log below.';
        redirect('/admin/mail');
    }

    /**
     * POST /admin/users/{id}/transfer  body: to_email, deactivate(0|1)
     * Admin-assisted handover — no acceptance step (the person may be gone).
     * Optionally blocks the departing login in the same action.
     */
    public static function transferUser(string $userId): void
    {
        require_admin();
        header('Content-Type: application/json');
        require_once __DIR__ . '/../app/transfer.php';
        global $db;

        $from = self::targetUser($db, $userId);
        if (!$from) return;

        $toEmail = trim((string)($_POST['to_email'] ?? ''));
        $ts = $db->prepare("SELECT id, name, email FROM users WHERE email = ? LIMIT 1");
        $ts->execute([$toEmail]);
        $to = $ts->fetch(PDO::FETCH_ASSOC);
        if (!$to) { http_response_code(404); echo json_encode(['error' => 'No account with that email']); return; }
        if ((int)$to['id'] === (int)$from['id']) {
            http_response_code(422); echo json_encode(['error' => 'Pick a different recipient']); return;
        }

        try {
            $moved = AccountTransfer::perform($db, (int)$from['id'], (int)$to['id']);
            // Cancel any pending self-serve requests for the emptied account.
            $db->prepare("UPDATE account_transfers SET status='cancelled', resolved_at=NOW()
                           WHERE from_user_id=? AND status='pending'")->execute([(int)$from['id']]);

            $deactivated = false;
            if (!empty($_POST['deactivate'])) {
                $db->prepare("UPDATE users SET deactivated_at = NOW() WHERE id = ?")
                   ->execute([(int)$from['id']]);
                $deactivated = true;
            }
            echo json_encode([
                'success' => true,
                'moved'   => AccountTransfer::summaryText($moved),
                'to'      => $to['name'] ?: $to['email'],
                'deactivated' => $deactivated,
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Transfer failed: ' . $e->getMessage()]);
        }
    }

    /** POST /admin/users/{id}/deactivate  body: on(0|1) — block/unblock login. */
    public static function setDeactivated(string $userId): void
    {
        require_admin();
        header('Content-Type: application/json');
        global $db, $currentUserId;

        if ((int)$userId === (int)$currentUserId) {
            http_response_code(422);
            echo json_encode(['error' => 'You can\'t deactivate your own account']);
            return;
        }
        if (!self::targetUser($db, $userId)) return;

        $on = !empty($_POST['on']);
        try {
            $db->prepare("UPDATE users SET deactivated_at = ? WHERE id = ?")
               ->execute([$on ? date('Y-m-d H:i:s') : null, (int)$userId]);
            echo json_encode(['success' => true, 'deactivated' => $on]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Run the account-transfer migration first']);
        }
    }

    /** Resolve target user or emit 404 JSON. */
    private static function targetUser(PDO $db, string $userId): ?array
    {
        $st = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
        $st->execute([(int)$userId]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        if (!$u) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return null;
        }
        return $u;
    }

    /**
     * POST /admin/users/{id}/verify — manually confirm an address.
     * The escape hatch for when a link never arrives: without it, a delivery
     * failure would lock a real user out with no way back in.
     */
    public static function verifyUser(string $userId): void
    {
        require_admin();
        header('Content-Type: application/json');
        global $db;
        if (!self::targetUser($db, $userId)) return;
        try {
            $db->prepare('UPDATE users SET email_verified_at = NOW(),
                                 verify_token = NULL, verify_expires_at = NULL
                           WHERE id = ?')->execute([(int)$userId]);
            echo json_encode(['success' => true, 'verified_at' => date('Y-m-d H:i')]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Run the email-verification migration first']);
        }
    }

    /** POST /admin/users/{id}/role  body: role=admin|user */
    public static function setRole(string $userId): void
    {
        header('Content-Type: application/json');
        require_admin();
        global $db, $currentUserId;

        $target = self::targetUser($db, $userId);
        if (!$target) return;

        $role = (string)($_POST['role'] ?? '');
        if (!in_array($role, ['admin', 'user'], true)) {
            http_response_code(422);
            echo json_encode(['error' => 'Invalid role']);
            return;
        }
        // Don't let an admin demote themself — avoids locking yourself out.
        if ((int)$target['id'] === (int)$currentUserId && $role !== 'admin') {
            http_response_code(422);
            echo json_encode(['error' => "You can't demote your own account"]);
            return;
        }

        $db->prepare("UPDATE users SET role = ? WHERE id = ?")
           ->execute([$role, (int)$target['id']]);
        echo json_encode(['success' => true]);
    }

    /** POST /admin/users/{id}/password  body: password=... */
    public static function setPassword(string $userId): void
    {
        header('Content-Type: application/json');
        require_admin();
        global $db;

        $target = self::targetUser($db, $userId);
        if (!$target) return;

        $pass = (string)($_POST['password'] ?? '');
        if (strlen($pass) < 6) {
            http_response_code(422);
            echo json_encode(['error' => 'Password must be at least 6 characters']);
            return;
        }
        $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
           ->execute([password_hash($pass, PASSWORD_DEFAULT), (int)$target['id']]);
        echo json_encode(['success' => true]);
    }

    /** POST /admin/users/{id}/delete — removes the account (boards stay, orphaned). */
    public static function deleteUser(string $userId): void
    {
        header('Content-Type: application/json');
        require_admin();
        global $db, $currentUserId;

        $target = self::targetUser($db, $userId);
        if (!$target) return;

        if ((int)$target['id'] === (int)$currentUserId) {
            http_response_code(422);
            echo json_encode(['error' => "You can't delete your own account"]);
            return;
        }

        $db->prepare("DELETE FROM vision_roles WHERE user_id = ?")->execute([(int)$target['id']]);
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([(int)$target['id']]);
        echo json_encode(['success' => true]);
    }

    /**
     * GET /admin/users/{id}/impersonate — "View as" a user for support.
     * The admin's own id is kept in the session so they can return.
     */
    public static function impersonate(string $userId): void
    {
        require_admin();
        global $db, $currentUserId;

        $st = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $st->execute([(int)$userId]);
        $target = $st->fetch(PDO::FETCH_ASSOC);
        if (!$target) { http_response_code(404); echo 'User not found'; return; }
        if ((int)$target['id'] === (int)$currentUserId) { redirect('/admin/users'); }

        $_SESSION['impersonator_id'] = (int)$currentUserId;
        $_SESSION['user_id']         = (int)$target['id'];
        $_SESSION['authenticated']   = true;
        $_SESSION['user'] = [
            'id'    => (int)$target['id'],
            'name'  => $target['name']  ?? '',
            'email' => $target['email'] ?? '',
            'role'  => $target['role']  ?? 'user',
        ];
        redirect('/dashboard');
    }

    /** GET /admin/return — end impersonation, restore the admin session. */
    public static function stopImpersonate(): void
    {
        global $db;
        $adminId = (int)($_SESSION['impersonator_id'] ?? 0);
        if (!$adminId) { redirect('/'); }

        $st = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
        $st->execute([$adminId]);
        $admin = $st->fetch(PDO::FETCH_ASSOC);
        unset($_SESSION['impersonator_id']);
        if (!$admin) { redirect('/logout'); }

        $_SESSION['user_id']       = (int)$admin['id'];
        $_SESSION['authenticated'] = true;
        $_SESSION['user'] = [
            'id'    => (int)$admin['id'],
            'name'  => $admin['name']  ?? '',
            'email' => $admin['email'] ?? '',
            'role'  => 'admin',
        ];
        redirect('/admin/users');
    }
}
