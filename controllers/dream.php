<?php
// controllers/dream.php

global $db, $currentUserId;

// 1) PDO connection
require_once __DIR__ . '/../app/config.php';
// 2) helper that defines get_db()
require_once __DIR__ . '/../app/helpers.php';
// 3) model
require_once __DIR__ . '/../models/dream.php';

class dream_controller
{
    /**
     * GET /dreams/{slug}
     */
    public static function show(string $slug): void
	{
		global $db;

		$dream = dream_model::get($db, $slug);
		
		if (!$dream) {
			http_response_code(404);
			echo 'Dream not found';
			return;
		}
		
		$anchors = dream_model::getAnchors($db, $dream['id']);
		$title = htmlspecialchars($dream['title']);
		$view = 'dream_show';
		include __DIR__ . '/../views/dream_show.php';
		
		include __DIR__ . '/../views/layout.php';
	}

    /**
     * GET /dreams/new
     */
   public static function create(): void
	{
		$title = 'New Dream';

		ob_start();
		include __DIR__ . '/../views/dream_form.php';
//		$content = ob_get_clean();

		include __DIR__ . '/../views/layout.php';
	}



    // Handles POST /api/dreams/store.php
    public static function store()
    {
        $user = auth_user();
        $db   = get_db();

        // Basic fields
        $title       = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';

        // 1) Create the dream
        $dreamId = dream_model::create($db, $user->id, $title, $description);

        // 2) Persist anchors if any
        foreach (['locations','brands','people','seasons'] as $type) {
            if (!empty($_POST[$type]) && is_array($_POST[$type])) {
                dream_model::addAnchors($db, $dreamId, $type, $_POST[$type]);
            }
        }

        // 3) Return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'ok'   => true,
            'slug' => self::slugForDream($db, $dreamId)
        ]);
    }

    private static function slugForDream(PDO $db, int $dreamId): string
    {
        $stmt = $db->prepare("SELECT slug FROM dreams WHERE id = ?");
        $stmt->execute([$dreamId]);
        return $stmt->fetchColumn();
    }

    /**
     * GET /dreams/{slug}/edit
     */
    public static function edit(string $slug): void
    {
        global $db;
        $dream = dream_model::get($db, $slug);
        if (!$dream) {
            http_response_code(404);
            echo 'Dream not found';
            return;
        }
		
		// FETCH ANCHORS HERE:
        $anchors = dream_model::getAnchors($db, $dream['id']);
		
        include __DIR__ . '/../views/dream_form.php';
    }

    /**
     * GET /dreams/{slug}/archive
     */
    public static function archive(string $slug): void
    {
        global $db, $currentUserId;
        $dream = dream_model::findBySlug($db, $slug);

        if (!$dream || $dream['user_id'] != $currentUserId) {
            http_response_code(403);
			var_dump($dream);
var_dump($currentUserId);
exit;
            echo 'Forbidden';
            return;
        }

        dream_model::setArchived($db, $dream['id'], true);

        header('Location: /dashboard');
        exit;
    }

    /**
     * GET /dreams/{slug}/unarchive
     */
    public static function unarchive(string $slug): void
    {
        global $db, $currentUserId;
        $d = dream_model::findBySlug($db, $slug);
        if (!$d || $d['user_id'] != $currentUserId) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }
        dream_model::setArchived($db, $d['id'], false);
        header('Location: /dashboard/archived');
        exit;
    }

    /**
     * GET /dreams/{slug}/delete
     */
    public static function destroy(string $slug): void
    {
        global $db, $currentUserId;
        $d = dream_model::findBySlug($db, $slug);
        if (!$d || $d['user_id'] != $currentUserId) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }
        dream_model::softDelete($db, $d['id']);
        header('Location: /dashboard');
        exit;
    }

    /**
     * GET /dreams/{slug}/restore
     */
    public static function restore(string $slug): void
    {
        global $db, $currentUserId;
        $d = dream_model::findBySlug($db, $slug);
        if (!$d || $d['user_id'] != $currentUserId) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }
        dream_model::restore($db, $d['id']);
        header('Location: /dashboard/trash');
        exit;
    }
}
