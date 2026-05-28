<?php
/**
 * tools/whoami.php — show users + per-user board ownership counts.
 *
 * Usage (from the project root):
 *     php tools/whoami.php
 *
 * Read-only. Useful for figuring out which user_id owns what, especially
 * after Phase 2 when the old fallback (user_id=1) is gone and you might
 * have registered as a different id.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This tool is CLI-only.\n");
}

$configPath = __DIR__ . '/../app/config.php';
if (!is_file($configPath)) { fwrite(STDERR, "Missing app/config.php\n"); exit(2); }
require $configPath;
if (!isset($db) || !($db instanceof PDO)) { fwrite(STDERR, "No \$db PDO\n"); exit(2); }

echo "── USERS ─────────────────────────────────────────────────────────\n";
$users = $db->query("SELECT id, name, email, created_at FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
if (!$users) {
    echo "(no users yet)\n";
} else {
    foreach ($users as $u) {
        printf("  id=%-3d  %s  <%s>  (since %s)\n",
            $u['id'], $u['name'] ?? '?', $u['email'] ?? '?', $u['created_at'] ?? '?');
    }
}

echo "\n── BOARD OWNERSHIP ───────────────────────────────────────────────\n";
$queries = [
    'dream_boards' => 'SELECT user_id, COUNT(*) AS n FROM dream_boards WHERE deleted_at IS NULL GROUP BY user_id ORDER BY user_id',
    'visions'      => 'SELECT user_id, COUNT(*) AS n FROM visions      WHERE deleted_at IS NULL GROUP BY user_id ORDER BY user_id',
    'mood_boards'  => 'SELECT user_id, COUNT(*) AS n FROM mood_boards  WHERE deleted_at IS NULL GROUP BY user_id ORDER BY user_id',
];
foreach ($queries as $table => $sql) {
    echo "  $table:\n";
    $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { echo "    (none)\n"; continue; }
    foreach ($rows as $r) {
        printf("    user_id=%-3d → %d rows\n", $r['user_id'], $r['n']);
    }
}

echo "\nDone.\n";
