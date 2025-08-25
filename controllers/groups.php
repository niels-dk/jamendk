<?php
// controllers/groups.php
require_once __DIR__ . '/../models/group_model.php';

class groups_controller {
    // GET /api/visions/{slug}/groups
    public static function list(string $slug): void {
        header('Content-Type: application/json');
        global $db;
        $rows = group_model::allForVision($db, $slug, $_SESSION['user_id']);
        echo json_encode(['success'=>true, 'groups'=>$rows]);
    }

    // POST /api/visions/{slug}/groups:create
    public static function create(string $slug): void {
        header('Content-Type: application/json');
        global $db;
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { http_response_code(422); echo json_encode(['error'=>'Name required']); return; }
        $id = group_model::create($db, $slug, $_SESSION['user_id'], $name);
        if (!$id) { http_response_code(500); echo json_encode(['error'=>'Failed to create']); return; }
        echo json_encode(['success'=>true,'group'=>['id'=>$id,'name'=>$name]]);
    }
}
