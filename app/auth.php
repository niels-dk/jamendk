<?php
session_start();

/* expose $currentUserId to the global scope */
global $currentUserId;

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;                // TEMP fallback user
}

$currentUserId = (int) $_SESSION['user_id']; // now truly global
