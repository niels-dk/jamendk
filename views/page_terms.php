<?php
// views/page_terms.php (fragment; layout wraps it)
$p_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$siteName  = defined('SITE_NAME')  ? SITE_NAME  : 'DreamBoard';
$siteEmail = defined('SITE_EMAIL') ? SITE_EMAIL : 'hello@jamen.dk';
$siteOwner = defined('SITE_LEGAL_ENTITY') ? SITE_LEGAL_ENTITY : 'Niels, Denmark';
$updated   = '15 July 2026';
?>
<div class="doc">
  <h1>Terms of use</h1>
  <p class="doc-lead">
    Plain language, because you should actually be able to read this.
    <?= $p_e($siteName) ?> is free, it's early, and your work is your own.
  </p>
  <p class="doc-meta">Last updated <?= $p_e($updated) ?></p>

  <h2>1. Who we are</h2>
  <p>
    <?= $p_e($siteName) ?> is operated by <?= $p_e($siteOwner) ?>. Contact:
    <a href="mailto:<?= $p_e($siteEmail) ?>"><?= $p_e($siteEmail) ?></a>. Using
    the service means you accept these terms.
  </p>

  <h2>2. Your account</h2>
  <p>
    You need a working email address, and you have to confirm it before you can
    sign in. Keep your password to yourself — you're responsible for what
    happens under your account. Tell us if you think someone else has got into it.
  </p>
  <p>One account per person. Don't sign up on someone else's behalf.</p>

  <h2>3. It's free, and it's early</h2>
  <p>
    <?= $p_e($siteName) ?> is free to use while it's being built. If that ever
    changes, existing users will be told well in advance — nobody will be
    surprised by a bill.
  </p>
  <p>
    Being early also means: features change, things occasionally break, and
    <strong>you should keep your own copies of anything you can't afford to
    lose.</strong> Use the <em>Offline copy</em> button on a Trip page to
    download a self-contained backup of a project.
  </p>

  <h2>4. Your content stays yours</h2>
  <p>
    Everything you create here — your Dreams, Visions, shot lists, images,
    documents — belongs to you. We claim no ownership of it and no licence to
    use it for anything beyond running the service for you: storing it,
    showing it back to you, and displaying it to people you've shared it with.
  </p>
  <p>
    You're responsible for having the right to upload what you upload. Don't
    put other people's copyrighted material in here as if it were yours.
  </p>

  <h2>5. Sharing is your choice — and your responsibility</h2>
  <p>
    A published Trip page is <strong>public to anyone with the link</strong>.
    That's what it's for. Before you publish, use the <em>Show on Trip layer</em>
    toggles to decide what appears — budgets, contacts and documents are hidden
    or shown by you, not by us. Anyone you send the link to can forward it.
  </p>

  <h2>6. What you may not do</h2>
  <ul>
    <li>Break the law with it, or use it to harm or harass someone.</li>
    <li>Upload malware, or content that's illegal to hold or share.</li>
    <li>Try to break into other people's accounts or data.</li>
    <li>Hammer the service, scrape it, or try to knock it over.</li>
    <li>Use it to send spam, or abuse the signup and reset forms to mail strangers.</li>
  </ul>
  <p>
    If an account is doing any of that, we may suspend or delete it. If it's a
    genuine misunderstanding, email us and we'll talk.
  </p>

  <h2>7. Ending it</h2>
  <p>
    Leave whenever you like — email us and we'll delete your account and its
    contents. We can close an account that breaks section 6, or if we ever shut
    the service down, in which case we'll give you reasonable notice and a
    chance to get your work out.
  </p>

  <h2>8. No warranty</h2>
  <p>
    The service is provided “as is”. We work hard on it, but we can't promise
    it will always be available, error-free, or that nothing will ever be lost.
    Don't rely on it as the only copy of something critical.
  </p>

  <h2>9. Liability</h2>
  <p>
    To the extent the law allows, we're not liable for indirect or
    consequential losses — a missed shot, a missed deadline, lost profit —
    arising from using (or not being able to use) the service. Nothing here
    limits liability that can't legally be limited.
  </p>

  <h2>10. Changes</h2>
  <p>
    We may update these terms. If a change actually matters to you, we'll
    update the date at the top and tell you by email before it takes effect.
    Carrying on using the service after that means you accept the new terms.
  </p>

  <h2>11. Law</h2>
  <p>
    These terms are governed by Danish law, and disputes go to the Danish
    courts. If you're a consumer, this doesn't take away rights you have under
    the law of the country you live in.
  </p>

  <div class="doc-cta">
    <p>Something here unclear?</p>
    <a class="doc-btn" href="/contact">Ask us about it</a>
  </div>
</div>
