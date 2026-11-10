<?php
/**
 * POST /api/dreams/update.php — save the Dream form. Returns {ok, slug}.
 *
 * Served as a real file (it never passes through index.php), so it has to do
 * its own guarding.
 *
 * Before Nov 2026 it checked ownership of the Dream named by `slug` and then
 * wrote to the Dream numbered `dream_id` — two values from the same form, and
 * nothing tied them together. Sending your own slug with someone else's id
 * passed the check and overwrote their Dream. The slug is now the only thing
 * that selects the Dream; `dream_id`, if sent, must agree with it.
 * It also never required a login in the first place.
 */
require_once __DIR__ . '/../../app/session_cookie.php';
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../models/dream.php';

header('Content-Type: application/json');

if (empty($isAuthenticated) || (int)$currentUserId <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'auth']);
    exit;
}

try {
    $postedId = (int)($_POST['dream_id'] ?? 0);
    $slug     = trim((string)($_POST['slug'] ?? ''));
    $title    = trim((string)($_POST['title'] ?? ''));
    $desc     = trim((string)($_POST['description'] ?? ''));

    if ($slug === '' || $title === '') throw new Exception('Invalid data');

    $dream = dream_model::findBySlug($db, $slug);
    // Cast both sides: depending on the driver the column comes back as "5"
    // or 5, and a strict compare of the two would lock every owner out.
    if (!$dream || (int)$dream['user_id'] !== (int)$currentUserId) {
        throw new Exception('Permission denied');
    }

    $id = (int)$dream['id'];
    if ($postedId && $postedId !== $id) throw new Exception('Invalid data');

    dream_model::update($db, $id, $title, $desc);
    dream_model::clearAnchors($db, $id);

    $anchors = static function (string $singular, string $plural): array {
        $v = $_POST[$singular] ?? $_POST[$plural] ?? [];
        return is_array($v) ? $v : [];
    };
    dream_model::addAnchors($db, $id, $anchors('location', 'locations'), 'dream_locations', 'location');
    dream_model::addAnchors($db, $id, $anchors('brand',    'brands'),    'dream_brands',    'brand');
    dream_model::addAnchors($db, $id, $anchors('person',   'people'),    'dream_people',    'person');
    dream_model::addAnchors($db, $id, $anchors('season',   'seasons'),   'dream_seasons',   'season');

    echo json_encode(['ok' => true, 'slug' => $slug]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
