<?php
/** Generate a URL-safe random slug (default 8 chars) */
function make_slug(int $len = 12): string
{
    return substr(
        str_replace(['+','/','='], '', base64_encode(random_bytes(10))),
        0,
        $len
    );
}

/** Redirect to a relative path. Sends an absolute URL for RFC compliance. */
function redirect(string $path): void
{
    if ($path === '' || $path[0] !== '/') $path = '/' . $path;
    // Discard any buffered output so the Location header can land.
    while (ob_get_level() > 0) ob_end_clean();
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    header('Location: ' . $scheme . '://' . $host . $path);
    exit;
}

/** Get or lazily generate the CSRF token for this session. */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Constant-time validate a posted CSRF token. */
function csrf_check(?string $token): bool
{
    if (!$token || empty($_SESSION['csrf_token'])) return false;
    return hash_equals((string)$_SESSION['csrf_token'], $token);
}

/** True when the visitor is a real logged-in user (vs the anonymous fallback). */
function is_logged_in(): bool
{
    return !empty($_SESSION['authenticated']) && !empty($_SESSION['user_id']);
}

/** Get the logged-in user row from session (or null when anonymous). */
function current_user(): ?array
{
    return is_logged_in() && !empty($_SESSION['user']) ? $_SESSION['user'] : null;
}

/**
 * Guard for HTML pages: send anonymous visitors to /login and remember
 * where they were trying to go so we can bounce them back after sign-in.
 */
function require_login(): void
{
    if (is_logged_in()) return;
    $next = $_SERVER['REQUEST_URI'] ?? '/';
    redirect('/login?next=' . urlencode($next));
}

/**
 * Guard for JSON / API endpoints: return 401 JSON when not authenticated.
 */
function api_require_login(): void
{
    if (is_logged_in()) return;
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

/** Does the current user own this row? */
function is_owner(?array $row, string $key = 'user_id'): bool
{
    global $currentUserId;
    if (!$row || !$currentUserId) return false;
    return (int)($row[$key] ?? 0) === (int)$currentUserId;
}

/**
 * Guard for HTML pages that operate on a specific record:
 *   - if anonymous → redirect to /login
 *   - if logged in but not the owner → 404 (don't leak existence)
 */
function require_owner(?array $row, string $key = 'user_id'): void
{
    require_login();
    if (!$row || !is_owner($row, $key)) {
        http_response_code(404);
        echo 'Not found';
        exit;
    }
}

/** JSON-flavored ownership check for API endpoints. */
function api_require_owner(?array $row, string $key = 'user_id'): void
{
    api_require_login();
    if (!$row || !is_owner($row, $key)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not found']);
        exit;
    }
}
