<?php
//  ───  DEBUG ONLY  ───────────────────────────────────────────────
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//  ────────────────────────────────────────────────────────────────

require_once __DIR__.'/app/config.php';
require_once __DIR__.'/app/routes.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
route(rtrim($uri,'/'));
?>