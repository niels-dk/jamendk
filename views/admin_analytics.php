<?php
// views/admin_analytics.php — traffic + product funnel (fragment; layout wraps).
// Expects: $traffic, $product, $days
$a_e  = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$pct  = fn(int $n, int $of) => $of > 0 ? round($n / $of * 100) : 0;

// Inline sparkline: tiny SVG, no libraries, works offline.
$spark = function (array $rows, string $key, string $colour = '#8fb1d8') {
    $vals = array_map(fn($r) => (int)$r[$key], $rows);
    if (count($vals) < 2) return '';
    $max = max($vals) ?: 1;
    $w = 260; $h = 34; $n = count($vals);
    $pts = [];
    foreach ($vals as $i => $v) {
        $x = round($i * ($w / max(1, $n - 1)), 1);
        $y = round($h - ($v / $max) * ($h - 4) - 2, 1);
        $pts[] = "$x,$y";
    }
    return '<svg viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none" '
         . 'style="width:100%;height:34px;display:block;">'
         . '<polyline fill="none" stroke="' . $colour . '" stroke-width="2" '
         . 'stroke-linejoin="round" points="' . implode(' ', $pts) . '"/></svg>';
};

$card = function (string $label, $value, string $sub = '', string $colour = '#eaf0f7') use ($a_e) {
    echo '<div style="background:rgba(255,255,255,.04);border:1px solid #2b3346;'
       . 'border-radius:12px;padding:.9rem 1rem;">'
       . '<div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;'
       . 'color:#8593a6;font-weight:700;">' . $a_e($label) . '</div>'
       . '<div style="font-size:1.6rem;font-weight:800;color:' . $colour . ';margin:.15rem 0;">'
       . $a_e((string)$value) . '</div>'
       . ($sub !== '' ? '<div style="font-size:.76rem;color:#6c7d92;">' . $a_e($sub) . '</div>' : '')
       . '</div>';
};

