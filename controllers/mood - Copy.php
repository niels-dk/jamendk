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
        global $db, $user;
        $draft = mood_model::createDraft($db, (int)$user['id']);
        // redirect to edit form for the new board
        header('Location: /moods/' . $draft['slug'] . '/edit');
        exit;
    }

    /** GET /moods/{slug} – display a mood board. */
    public static function show(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo 'Mood board not found'; return; }
        $boardType = 'mood';
        $pageTitle = htmlspecialchars($board['title'] ?? 'Untitled Mood Board');
        ob_start();
        include __DIR__ . '/../views/mood_show.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** GET /moods/{slug}/edit – edit form for a mood board. */
    public static function edit(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo 'Mood board not found'; return; }
        $boardType = 'mood';
        $pageTitle = htmlspecialchars($board['title'] ?? 'Edit Mood Board');
        ob_start();
        include __DIR__ . '/../views/mood_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** POST /moods/update – save title or other basics (non‑AJAX fallback). */
    public static function update(): void
    {
        global $db;
        $id    = (int)($_POST['mood_id'] ?? 0);
        if (!$id) { http_response_code(400); echo 'Missing ID'; return; }
        $title = trim($_POST['title'] ?? '');
        $visionId = isset($_POST['vision_id']) ? (int)$_POST['vision_id'] : null;
        $fields = [];
        if ($title !== '') $fields['title'] = $title;
        if ($visionId)     $fields['vision_id'] = $visionId;
        mood_model::partialUpdate($db, $id, $fields);
        // redirect back to show page
        $slug = (string)$db->query("SELECT slug FROM mood_boards WHERE id=$id")->fetchColumn();
        header('Location: /moods/' . $slug);
        exit;
    }

    /** Archive a board (POST or GET). */
    public static function archive(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo 'Mood board not found'; return; }
        mood_model::setArchived($db, (int)$board['id'], true);
        header('Location: /dashboard/moods');
        exit;
    }

    public static function unarchive(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo 'Mood board not found'; return; }
        mood_model::setArchived($db, (int)$board['id'], false);
        header('Location: /dashboard/moods/archived');
        exit;
    }

    /** Soft delete (move to trash). */
    public static function destroy(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo 'Mood board not found'; return; }
        mood_model::softDelete($db, (int)$board['id']);
        header('Location: /dashboard/moods');
        exit;
    }

    /** Restore from trash. */
    public static function restore(string $slug): void
    {
        global $db;
        // fetch even if deleted
        $st = $db->prepare("SELECT id FROM mood_boards WHERE slug=? LIMIT 1");
        $st->execute([$slug]);
        $id = (int)($st->fetchColumn() ?: 0);
        if (!$id) { http_response_code(404); echo 'Mood board not found'; return; }
        mood_model::restore($db, $id);
        header('Location: /dashboard/moods/trash');
        exit;
    }
	
	// GET /moods/{slug} or /moods/{slug}/info
    // Shows the “Info” overview page for a mood board.
    public static function info(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) {
            http_response_code(404);
            echo 'Mood board not found';
            return;
        }

        // load view 'mood/info.php' which contains the Info layout
        include __DIR__ . '/../views/mood/info.php';
    }

    // GET /moods/{slug}/media
    // Shows the media library page (upload/list/filter UI).
    public static function media(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) {
            http_response_code(404);
            echo 'Mood board not found';
            return;
        }

        // You may reuse the existing edit view for media, or create a new view.
        include __DIR__ . '/../views/mood/media.php';
    }

    // GET /moods/{slug}/canvas
    // Shows the canvas / item placement page.
    public static function canvas(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) {
            http_response_code(404);
            echo 'Mood board not found';
            return;
        }

        include __DIR__ . '/../views/mood/canvas.php';
    }
	
	public static function overview(string $slug): void
	{
		header('Content-Type: text/html; charset=utf-8');
		global $db;

		// Fetch the board by slug
		$board = mood_model::get($db, $slug);
		if (!$board) {
			http_response_code(404);
			echo '<h1>Board not found</h1>';
			return;
		}

		// If you already have a view for this, require it here:
		//   require __DIR__ . '/../views/moods/overview.php';
		//
		// To avoid breaking anything right now, fallback to a minimal
		// template that uses the same page chrome as your other pages.

		// Try to reuse your site header/footer if they exist.
		$header = __DIR__ . '/../views/_header.php';
		$footer = __DIR__ . '/../views/_footer.php';

		if (is_file($header)) require $header;

		// ---- minimal overview content (safe to keep/replace with your real view) ----
		?>
		<main class="container" style="max-width:1100px;margin:32px auto;">
		  <section class="card" style="background:#0f141b;border-radius:16px;padding:24px;">
			<h1 style="margin:0 0 8px; font-weight:700;"><?= htmlspecialchars($board['name'] ?? $board['title'] ?? $slug) ?></h1>
			<p style="opacity:.8;margin:0 0 20px;">
			  This mood board is under construction. An interactive canvas will appear here in a future update.
			</p>

			<div style="display:flex; gap:12px;">
			  <a class="btn" href="/moods/<?= urlencode($slug) ?>/edit">Edit</a>
			  <a class="btn btn-secondary" href="/moods">Back to list</a>
			</div>
		  </section>
		</main>
		<style>
		  .btn{background:#0a5aee;color:#fff;padding:10px 16px;border-radius:10px;text-decoration:none}
		  .btn-secondary{background:#1f2632}
		  .card{box-shadow:0 10px 30px rgba(0,0,0,.35)}
		</style>
		<?php
		// -----------------------------------------------------------------------------

		if (is_file($footer)) require $footer;
	}


    // GET /moods/{slug}/settings
    // Shows the settings page (relate to Vision board, status, usage, etc.).
    public static function settings(string $slug): void
    {
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) {
            http_response_code(404);
            echo 'Mood board not found';
            return;
        }

        include __DIR__ . '/../views/mood/settings.php';
    }
}