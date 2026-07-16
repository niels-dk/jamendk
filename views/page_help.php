<?php
// views/page_help.php (fragment; layout wraps it)
$p_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$siteEmail = defined('SITE_EMAIL') ? SITE_EMAIL : 'hello@jamen.dk';
?>
<div class="doc">
  <h1>Help</h1>
  <p class="doc-lead">
    DreamBoard has four pieces. They're really one idea at four stages of
    growing up.
  </p>

  <h2>🌕 Dream — catch it</h2>
  <p>
    A Dream is one line. “Get sponsored by Toyota and drive a Hilux across
    Brazil.” That's a complete Dream. It exists so an idea has somewhere to
    land in the ten seconds before it's gone — no forms, no required fields.
  </p>
  <p>
    It works with no signal. If you're offline, the Dream is stored on your
    phone and syncs itself the moment you're back online.
  </p>

  <h2>📄 Vision — grow it</h2>
  <p>
    When a Dream is worth pursuing, promote it to a Vision. This is where the
    real work lives:
  </p>
  <ul>
    <li><strong>Itinerary</strong> — the day-by-day plan, with map links.</li>
    <li><strong>Shots</strong> — what you want to capture: the angle, the light,
        what to say to camera, and reference images pinned from your Mood board.</li>
    <li><strong>Goals &amp; milestones</strong> — assign them to people, resolve
        them, or send work back with a note.</li>
    <li><strong>Budget</strong> — a total plus line items, so you can see what's left.</li>
    <li><strong>Contacts &amp; documents</strong> — the people behind a deal and
        the contracts, bookings and permits that go with them, kept with the
        project instead of lost in email.</li>
    <li><strong>Roles &amp; permissions</strong> — share the board with
        collaborators, or add a whole team at once.</li>
  </ul>

  <h2>🎨 Mood — the look</h2>
  <p>
    A Mood board is a free canvas: drop images, notes and frames, connect them
    up. Link it to a Vision under <strong>Relations</strong>, and its images
    become pickable references on your shot list.
  </p>

  <h2>🗺️ Trip — take it with you</h2>
  <p>
    A Vision plus its Mood board publishes as a <strong>Trip page</strong>: one
    link, no login needed to read it. It's the page you open at 6am on a
    roadside.
  </p>
  <ul>
    <li><strong>Works offline.</strong> Visit the link once while you have
        signal; it keeps working when you don't.</li>
    <li><strong>Tick shots off in the field.</strong> If you're signed in with
        edit rights, checkboxes are live — and ticks made offline sync
        themselves when you're back.</li>
    <li><strong>Offline copy.</strong> Download a single HTML file with images
        and documents embedded — no internet needed at all.</li>
    <li><strong>Print it.</strong> Some days the best device is paper and a pencil.</li>
  </ul>
  <p>
    You control exactly what's published: a master <strong>Publish as Trip</strong>
    switch in Basics, per-section toggles, and a <strong>Show on Trip layer</strong>
    switch on individual goals, shots, contacts and budget lines.
  </p>

  <h2>Common questions</h2>

  <h3>Is it free?</h3>
  <p>Yes — DreamBoard is free to use while it's being built.</p>

  <h3>Who can see my boards?</h3>
  <p>
    Only you, and anyone you explicitly add under <strong>Roles &amp;
    Permissions</strong>. The one exception is a published Trip page: anyone
    with that link can read it, which is the point of it. Turn off
    <strong>Publish as Trip</strong> and the link stops working.
  </p>

  <h3>I never got my confirmation email.</h3>
  <p>
    Check your spam folder first. If it isn't there, request a new link from
    the <a href="/verify-resend">resend page</a> — links last 24 hours and only
    work once. Still stuck? <a href="/contact">Tell us</a> and we'll confirm
    the account by hand.
  </p>

  <h3>Can I change my email address?</h3>
  <p>
    Not yet from your account page. <a href="/contact">Email us</a> and we'll
    move it for you.
  </p>

  <h3>How do I delete my account?</h3>
  <p>
    <a href="mailto:<?= $p_e($siteEmail) ?>">Email us</a> from the address on
    the account and we'll delete it and its contents. See the
    <a href="/privacy">privacy policy</a> for what's stored.
  </p>

  <div class="doc-cta">
    <p>Still stuck, or something's broken?</p>
    <a class="doc-btn" href="/contact">Get in touch</a>
  </div>
</div>
