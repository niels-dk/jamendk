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

/** Redirect to a relative path. Accepts '/foo' or 'foo'. */
function redirect(string $path): void
{
    if ($path === '' || $path[0] !== '/') $path = '/' . $path;
    header('Location: ' . $path);
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
?>
