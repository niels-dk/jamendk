<?php
// views/admin_pricing.php — shadow revenue (fragment; layout wraps it).
// Pricing class is loaded by admin_controller::pricing().
$a_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$rows = $stats['rows'] ?? [];
$dist = $stats['distribution'] ?? [];
$tierMeta = [];
foreach (Pricing::TIERS as [$k, $label, $min, $max, $mc]) {
    $tierMeta[$k] = ['label'=>$label, 'monthly'=>$mc];
}
?>
<div style="max-width:1000px;margin:2rem auto;padding:0 1rem;">
  <h1 style="font-size:1.7rem;margin:0 0 .3rem;">Shadow revenue</h1>
  <p style="color:#8593a6;font-size:.9rem;margin:0 0 1.4rem;">
    What the base would bill <em>if</em> we charged today. Everything is free
    while we're getting started — this is the number that says when payments are worth
    building. <a href="/admin/users" style="color:#8fb1d8;">Users →</a>
    &nbsp;·&nbsp; <a href="/admin/mail" style="color:#8fb1d8;">Mail log →</a>
  </p>

  <?php if (!empty($stats['error'])): ?>
    <div style="background:rgba(232,194,103,.12);border:1px solid rgba(232,194,103,.4);
                color:#e8c267;padding:.7rem 1rem;border-radius:8px;font-size:.9rem;">
      Couldn't read accounts — is the database reachable?
    </div>
  <?php else: ?>

  <!-- Headline numbers -->
  <div style="display:grid;gap:.8rem;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
              margin-bottom:1.6rem;">
    <?php
      $cards = [
        ['Shadow MRR',  Pricing::money((int)($stats['total_monthly_cents'] ?? 0)), '#7fc98d', 'per month'],
        ['Shadow ARR',  Pricing::money((int)($stats['total_yearly_cents'] ?? 0)),  '#8fb1d8', 'per year'],
        ['Would pay',   (int)($stats['paying'] ?? 0),                                '#e8c889', 'of ' . (int)($stats['accounts'] ?? 0) . ' accounts'],
      ];
      foreach ($cards as [$label, $val, $col, $sub]):
    ?>
      <div style="background:rgba(255,255,255,.04);border:1px solid #2b3346;
                  border-radius:12px;padding:1rem 1.1rem;">
        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;
                    color:#8593a6;font-weight:700;"><?= $a_e($label) ?></div>
        <div style="font-size:1.7rem;font-weight:800;color:<?= $col ?>;margin:.2rem 0;">
          <?= $a_e($val) ?>
        </div>
        <div style="font-size:.78rem;color:#6c7d92;"><?= $a_e($sub) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Distribution across tiers -->
  <h2 style="font-size:1rem;color:#cfdbe8;margin:0 0 .7rem;">How teams cluster</h2>
  <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1.8rem;">
    <?php foreach ($tierMeta as $k => $m): $n = (int)($dist[$k] ?? 0); ?>
      <div style="flex:1;min-width:120px;background:rgba(255,255,255,.03);
                  border:1px solid #2b3346;border-radius:10px;padding:.7rem .8rem;">
        <div style="font-weight:700;color:#eaf0f7;font-size:.9rem;"><?= $a_e($m['label']) ?></div>
        <div style="font-size:1.4rem;font-weight:800;color:#8fb1d8;"><?= $n ?></div>
        <div style="font-size:.74rem;color:#6c7d92;">
          <?= $m['monthly'] > 0 ? $a_e(Pricing::money($m['monthly'])) . '/mo' : 'free' ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Per-account table -->
  <h2 style="font-size:1rem;color:#cfdbe8;margin:0 0 .7rem;">By account
    <span style="font-weight:400;color:#6c7d92;font-size:.85rem;">— biggest would-be bills first</span>
  </h2>
  <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:.88rem;">
      <thead>
        <tr style="text-align:left;color:#8593a6;font-size:.75rem;
                   text-transform:uppercase;letter-spacing:.05em;">
          <th style="padding:.5rem .5rem;">Account</th>
          <th style="padding:.5rem .5rem;">People</th>
          <th style="padding:.5rem .5rem;">Tier</th>
          <th style="padding:.5rem .5rem;">Would bill</th>
          <th style="padding:.5rem .5rem;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr style="border-top:1px solid #2b3346;">
            <td style="padding:.5rem .5rem;">
              <div style="color:#eaeaea;font-weight:600;"><?= $a_e($r['name']) ?></div>
              <div style="color:#6c7d92;font-size:.8rem;"><?= $a_e($r['email']) ?></div>
            </td>
            <td style="padding:.5rem .5rem;color:#cfdbe8;font-family:monospace;">
              <?= (int)$r['seats'] ?>
            </td>
            <td style="padding:.5rem .5rem;color:#a8c8ee;"><?= $a_e($r['tier']['label']) ?></td>
            <td style="padding:.5rem .5rem;font-family:monospace;
                       color:<?= $r['tier']['is_paid'] ? '#7fc98d' : '#6c7d92' ?>;">
              <?= $r['tier']['is_paid'] ? $a_e(Pricing::money($r['tier']['monthly_cents'])) . '/mo' : '—' ?>
            </td>
            <td style="padding:.5rem .5rem;">
              <?php if (!empty($r['near_boundary'])): ?>
                <span title="One teammate away from a paid band — a future customer"
                      style="background:rgba(232,176,74,.16);color:#e8c889;
                             padding:.1rem .5rem;border-radius:999px;font-size:.72rem;font-weight:700;">
                  near paid
                </span>
              <?php endif; ?>
              <?php if (!empty($r['is_founder'])): ?>
                <span title="Founding Creator — free forever at this size"
                      style="color:#e8c889;font-size:.8rem;">✨</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php endif; ?>
</div>
