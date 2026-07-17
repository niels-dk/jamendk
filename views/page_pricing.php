<?php
// views/page_pricing.php (fragment; layout wraps it)
// Pricing class is already loaded by page_controller::pricing().
$p_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$in  = function_exists('is_logged_in') && is_logged_in();

// Human band label from min/max people.
$band = function (array $t): string {
    [, , $min, $max] = $t;
    if ($max === null)   return $min . '+ people';
    if ($min === $max)   return $min . ($min === 1 ? ' person' : ' people');
    return $min . '–' . $max . ' people';
};
$blurbs = [
    'solo'       => 'Everything, for one creator. Forever.',
    'crew'       => 'You and a couple of collaborators — a director, an editor, an AC.',
    'studio'     => 'A small production working a real shoot together.',
    'production' => 'A production company running several projects at once.',
    'network'    => 'Studios, agencies and networks running many crews at once.',
];
?>
<div class="doc pricing">
  <div style="text-align:center;">
    <span class="pr-beta">✨ Free while we're just getting started</span>
    <h1>Pay for people, never for features</h1>
    <p class="doc-lead" style="margin:0 auto 2rem;max-width:34rem;">
      One creator gets everything, free forever. You only move up a band when a
      team works <em>with</em> you — and sharing your work with the world always
      costs nothing.
    </p>
  </div>

  <div class="pr-grid">
    <?php foreach (Pricing::TIERS as $t): ?>
      <?php
        [$key, $label, $min, $max, $mCents] = $t;
        $paid = $mCents > 0;
        $feature = $key === 'studio'; // gently highlight the first paid tier
      ?>
      <div class="pr-card <?= $paid ? '' : 'pr-free' ?> <?= $feature ? 'pr-feature' : '' ?>">
        <div class="pr-name"><?= $p_e($label) ?></div>
        <div class="pr-band"><?= $p_e($band($t)) ?></div>
        <div class="pr-price">
          <?php if ($paid): ?>
            <span class="pr-was"><?= $p_e(Pricing::money($mCents)) ?><span class="pr-per">/mo</span></span>
            <span class="pr-now">Free right now</span>
          <?php else: ?>
            <span class="pr-now pr-now-free">Free</span>
            <span class="pr-per">always</span>
          <?php endif; ?>
        </div>
        <p class="pr-blurb"><?= $p_e($blurbs[$key] ?? '') ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="pr-foot">
    Prices are shown so you know where things are headed — but
    <strong>nothing charges today</strong>. Every plan is free while we're
    still getting started. When paid plans arrive, paying yearly will get
    you two months free.
  </p>

  <div class="pr-founder">
    <h2>💛 Here first? You stay free.</h2>
    <p>
      Everyone who signs up <strong>before paid plans launch</strong> is a
      <strong>Founding Creator</strong>. The promise, precisely: whatever size
      your team is <em>on the day paid plans switch on</em>, that stays free for
      you — forever. Grow before that day and the bigger team is what's locked
      in; grow beyond it afterwards and only the extra band is ever billed.
      You believed in it early; that's the thank-you. Your dashboard shows what
      your plan would cost, so you can watch the gift add up.
    </p>
  </div>

  <h2>Questions</h2>

  <h3>What counts as a “person”?</h3>
  <p>
    Someone who <em>works</em> on your boards with you — a co-owner, editor or
    delegate, or a team member with one of those roles. Two kinds of people
    never count: anyone you share a published Trip page with (that link is
    public and unlimited), and <strong>read-only viewers</strong> — someone who
    signs in only to look is audience, not crew. Pay for collaborators, not
    for an audience.
  </p>

  <h3>Are any features locked behind a paid plan?</h3>
  <p>
    No. Every feature is available on every plan, including free. The only thing
    that grows with a paid band is how many people can work alongside you.
  </p>

  <h3>Is it really free right now?</h3>
  <p>
    Yes — completely, on every tier, with no card required. When paid plans do
    arrive, we'll tell you by email well before anything changes, and Founding
    Creators keep their free plan.
  </p>

  <div class="doc-cta">
    <p><?= $in ? 'Go build something.' : 'Start free — no card, no catch.' ?></p>
    <a class="doc-btn" href="<?= $in ? '/dashboard' : '/register' ?>">
      <?= $in ? 'Open your dashboard' : 'Create your free account' ?>
    </a>
  </div>
</div>

<style>
  .pricing .pr-beta {
    display:inline-block; margin:0 0 1rem; padding:.3rem .8rem; border-radius:999px;
    background:rgba(232,176,74,.12); border:1px solid rgba(232,176,74,.3);
    color:#e8c267; font-size:.8rem; font-weight:700; letter-spacing:.04em;
  }
  .pricing .pr-grid {
    display:grid; gap:.8rem; grid-template-columns:1fr; margin:0 0 1.4rem;
  }
  @media (min-width:640px){ .pricing .pr-grid { grid-template-columns:repeat(2,1fr); } }
  @media (min-width:900px){ .pricing .pr-grid { grid-template-columns:repeat(5,1fr); } }
  .pricing .pr-card {
    background:rgba(255,255,255,.04); border:1px solid #2b3346; border-radius:14px;
    padding:1.2rem 1rem; display:flex; flex-direction:column; gap:.3rem;
  }
  .pricing .pr-card.pr-free   { border-color:rgba(127,201,141,.35); }
  .pricing .pr-card.pr-feature{ border-color:rgba(58,118,210,.5); box-shadow:0 0 0 1px rgba(58,118,210,.25); }
  .pricing .pr-name  { font-weight:800; color:#f0f4fa; font-size:1.05rem; }
  .pricing .pr-band  { color:#8593a6; font-size:.82rem; }
  .pricing .pr-price { margin:.5rem 0 .2rem; }
  .pricing .pr-was {
    display:block; color:#6c7d92; text-decoration:line-through;
    font-size:1.1rem; font-weight:700;
  }
  .pricing .pr-was .pr-per { text-decoration:none; }
  .pricing .pr-now { display:block; color:#7fc98d; font-weight:800; font-size:1.05rem; }
  .pricing .pr-now-free { font-size:1.6rem; }
  .pricing .pr-per { color:#6c7d92; font-size:.8rem; font-weight:600; }
  .pricing .pr-blurb { color:#9bb0c5; font-size:.86rem; line-height:1.5; margin:.4rem 0 0; }
  .pricing .pr-foot { text-align:center; color:#8593a6; font-size:.9rem; margin:0 0 2.4rem; }
  .pricing .pr-founder {
    background:rgba(58,118,210,.1); border:1px solid rgba(58,118,210,.3);
    border-radius:14px; padding:1.4rem 1.6rem; margin:0 0 2.4rem;
  }
  .pricing .pr-founder h2 { margin:0 0 .5rem; border:0; padding:0; }
  .pricing .pr-founder p  { margin:0; color:#c3d0de; }
</style>
