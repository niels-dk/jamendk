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
 * @return array<int,array{id:int,name:string,email:string,sent_count:int}>
 */
function verify_reminders_due(): array
{
    global $db;
    if (!isset($db) || !($db instanceof PDO)) return [];

    $after = (int)REMIND_AFTER_HOURS;   // literals: MySQL wants them after INTERVAL
    $gap   = (int)REMIND_GAP_HOURS;
    $max   = (int)REMIND_MAX;
    $batch = (int)REMIND_BATCH;

    // deactivated_at may not be migrated on every install; the whole query is
    // guarded, and a failure here simply means no reminders go out.
    $sql = "
        SELECT u.id, u.name, u.email,
               (SELECT COUNT(*) FROM mail_log m
                 WHERE m.to_email = u.email
                   AND m.type = 'verify_reminder'
                   AND m.status = 'sent')          AS sent_count,
               (SELECT MAX(m2.created_at) FROM mail_log m2
                 WHERE m2.to_email = u.email
                   AND m2.type = 'verify_reminder'
                   AND m2.status = 'sent')         AS last_sent
          FROM users u
         WHERE u.email_verified_at IS NULL
           AND u.deactivated_at IS NULL
           AND u.email <> ''
           AND u.email LIKE '%@%'
           AND u.created_at < (NOW() - INTERVAL $after HOUR)
        HAVING sent_count < $max
           AND (last_sent IS NULL OR last_sent < (NOW() - INTERVAL $gap HOUR))
         ORDER BY u.created_at ASC
         LIMIT $batch";

    try {
        $st = $db->query($sql);
        return $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * @param bool $send false = report only, nothing leaves the server.
 * @return array<int,array{email:string,name:string,sent_count:int,result:string}>
 */
function verify_reminders_run(bool $send = false): array
{
    require_once __DIR__ . '/mailer.php';
    $out = [];

    foreach (verify_reminders_due() as $u) {
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
