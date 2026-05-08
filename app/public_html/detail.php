<?php
require_once __DIR__ . '/content.php';

$lang = shine_bright_resolve_lang();
$dictionary = shine_bright_load_content();
$content = $dictionary[$lang];
$ui = $content['ui'];
$brand = $content['brand'];

$section = isset($_GET['section']) && is_string($_GET['section']) ? trim($_GET['section']) : '';
$id = isset($_GET['id']) && is_string($_GET['id']) ? trim($_GET['id']) : '';

$allowedSections = ['classes', 'events', 'products'];
if (!in_array($section, $allowedSections, true) || $id === '') {
    http_response_code(404);
    require __DIR__ . '/not-found.php';
    exit;
}

$item = shine_bright_find_public_content_item($dictionary, $lang, $section, $id);
if (!$item) {
    http_response_code(404);
    require __DIR__ . '/not-found.php';
    exit;
}

$metaTitle = ($item['title'] ?? $brand['name']) . ' | ' . $brand['name'];
$metaDescription = trim((string) ($item['description'] ?? ''));
if ($metaDescription === '') {
    $metaDescription = $content['meta']['description'];
}

function sb_home_url(string $lang, string $anchor = ''): string
{
    $url = '/?lang=' . $lang;
    if ($anchor !== '') {
        $url .= '#' . $anchor;
    }
    return $url;
}

function sb_item_path(string $section, string $id, string $lang): string
{
    return '/' . $section . '/' . rawurlencode($id) . '?lang=' . $lang;
}

function sb_maps_link(?string $url): string
{
    if (!is_string($url) || trim($url) === '') {
        return '';
    }

    $url = trim($url);
    if (preg_match('~^https://(?:www\.)?google\.[^/]+/maps~i', $url) || preg_match('~^https://maps\.app\.goo\.gl/~i', $url) || preg_match('~^https://goo\.gl/maps/~i', $url)) {
        return $url;
    }

    return '';
}

function sb_media_url(?string $url): string
{
    return shine_bright_normalize_public_media_url($url);
}

$backHref = match ($section) {
    'classes' => sb_home_url($lang, 'classes'),
    'events' => sb_home_url($lang, 'events'),
    'products' => sb_home_url($lang, 'shop'),
};

$backLabel = match ($section) {
    'classes' => $ui['back_to_classes'],
    'events' => $ui['back_to_events'],
    'products' => $ui['back_to_products'],
};

$itemType = match ($section) {
    'classes' => 'class',
    'events' => 'event',
    'products' => 'product',
};

$primaryCtaLabel = match ($section) {
    'classes' => $ui['reserve_spot'],
    'events' => $ui['join_event'],
    'products' => $ui['order_product'],
};

$eyebrow = match ($section) {
    'classes' => $ui['classes_eyebrow'],
    'events' => $ui['events_eyebrow'],
    'products' => $ui['shop_eyebrow'],
};

$heroImage = sb_media_url($item['image_url'] ?? '');
if ($heroImage === '') {
    $heroImage = sb_media_url($brand['founder_image_url'] ?? '');
}

$classSchedules = $section === 'classes' ? shine_bright_class_schedules($item) : [];
$classScheduleSummary = $section === 'classes' ? shine_bright_class_schedule_summary($item, $lang, 4) : [];
$classUpcomingSessions = $section === 'classes' ? shine_bright_class_upcoming_sessions($item, $lang, 21, 6) : [];
$classSessionFieldLabel = $lang === 'bg' ? 'Избери дата и час' : 'Choose date and time';
$classSessionFieldPlaceholder = $lang === 'bg' ? 'Избери предстояща дата' : 'Choose an upcoming date';
$primarySchedule = $classSchedules[0] ?? null;
$location = $section === 'classes'
    ? trim((string) ($primarySchedule['location'] ?? ''))
    : trim((string) ($item['location'] ?? ''));
$dateLabel = shine_bright_format_date_range($item, $lang);
$timeLabel = shine_bright_format_time_range($item);
$durationLabel = ($section === 'classes' || shine_bright_format_time_range($item) !== '') ? (string) ((function () use ($item, $lang, $primarySchedule) {
    $start = shine_bright_datetime($item['start_at'] ?? null);
    $end = shine_bright_datetime($item['end_at'] ?? null);
    if (!$start || !$end) {
        if ($primarySchedule) {
            $startTime = trim((string) ($primarySchedule['start_time'] ?? ''));
            $endTime = trim((string) ($primarySchedule['end_time'] ?? ''));
            if ($startTime !== '' && $endTime !== '') {
                [$startHour, $startMinute] = array_pad(explode(':', $startTime, 2), 2, '00');
                [$endHour, $endMinute] = array_pad(explode(':', $endTime, 2), 2, '00');
                $minutes = max(0, (((int) $endHour) * 60 + (int) $endMinute) - (((int) $startHour) * 60 + (int) $startMinute));
                return $lang === 'bg' ? $minutes . ' мин' : $minutes . ' min';
            }
        }
        return (string) ($item['duration'] ?? '');
    }
    $minutes = max(0, (int) round(($end->getTimestamp() - $start->getTimestamp()) / 60));
    return $lang === 'bg' ? $minutes . ' мин' : $minutes . ' min';
})()) : '';
$mapsUrl = $section === 'classes'
    ? sb_maps_link($primarySchedule['maps_url'] ?? null)
    : sb_maps_link($item['maps_url'] ?? null);
