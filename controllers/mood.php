<?php
/**
 * mood controller
 *
 * This controller handles CRUD operations for Mood Boards.  It follows the
 * conventions used by the existing dream and vision controllers: mapping
 * routes to methods that prepare data and render appropriate views.  At
 * this stage the implementation is deliberately minimal – the actual mood
 * board editing UI and API endpoints will be layered on later once the
 * database tables and models are in place.  For now it supports listing,
 * creating drafts, editing basics and soft‑deleting boards.
 */
require_once __DIR__ . '/../models/mood.php';

class mood_controller
{
    /** Show a list of the current user’s mood boards (active). */
    public static function index(): void
    {
        global $db, $user;
        $boards = mood_model::listActive($db, (int)$user['id']);
        $boardType = 'mood';
        $pageTitle = 'My Mood Boards';
        ob_start();
        include __DIR__ . '/../views/dashboard_moods.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** GET /moods/new – create a draft and redirect to edit form. */
    public static function create(): void
    {
        require_login();
        global $db, $currentUserId;
        $draft = mood_model::createDraft($db, (int)$currentUserId);
			header('Location: /moods/' . $draft['slug'] . '/media'); // previously '/edit'
		exit;
    }

    /** GET /moods/{slug} – display a mood board (owner-only). */
    public static function show(string $slug): void
    {
        require_login();
        global $db;
        $board = mood_model::get($db, $slug);
        require_owner($board);
        $boardType = 'mood';
        $pageTitle = htmlspecialchars($board['title'] ?? 'Untitled Mood Board');

        // Linked vision (if any)
        $linkedVision = null;
        if (!empty($board['vision_id'])) {
            $vs = $db->prepare("SELECT slug, title FROM visions WHERE id=? AND deleted_at IS NULL LIMIT 1");
            $vs->execute([(int)$board['vision_id']]);
            $linkedVision = $vs->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        // Canvas item counts grouped by kind (skip hidden)
        $cs = $db->prepare("SELECT kind, COUNT(*) AS n
                              FROM canvas_items
                             WHERE board_id=? AND hidden=0
                             GROUP BY kind");
        $cs->execute([(int)$board['id']]);
        $itemCounts = [];
        foreach ($cs->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $itemCounts[$row['kind']] = (int)$row['n'];
        }

        ob_start();
        include __DIR__ . '/../views/mood_show.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** GET /moods/{slug}/edit – edit form for a mood board. */
    public static function editMedia(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        require_owner($board);
        $boardType = 'mood';
        $pageTitle = htmlspecialchars($board['title'] ?? 'Edit Mood Board');
        ob_start();
        include __DIR__ . '/../views/mood_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }
	
	public static function edit(string $slug): void
	{
		require_login();
		global $db;

		// Fetch the board by slug and verify ownership.
		$board = mood_model::get($db, $slug);
		require_owner($board);

		// On POST, update the title and description then redirect back to the
		// mood board info page.
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$title       = trim($_POST['title'] ?? '');
			$description = trim($_POST['description'] ?? '');

			// Only update fields if they are provided.  The partialUpdate
			// method will ignore unknown columns.
			$fields = [];
			if ($title !== '') {
				$fields['title'] = $title;
			}
			if ($description !== '') {
				$fields['description'] = $description;
			}
			if ($fields) {
				mood_model::partialUpdate($db, (int)$board['id'], $fields);
			}
			// Redirect to the mood board's show page after saving.
			header('Location: /moods/' . $slug);
			exit;
		}

		// Otherwise show the edit form.  Set a page title for the layout.
		$pageTitle = 'Edit Mood Board';
		ob_start();
		include __DIR__ . '/../views/mood_edit.php';
		$content = ob_get_clean();
		include __DIR__ . '/../views/layout.php';
	}

    /** POST /moods/update – save title or other basics (non‑AJAX fallback). */
    public static function update(): void
    {
        require_login();
        global $db, $currentUserId;
        $id    = (int)($_POST['mood_id'] ?? 0);
        if (!$id) { http_response_code(400); echo 'Missing ID'; return; }

        // Ownership check before any write
        $own = $db->prepare("SELECT user_id, slug FROM mood_boards WHERE id = ? LIMIT 1");
        $own->execute([$id]);
        $row = $own->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['user_id'] !== (int)$currentUserId) {
            http_response_code(404); echo 'Not found'; return;
        }

        $title = trim($_POST['title'] ?? '');
        $visionId = isset($_POST['vision_id']) ? (int)$_POST['vision_id'] : null;
        $fields = [];
        if ($title !== '') $fields['title'] = $title;
        if ($visionId)     $fields['vision_id'] = $visionId;
        mood_model::partialUpdate($db, $id, $fields);
        header('Location: /moods/' . $row['slug']);
        exit;
    }

    /** Archive a board (POST or GET). */
    public static function archive(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        require_owner($board);
        mood_model::setArchived($db, (int)$board['id'], true);
        header('Location: /dashboard/moods');
        exit;
    }

    public static function unarchive(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        require_owner($board);
        mood_model::setArchived($db, (int)$board['id'], false);
        header('Location: /dashboard/moods/archived');
        exit;
    }

    /** Soft delete (move to trash). */
    public static function destroy(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        require_owner($board);
        mood_model::softDelete($db, (int)$board['id']);
        header('Location: /dashboard/moods');
        exit;
    }

    /** Restore from trash. */
    public static function restore(string $slug): void
    {
        require_login();
        global $db, $currentUserId;
        // fetch even if deleted
        $st = $db->prepare("SELECT id, user_id FROM mood_boards WHERE slug=? LIMIT 1");
        $st->execute([$slug]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['user_id'] !== (int)$currentUserId) {
            http_response_code(404); echo 'Not found'; return;
        }
        mood_model::restore($db, (int)$row['id']);
        header('Location: /dashboard/moods/trash');
        exit;
    }
	
	/** 
	 * GET /moods/{slug}/canvas – display the interactive canvas for a mood board.
	 * Loads the board and renders the canvas view. Returns 404 if not found.
	 * The view will pull in a JavaScript file to power the editor.
	 */
	public static function canvas(string $slug): void
	{
		global $db;
		$board = mood_model::get($db, $slug);
		require_owner($board);
		$boardType = 'mood';
		// Append “Canvas” to the page title
		$pageTitle = htmlspecialchars(($board['title'] ?? '') . ' Canvas');
		ob_start();
		include __DIR__ . '/../views/mood_canvas.php';
		$content = ob_get_clean();
		include __DIR__ . '/../views/layout.php';
	}

}