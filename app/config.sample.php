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
 *
 * MAIL_FROM is the setting that decides inbox vs spam. It must be a real
 * mailbox on this domain: it becomes both the From: header AND the envelope
 * sender, so SPF is checked against jamen.dk (which authorises DreamHost's
 * IPs) and aligns with From:. That alignment is what DMARC wants.
 *
 * Driver options:
 *   'mail' — PHP mail(). Zero credentials. With MAIL_FROM set it passes
 *            DMARC on SPF alignment alone. Start here.
 *   'smtp' — authenticated send. Adds a DKIM signature on top of SPF, which
 *            is stronger, but needs the mailbox password to be correct.
 *   'log'  — writes to mail_log without sending. Useful for testing.
 */
define('MAIL_DRIVER',    'mail');

// MUST be a real mailbox on this domain. This is the important line.
define('MAIL_FROM',      'dream@jamen.dk');
define('MAIL_FROM_NAME', 'DreamBoard');

/* ── SMTP (only needed when MAIL_DRIVER is 'smtp') ──────────────────────── */
// define('MAIL_HOST', 'smtp.dreamhost.com');
// define('MAIL_PORT', 465);                  // 465 = implicit SSL; 587 = STARTTLS
// define('MAIL_USER', 'dream@jamen.dk');     // full address is the username
//
// Single quotes: the password may contain " # $ etc., and single-quoted PHP
// strings don't interpret them. If it contains a literal ' escape it as \'.
// Check the character count on /admin/mail matches the password you typed —
// a short count means the quoting ate part of it.
// define('MAIL_PASS', 'PUT-THE-MAILBOX-PASSWORD-HERE');

// Host used to build links inside emails. Pin it so a link never points at
// a staging host — email is read long after the request that generated it.
define('MAIL_SITE_HOST', 'jamen.dk');

/* ── Landing page ────────────────────────────────────────────────────────
 * Token of a published Trip to show strangers as a live example. The
 * "See a real Trip page" button on the landing page stays hidden until this
 * is set — a half-filled example is worse than none.
 */
// define('DEMO_TRIP_TOKEN', 'your-published-trip-token');

/* ── Site identity ───────────────────────────────────────────────────────
 * Used by the footer, the info pages and the email templates. Defined here
 * so the coming domain/brand change is one edit, not a hunt through
 * templates. Every one of these has a working default, so the site runs
 * without them — set them when the new domain lands.
 */
// define('SITE_NAME',      'DreamBoard');
// define('SITE_EMAIL',     'hello@jamen.dk');
// define('SITE_INSTAGRAM', 'https://www.instagram.com/dreamboardapp/');
//
// Named on the Terms and Privacy pages as the party responsible for the
// service. GDPR expects the controller to be identifiable — put a legal
// entity here if you register one.
// define('SITE_LEGAL_ENTITY', 'Niels, Denmark');
