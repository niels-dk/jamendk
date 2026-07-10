<?php
/**
 * Board-level permissions (Vision-centric, inherited by Mood boards).
 *
 * Roles per the product scope docs:
 *   owner     — implicit via visions.user_id
 *   co_owner  — everything the owner can do, incl. managing roles
 *   editor    — modify content (upload, place, connect, delete items)
 *   viewer    — read-only
 *   delegate  — acts on behalf of the owner (edit-level for now)
 *
 * Abilities:
 *   view   → owner, co_owner, editor, viewer, delegate  (+ site admin)
 *   edit   → owner, co_owner, editor, delegate          (+ site admin)
 *   manage → owner, co_owner                            (+ site admin)
 *            (role management + lifecycle: archive/delete/restore)
 */

/** Current user's role on a vision ('' when no access). Cached per request. */
function vision_role(PDO $db, ?array $vision): string
{
    static $cache = [];
    global $currentUserId;

    if (!$vision || empty($vision['id'])) return '';
    if (is_admin()) return 'owner';
    if (!$currentUserId) return '';

    $vid = (int)$vision['id'];
    if ((int)($vision['user_id'] ?? 0) === (int)$currentUserId) return 'owner';

    if (!array_key_exists($vid, $cache)) {
        try {
            $st = $db->prepare('SELECT role FROM vision_roles WHERE vision_id = ? AND user_id = ? LIMIT 1');
            $st->execute([$vid, (int)$currentUserId]);
            $cache[$vid] = (string)($st->fetchColumn() ?: '');
        } catch (\Throwable $e) {
            $cache[$vid] = ''; // table not migrated yet → owner-only behavior
        }
    }
    return $cache[$vid];
}

/** Can the current user perform $ability ('view'|'edit'|'manage') on this vision? */
function vision_can(PDO $db, ?array $vision, string $ability): bool
{
    $role = vision_role($db, $vision);
    if ($role === '') return false;
    switch ($ability) {
        case 'view':   return true; // any role can view
        case 'edit':   return in_array($role, ['owner','co_owner','editor','delegate'], true);
        case 'manage': return in_array($role, ['owner','co_owner'], true);
    }
    return false;
}

/** Resolve the parent Vision of a mood board (either linkage direction), or null. */
function mood_parent_vision(PDO $db, ?array $board): ?array
{
    static $cache = [];
    if (!$board || empty($board['id'])) return null;
    $bid = (int)$board['id'];
    if (array_key_exists($bid, $cache)) return $cache[$bid];

    $vision = null;
    try {
        // Direction 1: mood_boards.vision_id → visions.id
        if (!empty($board['vision_id'])) {
            $st = $db->prepare('SELECT * FROM visions WHERE id = ? AND deleted_at IS NULL LIMIT 1');
            $st->execute([(int)$board['vision_id']]);
            $vision = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        // Direction 2: visions.mood_id (slug) → this board
        if (!$vision && !empty($board['slug'])) {
            $st = $db->prepare("SELECT * FROM visions
                                 WHERE mood_id COLLATE utf8mb4_general_ci = ? AND deleted_at IS NULL
                                 LIMIT 1");
            $st->execute([$board['slug']]);
            $vision = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    } catch (\Throwable $e) {
        $vision = null;
    }
    return $cache[$bid] = $vision;
}

/** Can the current user perform $ability on this mood board (owner or inherited)? */
function mood_can(PDO $db, ?array $board, string $ability): bool
{
    global $currentUserId;
    if (!$board) return false;
    if (is_admin()) return true;
    if ($currentUserId && (int)($board['user_id'] ?? 0) === (int)$currentUserId) return true;

    // Inherit from the parent vision (per docs: Mood inherits Vision permissions)
    $vision = mood_parent_vision($db, $board);
    return $vision ? vision_can($db, $vision, $ability) : false;
}

/* ── HTML-page guards ────────────────────────────────────────────────── */

function require_vision(PDO $db, ?array $vision, string $ability): void
{
    require_login();
    if (!$vision || !vision_can($db, $vision, $ability)) {
        http_response_code(404);
        echo 'Not found';
        exit;
    }
}

function require_mood(PDO $db, ?array $board, string $ability): void
{
    require_login();
    if (!$board || !mood_can($db, $board, $ability)) {
        http_response_code(404);
        echo 'Not found';
        exit;
    }
}

/* ── JSON / API guards ───────────────────────────────────────────────── */

function api_require_vision(PDO $db, ?array $vision, string $ability): void
{
    api_require_login();
    if (!$vision || !vision_can($db, $vision, $ability)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not found']);
        exit;
    }
}

function api_require_mood(PDO $db, ?array $board, string $ability): void
{
    api_require_login();
    if (!$board || !mood_can($db, $board, $ability)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not found']);
        exit;
    }
}
