<?php
// views/trip_disabled.php — shown when a trip's master switch is off.
// Standalone HTML, no site chrome.
$titleE = htmlspecialchars($title ?? 'Trip', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $titleE ?> — Not published</title>
  <style>
    html, body { margin:0; padding:0; }
    body {
      font: 16px/1.55 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      color: #1a2332; background: #f4f5f7;
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      padding: 2rem;
    }
    .card {
      background: #fff; border: 1px solid #e4e7ec; border-radius: 14px;
      box-shadow: 0 1px 2px rgba(11,23,39,.04), 0 8px 24px rgba(11,23,39,.05);
      max-width: 480px; width: 100%;
      padding: 2.4rem 2rem; text-align: center;
    }
    .icon {
      width: 56px; height: 56px; border-radius: 999px;
      background: #eef2f7; color: #5a6878;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 1.5rem; margin-bottom: 1rem;
    }
    h1 { margin: 0 0 .35rem; font-size: 1.4rem; color: #0b1727; }
    p  { margin: 0 .25rem; color: #5a6878; font-size: .95rem; }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">🔒</div>
    <h1>This trip isn't published</h1>
    <p>The owner hasn't made <strong><?= $titleE ?></strong> public yet.</p>
  </div>
</body>
</html>
