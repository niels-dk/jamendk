<?php
/**
 * Edit dream page.
 * Expects a `$dream` object (or associative array) with keys: id, name, inspiration,
 * and anchor arrays for locations, brands, people, seasons/time and any other categories.
 */
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Dream – <?= htmlspecialchars($dream['name'] ?? 'Dream') ?></title>
    <link rel="stylesheet" href="/public/css/app.css" />
  </head>
  <body>
    <main style="max-width:640px;margin:4rem auto;padding:0 1rem;">
      <h1>Edit Dream</h1>
      <form method="post" action="/dreams/<?= (int)($dream['id'] ?? 0) ?>/update">
        <!-- CSRF token / method spoofing should be added by your framework -->
        <label for="name">Dream Name</label>
        <input id="name" type="text" name="name" value="<?= htmlspecialchars($dream['name'] ?? '') ?>" required />

        <label for="inspiration">Inspiration</label>
        <textarea id="inspiration" name="inspiration" rows="5" required>
<?= htmlspecialchars($dream['inspiration'] ?? '') ?>
        </textarea>

        <div class="anchors" style="margin-top:1rem;">
          <?php
            $anchors = $dream['anchors'] ?? [
              'Locations' => [],
              'Brands' => [],
              'People' => [],
              'Seasons / Time' => [],
              'Any' => [],
            ];
          ?>
          <?php foreach ($anchors as $anchorName => $anchorValues): ?>
            <details>
              <summary style="cursor:pointer;font-weight:500;">
                <?= htmlspecialchars($anchorName) ?>
              </summary>
              <div style="padding:0.5rem 1rem;">
                <?php if (!empty($anchorValues)): ?>
                  <?php foreach ($anchorValues as $value): ?>
                    <div style="margin-bottom:0.25rem;">
                      <input type="text" name="anchors[<?= htmlspecialchars($anchorName) ?>][]" value="<?= htmlspecialchars($value) ?>" />
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <input type="text" name="anchors[<?= htmlspecialchars($anchorName) ?>][]" placeholder="Add <?= htmlspecialchars($anchorName) ?>" />
                <?php endif; ?>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
        <button type="submit" class="btn-primary" style="margin-top:2rem;">Save Dream</button>
      </form>
    </main>
  </body>
</html>