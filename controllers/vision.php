<?php
// controllers/vision.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../models/vision.php';

class vision_controller
{
    /** GET /visions/new */
   	public static function create(): void
	{
		global $db, $currentUserId;
		$userId = $currentUserId ?: 1;
		// create draft with empty title/description
		$draft  = vision_model::createDraft($db, $userId);
		// redirect to edit screen
		header("Location: /visions/{$draft['slug']}/edit");
		exit;
	}

    /** POST /visions/store */
    public static function store(): void
	{
		global $db, $currentUserId;
		$userId = $currentUserId ?: 1;
		$title = trim($_POST['title'] ?? '');
		$desc  = $_POST['description'] ?? '';

		// Create the vision (we're no longer collecting start/end dates here)
		$id     = vision_model::create($db, $userId, $title ?: null, $desc ?: null);

		// Build anchors from anchors[][] array
		$anchors = $_POST['anchors'] ?? [];
		$kv = [];
		foreach ($anchors as $row) {
			$kv[] = ['key' => $row['key'] ?? '', 'value' => $row['value'] ?? ''];
		}
		vision_model::replaceAnchors($db, $id, $kv);

		// redirect to show
		$st = $db->prepare("SELECT slug FROM visions WHERE id=?");
		$st->execute([$id]);
		$slug = (string)$st->fetchColumn();
		header("Location: /visions/$slug");
		exit;
	}

    /** GET /visions/{slug} */
    public static function show(string $slug): void
    {
        global $db;
        $vision = vision_model::get($db, $slug);
        if (!$vision) {
            http_response_code(404);
            echo 'Vision not found';
            return;
        }

        // fetch flags
        $st = $db->prepare("SELECT * FROM vision_presentation WHERE vision_id=?");
        $st->execute([(int)$vision['id']]);
        $presentationFlags = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        // existing: fetch anchors etc...
        // pass $presentationFlags into the view

        ob_start();
        include __DIR__ . '/../views/vision_show.php';
        $content = ob_get_clean();

        $boardType = 'vision';
        include __DIR__ . '/../views/layout.php';
    }

    /** GET /visions/{slug}/edit */
    public static function edit(string $slug): void
    {
        global $db;
        $vision = vision_model::get($db, $slug);
        if (!$vision) {
            http_response_code(404);
            echo 'Vision not found';
            return;
        }

        $st = $db->prepare("SELECT * FROM vision_presentation WHERE vision_id=?");
        $st->execute([(int)$vision['id']]);
        $presentationFlags = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        // existing: fetch anchors, build kv...
        ob_start();
        include __DIR__ . '/../views/vision_form.php';
        $content = ob_get_clean();

        $boardType = 'vision';
        include __DIR__ . '/../views/layout.php';
    }

    /** POST /visions/update */
    public static function update(): void
	{
		global $db;

		$id = (int)($_POST['vision_id'] ?? 0);
		if (!$id) { http_response_code(400); echo 'Missing ID'; return; }

		$title = trim($_POST['title'] ?? '');
		$desc  = $_POST['description'] ?? '';

		vision_model::update($db, $id, $title, $desc);

		// update anchors
		$anchors = $_POST['anchors'] ?? [];
		$kv = [];
		foreach ($anchors as $row) {
			$kv[] = ['key' => $row['key'] ?? '', 'value' => $row['value'] ?? ''];
		}
		vision_model::replaceAnchors($db, $id, $kv);

		// redirect back to vision
		$st = $db->prepare("SELECT slug FROM visions WHERE id=?");
		$st->execute([$id]);
		$slug = (string)$st->fetchColumn();
		header("Location: /visions/$slug");
		exit;
	}

    /** State actions */
    public static function archive(string $slug): void
    {
        global $db; $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::setArchived($db, (int)$v['id'], true);
        header('Location: /dashboard/vision'); exit;
    }
    public static function unarchive(string $slug): void
    {
        global $db; $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::setArchived($db, (int)$v['id'], false);
        header('Location: /dashboard/vision/archived'); exit;
    }
    public static function destroy(string $slug): void
    {
        global $db; $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::softDelete($db, (int)$v['id']);
        header('Location: /dashboard/vision'); exit;
    }
    public static function restore(string $slug): void
    {
        global $db; $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::restore($db, (int)$v['id']);
        header('Location: /dashboard/vision/trash'); exit;
    }
	
	public static function updateBasics(): void
    {
        global $db;

        $id = (int)($_POST['vision_id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing Vision ID']);
            return;
        }

        $start = $_POST['start_date'] ?? null;
        $end   = $_POST['end_date'] ?? null;

        // Update start/end
        $stmt = $db->prepare("UPDATE visions SET start_date=?, end_date=? WHERE id=?");
        $stmt->execute([$start, $end, $id]);

        // Update presentation flags
        $defaults = [
            'relations' => 1,
            'goals'     => 1,
            'budget'    => 1,
            'roles'     => 0,
            'contacts'  => 1,
            'documents' => 1,
            'workflow'  => 1,
        ];
        $flags = [];
        foreach ($defaults as $key => $def) {
            $flags[$key] = isset($_POST["show_$key"]) ? 1 : 0;
        }

        // Upsert to vision_presentation table
        $fields = array_keys($defaults);
        $columns = implode(',', $fields);
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $updates = implode(',', array_map(fn($f) => "$f = VALUES($f)", $fields));
        $stmt = $db->prepare("
            INSERT INTO vision_presentation (vision_id, $columns)
            VALUES (?, $placeholders)
            ON DUPLICATE KEY UPDATE $updates
        ");
        $stmt->execute(array_merge([$id], array_values($flags)));

        echo json_encode(['success' => true]);
    }
}