$table = function (string $title, array $rows, string $labelKey, string $valueKey,
                   string $empty, string $valueLabel = 'visitors') use ($a_e) {
    echo '<h2 style="font-size:1rem;color:#cfdbe8;margin:1.6rem 0 .5rem;">' . $a_e($title) . '</h2>';
    if (!$rows) { echo '<p style="color:#6c7d92;font-size:.88rem;margin:0;">' . $a_e($empty) . '</p>'; return; }
    $max = max(array_map(fn($r) => (int)$r[$valueKey], $rows)) ?: 1;
    echo '<table style="width:100%;border-collapse:collapse;font-size:.88rem;">';
    foreach ($rows as $r) {
        $v = (int)$r[$valueKey];
        $w = round($v / $max * 100);
        echo '<tr style="border-top:1px solid #2b3346;">'
           . '<td style="padding:.4rem .4rem;position:relative;">'
           . '<div style="position:absolute;left:0;top:2px;bottom:2px;width:' . $w . '%;'
           . 'background:rgba(58,118,210,.13);border-radius:4px;"></div>'
           . '<span style="position:relative;color:#eaeaea;">'
           . $a_e((string)($r[$labelKey] ?? '—')) . '</span></td>'
           . '<td style="padding:.4rem .4rem;text-align:right;color:#8fb1d8;'
           . 'font-family:monospace;white-space:nowrap;">' . $v . '</td></tr>';
    }
    echo '</table>';
};
?>
<div style="max-width:1000px;margin:2rem auto;padding:0 1rem;">
  <h1 style="font-size:1.7rem;margin:0 0 .3rem;"><?= te('adm.analytics') ?></h1>
  <p style="color:#8593a6;font-size:.9rem;margin:0 0 1rem;">
    <?= te('adm.analytics_sub') ?>
    <a href="/admin/users" style="color:#8fb1d8;"><?= te('adm.users') ?> →</a> ·
    <a href="/admin/pricing" style="color:#8fb1d8;"><?= te('adm.revenue') ?> →</a> ·
    <a href="/admin/mail" style="color:#8fb1d8;"><?= te('adm.mail') ?> →</a> ·
    <a href="/admin/links" style="color:#8fb1d8;"><?= te('adm.links') ?> →</a> ·
    <a href="/admin/backups" style="color:#8fb1d8;"><?= te('adm.backups') ?> →</a>
  </p>

  <div style="margin-bottom:1.4rem;font-size:.85rem;">
    <span style="color:#6c7d92;"><?= te('adm.range') ?>:</span>
    <?php foreach ([7, 30, 90, 365] as $d): ?>
      <a href="/admin/analytics?days=<?= $d ?>"
         style="display:inline-block;margin-left:.3rem;padding:.2rem .6rem;border-radius:999px;
                text-decoration:none;<?= $d === $days
                  ? 'background:#3a76d2;color:#fff;font-weight:700;'
                  : 'background:rgba(255,255,255,.05);color:#9bb0c5;' ?>">
        <?= $d === 365 ? te('adm.one_year') : te('adm.n_days', ['n' => $d]) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- ─────────── Are people arriving? ─────────── -->
  <h2 style="font-size:1.05rem;color:#eaf0f7;margin:0 0 .6rem;"><?= te('adm.traffic') ?></h2>
  <?php if (empty($traffic['has_data'])): ?>
    <div style="background:rgba(232,194,103,.12);border:1px solid rgba(232,194,103,.4);
                color:#e8c267;padding:.8rem 1rem;border-radius:8px;font-size:.9rem;">
      <?= te('adm.no_visits') ?>
      <code>db/migrations/2026-07-18_analytics.sql</code> — <?= te('adm.no_visits_2') ?>
    </div>
  <?php else: ?>
    <div style="display:grid;gap:.7rem;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
      <?php
        $lastN = t('adm.last_n_days', ['n' => $days]);
        $card(t('adm.visitors'), number_format($traffic['visitors']), $lastN, '#7fc98d');
        $card(t('adm.page_views'), number_format($traffic['views']), $lastN);
        $card(t('time.today'), number_format($traffic['today_visitors']),
              t('adm.n_views', ['n' => $traffic['today_views']]), '#e8c889');
        $devTop = $traffic['devices'][0] ?? null;
        $card(t('adm.top_device'), $devTop ? ucfirst($devTop['device']) : '—',
              $devTop ? t('adm.n_visitors', ['n' => $devTop['visitors']]) : '');
      ?>
    </div>
    <?php if (!empty($traffic['daily'])): ?>
      <div style="background:rgba(255,255,255,.03);border:1px solid #2b3346;border-radius:12px;
                  padding:.8rem 1rem;margin-top:.7rem;">
        <div style="font-size:.72rem;color:#8593a6;font-weight:700;text-transform:uppercase;
                    letter-spacing:.06em;margin-bottom:.3rem;"><?= te('adm.visitors_per_day') ?></div>
        <?= $spark($traffic['daily'], 'visitors', '#7fc98d') ?>
      </div>
    <?php endif; ?>

    <div style="display:grid;gap:1.2rem;grid-template-columns:1fr;margin-top:.4rem;">
      <div style="display:grid;gap:1.2rem;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
        <div><?php $table(t('adm.referrers'), $traffic['referrers'], 'ref_host', 'visitors',
              t('adm.no_referrers')); ?></div>
        <div><?php $table(t('adm.top_pages'), $traffic['pages'], 'path', 'views',
              t('adm.no_pages'), 'views'); ?></div>
      </div>
      <?php if (!empty($traffic['campaigns'])): ?>
        <div>
          <h2 style="font-size:1rem;color:#cfdbe8;margin:0 0 .5rem;"><?= te('adm.campaigns') ?></h2>
          <table style="width:100%;border-collapse:collapse;font-size:.88rem;">
            <?php foreach ($traffic['campaigns'] as $c): ?>
              <tr style="border-top:1px solid #2b3346;">
                <td style="padding:.4rem;color:#eaeaea;"><?= $a_e($c['src']) ?></td>
                <td style="padding:.4rem;color:#9bb0c5;"><?= $a_e($c['camp']) ?></td>
                <td style="padding:.4rem;text-align:right;color:#8fb1d8;font-family:monospace;">
                  <?= (int)$c['visitors'] ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- ─────────── Are they actually using it? ─────────── -->
  <h2 style="font-size:1.05rem;color:#eaf0f7;margin:2.4rem 0 .3rem;"><?= te('adm.funnel') ?></h2>
  <p style="color:#6c7d92;font-size:.84rem;margin:0 0 .7rem;"><?= te('adm.funnel_sub') ?></p>
  <?php
    $steps = [
      [t('adm.step_signed_up'), $product['users'],     '#8fb1d8'],
      [t('adm.step_confirmed'), $product['verified'],  '#a8c8ee'],
      [t('adm.step_made'),      $product['activated'], '#e8c889'],
      [t('adm.f_published'),    $product['published'], '#7fc98d'],
    ];
    $top = max(1, (int)$product['users']);
  ?>
  <div style="display:flex;flex-direction:column;gap:.35rem;">
    <?php foreach ($steps as [$label, $n, $col]): $w = max(2, $pct((int)$n, $top)); ?>
      <div style="display:flex;align-items:center;gap:.7rem;">
        <span style="width:150px;flex-shrink:0;color:#9bb0c5;font-size:.88rem;"><?= $a_e($label) ?></span>
        <div style="flex:1;background:rgba(255,255,255,.04);border-radius:6px;height:26px;position:relative;">
          <div style="width:<?= $w ?>%;height:100%;background:<?= $col ?>;opacity:.35;border-radius:6px;"></div>
          <span style="position:absolute;left:.6rem;top:0;line-height:26px;font-size:.85rem;
                       color:#eaf0f7;font-weight:700;"><?= (int)$n ?></span>
        </div>
        <span style="width:46px;text-align:right;color:#6c7d92;font-size:.8rem;">
          <?= $pct((int)$n, $top) ?>%</span>
      </div>
    <?php endforeach; ?>
  </div>

  <div style="display:grid;gap:.7rem;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-top:1rem;">
    <?php
      $lastN2 = t('adm.last_n_days', ['n' => $days]);
      $card(t('adm.new_signups'), $product['new_users'], $lastN2, '#7fc98d');
      $card(t('adm.signed_in'), $product['active_30'], $lastN2);
      $card(t('adm.shots_captured'), number_format($product['shots_captured']),
            t('adm.of_n_planned', ['n' => number_format($product['shots_planned'])]), '#e8c889');
      $verifyRate = $pct((int)$product['verified'], max(1, (int)$product['users']));
      $card(t('adm.confirm_rate'), $verifyRate . '%',
            $verifyRate < 70 ? t('adm.rate_low') : t('adm.rate_ok'),
            $verifyRate < 70 ? '#f0a0a0' : '#7fc98d');
    ?>
  </div>

  <h2 style="font-size:1rem;color:#cfdbe8;margin:1.8rem 0 .5rem;"><?= te('adm.features_used') ?>
    <span style="font-weight:400;color:#6c7d92;font-size:.85rem;">— <?= te('adm.features_used_sub') ?></span>
  </h2>
  <?php
    $rows = [];
    foreach ($product['features'] as $label => $n) $rows[] = ['f' => $label, 'n' => $n];
    usort($rows, fn($a, $b) => $b['n'] <=> $a['n']);
    $table('', $rows, 'f', 'n', t('adm.nothing_yet'), 'accounts');
  ?>

  <p style="color:#6c7d92;font-size:.8rem;margin-top:2rem;line-height:1.6;">
    <?= te('adm.privacy_note') ?>
  </p>
</div>
