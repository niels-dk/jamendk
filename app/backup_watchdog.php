<?php
/**
 * Web-triggered backup fallback.
 *
 * DreamHost's cron has twice run the backup successfully and then stopped
 * firing for days — no error, no mail, nothing to fix, because the command was
 * simply never invoked. The script is healthy (a hand-run works every time)
 * and nothing local blocks it, so the unreliable part is the scheduler itself,
 * which is not ours to repair.
 *
 * This makes the site its own scheduler. Every request cheaply checks how old
 * the last SUCCESSFUL backup is; if it is past the threshold, the backup is
 * launched detached and the request carries on. Traffic is the heartbeat —
 * and this site gets plenty, scanners included.
 *
 * Cron stays primary. This only closes the gap when cron goes quiet, turning a
 * four-day hole into a few hours.
 *
 * Deliberate properties:
 *   - never delays a response: called from a shutdown function, and the
 *     command is backgrounded with nohup … &
 *   - never throws: every filesystem call is guarded, and a missing backups
 *     directory or a disabled exec() just means it does nothing
 *   - never stampedes: an attempt marker is written BEFORE launching, so
 *     concurrent requests cannot fire several at once, and a failing backup is
 *     retried at most hourly rather than on every page load
 *   - the script's own lock is the final guard against overlap
 */

/** Hours without a successful backup before the web tries to run one. */
const BACKUP_STALE_HOURS = 30;   // nightly cron + slack, same as /admin/backups

/** Minimum gap between web-triggered attempts, successful or not. */
const BACKUP_RETRY_SECONDS = 3600;

function backup_watchdog_run(): void
{
    // ~/backups sits one level above the site directory, outside the web root.
    $base = dirname(__DIR__, 2) . '/backups';
    if (!is_dir($base)) return;                 // not set up on this host

    $okFile  = $base . '/last_success.txt';
    $tryFile = $base . '/last_attempt';

    $lastOk = 0;
    if (is_file($okFile)) {
        $lastOk = (int)strtotime(trim((string)@file_get_contents($okFile)));
    }
    if ($lastOk > 0 && (time() - $lastOk) < BACKUP_STALE_HOURS * 3600) {
        return;                                  // recent enough, nothing to do
    }

    // Claim the attempt before doing anything slow, so two simultaneous
    // requests cannot both decide to launch.
    $lastTry = is_file($tryFile) ? (int)@file_get_contents($tryFile) : 0;
    if ((time() - $lastTry) < BACKUP_RETRY_SECONDS) return;
    if (@file_put_contents($tryFile, (string)time(), LOCK_EX) === false) return;

    // Shared hosting often disables exec(). If it is unavailable there is
    // nothing to do here — /admin/backups still shows the staleness.
    if (!function_exists('exec')) return;
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if (in_array('exec', $disabled, true)) return;

    $script = dirname(__DIR__) . '/scripts/backup.sh';
    if (!is_file($script)) return;

    // Detached: nohup plus & means the request does not wait for mysqldump.
    @exec('nohup ' . escapeshellarg($script) . ' > /dev/null 2>&1 &');
}

/**
 * Register the check to run after the response has been sent, so it can never
 * add latency to a page. Cheap on the overwhelming majority of requests: one
 * is_dir plus one filemtime-style read, then an early return.
 */
function backup_watchdog(): void
{
    register_shutdown_function(static function () {
        try { backup_watchdog_run(); } catch (\Throwable $e) { /* never surface */ }
    });
}
