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

        $sql = "
            SELECT u.id, u.name, u.email, u.role, u.created_at,
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
}
