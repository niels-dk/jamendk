<?php
/**
 * Reminders for people who signed up but never confirmed their address.
 *
 * An unconfirmed account cannot sign in, so someone who misses the first email
 * — spam folder, wrong moment, link expired after 24h — is simply lost. This
 * sends them a fresh link.
 *
 * DELIBERATELY OFF BY DEFAULT. Sending is gated on VERIFY_REMINDERS_ENABLED in
 * app/config.php, so deploying this cannot mail a real person before that is a
 * decision someone made. Run scripts/verify_reminders.php first to see exactly
 * who is due; it is a dry run unless you pass --send.
 *
 * Restraint, because this is unsolicited mail to someone who has not confirmed
 * they want anything from us:
 *   - nothing before the first link has actually expired (24h)
 *   - at most TWO reminders, ever, then silence
 *   - 72h between them
 *   - never to a deactivated account
 *
 * How many have been sent is counted from mail_log rather than a new column —
 * the row is written there anyway, so it is already the record of what left
 * the building.
 */

/** Hours after signup before the first reminder. The first link lasts 24h. */
const REMIND_AFTER_HOURS = 24;
/** Hours between reminders. */
const REMIND_GAP_HOURS = 72;
/** Total reminders per account, ever. */
const REMIND_MAX = 2;
/** Ceiling per run, so a backlog trickles instead of bursting. */
const REMIND_BATCH = 20;

/**
 * Accounts currently due a reminder.
 *
 * Always returns [] on failure, so the web path can never break over this —
 * but it reports WHY through $err. Without that, "the query failed" and
 * "nobody is due" are the same empty array, which is exactly the wrong thing
 * for a tool whose whole job is to tell you who is about to be emailed.
 *
 * @param string|null $err out: the reason the list is empty, if it went wrong.
 * @return array<int,array{id:int,name:string,email:string,sent_count:int}>
 */
function verify_reminders_due(?string &$err = null): array
{
    global $db;
    $err = null;
    if (!isset($db) || !($db instanceof PDO)) {
        $err = 'No database connection ($db is not a PDO instance).';
        return [];
    }

    $after = (int)REMIND_AFTER_HOURS;   // literals: MySQL wants them after INTERVAL
    $gap   = (int)REMIND_GAP_HOURS;

    try {
        // Two queries rather than one, because users.email and mail_log.to_email
        // do not share a collation — the tables were created on either side of a
        // server upgrade, so users.email is utf8mb4_0900_ai_ci and to_email is
        // utf8mb4_general_ci. Comparing them directly is error 1267, "Illegal mix
        // of collations". A COLLATE clause would force it through, but only by
        // making mail_log's index on to_email unusable. Bound parameters instead
        // take the collation of the column they are compared against, so going
        // out through PHP sidesteps the mismatch and keeps the index.

        // Step 1: candidates, from users alone.
        // deactivated_at may not be migrated on every install; the whole thing is
        // guarded, and a failure here simply means no reminders go out.
        $st = $db->query(
            "SELECT id, name, email
               FROM users
              WHERE email_verified_at IS NULL
                AND deactivated_at IS NULL
                AND email <> ''
                AND email LIKE '%@%'
                AND created_at < (NOW() - INTERVAL $after HOUR)
              ORDER BY created_at ASC
              LIMIT 200"
        );
        // 200, not REMIND_BATCH: the batch limit belongs AFTER the reminder
        // history is applied, or a handful of already-reminded accounts at the
        // front of the queue would hide everyone behind them forever.
        $cand = $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        if (!$cand) return [];

        // Step 2: the whole reminder history for those addresses, in one trip.
        $emails = array_column($cand, 'email');
        $ph     = implode(',', array_fill(0, count($emails), '?'));
        $hs = $db->prepare(
            "SELECT to_email,
                    COUNT(*) AS sent_count,
                    (MAX(created_at) > (NOW() - INTERVAL $gap HOUR)) AS too_recent
               FROM mail_log
              WHERE type = 'verify_reminder'
                AND status = 'sent'
                AND to_email IN ($ph)
              GROUP BY to_email"
        );
        // too_recent is computed by MySQL on purpose: comparing a DB timestamp
        // against PHP's clock would silently drift if the two disagree on zone.
        $hs->execute($emails);

        // Keyed lowercase — MySQL matched these case-insensitively, PHP will not.
        $hist = [];
        foreach ($hs->fetchAll(PDO::FETCH_ASSOC) as $h) {
            $hist[mb_strtolower((string)$h['to_email'])] = $h;
        }

        $out = [];
        foreach ($cand as $u) {
            $h     = $hist[mb_strtolower((string)$u['email'])] ?? null;
            $count = $h ? (int)$h['sent_count'] : 0;

            if ($count >= REMIND_MAX) continue;          // had their two, done
            if ($h && (int)$h['too_recent'] === 1) continue;  // too soon

            $u['sent_count'] = $count;
            $out[] = $u;
            if (count($out) >= REMIND_BATCH) break;
        }
        return $out;
    } catch (\Throwable $e) {
        $err = $e->getMessage();
        return [];
    }
}

