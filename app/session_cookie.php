<?php
/**
 * Session setup — the one place that decides how a login is stored, how long
 * it lasts, and what its cookie looks like.
 *
 * Every entry point requires this file FIRST, before anything that might
 * start a session (app/config.php, app/auth.php):
 *
 *   index.php            — the front controller
 *   api/dreams/*.php     — legacy endpoints that are served as real files and
 *                          never pass through index.php
 *
 * ── Cookie flags ───────────────────────────────────────────────────────────
 * The flags used to be set in index.php AFTER `require app/config.php`, behind
 * "if no session is active yet". On the live server a session WAS already
 * active by then, the guard skipped the block, and the cookie went out as a
 * bare `PHPSESSID=…; path=/` (checked with curl, Nov 2026).
 *
 * Two ways a session can be active that early, and this handles both:
 *   - something before us called session_start()  → running first fixes it
 *   - the host has session.auto_start on          → the session exists before
 *     any script line runs, so the parameters can no longer be changed; the
 *     cookie is re-issued with the flags instead (the browser keeps the last
 *     Set-Cookie for a name, and the session id is unchanged).
 *
 *   httponly  — JavaScript cannot read the session id, so an injected script
 *               cannot walk off with a login
 *   secure    — never sent over plain http
 *   samesite  — Lax stops another site POSTing to us with your cookie attached,
 *               which is what stands in for the CSRF tokens the overlay APIs
 *               do not yet carry. Lax rather than Strict so links INTO the app
 *               (a shared trip page, a link in an email) still arrive signed in.
 *
 * ── How long a login lasts ────────────────────────────────────────────────
 * This is a tool for the field: you open it on a roadside, days into a trip,
 * often with no signal — where signing in again is not possible. A login used
 * to last as long as the browser kept a session cookie AND the host's cleanup
 * left the session file alone, which on PHP's defaults is about 24 idle
 * minutes. Now:
 *
 *   - The cookie lasts MAD_LOGIN_DAYS and is renewed (at most once a day)
 *     while the account is in use, so it only runs out after that many days
 *     of NOT using the app.
 *   - Session files live in a folder of our own. In the shared default folder
 *     the host's cleanup deletes them on its own schedule, whatever lifetime
 *     we ask for.
 *   - That folder is cleaned here (see mad_session_gc): a signed-in session
 *     is kept for the full period, an anonymous one only for a couple of
 *     hours — scanners request /login thousands of times a day, and each
 *     request without a cookie creates a session file.
 *
 * Signing out still ends the login at once (user_controller::logout).
 */

if (!defined('MAD_LOGIN_DAYS')) define('MAD_LOGIN_DAYS', 30);

if (!function_exists('mad_harden_session_cookie')) {

    function mad_login_seconds(): int
    {
        return (int)MAD_LOGIN_DAYS * 86400;
    }

    function mad_cookie_flags(): array
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        return [
            'path'     => '/',
            'httponly' => true,
            'secure'   => $https,
            'samesite' => 'Lax',
        ];
    }

    /**
     * Where session files go: a folder of our own, outside the web root if
     * the host allows it. Returns '' when none can be used — sessions then
     * stay wherever PHP puts them, exactly as before, rather than breaking.
     */
    function mad_session_dir(): string
    {
        // Tests point this somewhere disposable.
        $override = getenv('MAD_SESSION_DIR');
        $candidates = $override ? [$override] : [
            dirname(__DIR__, 2) . '/.merelyadream_sessions',     // beside the site, not inside it
            dirname(__DIR__) . '/storage/private/sessions',      // inside, but never served (.htaccess + index.php)
        ];
        foreach ($candidates as $dir) {
            if (!@is_dir($dir)) @mkdir($dir, 0700, true);
            if (!@is_dir($dir)) continue;
            // is_writable() can be wrong on shared hosting; prove it once.
            if (!@is_file($dir . '/.ok')) {
                if (@file_put_contents($dir . '/.ok', "session folder — see app/session_cookie.php\n") === false) continue;
            }
            return $dir;
        }
        return '';
    }

    /** Runs before the session starts: storage, lifetime and cookie. */
    function mad_harden_session_cookie(): void
    {
        if (headers_sent()) return;
        $life  = mad_login_seconds();
        $flags = mad_cookie_flags();

        if (session_status() === PHP_SESSION_ACTIVE) {
            // Started before us (session.auto_start): storage can no longer be
            // moved, but the cookie can be re-issued with flags and lifetime.
            if (session_id() !== '') {
                setcookie(session_name(), session_id(), ['expires' => time() + $life] + $flags);
            }
            return;
        }

        if (ini_get('session.save_handler') === 'files') {
            $dir = mad_session_dir();
            if ($dir !== '') {
                session_save_path($dir);
                // PHP's own cleanup would walk the whole folder; ours is bounded
                // and treats signed-in and anonymous sessions differently.
                @ini_set('session.gc_probability', '0');
                $GLOBALS['__mad_session_dir'] = $dir;
                register_shutdown_function('mad_session_gc', $dir, $life);
            }
        }
        @ini_set('session.gc_maxlifetime', (string)$life);
        session_set_cookie_params(['lifetime' => $life] + $flags);
    }

    /**
     * Push the cookie's expiry out again, at most once a day, so a login runs
     * out after MAD_LOGIN_DAYS of not being used rather than MAD_LOGIN_DAYS
     * after signing in. Called from app/auth.php once the session is open,
     * and after the login regenerates the session id.
     */
    function mad_keep_login_alive(bool $force = false): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE || headers_sent() || session_id() === '') return;
        $now = time();
        if (!$force && $now - (int)($_SESSION['cookie_at'] ?? 0) < 86400) return;
        $_SESSION['cookie_at'] = $now;
        setcookie(session_name(), session_id(), ['expires' => $now + mad_login_seconds()] + mad_cookie_flags());
    }

    /**
     * Clean the session folder. About one request in 200, one slice of the
     * folder at a time (by the first character of the session id), after the
     * page has been sent.
     *
     *   older than the login period            → delete
     *   idle for 2 h and not a signed-in user  → delete
     */
    function mad_session_gc(string $dir, int $life, bool $force = false): int
    {
        if (!$force && mt_rand(1, 200) !== 1) return 0;
        $chars = '0123456789abcdefghijklmnopqrstuv,-';
        $slice = $force ? '' : $chars[mt_rand(0, strlen($chars) - 1)];
        $files = @glob($dir . '/sess_' . $slice . '*') ?: [];
        $now = time();
        $deleted = 0;
        foreach ($files as $path) {
            $mtime = @filemtime($path);
            if ($mtime === false) continue;
            $idle = $now - $mtime;
            if ($idle > $life) { if (@unlink($path)) $deleted++; continue; }
            if ($idle > 7200) {
                $data = @file_get_contents($path, false, null, 0, 65536);
                $signedIn = $data !== false
                    && (strpos($data, 'authenticated|b:1') !== false            // default "php" handler
                     || strpos($data, '"authenticated";b:1') !== false);        // "php_serialize" handler
                if (!$signedIn && @unlink($path)) $deleted++;
            }
        }
        return $deleted;
    }
}

mad_harden_session_cookie();