$classScheduleOptions = array_map(static function (array $session): array {
    return [
        'id' => (string) ($session['id'] ?? ''),
        'label' => trim((string) ($session['summary_label'] ?? '')),
    ];
}, $classUpcomingSessions);
$canonicalPath = 'https://www.shinebrightyoga.com/' . $section . '/' . rawurlencode($id) . '?lang=' . $lang;
$detailToneClass = 'detail-kind-' . $section;

$detailFormatLabel = match ($section) {
    'classes' => $lang === 'bg' ? 'Редовна практика' : 'Regular Practice',
    'events' => $lang === 'bg' ? 'Специален формат' : 'Special Format',
    'products' => $lang === 'bg' ? 'Подбран продукт' : 'Curated Product',
};

$detailFormatBody = match ($section) {
    'classes' => $lang === 'bg'
        ? 'Този формат е създаден за хора, които искат устойчив седмичен ритъм и ясно водена практика.'
        : 'This format is designed for people who want a steadier weekly rhythm and clearly guided practice.',
    'events' => $lang === 'bg'
        ? 'Тук преживяването е по-дълго, по-малко като група и по-силно тематично от редовния клас.'
        : 'This format is longer, smaller in group size, and more thematic than the regular class.',
    'products' => $lang === 'bg'
        ? 'Подбран детайл, който продължава усещането от практиката и извън студиото.'
        : 'A curated detail designed to extend the feeling of practice beyond the studio.',
};

$detailSideTitle = match ($section) {
    'classes' => $lang === 'bg' ? 'За твоя седмичен ритъм' : 'For Your Weekly Rhythm',
    'events' => $lang === 'bg' ? 'За по-дълбока среща' : 'For a Deeper Experience',
    'products' => $lang === 'bg' ? 'За атмосфера и грижа' : 'For Atmosphere and Care',
};

$detailSideBody = match ($section) {
    'classes' => $lang === 'bg'
        ? 'Класовете са създадени така, че да могат да бъдат част от реален, устойчив личен ритъм.'
        : 'Classes are shaped to become part of a real, sustainable personal rhythm.',
    'events' => $lang === 'bg'
        ? 'Събитията събират повече време, повече тема и по-малка група в едно по-запомнящо се преживяване.'
        : 'Events bring together more time, more theme, and a smaller group into a more memorable experience.',
    'products' => $lang === 'bg'
        ? 'Продуктите са подбрани като естествено продължение на усещането за баланс, присъствие и домашна грижа.'
        : 'Products are selected as a natural continuation of the feeling of balance, presence, and home care.',
};

$productExperienceTitle = $lang === 'bg' ? 'Как стои в ежедневието' : 'How It Fits Into Daily Life';
$productExperienceBody = $lang === 'bg'
    ? 'Този продукт е подбран не като отделен артикул, а като детайл, който допълва атмосферата след практика или в спокойна домашна среда.'
    : 'This product is selected not as a standalone item, but as a detail that complements the atmosphere after practice or in a calmer home setting.';

