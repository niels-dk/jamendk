<?php
/**
 * Who is due a "you never confirmed your email" reminder.
 *
 *   php scripts/verify_reminders.php            # dry run — sends NOTHING
 *   php scripts/verify_reminders.php --send     # actually sends
 *
 * Dry run is the default on purpose: the accounts listed here belong to real
 * people, and the safe thing must be what happens when you forget the flag.
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

$rows = verify_reminders_run($send);

if (!$rows) {
    echo "Nobody is due a reminder.\n";
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
