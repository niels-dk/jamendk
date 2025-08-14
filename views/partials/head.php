<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $title ?? 'Jamen' ?></title>
<meta name="color-scheme" content="dark light">
<meta name="theme-color" content="#1a1b1e">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js');
}
</script>
<link rel="stylesheet" href="/public/css/style.css?v=9">
<link rel="stylesheet" href="/public/css/app.css">
