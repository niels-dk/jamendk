<?php
// views/admin_links.php — create and measure tracked links (fragment).
// Expects: $links, $byDim, $days, $flash. LinkTokens is already loaded.
$l_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = $scheme . '://' . (defined('SITE_NAME') && defined('MAIL_SITE_HOST')
          ? MAIL_SITE_HOST : ($_SERVER['HTTP_HOST'] ?? 'merelyadream.com'));
$inp = 'background:#15161A;border:1px solid #2b3346;color:#ddd;padding:.45rem .6rem;'
     . 'border-radius:7px;font-size:.88rem;width:100%;box-sizing:border-box;';
?>
<div style="max-width:1050px;margin:2rem auto;padding:0 1rem;">
  <h1 style="font-size:1.7rem;margin:0 0 .3rem;">Tracked links</h1>
  <p style="color:#8593a6;font-size:.9rem;margin:0 0 1rem;">
    One short link per effort. Everything about it lives here, so you can
    re-point or re-label a link that's already printed on a flyer.
    <a href="/admin/analytics" style="color:#8fb1d8;">Analytics →</a> ·
    <a href="/admin/users" style="color:#8fb1d8;">Users →</a>
  </p>

  <?php if ($flash): ?>
    <div style="background:rgba(58,118,210,.15);border:1px solid rgba(58,118,210,.4);
                color:#a8c8ee;padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem;
                font-size:.9rem;"><?= $l_e($flash) ?></div>
  <?php endif; ?>

  <!-- ── Create ─────────────────────────────────────────────── -->
  <form method="post" action="/admin/links/create"
        style="background:rgba(255,255,255,.04);border:1px solid #2b3346;border-radius:12px;
               padding:1rem 1.1rem;margin-bottom:1.6rem;">
    <input type="hidden" name="csrf_token" value="<?= $l_e(csrf_token()) ?>">
    <div style="font-weight:700;color:#eaf0f7;margin-bottom:.7rem;">New link</div>

    <div style="display:grid;gap:.6rem;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));">
      <label style="font-size:.78rem;color:#8593a6;">Name (for you)
        <input name="label" required placeholder="Instagram bio — July" style="<?= $inp ?>">
      </label>
      <label style="font-size:.78rem;color:#8593a6;">Token <span style="opacity:.6;">(optional)</span>
        <input name="token" placeholder="auto from name" style="<?= $inp ?>">
      </label>
      <label style="font-size:.78rem;color:#8593a6;">Lands on
        <input name="target" value="/" placeholder="/ or /pricing" style="<?= $inp ?>">
      </label>
      <?php foreach (LinkTokens::DIMENSIONS as $key => [$label, $ph]): ?>
        <label style="font-size:.78rem;color:#8593a6;"><?= $l_e($label) ?>
          <input name="<?= $l_e($key) ?>" placeholder="<?= $l_e($ph) ?>" style="<?= $inp ?>">
        </label>
      <?php endforeach; ?>
    </div>
    <button type="submit" style="margin-top:.8rem;padding:.5rem 1.1rem;border:0;border-radius:8px;
            background:#3a76d2;color:#fff;font-weight:600;cursor:pointer;font-size:.9rem;">
      Create link
    </button>
  </form>

  <!-- ── Range ──────────────────────────────────────────────── -->
  <div style="margin-bottom:.8rem;font-size:.85rem;">
    <span style="color:#6c7d92;">Range:</span>
    <?php foreach ([7, 30, 90, 3650] as $d): ?>
      <a href="/admin/links?days=<?= $d ?>"
         style="display:inline-block;margin-left:.3rem;padding:.2rem .6rem;border-radius:999px;
                text-decoration:none;<?= $d === $days
                  ? 'background:#3a76d2;color:#fff;font-weight:700;'
                  : 'background:rgba(255,255,255,.05);color:#9bb0c5;' ?>">
        <?= $d === 3650 ? 'All time' : $d . ' days' ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- ── Links + performance ────────────────────────────────── -->
  <?php if (!$links): ?>
    <p style="color:#6c7d92;font-size:.9rem;">
      No links yet. Create one above, then use its URL everywhere that effort appears.
      <br>Run <code>db/migrations/2026-07-19_link_tokens.sql</code> if you haven't.
    </p>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:.86rem;min-width:760px;">
        <thead>
          <tr style="text-align:left;color:#8593a6;font-size:.72rem;text-transform:uppercase;
                     letter-spacing:.05em;">
            <th style="padding:.5rem .4rem;">Link</th>
            <th style="padding:.5rem .4rem;">Dimensions</th>
            <th style="padding:.5rem .4rem;text-align:right;">People</th>
            <th style="padding:.5rem .4rem;text-align:right;">Views</th>
            <th style="padding:.5rem .4rem;text-align:right;">Signups</th>
            <th style="padding:.5rem .4rem;"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($links as $L): $url = $base . '/l/' . $L['token']; ?>
          <tr style="border-top:1px solid #2b3346;<?= empty($L['active']) ? 'opacity:.45;' : '' ?>">
            <td style="padding:.55rem .4rem;">
              <div style="color:#eaeaea;font-weight:600;"><?= $l_e($L['label']) ?></div>
              <div style="font-family:monospace;font-size:.78rem;color:#8fb1d8;word-break:break-all;">
                <?= $l_e($url) ?>
              </div>
              <button type="button" class="copy-link" data-url="<?= $l_e($url) ?>"
                      style="margin-top:.2rem;background:transparent;border:1px solid #2b3346;
                             color:#9bb0c5;border-radius:6px;padding:.1rem .5rem;font-size:.72rem;
                             cursor:pointer;">Copy</button>
              <?php if (!empty($L['target']) && $L['target'] !== '/'): ?>
                <span style="font-size:.72rem;color:#6c7d92;">→ <?= $l_e($L['target']) ?></span>
              <?php endif; ?>
            </td>
            <td style="padding:.55rem .4rem;">
              <?php foreach (LinkTokens::DIMENSIONS as $key => [$dl, $ph]):
                      if (empty($L[$key])) continue; ?>
                <span style="display:inline-block;margin:.1rem .2rem .1rem 0;padding:.05rem .45rem;
                             border-radius:999px;background:rgba(58,118,210,.15);color:#a8c8ee;
                             font-size:.72rem;" title="<?= $l_e($dl) ?>">
                  <?= $l_e($L[$key]) ?></span>
              <?php endforeach; ?>
            </td>
            <td style="padding:.55rem .4rem;text-align:right;color:#7fc98d;font-family:monospace;">
              <?= (int)$L['visitors'] ?></td>
            <td style="padding:.55rem .4rem;text-align:right;color:#8593a6;font-family:monospace;">
              <?= (int)$L['views'] ?></td>
            <td style="padding:.55rem .4rem;text-align:right;font-family:monospace;
                       color:<?= (int)$L['signups'] > 0 ? '#e8c889' : '#5a6878' ?>;font-weight:700;">
              <?= (int)$L['signups'] ?></td>
            <td style="padding:.55rem .4rem;white-space:nowrap;">
              <form method="post" action="/admin/links/<?= (int)$L['id'] ?>/toggle" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= $l_e(csrf_token()) ?>">
                <?php if (empty($L['active'])): ?><input type="hidden" name="on" value="1"><?php endif; ?>
                <button type="submit" style="background:transparent;border:1px solid #2b3346;
                        color:#9bb0c5;border-radius:6px;padding:.15rem .5rem;font-size:.75rem;
                        cursor:pointer;"><?= empty($L['active']) ? 'Enable' : 'Pause' ?></button>
              </form>
              <form method="post" action="/admin/links/<?= (int)$L['id'] ?>/delete" style="display:inline;"
                    onsubmit="return confirm('Delete this link? Past visits are kept but lose their label.');">
                <input type="hidden" name="csrf_token" value="<?= $l_e(csrf_token()) ?>">
                <button type="submit" style="background:transparent;border:0;color:#f08792;
                        cursor:pointer;font-size:.75rem;">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <!-- ── Roll-ups by dimension ──────────────────────────────── -->
  <?php if ($byDim): ?>
    <h2 style="font-size:1.05rem;color:#eaf0f7;margin:2rem 0 .2rem;">Which dimensions perform</h2>
    <p style="color:#6c7d92;font-size:.82rem;margin:0 0 .8rem;">
      Same visits, grouped different ways — answers "is social beating email?"
      or "which partner actually sends people who sign up?"
    </p>
    <div style="display:grid;gap:1.2rem;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));">
      <?php foreach ($byDim as $dim => $rows): ?>
        <div>
          <h3 style="font-size:.82rem;color:#cfdbe8;margin:0 0 .3rem;text-transform:uppercase;
                     letter-spacing:.05em;"><?= $l_e(LinkTokens::DIMENSIONS[$dim][0]) ?></h3>
          <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
            <?php foreach ($rows as $r): ?>
              <tr style="border-top:1px solid #2b3346;">
                <td style="padding:.35rem .3rem;color:#eaeaea;"><?= $l_e($r['k']) ?></td>
                <td style="padding:.35rem .3rem;text-align:right;color:#7fc98d;font-family:monospace;">
                  <?= (int)$r['visitors'] ?></td>
                <td style="padding:.35rem .3rem;text-align:right;color:#e8c889;font-family:monospace;"
                    title="signups"><?= (int)$r['signups'] ?>★</td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p style="color:#6c7d92;font-size:.8rem;margin-top:2rem;line-height:1.6;">
    <strong style="color:#9bb0c5;">People</strong> = distinct visitors in the range ·
    <strong style="color:#9bb0c5;">Views</strong> = pages they looked at ·
    <strong style="color:#9bb0c5;">Signups ★</strong> = accounts actually created after arriving
    through that link. Attribution lasts for the visit, so the whole journey is
    credited — not just the page they landed on.
  </p>
</div>

<script>
document.addEventListener('click', function (e) {
  var b = e.target.closest('.copy-link');
  if (!b) return;
  var t = b.textContent;
  navigator.clipboard.writeText(b.dataset.url).then(function () {
    b.textContent = '✓ Copied';
    setTimeout(function () { b.textContent = t; }, 1200);
  }).catch(function () {});
});
</script>
