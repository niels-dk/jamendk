<?php
// views/partials/lang-picker.php — flag + code, click for the list.
//
// Included from both branches of the topbar: signed-in visitors get their
// stored users.lang, anonymous visitors get a session-only choice. There is
// deliberately NO Accept-Language sniffing — one URL always serves English
// until a human clicks the flag, so a crawler and a first-time visitor see
// the same page and there is nothing for search engines to get confused by.
$curLang  = I18n::lang();
$curMeta  = I18n::LANGUAGES[$curLang] ?? I18n::LANGUAGES['en'];
$backHere = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/', ENT_QUOTES);
?>
<div class="lang-picker">
  <button type="button" class="btn btn-ghost lang-toggle"
          aria-haspopup="true" aria-expanded="false"
          title="<?= te('nav.language') ?>">
    <?= I18n::flag($curMeta['cc'], 18) ?>
    <span class="lang-code"><?= htmlspecialchars(strtoupper(explode('-', $curLang)[0])) ?></span>
  </button>
  <div class="lang-menu" hidden>
    <div class="lang-menu-head"><?= te('nav.language') ?></div>
    <?php foreach (I18n::LANGUAGES as $code => $meta): ?>
      <form method="post" action="/account/language">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
        <input type="hidden" name="lang" value="<?= htmlspecialchars($code, ENT_QUOTES) ?>">
        <input type="hidden" name="next" value="<?= $backHere ?>">
        <button type="submit" class="lang-item <?= $code === $curLang ? 'is-current' : '' ?>">
          <?= I18n::flag($meta['cc'], 20) ?>
          <span class="lang-native"><?= htmlspecialchars($meta['native']) ?></span>
          <span class="lang-label"><?= htmlspecialchars($meta['label']) ?></span>
          <?php if ($code === $curLang): ?><span class="lang-check">✓</span><?php endif; ?>
        </button>
      </form>
    <?php endforeach; ?>
  </div>
</div>
