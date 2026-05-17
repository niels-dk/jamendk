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

		// Has this dream already been promoted? If so, surface the link.
		$linkedVision = self::findLinkedVision($db, (int)$dream['id']);

		$title = htmlspecialchars($dream['title']);
		$view = 'dream_show';
		include __DIR__ . '/../views/dream_show.php';
		$noSidebar = true;
		include __DIR__ . '/../views/layout.php';
	}

	/** Internal: look up the vision that a dream was promoted into (if any). */
	private static function findLinkedVision(PDO $db, int $dreamId): ?array
	{
		try {
			$st = $db->prepare("SELECT id, slug, title
								  FROM visions
								 WHERE dream_id = ? AND deleted_at IS NULL
								 ORDER BY id ASC
								 LIMIT 1");
			$st->execute([$dreamId]);
			return $st->fetch(PDO::FETCH_ASSOC) ?: null;
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * POST /dreams/{slug}/promote — promote this Dream into a new Vision.
	 *
	 * Copies title, description and anchors into the new vision, sets the
	 * vision.dream_id back-reference, and redirects to the vision's edit page.
	 * If this dream has already been promoted, redirects to the existing vision
	 * rather than creating a duplicate.
	 */
	public static function promote(string $slug): void
	{
		global $db, $currentUserId;

		$dream = dream_model::get($db, $slug);
		if (!$dream) { http_response_code(404); echo 'Dream not found'; return; }

		// Idempotent — if already promoted, just bounce to the existing vision.
		$existing = self::findLinkedVision($db, (int)$dream['id']);
		if ($existing) {
			header('Location: /visions/' . $existing['slug'] . '/edit');
			return;
		}

		require_once __DIR__ . '/../models/vision.php';

		$userId      = (int)($currentUserId ?: ($dream['user_id'] ?? 1));
		$title       = (string)($dream['title']       ?? '');
		$description = (string)($dream['description'] ?? '');

		$db->beginTransaction();
		try {
			// 1) Create the vision (use the existing draft helper to get a slug,
			//    then fill in fields including the dream_id back-reference)
			$draft = vision_model::createDraft($db, $userId);
			$visionId   = (int)$draft['id'];
			$visionSlug = (string)$draft['slug'];

			vision_model::partialUpdate($db, $visionId, [
				'title'       => $title !== '' ? $title : null,
				'description' => $description !== '' ? $description : null,
				'status'      => 'active',
				'dream_id'    => (int)$dream['id'],
			]);

			// 2) Copy anchors. Dream anchors live in per-key tables; flatten
			//    into the {key, value} pairs vision_anchors expects.
			$dreamAnchors = dream_model::getAnchors($db, (int)$dream['id']);
			$kv = [];
			foreach ($dreamAnchors as $key => $vals) {
				if (!is_array($vals)) continue;
				foreach ($vals as $val) {
					$val = trim((string)$val);
					if ($val === '') continue;
					$kv[] = ['key' => (string)$key, 'value' => $val];
				}
			}
			if ($kv) {
				vision_model::replaceAnchors($db, $visionId, $kv);
			}

			$db->commit();
			header('Location: /visions/' . $visionSlug . '/edit');
		} catch (\Throwable $e) {
			$db->rollBack();
			http_response_code(500);
			echo 'Promote failed: ' . htmlspecialchars($e->getMessage());
		}
	}

    /**
     * GET /dreams/new
     */
   public static function create(): void
	{
		$title = 'New Dream';

		ob_start();
		include __DIR__ . '/../views/dream_form.php';
		//$content = ob_get_clean();
		$noSidebar = true;      // tell the layout to hide the sidebar for /dreams/new
	    $noSidebar = true;
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
		$noSidebar = true;
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
