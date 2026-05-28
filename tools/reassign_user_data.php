<?php
/**
 * tools/reassign_user_data.php — move all boards owned by one user to another.
 *
 * Usage:
 *     php tools/reassign_user_data.php  FROM_USER_ID  TO_USER_ID
 *     php tools/reassign_user_data.php  1  2          # move all user 1's data to user 2
 *     php tools/reassign_user_data.php  --dry  1  2   # show what would change, don't write
 *
 * Tables touched (only the ones with user_id):
 *     dream_boards, visions, mood_boards
 *
 * Sub-tables (vision_anchors, vision_goals, vision_documents, canvas_items, etc.)
 * follow naturally because they reference the board ids, which don't change —
 * only the user_id on the parent row moves.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$args = array_slice($argv, 1);
$dry  = false;
if ($args && $args[0] === '--dry') { $dry = true; array_shift($args); }

if (count($args) !== 2) {
    fwrite(STDERR, "Usage: php tools/reassign_user_data.php [--dry] FROM_USER_ID TO_USER_ID\n");
    exit(2);
}
$from = (int)$args[0];
$to   = (int)$args[1];
if (!$from || !$to || $from === $to) {
    fwrite(STDERR, "FROM and TO must be different positive integers.\n");
    exit(2);
}

require __DIR__ . '/../app/config.php';
if (!isset($db) || !($db instanceof PDO)) { fwrite(STDERR, "No \$db PDO.\n"); exit(2); }

// Verify both users exist
$verify = $db->prepare('SELECT id, name, email FROM users WHERE id IN (?, ?)');
$verify->execute([$from, $to]);
$users = $verify->fetchAll(PDO::FETCH_ASSOC);
$byId = [];
foreach ($users as $u) $byId[(int)$u['id']] = $u;

if (!isset($byId[$to])) {
    fwrite(STDERR, "Target user $to does not exist. Aborting.\n");
    exit(3);
}
// FROM is allowed not to exist (the old fallback user 1 might never have been a real row).

echo "Reassigning data from user $from"
   . (isset($byId[$from]) ? " ({$byId[$from]['name']})" : ' (no users row)')
   . " → user $to ({$byId[$to]['name']} <{$byId[$to]['email']}>)\n";
if ($dry) echo "DRY RUN — no writes will be made.\n";
echo "\n";

$tables = ['dream_boards', 'visions', 'mood_boards'];
$total = 0;
foreach ($tables as $t) {
    $c = $db->prepare("SELECT COUNT(*) FROM `$t` WHERE user_id = ?");
    $c->execute([$from]);
    $n = (int)$c->fetchColumn();
    echo "  $t: $n rows\n";
    if ($n && !$dry) {
        $u = $db->prepare("UPDATE `$t` SET user_id = ? WHERE user_id = ?");
        $u->execute([$to, $from]);
    }
    $total += $n;
}

echo "\n" . ($dry ? "Would update $total rows.\n" : "Reassigned $total rows.\n");
