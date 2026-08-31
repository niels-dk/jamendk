<?php
// Buffer all output so headers can always be sent late (defensive against
// stray whitespace / BOMs in included files breaking redirects).
ob_start();

// Errors are LOGGED, never printed. A printed warning leaks server paths and
// query fragments to whoever triggered it, and — worse in practice — lands in
// front of a JSON response body, so an API call that "mysteriously failed" was
// often a warning making the JSON unparseable.
//
// To debug on the live site, add   define('APP_DEBUG', true);   to
// app/config.php (which is gitignored and server-only) and remove it after.
// This runs before config.php is loaded, so the default is the safe one and
// the override is applied a few lines below.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Serve static files from /public or /storage if they physically exist.
$reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$full = __DIR__ . $reqPath;

// storage/private holds the encrypted document blobs. They are only ever
// meant to leave through /documents/{uuid}/download, which checks permission
// and decrypts. .htaccess forbids the path too, but this must not depend on
// .htaccess being applied — the whole point of defence in depth.
if (preg_match('#^/storage/private(/|$)#', $reqPath)) {
    http_response_code(404);
    exit;
}

if (preg_match('#^/(public|storage)/#', $reqPath) && is_file($full)) {
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $types = [
        'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp',
        'gif'=>'image/gif','bmp'=>'image/bmp','svg'=>'image/svg+xml','pdf'=>'application/pdf',
        'css'=>'text/css','js'=>'application/javascript',
        'json'=>'application/manifest+json','ico'=>'image/x-icon'
    ];
    // header() takes ONE string. The old call passed the type as the second
    // argument, which is the $replace flag — so it emitted a valueless
    // "Content-Type" header and every static file served through here went out
    // untyped, leaving the browser to sniff. That has to be right before
    // X-Content-Type-Options: nosniff is switched on below, or these files
    // stop rendering.
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=2592000'); // 30 days
    readfile($full);
    exit;
}

require_once __DIR__.'/app/config.php';

// Now that config.php has had its say, allow an explicit local override.
if (defined('APP_DEBUG') && APP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

// Session cookie hardening. Must be set BEFORE anything calls session_start(),
// which app/auth.php does on its very first line.
//   httponly  — JavaScript cannot read the session id, so an injected script
//               cannot walk off with a login
//   secure    — never sent over plain http
//   samesite  — Lax stops another site POSTing to us with your cookie attached,
//               which is what stands in for the CSRF tokens the overlay APIs
//               do not yet carry. Lax rather than Strict so links INTO the app
//               (a shared trip page, a link in an email) still arrive signed in.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Lax',
    ]);
}

// Response headers for everything this app renders.
if (!headers_sent()) {
    // Do not let a browser second-guess a declared Content-Type.
    header('X-Content-Type-Options: nosniff');
    // No framing: nothing here is meant to be embedded, and a published trip
    // page inside someone else's frame is a clickjacking surface.
    header('X-Frame-Options: DENY');
    header('Content-Security-Policy: frame-ancestors \'none\'');
    // The important one for this app. A trip page lives at /t/{token} and that
    // token IS the credential; its itinerary and shot locations link out to
    // Google Maps. Without this, clicking one hands the full /t/{token} URL to
    // Google in the Referer header. Same for /documents/{uuid}/download.
    // Cross-origin requests now carry the origin only, never the path.
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

require_once __DIR__.'/app/helpers.php';
require_once __DIR__.'/app/auth.php';
require_once __DIR__.'/app/permissions.php';
require_once __DIR__.'/app/i18n.php';   // after auth: language depends on the user
require_once __DIR__.'/app/analytics.php';
require_once __DIR__.'/app/backup_watchdog.php';
require_once __DIR__.'/app/routes.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// First-party page-view record. Runs after auth (so it knows logged-in vs
// anonymous). Cookie-free, never throws, skips bots, assets, APIs and the
// admin's own browsing — and now also skips anything that did not resolve to a
// real page, so the endless scanner traffic for /wp-login.php and /.env stops
// counting as visitors. The write happens at shutdown, once the status code is
// known; everything else about it is unchanged.
Analytics::recordWhenResolved($uri);

// Safety net for the nightly backup. DreamHost's cron has twice stopped firing
// for days with no error and nothing to fix, so the site's own traffic acts as
// a fallback scheduler: if the last SUCCESSFUL backup is over ~30h old, this
// launches one detached after the response has been sent. Cron stays primary.
backup_watchdog();

route(rtrim($uri,'/'));
?>