<?php
// controllers/vision.php

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../models/board.php'; // new unified model (optional)

class vision_controller
{
    public static function create(): void
    {
        $type = 'vision';
        $pageTitle = 'Create Vision Board';
        include __DIR__ . '/../views/vision/new.php';
    }

    public static function store(): void
    {
        global $db;
        $userId = $_SESSION['user_id'] ?? 1; // fallback for now

        $title = $_POST['title'] ?? '';
        $desc  = $_POST['description'] ?? '';
        $keys  = $_POST['custom_keys'] ?? [];
        $vals  = $_POST['custom_values'] ?? [];

        // Insert into vision_boards (or dream_boards with type='vision')
        $stmt = $db->prepare("
            INSERT INTO dream_boards (user_id, title, description, type, created_at)
            VALUES (?, ?, ?, 'vision', NOW())
        ");
        $stmt->execute([$userId, $title, $desc]);

        $boardId = $db->lastInsertId();

        // Save custom anchors
        $stmtA = $db->prepare("INSERT INTO vision_anchors (board_id, `key`, `value`) VALUES (?, ?, ?)");
        foreach ($keys as $i => $key) {
            $value = $vals[$i] ?? '';
            if ($key && $value) {
                $stmtA->execute([$boardId, $key, $value]);
            }
        }

        // Redirect
        header('Location: /dashboard');
        exit;
    }

    // ... (you can later add show/edit/update/archive/etc.)
}
