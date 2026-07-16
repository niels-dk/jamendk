<?php
// views/page_privacy.php (fragment; layout wraps it)
// Written to match what the app ACTUALLY stores — see the schema in
// db/migrations. If you add tracking, analytics or a third-party service,
// this page has to change with it.
$p_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$siteName  = defined('SITE_NAME')     ? SITE_NAME     : 'DreamBoard';
$siteEmail = defined('SITE_EMAIL')    ? SITE_EMAIL    : 'hello@jamen.dk';
$siteOwner = defined('SITE_LEGAL_ENTITY') ? SITE_LEGAL_ENTITY : 'Niels, Denmark';
$updated   = '15 July 2026';
?>
<div class="doc">
  <h1>Privacy policy</h1>
  <p class="doc-lead">
    The short version: your boards are yours, we don't track you, we don't run
    ads, and we don't sell anything to anyone. Below is the detail.
  </p>
  <p class="doc-meta">Last updated <?= $p_e($updated) ?></p>

  <h2>Who is responsible</h2>
  <p>
    <?= $p_e($siteName) ?> is operated by <?= $p_e($siteOwner) ?>. For anything
    on this page, contact <a href="mailto:<?= $p_e($siteEmail) ?>"><?= $p_e($siteEmail) ?></a>.
  </p>

  <h2>What we store, and why</h2>

  <h3>Your account</h3>
  <p>
    Your email address, your name, and optionally a company or organisation if
    you fill them in. Your password is never stored — only a one-way hash of
    it, which cannot be reversed back into your password. We record the time of
    your last sign-in so you can spot access you don't recognise.
  </p>
  <p>
    We need these to give you an account at all. Your email is also how we send
    you a confirmation link and, if you ask for one, a password-reset link.
  </p>

  <h3>Your content</h3>
  <p>
    Everything you put into the app: Dreams, Visions, Mood boards, shot lists,
    itineraries, budgets, contacts, notes, and any images or documents you
    upload. It's stored so we can show it back to you. We don't read it, mine
    it, or use it to train anything.
  </p>
  <p>
    <strong>Uploaded documents are encrypted at rest</strong>, so the raw files
    on the server are unreadable without the key held by the application.
  </p>

  <h3>Email log</h3>
  <p>
    When the app sends you an email (confirmation, password reset) we log the
    recipient address, the subject, whether it sent or failed, and the IP
    address the request came from. This exists so we can tell you what happened
    when a link doesn't arrive, and to stop the signup form being abused to
    send mail to strangers.
  </p>

  <h3>Cookies</h3>
  <p>
    One cookie: a session cookie that keeps you signed in. It's strictly
    necessary for the site to work and it disappears when you sign out.
  </p>
  <p>
    <strong>There is no analytics, no advertising, and no third-party
    tracking on this site.</strong> Nothing follows you anywhere. That's also
    why you aren't being nagged by a cookie banner.
  </p>
  <p>
    The app also stores a little data in your browser's own storage — offline
    Dreams waiting to sync, shot ticks made with no signal, and whether you
    collapsed the sidebar. That never leaves your device except to sync your
    own content to your own account.
  </p>

  <h2>Who else can see your data</h2>
  <p>
    People you explicitly share a board with, under <strong>Roles &amp;
    Permissions</strong>. Nobody else — with two exceptions you control:
  </p>
  <ul>
    <li>
      <strong>Published Trip pages are public.</strong> If you switch on
      <em>Publish as Trip</em>, anyone with that link can read the page without
      an account. That's the purpose of the feature. The link is long and
      unguessable, but treat it as public: anyone you send it to can forward
      it. Switch the toggle off and the link stops working immediately.
    </li>
    <li>
      <strong>Document download links work the same way.</strong> They're long
      and unguessable, but anyone holding one can download that file — that's
      what lets documents work on a shared Trip page.
    </li>
  </ul>
  <p>
    Site administrators can access accounts for support purposes.
  </p>

  <h2>Where your data lives</h2>
  <p>
    On servers operated by <strong>DreamHost</strong>, our hosting provider,
    located in the United States. Using the service means your data is
    transferred to and stored there. We use no other processors — no analytics
    service, no email marketing platform, no CDN.
  </p>

  <h2>How long we keep it</h2>
  <p>
    Your content stays until you delete it or ask us to delete your account.
    Email logs are kept as long as they're useful for support and abuse
    prevention.
  </p>

  <h2>Your rights</h2>
  <p>
    Under the GDPR you can ask us to show you what we hold about you, correct
    it, delete it, or give you a copy of it. You can object to how we use it.
    Email <a href="mailto:<?= $p_e($siteEmail) ?>"><?= $p_e($siteEmail) ?></a>
    from the address on your account and we'll sort it out — no forms, no
    hoops.
  </p>
  <p>
    If you think we've handled your data badly and we haven't fixed it, you can
    complain to the Danish Data Protection Agency
    (<a href="https://www.datatilsynet.dk" target="_blank" rel="noopener">Datatilsynet</a>).
  </p>

  <h2>Changes</h2>
  <p>
    If this policy changes in a way that matters, we'll update the date at the
    top and tell you by email before it takes effect.
  </p>

  <div class="doc-cta">
    <p>A question this page doesn't answer?</p>
    <a class="doc-btn" href="/contact">Ask us</a>
  </div>
</div>
