<?php
/**
 * Sample of app/config.php — the real file is gitignored and lives ONLY on
 * the server, because it holds credentials. Never commit the real one.
 *
 * To enable email: copy the MAIL block below into your existing
 * app/config.php on DreamHost (keep your current $db / PDO setup as-is).
 */

/* ── Database (your existing setup — shown for shape only) ────────────── */
// $db = new PDO('mysql:host=...;dbname=YOUR_DB;charset=utf8mb4', 'user', 'pass', [
//     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
// ]);

/* ── Mail ─────────────────────────────────────────────────────────────────
 *
 * MAIL_FROM is the setting that decides inbox vs spam. It must be a real
 * mailbox on this domain: it becomes both the From: header AND the envelope
 * sender, so SPF is checked against merelyadream.com (which must authorise DreamHost's
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
define('MAIL_FROM',      'hello@merelyadream.com');
define('MAIL_FROM_NAME', 'Niels at Merely a Dream');

/* ── SMTP (only needed when MAIL_DRIVER is 'smtp') ──────────────────────── */
// define('MAIL_HOST', 'smtp.dreamhost.com');
// define('MAIL_PORT', 465);                  // 465 = implicit SSL; 587 = STARTTLS
// define('MAIL_USER', 'hello@merelyadream.com');     // full address is the username
//
// Single quotes: the password may contain " # $ etc., and single-quoted PHP
// strings don't interpret them. If it contains a literal ' escape it as \'.
// Check the character count on /admin/mail matches the password you typed —
// a short count means the quoting ate part of it.
// define('MAIL_PASS', 'PUT-THE-MAILBOX-PASSWORD-HERE');

// Host used to build links inside emails. Pin it so a link never points at
// a staging host — email is read long after the request that generated it.
define('MAIL_SITE_HOST', 'merelyadream.com');

/* ── Reminders for unconfirmed accounts ──────────────────────────────────
 *
 * An unconfirmed account cannot sign in, so anyone who misses the first
 * verification email is simply lost. With this on, they get a fresh link:
 * once after 24h, at most twice ever, 72h apart, never to a deactivated
 * account.
 *
 * OFF unless this line exists. That is deliberate — this is unsolicited mail
 * to people who have not confirmed they want anything from us, so deploying
 * the code must never be what starts sending it. Turning it on has to be a
 * decision someone made.
 *
 * Before enabling, see exactly who is due:
 *     php scripts/verify_reminders.php        # dry run, sends nothing
 *
 * Those are real people. Read the list first.
 */
// define('VERIFY_REMINDERS_ENABLED', true);

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
// define('SITE_NAME',      'Merely a Dream');
// define('SITE_EMAIL',     'hello@merelyadream.com');
// define('SITE_INSTAGRAM', 'https://www.instagram.com/merely.a.dream/');
//
// Named on the Terms and Privacy pages as the party responsible for the
// service. GDPR expects the controller to be identifiable — put a legal
// entity here if you register one.
// define('SITE_LEGAL_ENTITY', 'Niels, Denmark');
