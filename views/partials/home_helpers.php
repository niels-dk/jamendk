<?php
// Small formatting helpers shared by the landing page (views/home.php) and
// the dashboard (views/dashboard_overview.php). Guarded so including this
// twice in one request can never be a fatal 'cannot redeclare'.
if (!function_exists('h_e')):

function h_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function h_dt($s) { return $s ? fmt_date($s) : ''; }

// Relative time: "just now", "3 days ago", "in 2 days", "yesterday", etc.
function h_rel($s) {
    if (!$s) return '';
    $t = strtotime($s);
    if (!$t) return '';
    $diff = $t - time();              // future = positive
    $past = $diff < 0;
    $a = abs($diff);
    if ($a < 60)        return t($past ? 'time.just_now' : 'time.in_a_moment');
    if ($a < 3600)      { $n = round($a/60);   return t($past ? 'time.min_ago' : 'time.in_min', ['n' => $n]); }
    if ($a < 86400)     { $n = round($a/3600); return t($past ? 'time.hr_ago'  : 'time.in_hr',  ['n' => $n]); }
    $days = round($a/86400);
    if ($days === 1)    return t($past ? 'time.yesterday' : 'time.tomorrow');
    if ($days < 30)     return t($past ? 'time.days_ago' : 'time.in_days', ['n' => $days]);
    return fmt_date($t);
}
// "3 days ago" / "in 3 days" — split out because four call sites built the
// same phrase inline, and English pluralisation rules do not survive translation.
function h_ago($days)   { $n = abs((int)$days); return t($n === 1 ? 'time.day_ago' : 'time.days_ago', ['n' => $n]); }
function h_ahead($days) {
    $n = (int)$days;
    if ($n === 0) return t('time.today');
    if ($n === 1) return t('time.tomorrow');
    return t('time.in_days', ['n' => $n]);
}

// Days until a date (negative = overdue). Date-only granularity.
function h_days_until($s) {
    if (!$s) return null;
    $t = strtotime($s);
    if (!$t) return null;
    $today = strtotime('today');
    return (int) floor(($t - $today) / 86400);
}

endif;
