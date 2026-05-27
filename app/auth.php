<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* expose $currentUserId / $currentUser / $isAuthenticated to the global scope */
global $currentUserId, $currentUser, $isAuthenticated;

$isAuthenticated = !empty($_SESSION['authenticated']) && !empty($_SESSION['user_id']);

if (!isset($_SESSION['user_id'])) {
    // Anonymous visitor — keep the legacy fallback user so existing data
    // (created when there was no auth) is still browsable during Phase 1.
    $_SESSION['user_id'] = 1;
}

$currentUserId = (int) $_SESSION['user_id'];
$currentUser   = $isAuthenticated && !empty($_SESSION['user']) ? $_SESSION['user'] : null;
