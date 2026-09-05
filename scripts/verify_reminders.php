<?php
/**
 * Who is due a "you never confirmed your email" reminder.
 *
 *   php scripts/verify_reminders.php            # dry run — sends NOTHING
 *   php scripts/verify_reminders.php --send     # actually sends
 *
 * Dry run is the default on purpose: the accounts listed here belong to real
 * people, and the safe thing must be what happens when you forget the flag.
 *
 * An empty list is reported with the counts behind it, because "nobody is due"
 * has several very different causes — everyone is verified, the column is
 * missing, the flag is off, the query failed — and they should not all look
 * like the same silence.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("This script is command-line only.\n");
}

$root = dirname(__DIR__);
require_once $root . '/app/config.php';
require_once $root . '/app/helpers.php';
require_once $root . '/app/i18n.php';
require_once $root . '/models/User.php';
require_once $root . '/app/verify_reminders.php';

$send = in_array('--send', $argv ?? [], true);

// State first, so the numbers below can be read in context.
$flag = defined('VERIFY_REMINDERS_ENABLED') && VERIFY_REMINDERS_ENABLED;
echo "Automatic sending: " . ($flag ? "ON" : "OFF (VERIFY_REMINDERS_ENABLED not set)") . "\n";
echo "Database:          " . (isset($db) && $db instanceof PDO ? "connected" : "NOT CONNECTED") . "\n\n";

$err  = null;
$rows = verify_reminders_run($send, $err);

if ($err !== null) {
    echo "The query failed, so nothing was sent:\n  $err\n";
    exit(1);
}

if (!$rows) {
    echo "Nobody is due a reminder.\n\n";

    // Walk the conditions in order; the step that drops to zero is the answer.
    $s = verify_reminders_stats();
    $n = fn($v) => $v === null ? '?' : (string)$v;
    echo "Why:\n";
    printf("  %-42s %s\n", 'Accounts with an unconfirmed email',        $n($s['unverified']));
    printf("  %-42s %s\n", '  ...of those, not deactivated',            $n($s['active']));
    printf("  %-42s %s\n", '  ...of those, older than ' . REMIND_AFTER_HOURS . 'h', $n($s['old_enough']));
    printf("  %-42s %s\n", 'Reminders already sent, all time',          $n($s['reminded']));
    echo "\nA '?' means that query failed — most likely a missing column.\n";
    if ((int)$s['old_enough'] > 0) {
        echo "Some accounts qualify but none are listed: they have already had "
           . REMIND_MAX . " reminders, or the last one was under "
           . REMIND_GAP_HOURS . "h ago.\n";
    }
    exit(0);
}

echo $send ? "SENDING:\n" : "DRY RUN — nothing sent. Add --send to actually send.\n";
printf("%-38s %-22s %-9s %s\n", 'EMAIL', 'NAME', 'REMINDERS', 'RESULT');
foreach ($rows as $r) {
    printf("%-38s %-22s %-9s %s\n",
        substr($r['email'], 0, 38),
        substr($r['name'] ?: '—', 0, 22),
        $r['sent_count'] . ' of ' . REMIND_MAX,
        $r['result']);
}
echo "\n" . count($rows) . " account(s).\n";
