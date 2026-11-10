<?php
/**
 * Session cookie hardening — the one place that sets the flags.
 *
 * Every entry point requires this file FIRST, before anything that might
 * start a session (app/config.php, app/auth.php):
 *
 *   index.php            — the front controller
 *   api/dreams/*.php     — legacy endpoints that are served as real files and
 *                          never pass through index.php
 *
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
 */
if (!function_exists('mad_harden_session_cookie')) {

    function mad_harden_session_cookie(): void
    {
        if (headers_sent()) return;

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $flags = [
            'path'     => '/',
            'httponly' => true,
            'secure'   => $https,
            'samesite' => 'Lax',
        ];

        if (session_status() === PHP_SESSION_ACTIVE) {
            // Too late to change the parameters — re-issue the cookie itself.
            if (session_id() !== '') {
                setcookie(session_name(), session_id(), ['expires' => 0] + $flags);
            }
        } else {
            session_set_cookie_params(['lifetime' => 0] + $flags);
        }
    }
}

mad_harden_session_cookie();
