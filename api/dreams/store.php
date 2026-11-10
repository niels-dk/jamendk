<?php
/**
 * POST /api/dreams/store.php — create a Dream. Returns {ok, slug}.
 *
 * Served as a real file (it never passes through index.php), so it has to do
 * its own guarding. Used by the Dream form and by the offline queue when it
 * syncs (public/js/dream-sync.js).
 *
 * Three things this did not do before Nov 2026:
 *
 *  1. Require a login. app/auth.php only SETS $currentUserId; it stops nobody.
 *     An anonymous POST created a Dream owned by user 0 — so anyone could fill
 *     the table, and worse, a queue syncing after the login had expired got
 *     "ok" back, deleted its local copy, and the idea belonged to nobody.
 *     Now: 401 {ok:false, error:"auth"}, and the client keeps the idea.
 *
 *  2. Survive a retry. A phone that sends an idea, loses signal before the
 *     answer arrives and sends it again made two Dreams. An identical Dream
 *     (same owner, title and text) made in the last hour is treated as the
 *     same one and its slug is returned.
 *
 *  3. Read the anchors the offline form sends. That form posts locations[],
 *     brands[], people[], seasons[]; this file only read the singular names,
 *     so anchors typed offline were dropped at sync. Both spellings work now.
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
    $title = trim((string)($_POST['title'] ?? ''));
    $desc  = trim((string)($_POST['description'] ?? ''));

    // Pasted text that starts with a blank line: the text is the idea.
    if ($title === '' && $desc !== '') {
        $title = function_exists('mb_substr') ? mb_substr($desc, 0, 80) : substr($desc, 0, 80);
    }
    if ($title === '') throw new Exception('Title required');
    // The column holds 255; a long first line is an idea, not an error.
    $title = function_exists('mb_substr') ? mb_substr($title, 0, 255) : substr($title, 0, 255);

    $uid = (int)$currentUserId;

    // The same idea, sent again within the hour → the one we already have.
    // NOW() is read from the database so the comparison cannot be thrown off
    // by PHP and MySQL disagreeing about the time zone.
    $dup = $db->prepare(
        'SELECT slug, created_at, NOW() AS now_ts
           FROM dream_boards
          WHERE user_id = ? AND title = ? AND description = ? AND deleted_at IS NULL
          ORDER BY id DESC
          LIMIT 1'
    );
    $dup->execute([$uid, $title, $desc]);
    $prev = $dup->fetch(PDO::FETCH_ASSOC);
    if ($prev) {
        $age = strtotime((string)$prev['now_ts']) - strtotime((string)$prev['created_at']);
        if ($age >= 0 && $age <= 3600) {
            echo json_encode(['ok' => true, 'slug' => $prev['slug'], 'duplicate' => true]);
            exit;
        }
    }

    $slug  = dream_model::create($db, $uid, $title, $desc);
    $dream = dream_model::findBySlug($db, $slug);

    /* anchors — plural names from the offline form, singular from the page form */
    $anchors = static function (string $plural, string $singular): array {
        $v = $_POST[$plural] ?? $_POST[$singular] ?? [];
        return is_array($v) ? $v : [];
    };
    dream_model::addAnchors($db, (int)$dream['id'], $anchors('locations', 'location'), 'dream_locations', 'location');
    dream_model::addAnchors($db, (int)$dream['id'], $anchors('brands',    'brand'),    'dream_brands',    'brand');
    dream_model::addAnchors($db, (int)$dream['id'], $anchors('people',    'person'),   'dream_people',    'person');
    dream_model::addAnchors($db, (int)$dream['id'], $anchors('seasons',   'season'),   'dream_seasons',   'season');

    echo json_encode(['ok' => true, 'slug' => $slug]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
