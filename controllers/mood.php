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
}