<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* expose $currentUserId / $currentUser / $isAuthenticated to the global scope */
global $currentUserId, $currentUser, $isAuthenticated;

// Real auth required: $isAuthenticated is true only when both the user id
// and the 'authenticated' flag are present in the session. Legacy sessions
// that only had a fallback $_SESSION['user_id']=1 from the old auth.php
// will fail this test and be treated as anonymous.
$isAuthenticated = !empty($_SESSION['authenticated']) && !empty($_SESSION['user_id']);

$currentUserId = $isAuthenticated ? (int)$_SESSION['user_id'] : 0;
$currentUser   = $isAuthenticated && !empty($_SESSION['user']) ? $_SESSION['user'] : null;