$productSelectionTitle = $lang === 'bg' ? 'Защо е тук' : 'Why It Belongs Here';
$productSelectionBody = $lang === 'bg'
    ? 'Подборът следва усещане за мекота, ритъм и грижа, така че продуктите да стоят естествено до самата практика.'
    : 'The selection follows a sense of softness, rhythm, and care so that each product feels naturally connected to the practice itself.';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($metaTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
  <link rel="canonical" href="<?= htmlspecialchars($canonicalPath) ?>">
  <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?= htmlspecialchars($canonicalPath) ?>">
  <?php if ($heroImage !== ''): ?>
    <meta property="og:image" content="<?= htmlspecialchars($heroImage) ?>">
  <?php endif; ?>
  <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
  <link rel="shortcut icon" href="/assets/favicon.svg">
  <link rel="apple-touch-icon" href="/assets/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Noto+Serif:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/styles.css?v=29">
</head>
<body class="detail-page <?= htmlspecialchars($detailToneClass) ?>">
  <header class="site-header" id="site-header">
    <a class="brandmark" href="<?= htmlspecialchars(sb_home_url($lang)) ?>" aria-label="<?= htmlspecialchars($ui['brandmark_home']) ?>">
      <img src="/assets/favicon.svg" alt="" aria-hidden="true" class="brandmark-icon">
      <span><?= htmlspecialchars($brand['name']) ?></span>
    </a>
    <nav class="site-nav site-nav-desktop" aria-label="Primary">
      <a class="<?= $section === 'classes' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_home_url($lang, 'classes')) ?>"><?= htmlspecialchars($ui['nav_classes']) ?></a>
      <a class="<?= $section === 'events' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_home_url($lang, 'events')) ?>"><?= htmlspecialchars($ui['nav_events']) ?></a>
      <a class="<?= $section === 'products' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_home_url($lang, 'shop')) ?>"><?= htmlspecialchars($ui['nav_shop']) ?></a>
      <a href="<?= htmlspecialchars(sb_home_url($lang, 'founder')) ?>"><?= htmlspecialchars($ui['nav_founder']) ?></a>
      <a href="<?= htmlspecialchars(sb_home_url($lang, 'contact')) ?>"><?= htmlspecialchars($ui['nav_contact']) ?></a>
    </nav>
    <div class="header-tools header-tools-desktop">
      <div class="lang-switch" aria-label="<?= htmlspecialchars($ui['language_label']) ?>">
        <a class="<?= $lang === 'bg' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_item_path($section, $id, 'bg')) ?>"><?= htmlspecialchars($ui['lang_bg']) ?></a>
        <a class="<?= $lang === 'en' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_item_path($section, $id, 'en')) ?>"><?= htmlspecialchars($ui['lang_en']) ?></a>
      </div>
      <a class="header-cta" href="<?= htmlspecialchars($backHref) ?>"><?= htmlspecialchars($backLabel) ?></a>
    </div>
    <div class="header-actions">
      <a class="header-cta" href="<?= htmlspecialchars($backHref) ?>"><?= htmlspecialchars($backLabel) ?></a>
      <button class="menu-toggle" id="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu" aria-label="Open navigation menu">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </header>
  <div class="mobile-menu-backdrop" id="mobile-menu-backdrop" hidden></div>
  <aside class="mobile-menu" id="mobile-menu" hidden aria-label="Mobile navigation">
    <div class="mobile-menu-head">
      <strong><?= htmlspecialchars($brand['name']) ?></strong>
      <button class="mobile-menu-close" id="mobile-menu-close" type="button" aria-label="Close navigation menu">×</button>
    </div>
    <nav class="site-nav" aria-label="Primary">
      <a class="<?= $section === 'classes' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_home_url($lang, 'classes')) ?>"><?= htmlspecialchars($ui['nav_classes']) ?></a>
      <a class="<?= $section === 'events' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_home_url($lang, 'events')) ?>"><?= htmlspecialchars($ui['nav_events']) ?></a>
      <a class="<?= $section === 'products' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_home_url($lang, 'shop')) ?>"><?= htmlspecialchars($ui['nav_shop']) ?></a>
      <a href="<?= htmlspecialchars(sb_home_url($lang, 'founder')) ?>"><?= htmlspecialchars($ui['nav_founder']) ?></a>
      <a href="<?= htmlspecialchars(sb_home_url($lang, 'contact')) ?>"><?= htmlspecialchars($ui['nav_contact']) ?></a>
    </nav>
    <div class="header-tools">
      <div class="lang-switch" aria-label="<?= htmlspecialchars($ui['language_label']) ?>">
        <a class="<?= $lang === 'bg' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_item_path($section, $id, 'bg')) ?>"><?= htmlspecialchars($ui['lang_bg']) ?></a>
        <a class="<?= $lang === 'en' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_item_path($section, $id, 'en')) ?>"><?= htmlspecialchars($ui['lang_en']) ?></a>
      </div>
      <a class="header-cta header-cta-drawer" href="<?= htmlspecialchars($backHref) ?>"><?= htmlspecialchars($backLabel) ?></a>
    </div>
  </aside>

  <main class="detail-main">
    <section class="detail-hero">
      <div class="detail-copy">
        <a class="detail-back" href="<?= htmlspecialchars($backHref) ?>"><?= htmlspecialchars($backLabel) ?></a>
        <p class="eyebrow"><?= htmlspecialchars($eyebrow) ?></p>
        <h1><?= htmlspecialchars((string) ($item['title'] ?? '')) ?></h1>
        <p class="detail-lead"><?= htmlspecialchars((string) ($item['description'] ?? '')) ?></p>
        <div class="detail-chip-grid">
          <span class="detail-chip detail-chip-accent"><?= htmlspecialchars($detailFormatLabel) ?></span>
          <?php if ($section === 'products' && trim((string) ($item['category'] ?? '')) !== ''): ?>
            <span class="detail-chip"><?= htmlspecialchars((string) $item['category']) ?></span>
          <?php endif; ?>
          <?php if ($section === 'products' && trim((string) ($item['detail'] ?? '')) !== ''): ?>
            <span class="detail-chip"><?= htmlspecialchars((string) $item['detail']) ?></span>
          <?php endif; ?>
          <?php if ($section !== 'products' && $dateLabel !== ''): ?>
            <span class="detail-chip"><?= htmlspecialchars($dateLabel) ?></span>
          <?php endif; ?>
          <?php if ($section !== 'products' && $timeLabel !== ''): ?>
            <span class="detail-chip"><?= htmlspecialchars($timeLabel) ?></span>
          <?php endif; ?>
          <?php if (shine_bright_price_label($item) !== ''): ?>
            <span class="detail-chip"><?= htmlspecialchars(shine_bright_price_label($item)) ?></span>
          <?php endif; ?>
        </div>
        <div class="detail-actions">
          <button
            class="btn btn-primary js-open-inquiry"
            data-type="<?= htmlspecialchars($itemType) ?>"
            data-id="<?= htmlspecialchars((string) ($item['id'] ?? '')) ?>"
            data-title="<?= htmlspecialchars((string) ($item['title'] ?? '')) ?>"
            data-datetime="<?= htmlspecialchars($section === 'classes' ? implode(' · ', $classScheduleSummary) : trim($dateLabel . ($timeLabel !== '' ? ' · ' . $timeLabel : ''))) ?>"
            data-location="<?= htmlspecialchars($location) ?>"
            <?php if ($section === 'classes'): ?>data-schedules='<?= htmlspecialchars(json_encode($classScheduleOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'<?php endif; ?>
          >
            <?= htmlspecialchars($primaryCtaLabel) ?>
          </button>
          <a class="btn btn-secondary" href="<?= htmlspecialchars(sb_home_url($lang)) ?>"><?= htmlspecialchars($ui['back_home']) ?></a>
        </div>
      </div>
      <div class="detail-media">
        <div class="detail-media-frame"<?= $section === 'products' && shine_bright_product_media_style($item) !== '' ? ' style="' . shine_bright_product_media_style($item) . '"' : ($heroImage !== '' ? ' style="background-image:url(\'' . htmlspecialchars($heroImage) . '\')"' : '') ?>></div>
      </div>
    </section>

    <section class="detail-body">
      <article class="detail-card">
        <div class="detail-card-heading">
          <p class="eyebrow"><?= htmlspecialchars($detailFormatLabel) ?></p>
          <h2><?= htmlspecialchars((string) ($item['title'] ?? '')) ?></h2>
          <p class="detail-card-copy"><?= htmlspecialchars($detailFormatBody) ?></p>
        </div>
        <dl class="detail-facts">
          <?php if ($section === 'classes' && $classScheduleSummary !== []): ?>
            <div><dt><?= htmlspecialchars($ui['schedule']) ?></dt><dd><?= htmlspecialchars(implode(' / ', $classScheduleSummary)) ?></dd></div>
          <?php endif; ?>
          <?php if ($section !== 'products' && $section !== 'classes' && $dateLabel !== ''): ?>
            <div><dt><?= htmlspecialchars($ui['date']) ?></dt><dd><?= htmlspecialchars($dateLabel) ?></dd></div>
          <?php endif; ?>
          <?php if ($section !== 'products' && $section !== 'classes' && $timeLabel !== ''): ?>
            <div><dt><?= htmlspecialchars($ui['time']) ?></dt><dd><?= htmlspecialchars($timeLabel) ?></dd></div>
          <?php endif; ?>
          <?php if ($section === 'classes' && trim((string) ($item['level'] ?? '')) !== ''): ?>
            <div><dt><?= htmlspecialchars($ui['level']) ?></dt><dd><?= htmlspecialchars((string) $item['level']) ?></dd></div>
          <?php endif; ?>
          <?php if ($section === 'products' && trim((string) ($item['category'] ?? '')) !== ''): ?>
            <div><dt><?= htmlspecialchars($ui['category']) ?></dt><dd><?= htmlspecialchars((string) $item['category']) ?></dd></div>
          <?php endif; ?>
          <?php if ($section === 'products' && trim((string) ($item['detail'] ?? '')) !== ''): ?>
            <div><dt><?= htmlspecialchars($ui['detail_label']) ?></dt><dd><?= htmlspecialchars((string) $item['detail']) ?></dd></div>
          <?php endif; ?>
          <?php if ($durationLabel !== '' && $section !== 'products'): ?>
            <div><dt><?= htmlspecialchars($ui['duration']) ?></dt><dd><?= htmlspecialchars($durationLabel) ?></dd></div>
          <?php endif; ?>
          <?php if ($location !== ''): ?>
            <div>
              <dt><?= htmlspecialchars($ui['location']) ?></dt>
              <dd>
                <?php if ($mapsUrl !== ''): ?>
                  <a class="location-link" href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener noreferrer">
                    <span><?= htmlspecialchars($location) ?></span>
                    <small><?= htmlspecialchars($ui['open_maps']) ?></small>
                  </a>
                <?php else: ?>
                  <?= htmlspecialchars($location) ?>
                <?php endif; ?>
              </dd>
            </div>
          <?php endif; ?>
          <?php if (shine_bright_price_label($item) !== ''): ?>
            <div><dt><?= htmlspecialchars($ui['price']) ?></dt><dd><?= htmlspecialchars(shine_bright_price_label($item)) ?></dd></div>
          <?php endif; ?>
        </dl>
        <?php if ($section === 'products'): ?>
          <div class="product-story-grid">
            <article class="product-story-card">
              <p class="eyebrow"><?= htmlspecialchars($productExperienceTitle) ?></p>
              <p><?= htmlspecialchars($productExperienceBody) ?></p>
            </article>
            <article class="product-story-card">
              <p class="eyebrow"><?= htmlspecialchars($productSelectionTitle) ?></p>
              <p><?= htmlspecialchars($productSelectionBody) ?></p>
            </article>
          </div>
        <?php endif; ?>
      </article>

      <aside class="detail-sidecard">
        <p class="eyebrow"><?= htmlspecialchars($detailSideTitle) ?></p>
        <h3><?= htmlspecialchars($brand['founder_name']) ?></h3>
        <p><?= htmlspecialchars($detailSideBody) ?></p>
        <p class="detail-sidecard-note"><?= htmlspecialchars($brand['founder_title']) ?></p>
        <div class="detail-contact">
          <a class="btn btn-secondary" href="tel:<?= htmlspecialchars((string) preg_replace('/\s+/', '', (string) ($brand['contact_phone'] ?? ''))) ?>"><?= htmlspecialchars($brand['contact_phone'] ?? '') ?></a>
          <?php if (trim((string) ($brand['contact_email'] ?? '')) !== ''): ?>
            <a class="text-link" href="mailto:<?= htmlspecialchars((string) $brand['contact_email']) ?>"><?= htmlspecialchars((string) $brand['contact_email']) ?></a>
          <?php endif; ?>
        </div>
      </aside>
    </section>
  </main>

  <dialog class="inquiry-dialog" id="inquiry-dialog">
    <form class="inquiry-form" id="inquiry-form" method="dialog">
      <div class="dialog-head">
        <div>
          <p class="eyebrow"><?= htmlspecialchars($ui['inquiry_eyebrow']) ?></p>
          <h2 id="dialog-title"><?= htmlspecialchars($ui['inquiry_default_title']) ?></h2>
          <div class="dialog-context" id="dialog-context" data-kind="general" hidden>
            <div class="dialog-context-meta">
              <span class="dialog-context-icon" id="dialog-context-icon" aria-hidden="true"></span>
              <span class="dialog-context-type" id="dialog-context-type"></span>
            </div>
            <strong class="dialog-context-item" id="dialog-context-item"></strong>
            <p class="dialog-context-copy" id="dialog-context-copy"></p>
            <div class="dialog-context-details" id="dialog-context-details" hidden>
              <span class="dialog-context-detail" id="dialog-context-datetime"></span>
              <span class="dialog-context-detail" id="dialog-context-location"></span>
            </div>
          </div>
        </div>
        <button type="button" class="dialog-close" id="dialog-close" aria-label="<?= htmlspecialchars($ui['close_dialog']) ?>">×</button>
      </div>
      <input type="hidden" name="inquiry_type" id="inquiry-type">
      <input type="hidden" name="item_id" id="item-id">
      <input type="hidden" name="item_title" id="item-title">
      <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">

      <label>
        <span><?= htmlspecialchars($ui['field_name']) ?></span>
        <input type="text" name="customer_name" required autocomplete="name">
      </label>
      <label>
        <span><?= htmlspecialchars($ui['field_email']) ?></span>
        <input type="email" name="email" autocomplete="email" inputmode="email" spellcheck="false">
      </label>
      <label>
        <span><?= htmlspecialchars($ui['field_phone']) ?></span>
        <input type="tel" name="phone" autocomplete="tel" inputmode="tel">
      </label>
      <label class="dialog-field dialog-field-quantity is-hidden" id="dialog-field-quantity" hidden aria-hidden="true">
        <span><?= htmlspecialchars($ui['field_quantity']) ?></span>
        <input type="number" name="quantity" min="1" max="20" value="1" inputmode="numeric" id="field-quantity">
      </label>
      <label class="dialog-field dialog-field-schedule is-hidden" id="dialog-field-schedule" hidden aria-hidden="true">
        <span><?= htmlspecialchars($classSessionFieldLabel) ?></span>
        <select name="session_id" id="field-schedule"></select>
      </label>
      <label>
        <span><?= htmlspecialchars($ui['field_message']) ?></span>
        <textarea name="message" rows="4" placeholder="<?= htmlspecialchars($ui['field_message_placeholder']) ?>" autocomplete="off" id="field-message"></textarea>
      </label>
      <div class="form-feedback" id="form-feedback" aria-live="polite"></div>
      <div class="form-success-card" id="form-success-card" hidden aria-live="polite">
        <span class="form-success-pill"><?= htmlspecialchars($lang === 'bg' ? 'Мястото е запазено' : 'Spot reserved') ?></span>
        <div class="form-success-copy">
          <strong id="form-success-title"></strong>
          <p id="form-success-session"></p>
        </div>
        <div class="form-success-meta">
          <div>
            <span><?= htmlspecialchars($lang === 'bg' ? 'Локация' : 'Location') ?></span>
            <strong id="form-success-location"></strong>
          </div>
          <div id="form-success-maps-wrap" hidden>
            <span><?= htmlspecialchars($lang === 'bg' ? 'Карта' : 'Map') ?></span>
            <a id="form-success-maps" href="#" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($lang === 'bg' ? 'Отвори в Google Maps' : 'Open in Google Maps') ?></a>
          </div>
        </div>
      </div>
      <div class="dialog-actions">
        <button type="submit" class="btn btn-primary" id="dialog-submit"><?= htmlspecialchars($ui['send_inquiry']) ?></button>
        <button type="button" class="btn btn-secondary" id="dialog-cancel"><?= htmlspecialchars($ui['cancel']) ?></button>
      </div>
    </form>
  </dialog>

  <script>
    (function () {
      var copy = {
        inquiryDefaultTitle: <?= json_encode($ui['inquiry_default_title']) ?>,
        inquiryProductPrefix: <?= json_encode($ui['inquiry_product_prefix']) ?>,
        inquiryGeneralPrefix: <?= json_encode($ui['inquiry_general_prefix']) ?>,
        reserveDialogTitle: <?= json_encode($ui['reserve_dialog_title']) ?>,
        eventDialogTitle: <?= json_encode($ui['event_dialog_title']) ?>,
        productDialogTitle: <?= json_encode($ui['product_dialog_title']) ?>,
        generalDialogTitle: <?= json_encode($ui['general_dialog_title']) ?>,
        reserveContext: <?= json_encode($ui['reserve_context']) ?>,
        eventContext: <?= json_encode($ui['event_context']) ?>,
        productContext: <?= json_encode($ui['product_context']) ?>,
        generalContext: <?= json_encode($ui['general_context']) ?>,
        typeLabelClass: <?= json_encode($ui['type_label_class']) ?>,
        typeLabelEvent: <?= json_encode($ui['type_label_event']) ?>,
        typeLabelProduct: <?= json_encode($ui['type_label_product']) ?>,
        typeLabelGeneral: <?= json_encode($ui['type_label_general']) ?>,
        reserveSubmit: <?= json_encode($ui['reserve_submit']) ?>,
        eventSubmit: <?= json_encode($ui['event_submit']) ?>,
        productSubmit: <?= json_encode($ui['product_submit']) ?>,
        generalSubmit: <?= json_encode($ui['send_inquiry']) ?>,
        placeholderDefault: <?= json_encode($ui['field_message_placeholder']) ?>,
        placeholderClass: <?= json_encode($ui['field_message_placeholder_class']) ?>,
        placeholderEvent: <?= json_encode($ui['field_message_placeholder_event']) ?>,
        placeholderProduct: <?= json_encode($ui['field_message_placeholder_product']) ?>,
        sending: <?= json_encode($ui['sending']) ?>,
        success: <?= json_encode($ui['success']) ?>,
        successClass: <?= json_encode($ui['success_class']) ?>,
        successEvent: <?= json_encode($ui['success_event']) ?>,
        successProduct: <?= json_encode($ui['success_product']) ?>,
        errorDefault: <?= json_encode($ui['error_default']) ?>,
        errorInvalidEmail: <?= json_encode($ui['error_invalid_email']) ?>,
        errorInvalidPhone: <?= json_encode($ui['error_invalid_phone']) ?>
      };
      var dialog = document.getElementById('inquiry-dialog');
      var form = document.getElementById('inquiry-form');
      var feedback = document.getElementById('form-feedback');
      var successCard = document.getElementById('form-success-card');
      var successTitle = document.getElementById('form-success-title');
      var successSession = document.getElementById('form-success-session');
      var successLocation = document.getElementById('form-success-location');
      var successMapsWrap = document.getElementById('form-success-maps-wrap');
      var successMaps = document.getElementById('form-success-maps');
      var title = document.getElementById('dialog-title');
      var submitButton = document.getElementById('dialog-submit');
      var dialogContext = document.getElementById('dialog-context');
      var dialogContextIcon = document.getElementById('dialog-context-icon');
      var dialogContextType = document.getElementById('dialog-context-type');
      var dialogContextItem = document.getElementById('dialog-context-item');
      var dialogContextCopy = document.getElementById('dialog-context-copy');
      var dialogContextDetails = document.getElementById('dialog-context-details');
      var dialogContextDateTime = document.getElementById('dialog-context-datetime');
      var dialogContextLocation = document.getElementById('dialog-context-location');
      var typeInput = document.getElementById('inquiry-type');
      var itemIdInput = document.getElementById('item-id');
      var itemTitleInput = document.getElementById('item-title');
      var messageInput = document.getElementById('field-message');
      var quantityInput = document.getElementById('field-quantity');
      var quantityLabel = document.getElementById('dialog-field-quantity');
      var scheduleInput = document.getElementById('field-schedule');
      var scheduleLabel = document.getElementById('dialog-field-schedule');
      var nameInput = form.querySelector('input[name="customer_name"]');
      var emailInput = form.querySelector('input[name="email"]');
      var phoneInput = form.querySelector('input[name="phone"]');
      var menuToggle = document.getElementById('menu-toggle');
      var mobileMenu = document.getElementById('mobile-menu');
      var mobileMenuClose = document.getElementById('mobile-menu-close');
      var mobileMenuBackdrop = document.getElementById('mobile-menu-backdrop');
      var requiredNameMessage = <?= json_encode($lang === 'bg' ? 'Името е задължително.' : 'Name is required.') ?>;
      var contactRequiredMessage = <?= json_encode($lang === 'bg' ? 'Имейл или телефон са задължителни.' : 'Email or phone is required.') ?>;
      var scheduleRequiredMessage = <?= json_encode($lang === 'bg' ? 'Изберете дата и час за класа.' : 'Choose a class date and time.') ?>;
      var iconMarkup = {
        "class": '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4h10a2 2 0 0 1 2 2v12l-3-2.25L13 18l-3-2.25L7 18V6a2 2 0 0 1 2-2Z"/></svg>',
        "event": '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v2M17 3v2M4.5 9.5h15M6 5.5h12A1.5 1.5 0 0 1 19.5 7v11A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V7A1.5 1.5 0 0 1 6 5.5Zm2.5 7h3v3h-3z"/></svg>',
        "product": '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4.5 7v10L12 21l7.5-4V7L12 3Zm0 2.1 4.88 2.6L12 10.3 7.12 7.7 12 5.1Zm-5.5 4.2 4.5 2.4v5.74l-4.5-2.4V9.3Zm11 0v5.74l-4.5 2.4v-5.74l4.5-2.4Z"/></svg>',
        "general": '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6.75A1.75 1.75 0 0 1 5.75 5h12.5A1.75 1.75 0 0 1 20 6.75v10.5A1.75 1.75 0 0 1 18.25 19H5.75A1.75 1.75 0 0 1 4 17.25V6.75Zm1.9.25L12 11.35 18.1 7H5.9Zm12.6 1.17-5.92 4.2a1 1 0 0 1-1.16 0L5.5 8.17v9.08c0 .14.11.25.25.25h12.5a.25.25 0 0 0 .25-.25V8.17Z"/></svg>'
      };

      function setError(field, message) {
        if (field) field.setAttribute('aria-invalid', 'true');
        feedback.textContent = message;
      }
      function hideSuccessCard() {
        if (successCard) {
          successCard.hidden = true;
        }
      }
      function showClassSuccessCard(payload) {
        if (!successCard || !payload || !payload.reservation) {
          return;
        }

        successTitle.textContent = payload.reservation.class_title || itemTitleInput.value || '';
        successSession.textContent = payload.reservation.session_label || '';
        successLocation.textContent = payload.reservation.location || <?= json_encode($lang === 'bg' ? 'Локацията ще получите в потвърждението.' : 'Location will be included in the confirmation.') ?>;

        if (payload.reservation.maps_url) {
          successMapsWrap.hidden = false;
          successMaps.href = payload.reservation.maps_url;
        } else {
          successMapsWrap.hidden = true;
          successMaps.href = '#';
        }

        successCard.hidden = false;
      }
      function clearError(field) {
        if (field) field.removeAttribute('aria-invalid');
      }
      function resetScheduleField() {
        if (!scheduleLabel || !scheduleInput) {
          return;
        }
        scheduleLabel.hidden = true;
        scheduleLabel.classList.add('is-hidden');
        scheduleLabel.setAttribute('aria-hidden', 'true');
        scheduleInput.disabled = true;
        scheduleInput.required = false;
        scheduleInput.innerHTML = '';
      }
      function syncScheduleField(button, kind) {
        if (!scheduleLabel || !scheduleInput) {
          return;
        }
        resetScheduleField();
        if (kind !== 'class') {
          return;
        }
        var schedules = [];
        try {
          schedules = JSON.parse(button.getAttribute('data-schedules') || '[]');
        } catch (error) {
          schedules = [];
        }
        if (!Array.isArray(schedules) || schedules.length === 0) {
          return;
        }
        scheduleLabel.hidden = false;
        scheduleLabel.classList.remove('is-hidden');
        scheduleLabel.setAttribute('aria-hidden', 'false');
        scheduleInput.disabled = false;
        scheduleInput.required = true;

        var placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = <?= json_encode($classSessionFieldPlaceholder) ?>;
        scheduleInput.appendChild(placeholderOption);

        schedules.forEach(function (schedule, index) {
          var option = document.createElement('option');
          option.value = schedule.id || '';
          option.textContent = schedule.label || ('<?= htmlspecialchars($ui['schedule']) ?> ' + (index + 1));
          if (index === 0) {
            option.selected = true;
          }
          scheduleInput.appendChild(option);
        });
      }
      function openMenu() {
        document.body.classList.add('menu-open');
        mobileMenu.hidden = false;
        mobileMenuBackdrop.hidden = false;
        menuToggle.setAttribute('aria-expanded', 'true');
      }
      function closeMenu() {
        document.body.classList.remove('menu-open');
        mobileMenu.hidden = true;
        mobileMenuBackdrop.hidden = true;
        menuToggle.setAttribute('aria-expanded', 'false');
      }
      function setDialogContext(button) {
        var type = button.getAttribute('data-type') || 'general';
        var itemTitle = button.getAttribute('data-title') || '';
        var datetime = button.getAttribute('data-datetime') || '';
        var location = button.getAttribute('data-location') || '';
        var dialogTitle = copy.generalDialogTitle + itemTitle;
        var contextCopy = copy.generalContext;
        var typeLabel = copy.typeLabelGeneral;
        var submitLabel = copy.generalSubmit;
        var placeholder = copy.placeholderDefault;
        if (type === 'class') {
          dialogTitle = copy.reserveDialogTitle + itemTitle;
          contextCopy = copy.reserveContext;
          typeLabel = copy.typeLabelClass;
          submitLabel = copy.reserveSubmit;
          placeholder = copy.placeholderClass;
        } else if (type === 'event') {
          dialogTitle = copy.eventDialogTitle + itemTitle;
          contextCopy = copy.eventContext;
          typeLabel = copy.typeLabelEvent;
          submitLabel = copy.eventSubmit;
          placeholder = copy.placeholderEvent;
        } else if (type === 'product') {
          dialogTitle = copy.productDialogTitle + itemTitle;
          contextCopy = copy.productContext;
          typeLabel = copy.typeLabelProduct;
          submitLabel = copy.productSubmit;
          placeholder = copy.placeholderProduct;
        }
        title.textContent = dialogTitle;
        submitButton.textContent = submitLabel;
        typeInput.value = type;
        itemIdInput.value = button.getAttribute('data-id') || '';
        itemTitleInput.value = itemTitle;
        messageInput.placeholder = placeholder;
        syncScheduleField(button, type);
        quantityLabel.hidden = type !== 'product';
        quantityLabel.classList.toggle('is-hidden', type !== 'product');
        quantityLabel.setAttribute('aria-hidden', type !== 'product' ? 'true' : 'false');
        dialogContext.hidden = false;
        dialogContext.dataset.kind = type;
        dialogContextIcon.innerHTML = iconMarkup[type] || iconMarkup.general;
        dialogContextType.textContent = typeLabel;
        dialogContextItem.textContent = itemTitle;
        dialogContextCopy.textContent = contextCopy;
        dialogContextDateTime.textContent = datetime;
        dialogContextLocation.textContent = location;
        dialogContextDetails.hidden = datetime === '' && location === '';
      }
      function resetDialog() {
        form.reset();
        feedback.textContent = '';
        hideSuccessCard();
        [nameInput, emailInput, phoneInput, scheduleInput, messageInput].forEach(clearError);
        quantityLabel.hidden = true;
        quantityLabel.classList.add('is-hidden');
        resetScheduleField();
      }
      var siteHeader = document.getElementById('site-header');
      function syncHeaderState() {
        if (!siteHeader) return;
        siteHeader.classList.toggle('is-scrolled', window.scrollY > 24);
      }

      document.querySelectorAll('.js-open-inquiry').forEach(function (button) {
        button.addEventListener('click', function () {
          resetDialog();
          setDialogContext(button);
          dialog.showModal();
        });
      });
      document.getElementById('dialog-close').addEventListener('click', function () { dialog.close(); });
      document.getElementById('dialog-cancel').addEventListener('click', function () { dialog.close(); });
      dialog.addEventListener('close', resetDialog);
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        feedback.textContent = '';
        [nameInput, emailInput, phoneInput, messageInput].forEach(clearError);
        if (!nameInput.value.trim()) {
          setError(nameInput, requiredNameMessage);
          return;
        }
        if (!emailInput.value.trim() && !phoneInput.value.trim()) {
          setError(emailInput, contactRequiredMessage);
          setError(phoneInput, contactRequiredMessage);
          return;
        }
        if (scheduleInput && !scheduleInput.disabled && !scheduleInput.value) {
          setError(scheduleInput, scheduleRequiredMessage);
          return;
        }
        submitButton.disabled = true;
        submitButton.textContent = copy.sending;
        fetch('/submit.php', { method: 'POST', body: new FormData(form) })
          .then(function (response) { return response.json(); })
          .then(function (payload) {
            if (!payload.ok) throw new Error(payload.error || copy.errorDefault);
            var successMessage = copy.success;
            if (typeInput.value === 'class') successMessage = copy.successClass;
            else if (typeInput.value === 'event') successMessage = copy.successEvent;
            else if (typeInput.value === 'product') successMessage = copy.successProduct;
            if (typeInput.value === 'class' && payload.reservation) {
              feedback.textContent = '';
              showClassSuccessCard(payload);
            } else {
              feedback.textContent = successMessage;
              setTimeout(function () { dialog.close(); }, 1200);
            }
          })
          .catch(function (error) {
            feedback.textContent = error && error.message ? error.message : copy.errorDefault;
          })
          .finally(function () {
            submitButton.disabled = false;
            setDialogContext(document.querySelector('.js-open-inquiry'));
          });
      });
      if (menuToggle && mobileMenu && mobileMenuClose && mobileMenuBackdrop) {
        menuToggle.addEventListener('click', function () {
          if (mobileMenu.hidden) openMenu();
          else closeMenu();
        });
        mobileMenuClose.addEventListener('click', closeMenu);
        mobileMenuBackdrop.addEventListener('click', closeMenu);
        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape' && !mobileMenu.hidden) closeMenu();
        });
      }
      syncHeaderState();
      window.addEventListener('scroll', syncHeaderState, { passive: true });
    })();
  </script>
</body>
</html>
