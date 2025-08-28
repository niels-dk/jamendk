<?php
/**
 * canvas controller
 *
 * Exposes JSON endpoints for managing canvas items on a mood board. 
 */
require_once __DIR__ . '/../models/mood.php';
require_once __DIR__ . '/../models/mood_canvas.php';

class canvas
{
    // GET /api/moods/{slug}/canvas/items
    public static function listItems(string $slug): void {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo json_encode(['error'=>'Board not found']); return; }
        $items = mood_canvas_model::listItems($db, (int)$board['id']);
        header('Content-Type: application/json');
        echo json_encode($items);
    }

    // POST /api/moods/{slug}/canvas/items/create
    public static function createItem(string $slug): void {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo json_encode(['error'=>'Board not found']); return; }
        $data    = json_decode(file_get_contents('php://input'), true) ?: [];
        $kind    = (string)($data['kind'] ?? '');
        $x       = (int)($data['x'] ?? 0);
        $y       = (int)($data['y'] ?? 0);
        $w       = (int)($data['w'] ?? 200);
        $h       = (int)($data['h'] ?? 120);
        $payload = $data['payload'] ?? null;
        $item = mood_canvas_model::createItem($db, (int)$board['id'], $kind, $x, $y, $w, $h, $payload);
        header('Content-Type: application/json');
        echo json_encode($item);
    }

    // PATCH /api/moods/{slug}/canvas/items/{itemId}
    public static function updateItem(string $slug, int $itemId): void {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo json_encode(['error'=>'Board not found']); return; }
        $fields = json_decode(file_get_contents('php://input'), true) ?: [];
        mood_canvas_model::updateItem($db, $itemId, $fields);
        header('Content-Type: application/json');
        echo json_encode(['success'=>true]);
    }

    // DELETE /api/moods/{slug}/canvas/items/{itemId}/delete
    public static function deleteItem(string $slug, int $itemId): void {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo json_encode(['error'=>'Board not found']); return; }
        mood_canvas_model::deleteItem($db, $itemId);
        header('Content-Type: application/json');
        echo json_encode(['success'=>true]);
    }

    // PATCH /api/moods/{slug}/canvas/items/bulk
    public static function bulkUpdate(string $slug): void {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo json_encode(['error'=>'Board not found']); return; }
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $moves   = is_array($payload['moves'] ?? null) ? $payload['moves'] : [];
        mood_canvas_model::bulkUpdate($db, (int)$board['id'], $moves);
        header('Content-Type: application/json');
        echo json_encode(['success'=>true]);
    }
}
