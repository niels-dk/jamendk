<?php
/**
 * tools/reset_password.php — admin password reset.
 *
 * Usage (from the project root, on a server with app/config.php in place):
 *
 *     php tools/reset_password.php  email@example.com  NewPassword
 *     php tools/reset_password.php  email@example.com  --generate         # generates a random password
 *     php tools/reset_password.php  --hash NewPassword                    # just print a bcrypt hash, don't touch the DB
 *
 * Notes:
 *   - This script MUST be invoked from the CLI (not via the web).
 *   - It loads app/config.php to reuse the live $db PDO handle.
 *   - The new hash uses bcrypt with cost 12 (slightly stronger than
 *     PHP's current default of 10 — existing hashes keep working).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This tool is CLI-only.\n");
}

function out(string $s): void { fwrite(STDOUT, $s . "\n"); }
function err(string $s): void { fwrite(STDERR, $s . "\n"); }

$cost = 12;

// --hash NEWPASS  → just print a hash; no DB access required.
if (($argv[1] ?? null) === '--hash') {
    $pass = $argv[2] ?? null;
    if ($pass === null || $pass === '') {
        err('Usage: php tools/reset_password.php --hash NewPassword');
        exit(2);
    }
    out(password_hash($pass, PASSWORD_BCRYPT, ['cost' => $cost]));
    exit(0);
}

$email = $argv[1] ?? null;
$pass  = $argv[2] ?? null;

if (!$email) {
    err('Usage: php tools/reset_password.php email@example.com NewPassword');
    err('       php tools/reset_password.php email@example.com --generate');
    err('       php tools/reset_password.php --hash NewPassword');
    exit(2);
}

if ($pass === null || $pass === '') {
    err('Missing password (use --generate to auto-create one).');
    exit(2);
}

// --generate → make a friendly random 16-char password
if ($pass === '--generate') {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $pass = '';
    for ($i = 0; $i < 16; $i++) {
        $pass .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
}

// Load the live PDO handle the same way the app does.
$configPath = __DIR__ . '/../app/config.php';
if (!is_file($configPath)) {
    err("Cannot find app/config.php at $configPath");
    err('Make sure you are running this from the project root on a server with DB credentials configured.');
    exit(3);
}
require $configPath;

if (!isset($db) || !($db instanceof PDO)) {
    err('app/config.php did not expose a $db PDO instance.');
    exit(3);
}

try {
    $find = $db->prepare('SELECT id, name, email FROM users WHERE email = ? LIMIT 1');
    $find->execute([$email]);
    $user = $find->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        err("No user with email: $email");
        exit(4);
    }

    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => $cost]);

    $upd = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $upd->execute([$hash, (int)$user['id']]);

    out('');
    out("✓ Password reset for: {$user['name']} <{$user['email']}> (id {$user['id']})");
    out('  New password: ' . $pass);
    out('  Hash stored:  ' . $hash);
    out('');
    out('Share the password securely (in person, password manager, signal, etc.)');
    out('and tell the user to change it on next login.');
    exit(0);

} catch (\Throwable $e) {
    err('Failed: ' . $e->getMessage());
    exit(5);
}