/**
 * Counts behind the list, so an empty result can be explained rather than
 * guessed at. Read-only, and every figure degrades to null on error.
 *
 * @return array{unverified:?int,active:?int,old_enough:?int,reminded:?int}
 */
function verify_reminders_stats(): array
{
    global $db;
    $out = ['unverified' => null, 'active' => null, 'old_enough' => null, 'reminded' => null];
    if (!isset($db) || !($db instanceof PDO)) return $out;

    $after = (int)REMIND_AFTER_HOURS;
    $q = [
        // Each line adds ONE condition to the one above it, so the step where
        // the number falls to zero is the reason nobody is due.
        'unverified' => "SELECT COUNT(*) FROM users WHERE email_verified_at IS NULL",
        'active'     => "SELECT COUNT(*) FROM users WHERE email_verified_at IS NULL
                           AND deactivated_at IS NULL",
        'old_enough' => "SELECT COUNT(*) FROM users WHERE email_verified_at IS NULL
                           AND deactivated_at IS NULL
                           AND created_at < (NOW() - INTERVAL $after HOUR)",
        'reminded'   => "SELECT COUNT(*) FROM mail_log
                          WHERE type = 'verify_reminder' AND status = 'sent'",
    ];
    foreach ($q as $k => $sql) {
        try { $out[$k] = (int)$db->query($sql)->fetchColumn(); }
        catch (\Throwable $e) { $out[$k] = null; }
    }
    return $out;
}

/**
 * @param bool $send false = report only, nothing leaves the server.
 * @return array<int,array{email:string,name:string,sent_count:int,result:string}>
 */
function verify_reminders_run(bool $send = false, ?string &$err = null): array
{
    require_once __DIR__ . '/mailer.php';
    $out = [];

    foreach (verify_reminders_due($err) as $u) {
        $row = [
            'email'      => (string)$u['email'],
            'name'       => (string)($u['name'] ?? ''),
            'sent_count' => (int)$u['sent_count'],
            'result'     => 'would send',
        ];

        if ($send) {
            // A fresh token: by now the original has certainly expired, and a
            // reminder carrying a dead link is worse than no reminder.
            $raw = User::issueVerifyToken((int)$u['id']);
            $row['result'] = $raw && Mailer::sendVerifyReminder($row['email'], $row['name'], $raw)
                           ? 'sent' : 'FAILED';
        }
        $out[] = $row;
    }
    return $out;
}

/**
 * Hourly tick from the web, for the same reason the backup has one: cron on
 * this host has twice stopped firing for days without a word.
 *
 * Runs after the response, never blocks it, and does nothing at all unless
 * VERIFY_REMINDERS_ENABLED is true.
 */
function verify_reminders_tick(): void
{
    if (!defined('VERIFY_REMINDERS_ENABLED') || !VERIFY_REMINDERS_ENABLED) return;

    register_shutdown_function(static function () {
        try {
            $marker = dirname(__DIR__, 2) . '/backups/last_reminder_tick';
            $last   = is_file($marker) ? (int)@file_get_contents($marker) : 0;
            if ((time() - $last) < 3600) return;              // at most hourly
            if (@file_put_contents($marker, (string)time(), LOCK_EX) === false) return;
            verify_reminders_run(true);
        } catch (\Throwable $e) { /* never surface on a page */ }
    });
}
