<?php
/**
 * Sample of app/config.php — the real file is gitignored and lives ONLY on
 * the server, because it holds credentials. Never commit the real one.
 *
 * To enable email: copy the MAIL block below into your existing
 * app/config.php on DreamHost (keep your current $db / PDO setup as-is).
 */

/* ── Database (your existing setup — shown for shape only) ────────────── */
// $db = new PDO('mysql:host=...;dbname=jamen_dk;charset=utf8mb4', 'user', 'pass', [
//     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
// ]);

/* ── Mail ─────────────────────────────────────────────────────────────────
 * Driver options:
 *   'smtp' — authenticated send through DreamHost. Correct SPF/DKIM, best
 *            deliverability. Requires a real mailbox (you have dream@jamen.dk).
 *   'mail' — PHP mail(). Zero config, weaker deliverability. The fallback.
 *   'log'  — writes to mail_log without sending. Useful for local testing.
 */
define('MAIL_DRIVER',    'smtp');
define('MAIL_HOST',      'smtp.dreamhost.com');
define('MAIL_PORT',      465);                 // 465 = implicit SSL; 587 = STARTTLS
define('MAIL_USER',      'dream@jamen.dk');    // full address is the SMTP username

// Single quotes: the password may contain " # $ etc., and single-quoted PHP
// strings don't interpret them. If it contains a literal ' escape it as \'.
define('MAIL_PASS',      'PUT-THE-MAILBOX-PASSWORD-HERE');

// MUST be a real mailbox on this domain or DKIM/SPF alignment breaks and
// mail lands in spam. Reuse the SMTP mailbox unless you create another.
define('MAIL_FROM',      'dream@jamen.dk');
define('MAIL_FROM_NAME', 'DreamBoard');

// Host used to build links inside emails. Pin it so a link never points at
// a staging host — email is read long after the request that generated it.
define('MAIL_SITE_HOST', 'jamen.dk');
