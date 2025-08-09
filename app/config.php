<?php
session_start();

$currentUserId = $_SESSION['user_id'] ?? null;

$dsn = 'mysql:host=mysql.jamen.dk;port=3306;dbname=jamen_dk;charset=utf8mb4';
$dbUser   = 'jamendk';                                        // ← edit
$dbPass   = 'nUDbNFwi';                                        // ← edit

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try { $db = new PDO($dsn, $dbUser, $dbPass, $options); }
catch (PDOException $e) { die('Database connection failed: '.$e->getMessage()); }

/* helpers (slug generator, etc.) */
require_once __DIR__.'/helpers.php';
?>
