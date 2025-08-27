<?php
// controllers/group_controller.php

require_once __DIR__ . '/../models/media_model.php';

class group_controller
{
    public static function list(): void
    {
        header('Content-Type: application/json');
        global $db, $auth;
        $userId = $auth->id();
        $q = trim($_GET['q'] ?? '');
        $stmt = $db->prepare(
            "SELECT id, name FROM media_groups
             WHERE user_id = ? AND (name LIKE ? OR slug LIKE ?)
             ORDER BY name"
        );
        $like = '%' . $q . '%';
        $stmt->execute([$userId, $like, $like]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'groups' => $groups]);
    }

    public static function create(): void
    {
        header('Content-Type: application/json');
        global $db, $auth;
        $userId = $auth->id();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Group name required']);
            return;
        }
        $slug = strtolower(preg_replace('/\s+/', '-', $name));
        // return existing if present
        $check = $db->prepare("SELECT id, name FROM media_groups WHERE user_id = ? AND slug = ? LIMIT 1");
        $check->execute([$userId, $slug]);
        if ($group = $check->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['success' => true, 'group' => $group]);
            return;
        }
        $insert = $db->prepare(
            "INSERT INTO media_groups (user_id, name, slug, created_at)
             VALUES (?, ?, ?, NOW())"
        );
        $insert->execute([$userId, $name, $slug]);
        $id = (int)$db->lastInsertId();
        echo json_encode(['success' => true, 'group' => ['id' => $id, 'name' => $name]]);
    }
}
