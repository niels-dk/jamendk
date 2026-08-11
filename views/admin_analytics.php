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
  <h1 style="font-size:1.7rem;margin:0 0 .3rem;">Analytics</h1>
  <p style="color:#8593a6;font-size:.9rem;margin:0 0 1rem;">
    First-party and cookie-free — no Google, nothing leaves this server.
    <a href="/admin/users" style="color:#8fb1d8;">Users →</a> ·
    <a href="/admin/pricing" style="color:#8fb1d8;">Revenue →</a> ·
    <a href="/admin/mail" style="color:#8fb1d8;">Mail →</a> ·
    <a href="/admin/links" style="color:#8fb1d8;">Links →</a> ·
    <a href="/admin/backups" style="color:#8fb1d8;">Backups →</a>
  </p>

  <div style="margin-bottom:1.4rem;font-size:.85rem;">
    <span style="color:#6c7d92;">Range:</span>
    <?php foreach ([7, 30, 90, 365] as $d): ?>
      <a href="/admin/analytics?days=<?= $d ?>"
         style="display:inline-block;margin-left:.3rem;padding:.2rem .6rem;border-radius:999px;
                text-decoration:none;<?= $d === $days
                  ? 'background:#3a76d2;color:#fff;font-weight:700;'
                  : 'background:rgba(255,255,255,.05);color:#9bb0c5;' ?>">
        <?= $d === 365 ? '1 year' : $d . ' days' ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- ─────────── Are people arriving? ─────────── -->
  <h2 style="font-size:1.05rem;color:#eaf0f7;margin:0 0 .6rem;">Traffic</h2>
  <?php if (empty($traffic['has_data'])): ?>
    <div style="background:rgba(232,194,103,.12);border:1px solid rgba(232,194,103,.4);
                color:#e8c267;padding:.8rem 1rem;border-radius:8px;font-size:.9rem;">
      No visits recorded yet. If you've just deployed, run
      <code>db/migrations/2026-07-18_analytics.sql</code> — and remember your own
      admin visits are deliberately excluded, so test in a private window.
    </div>
  <?php else: ?>
    <div style="display:grid;gap:.7rem;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
      <?php
        $card('Visitors', number_format($traffic['visitors']), "last $days days", '#7fc98d');
        $card('Page views', number_format($traffic['views']), "last $days days");
        $card('Today', number_format($traffic['today_visitors']),
              $traffic['today_views'] . ' views', '#e8c889');
        $devTop = $traffic['devices'][0] ?? null;
        $card('Top device', $devTop ? ucfirst($devTop['device']) : '—',
              $devTop ? $devTop['visitors'] . ' visitors' : '');
      ?>
    </div>
    <?php if (!empty($traffic['daily'])): ?>
      <div style="background:rgba(255,255,255,.03);border:1px solid #2b3346;border-radius:12px;
                  padding:.8rem 1rem;margin-top:.7rem;">
        <div style="font-size:.72rem;color:#8593a6;font-weight:700;text-transform:uppercase;
                    letter-spacing:.06em;margin-bottom:.3rem;">Visitors per day</div>
        <?= $spark($traffic['daily'], 'visitors', '#7fc98d') ?>
      </div>
    <?php endif; ?>

    <div style="display:grid;gap:1.2rem;grid-template-columns:1fr;margin-top:.4rem;">
      <div style="display:grid;gap:1.2rem;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
        <div><?php $table('Where they came from', $traffic['referrers'], 'ref_host', 'visitors',
              'No external referrers yet — traffic so far is direct.'); ?></div>
        <div><?php $table('Most visited pages', $traffic['pages'], 'path', 'views',
              'No pages recorded yet.', 'views'); ?></div>
      </div>
      <?php if (!empty($traffic['campaigns'])): ?>
        <div>
          <h2 style="font-size:1rem;color:#cfdbe8;margin:0 0 .5rem;">Campaigns (UTM)</h2>
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
  <h2 style="font-size:1.05rem;color:#eaf0f7;margin:2.4rem 0 .3rem;">The funnel</h2>
  <p style="color:#6c7d92;font-size:.84rem;margin:0 0 .7rem;">
    All-time, from the real tables — not just since analytics was switched on.
  </p>
  <?php
    $steps = [
      ['Signed up',            $product['users'],     '#8fb1d8'],
      ['Confirmed email',      $product['verified'],  '#a8c8ee'],
      ['Made something',       $product['activated'], '#e8c889'],
      ['Published a Trip',     $product['published'], '#7fc98d'],
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
      $card('New signups', $product['new_users'], "last $days days", '#7fc98d');
      $card('Signed in recently', $product['active_30'], "last $days days");
      $card('Shots captured', number_format($product['shots_captured']),
            'of ' . number_format($product['shots_planned']) . ' planned', '#e8c889');
      $verifyRate = $pct((int)$product['verified'], max(1, (int)$product['users']));
      $card('Email confirm rate', $verifyRate . '%',
            $verifyRate < 70 ? 'low — check spam folder delivery' : 'healthy',
            $verifyRate < 70 ? '#f0a0a0' : '#7fc98d');
    ?>
  </div>

  <h2 style="font-size:1rem;color:#cfdbe8;margin:1.8rem 0 .5rem;">Which features get used
    <span style="font-weight:400;color:#6c7d92;font-size:.85rem;">— accounts that used each at least once</span>
  </h2>
  <?php
    $rows = [];
    foreach ($product['features'] as $label => $n) $rows[] = ['f' => $label, 'n' => $n];
    usort($rows, fn($a, $b) => $b['n'] <=> $a['n']);
    $table('', $rows, 'f', 'n', 'Nothing created yet.', 'accounts');
  ?>

  <p style="color:#6c7d92;font-size:.8rem;margin-top:2rem;line-height:1.6;">
    No cookies are set and no IP address is stored: visitors are counted with a
    one-way hash that includes a secret salt and the date, so the same person
    cannot be recognised on a later day. Your own admin visits are excluded.
    Raw rows are pruned after roughly a year.
  </p>
</div>
