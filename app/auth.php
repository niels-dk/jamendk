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

// Hydrate the site role for sessions created before users.role existed
// (one cheap lookup, then cached in the session for the rest of the visit).
if ($isAuthenticated && (!is_array($currentUser) || !isset($currentUser['role']))) {
    try {
        global $db;
        $st = $db->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
        $st->execute([$currentUserId]);
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['user'] = [
                'id'    => (int)$row['id'],
                'name'  => $row['name']  ?? '',
                'email' => $row['email'] ?? '',
                'role'  => $row['role']  ?? 'user',
            ];
            $currentUser = $_SESSION['user'];
        }
    } catch (\Throwable $e) {
        // users.role may not exist yet — treat as regular user until migrated
        if (is_array($currentUser)) { $currentUser['role'] = 'user'; }
    }
}
