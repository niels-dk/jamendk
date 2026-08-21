<?php
// Buffer all output so headers can always be sent late (defensive against
// stray whitespace / BOMs in included files breaking redirects).
ob_start();

//  ───  DEBUG ONLY  ───────────────────────────────────────────────
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//  ────────────────────────────────────────────────────────────────

// Serve static files from /public or /storage if they physically exist.
$reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$full = __DIR__ . $reqPath;

if (preg_match('#^/(public|storage)/#', $reqPath) && is_file($full)) {
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $types = [
        'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp',
        'gif'=>'image/gif','bmp'=>'image/bmp','svg'=>'image/svg+xml','pdf'=>'application/pdf',
        'css'=>'text/css','js'=>'application/javascript',
        'json'=>'application/manifest+json','ico'=>'image/x-icon'
    ];
    header('Content-Type', $types[$ext] ?? 'application/octet-stream');
    header('Cache-Control: public, max-age=2592000'); // 30 days
    readfile($full);
    exit;
}

require_once __DIR__.'/app/config.php';
require_once __DIR__.'/app/helpers.php';
require_once __DIR__.'/app/auth.php';
require_once __DIR__.'/app/permissions.php';
require_once __DIR__.'/app/i18n.php';   // after auth: language depends on the user
require_once __DIR__.'/app/analytics.php';
require_once __DIR__.'/app/routes.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// First-party page-view record. Runs after auth (so it knows logged-in vs
// anonymous) and before routing. Cookie-free, never throws, skips bots,
// assets, APIs and the admin's own browsing.
Analytics::record($uri);

route(rtrim($uri,'/'));
?>