<?php
require_once __DIR__ . '/content.php';

$lang = isset($lang) && is_string($lang) ? $lang : shine_bright_resolve_lang();
$dictionary = isset($dictionary) && is_array($dictionary) ? $dictionary : shine_bright_load_content();
$content = $dictionary[$lang];
$ui = $content['ui'];
$brand = $content['brand'];
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>404 | <?= htmlspecialchars($brand['name']) ?></title>
  <link rel="stylesheet" href="/assets/styles.css?v=26">
</head>
<body class="detail-page">
  <main class="detail-main">
    <section class="detail-empty">
      <p class="eyebrow">404</p>
      <h1><?= htmlspecialchars($lang === 'bg' ? 'Страницата не беше намерена.' : 'Page not found.') ?></h1>
      <p class="detail-lead"><?= htmlspecialchars($lang === 'bg' ? 'Този линк не води към наличен клас, събитие или продукт.' : 'This link does not point to an available class, event, or product.') ?></p>
      <div class="detail-actions">
        <a class="btn btn-primary" href="/?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($ui['back_home']) ?></a>
      </div>
    </section>
  </main>
</body>
</html>
