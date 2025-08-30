<?php
/**
 * canvas controller
 *
 * This controller exposes a simple JSON API for managing canvas items on a
 * mood board.  It implements CRUD operations along with a bulk update
 * endpoint, mirroring the conventions used by other controllers in the
 * project.  Each method first resolves the target mood board by slug and
 * returns a 404 or JSON error if the board is not found.  Responses are
 * always JSON encoded.  The underlying data access is delegated to
 * mood_canvas_model in the models layer.
 */

require_once __DIR__ . '/../models/mood.php';
require_once __DIR__ . '/../models/mood_canvas.php';

class canvas_controller
{
    /**
     * GET /api/moods/{slug}/canvas/items
     *
     * List all canvas items for the given board slug.  Items are returned
     * sorted by their z index and ID.  On error a 404 or JSON error is
     * returned.  The client is responsible for rendering the items.
     */
    public static function listItems(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) {
            http_response_code(404);
            echo json_encode(['error' => 'Board not found']);
            return;
        }
        $items = mood_canvas_model::listItems($db, (int)$board['id']);
        header('Content-Type: application/json');
        echo json_encode($items);
    }

    /**
     * POST /api/moods/{slug}/canvas/items/create
     *
     * Create a new canvas item.  Expects a JSON body containing at least
     * a `kind` property (e.g. "image", "note", "label", "frame",
     * "connector").  Optional properties such as x, y, w, h and payload
     * will be used to position and initialize the item.  Returns the
     * created record.
     */
    public static function createItem(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) {
            http_response_code(404);
            echo json_encode(['error' => 'Board not found']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $kind = isset($data['kind']) ? (string)$data['kind'] : '';
        $x    = isset($data['x']) ? (int)$data['x'] : 0;
        $y    = isset($data['y']) ? (int)$data['y'] : 0;
        $w    = isset($data['w']) ? (int)$data['w'] : 200;
        $h    = isset($data['h']) ? (int)$data['h'] : 120;
        $payload = $data['payload'] ?? null;
        $item = mood_canvas_model::createItem($db, (int)$board['id'], $kind, $x, $y, $w, $h, $payload);
        header('Content-Type: application/json');
        echo json_encode($item);
    }

    /**
     * PATCH /api/moods/{slug}/canvas/items/{itemId}
     *
     * Update a single canvas item.  Expects a JSON body containing any
     * fields that should be updated.  Allowed keys include x, y, w, h, z,
     * rotation, locked, hidden and payload_json.  Returns success
     * boolean.  If the board is not found, a 404 is returned.
     */
    public static function updateItem(string $slug, int $itemId): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) {
            http_response_code(404);
            echo json_encode(['error' => 'Board not found']);
            return;
        }
        $fields = json_decode(file_get_contents('php://input'), true) ?: [];
        mood_canvas_model::updateItem($db, $itemId, $fields);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    /**
     * DELETE /api/moods/{slug}/canvas/items/{itemId}/delete
     *
     * Delete a canvas item.  Returns success boolean.  If the board
     * cannot be found a 404 is returned.  Deletion is permanent and
     * cannot be undone through this endpoint.
     */
    // controllers/canvas.php
	public static function deleteItem($slug, $id) {
		require_once __DIR__.'/../models/mood_canvas.php';
		$ok = MoodCanvas::deleteItem($slug, (int)$id);
		header('Content-Type: application/json');
		echo json_encode(['success'=>$ok]);
	}



    /**
     * PATCH /api/moods/{slug}/canvas/items/bulk
     *
     * Bulk update multiple items at once.  Expects a JSON body with a
     * `moves` array where each element includes an `id` and any
     * fields to update.  Returns success boolean.  Missing items are
     * silently ignored.
     */
    public static function bulkUpdate(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) {
            http_response_code(404);
            echo json_encode(['error' => 'Board not found']);
            return;
        }
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $moves = isset($payload['moves']) && is_array($payload['moves']) ? $payload['moves'] : [];
        mood_canvas_model::bulkUpdate($db, (int)$board['id'], $moves);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
	
	public function saveArrow($slug) {
		require_once __DIR__ . '/../models/mood_canvas.php';
		$m = new MoodCanvas();
		$data = json_decode(file_get_contents("php://input"), true) ?: [];
		$arrow = $m->saveArrow($slug, $data);
		echo json_encode($arrow);
	}

	public static function deleteArrow($slug, $id) {
		require_once __DIR__.'/../models/mood_canvas.php';
		$ok = MoodCanvas::deleteArrow($slug, (int)$id);
		header('Content-Type: application/json');
		echo json_encode(['success'=>$ok]);
	}


	public function createArrow(string $slug): void
	{
		global $db;
		$board = mood_model::get($db, $slug);
		if (!$board) { http_response_code(404); echo json_encode(['error'=>'Board not found']); return; }

		$data = json_decode(file_get_contents('php://input'), true) ?: [];
		$from = (int)($data['from_item_id'] ?? 0);
		$to   = (int)($data['to_item_id'] ?? 0);
		$style = $data['style'] ?? 'solid';

		$arrow = mood_canvas_model::createArrow($db, (int)$board['id'], $from, $to, $style);
		header('Content-Type: application/json');
		echo json_encode($arrow);
	}


}