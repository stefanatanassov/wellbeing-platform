<?php
require_once __DIR__ . '/content.php';

$lang = shine_bright_resolve_lang();
$dictionary = shine_bright_load_content();
$content = $dictionary[$lang];
$ui = $content['ui'];
$meta = $content['meta'];
$brand = $content['brand'];
$classes = shine_bright_public_content_section_items($dictionary, $lang, 'classes');
$events = shine_bright_public_content_section_items($dictionary, $lang, 'events');
$products = array_map(static function (array $product): array {
    if (isset($product['image_url']) && is_string($product['image_url'])) {
        $product['image_url'] = shine_bright_normalize_public_media_url($product['image_url']);
    }

    return $product;
}, shine_bright_public_content_section_items($dictionary, $lang, 'products'));
$testimonials = shine_bright_public_content_section_items($dictionary, $lang, 'testimonials');
$classSessionFieldLabel = $lang === 'bg' ? 'Избери дата и час' : 'Choose date and time';
$classSessionFieldPlaceholder = $lang === 'bg' ? 'Избери предстояща дата' : 'Choose an upcoming date';

function sb_query_lang(string $targetLang, string $anchor = ''): string
{
    $suffix = $anchor !== '' ? '#' . $anchor : '';
    return '/?lang=' . $targetLang . $suffix;
}

function sb_item_path(string $section, string $id, string $lang): string
{
    return '/' . $section . '/' . rawurlencode($id) . '?lang=' . $lang;
}

function sb_format_date_range(array $item, string $lang): string
{
    return shine_bright_format_date_range($item, $lang);
}

function sb_format_time_range(array $item): string
{
    return shine_bright_format_time_range($item);
}

function sb_duration_label(array $item, string $lang): string
{
    $start = shine_bright_datetime($item['start_at'] ?? null);
    $end = shine_bright_datetime($item['end_at'] ?? null);

    if (!$start || !$end) {
        $primarySchedule = shine_bright_primary_class_schedule($item);
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
}

function sb_price_label(array $item): string
{
    $price = trim((string) ($item['price_eur'] ?? ''));
    if ($price === '') {
        return (string) ($item['price'] ?? '');
    }

    return '€' . $price;
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

function sb_youtube_id(?string $url): ?string
{
    if (!is_string($url) || trim($url) === '') {
        return null;
    }

    $url = trim($url);
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{11})~', $url, $matches)) {
        return $matches[1];
    }

    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    if (isset($query['v']) && is_string($query['v']) && preg_match('~^[A-Za-z0-9_-]{11}$~', $query['v'])) {
        return $query['v'];
    }

    return null;
}

$founderImageUrl = shine_bright_normalize_public_media_url(trim((string) ($brand['founder_image_url'] ?? '')));
$heroVideoUrl = shine_bright_normalize_public_media_url(trim((string) ($brand['hero_video_url'] ?? '')));
$heroVideoId = sb_youtube_id($heroVideoUrl);
$heroVideoPoster = shine_bright_normalize_public_media_url(trim((string) ($brand['hero_video_poster_url'] ?? '')));
if ($heroVideoPoster === '' && !$heroVideoId && $founderImageUrl !== '') {
    $heroVideoPoster = $founderImageUrl;
}
if ($heroVideoPoster === '' && $heroVideoId) {
    $heroVideoPoster = 'https://i.ytimg.com/vi/' . $heroVideoId . '/hqdefault.jpg';
}
$selfHostedHeroVideo = $heroVideoUrl;
$hasSelfHostedHeroVideo = $selfHostedHeroVideo !== '' && (str_starts_with($selfHostedHeroVideo, '/media/') || str_starts_with($selfHostedHeroVideo, './media/'));
$heroTextMode = (string) ($brand['hero_text_mode'] ?? 'auto');
if (!in_array($heroTextMode, ['auto', 'dark', 'light'], true)) {
    $heroTextMode = 'auto';
}
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($meta['title']) ?></title>
  <meta name="description" content="<?= htmlspecialchars($meta['description']) ?>">
  <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
  <link rel="shortcut icon" href="/assets/favicon.svg">
  <link rel="apple-touch-icon" href="/assets/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Noto+Serif:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/styles.css?v=29">
</head>
<body>
  <header class="site-header" id="site-header">
    <a class="brandmark" href="<?= htmlspecialchars(sb_query_lang($lang)) ?>" aria-label="<?= htmlspecialchars($ui['brandmark_home']) ?>">
      <img src="/assets/favicon.svg" alt="" aria-hidden="true" class="brandmark-icon">
      <span><?= htmlspecialchars($brand['name']) ?></span>
    </a>
    <nav class="site-nav site-nav-desktop" aria-label="Primary">
      <a href="<?= htmlspecialchars(sb_query_lang($lang, 'classes')) ?>"><?= htmlspecialchars($ui['nav_classes']) ?></a>
      <a href="<?= htmlspecialchars(sb_query_lang($lang, 'events')) ?>"><?= htmlspecialchars($ui['nav_events']) ?></a>
      <a href="<?= htmlspecialchars(sb_query_lang($lang, 'shop')) ?>"><?= htmlspecialchars($ui['nav_shop']) ?></a>
      <a href="<?= htmlspecialchars(sb_query_lang($lang, 'founder')) ?>"><?= htmlspecialchars($ui['nav_founder']) ?></a>
      <a href="<?= htmlspecialchars(sb_query_lang($lang, 'contact')) ?>"><?= htmlspecialchars($ui['nav_contact']) ?></a>
    </nav>
    <div class="header-tools header-tools-desktop">
      <div class="lang-switch" aria-label="<?= htmlspecialchars($ui['language_label']) ?>">
        <a class="<?= $lang === 'bg' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_query_lang('bg')) ?>" data-lang-link="bg"><?= htmlspecialchars($ui['lang_bg']) ?></a>
        <a class="<?= $lang === 'en' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_query_lang('en')) ?>" data-lang-link="en"><?= htmlspecialchars($ui['lang_en']) ?></a>
      </div>
      <a class="header-cta" href="<?= htmlspecialchars(sb_query_lang($lang, 'classes')) ?>"><?= htmlspecialchars($ui['header_cta']) ?></a>
    </div>
    <div class="header-actions">
      <a class="header-cta" href="<?= htmlspecialchars(sb_query_lang($lang, 'classes')) ?>"><?= htmlspecialchars($ui['header_cta']) ?></a>
      <button
        class="menu-toggle"
        id="menu-toggle"
        type="button"
        aria-expanded="false"
        aria-controls="mobile-menu"
        aria-label="Open navigation menu"
      >
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
      <a href="<?= htmlspecialchars(sb_query_lang($lang, 'classes')) ?>"><?= htmlspecialchars($ui['nav_classes']) ?></a>
      <a href="<?= htmlspecialchars(sb_query_lang($lang, 'events')) ?>"><?= htmlspecialchars($ui['nav_events']) ?></a>
      <a href="<?= htmlspecialchars(sb_query_lang($lang, 'shop')) ?>"><?= htmlspecialchars($ui['nav_shop']) ?></a>
      <a href="<?= htmlspecialchars(sb_query_lang($lang, 'founder')) ?>"><?= htmlspecialchars($ui['nav_founder']) ?></a>
      <a href="<?= htmlspecialchars(sb_query_lang($lang, 'contact')) ?>"><?= htmlspecialchars($ui['nav_contact']) ?></a>
    </nav>
    <div class="header-tools">
      <div class="lang-switch" aria-label="<?= htmlspecialchars($ui['language_label']) ?>">
        <a class="<?= $lang === 'bg' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_query_lang('bg')) ?>" data-lang-link="bg"><?= htmlspecialchars($ui['lang_bg']) ?></a>
        <a class="<?= $lang === 'en' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_query_lang('en')) ?>" data-lang-link="en"><?= htmlspecialchars($ui['lang_en']) ?></a>
      </div>
      <a class="header-cta header-cta-drawer" href="<?= htmlspecialchars(sb_query_lang($lang, 'classes')) ?>"><?= htmlspecialchars($ui['header_cta']) ?></a>
    </div>
  </aside>

  <main id="top">
    <section
      class="hero<?= ($hasSelfHostedHeroVideo || $heroVideoId) ? ' hero-has-video' : '' ?>"
      data-hero-text-mode="<?= htmlspecialchars($heroTextMode) ?>"
      data-hero-tone="<?= htmlspecialchars($heroTextMode === 'auto' ? 'dark' : $heroTextMode) ?>"
      <?= $heroVideoPoster !== '' ? ' style="--hero-poster:url(\'' . htmlspecialchars($heroVideoPoster) . '\')"' : '' ?>
    >
      <?php if ($hasSelfHostedHeroVideo): ?>
        <div class="hero-background" aria-hidden="true">
          <video class="hero-bg-video" autoplay muted loop playsinline preload="metadata" poster="<?= htmlspecialchars($heroVideoPoster) ?>">
            <source src="<?= htmlspecialchars($selfHostedHeroVideo) ?>">
          </video>
        </div>
      <?php elseif ($heroVideoId): ?>
        <div class="hero-background" aria-hidden="true"></div>
      <?php endif; ?>
      <div class="hero-inner">
      <div class="hero-copy">
        <p class="eyebrow"><?= htmlspecialchars($ui['hero_eyebrow']) ?></p>
        <h1><?= htmlspecialchars($brand['headline']) ?></h1>
        <p class="hero-text"><?= htmlspecialchars($brand['intro']) ?></p>
        <div class="hero-actions">
          <a class="btn btn-primary" href="<?= htmlspecialchars(sb_query_lang($lang, 'classes')) ?>"><?= htmlspecialchars($ui['hero_primary_cta']) ?></a>
          <a class="btn btn-secondary" href="<?= htmlspecialchars(sb_query_lang($lang, 'shop')) ?>"><?= htmlspecialchars($ui['hero_secondary_cta']) ?></a>
        </div>
        <p class="hero-note"><?= htmlspecialchars($ui['cta_note']) ?></p>
      </div>
      <div class="hero-visual">
        <?php if ($heroVideoId && !$hasSelfHostedHeroVideo): ?>
          <div
            class="hero-video-shell js-hero-video"
            data-youtube-id="<?= htmlspecialchars($heroVideoId) ?>"
            style="<?= $heroVideoPoster !== '' ? 'background-image:url(\'' . htmlspecialchars($heroVideoPoster) . '\')' : '' ?>"
          >
            <button class="hero-video-trigger" type="button" aria-label="Play introduction video">
              <span class="hero-video-play"></span>
              <span class="hero-video-label"><?= htmlspecialchars($brand['founder_name']) ?></span>
            </button>
          </div>
        <?php else: ?>
          <div class="hero-panel hero-panel-large" <?= $founderImageUrl !== '' ? 'style="background-image:url(\'' . htmlspecialchars($founderImageUrl) . '\')"' : '' ?>>
            <span><?= htmlspecialchars($brand['founder_name']) ?></span>
          </div>
        <?php endif; ?>
        <div class="hero-panel hero-panel-small">
          <span><?= htmlspecialchars($brand['founder_title']) ?></span>
        </div>
        <div class="hero-glow"></div>
      </div>
      </div>
    </section>

    <section class="intro-band">
      <p><?= htmlspecialchars($brand['subintro']) ?></p>
      <div class="intro-stats">
        <div>
          <strong><?= count($classes) ?></strong>
          <span><?= htmlspecialchars($ui['stats_classes']) ?></span>
        </div>
        <div>
          <strong><?= count($events) ?></strong>
          <span><?= htmlspecialchars($ui['stats_events']) ?></span>
        </div>
        <div>
          <strong><?= count($products) ?></strong>
          <span><?= htmlspecialchars($ui['stats_products']) ?></span>
        </div>
      </div>
    </section>

    <section class="approach-section">
      <div class="section-heading">
        <p class="eyebrow"><?= htmlspecialchars($ui['approach_eyebrow']) ?></p>
        <h2><?= htmlspecialchars($ui['approach_heading']) ?></h2>
      </div>
      <div class="approach-grid">
        <article class="approach-card">
          <h3><?= htmlspecialchars($ui['approach_one_title']) ?></h3>
          <p><?= htmlspecialchars($ui['approach_one_body']) ?></p>
        </article>
        <article class="approach-card">
          <h3><?= htmlspecialchars($ui['approach_two_title']) ?></h3>
          <p><?= htmlspecialchars($ui['approach_two_body']) ?></p>
        </article>
        <article class="approach-card">
          <h3><?= htmlspecialchars($ui['approach_three_title']) ?></h3>
          <p><?= htmlspecialchars($ui['approach_three_body']) ?></p>
        </article>
      </div>
    </section>

    <section class="section-grid" id="classes">
      <div class="section-heading">
        <p class="eyebrow"><?= htmlspecialchars($ui['classes_eyebrow']) ?></p>
        <h2><?= htmlspecialchars($ui['classes_heading']) ?></h2>
      </div>
      <div class="listing-grid">
        <?php foreach ($classes as $class): ?>
          <?php
            $classSchedules = shine_bright_class_schedules($class);
            $classScheduleSummary = shine_bright_class_schedule_summary($class, $lang, 2);
            $classUpcomingSessions = shine_bright_class_upcoming_sessions($class, $lang, 21, 6);
            $primarySchedule = $classSchedules[0] ?? null;
            $primaryLocation = (string) ($primarySchedule['location'] ?? '');
            $primaryMapsUrl = sb_maps_link($primarySchedule['maps_url'] ?? null);
            $scheduleOptions = array_map(static function (array $session): array {
                return [
                    'id' => (string) ($session['id'] ?? ''),
                    'label' => trim((string) ($session['summary_label'] ?? '')),
                ];
            }, $classUpcomingSessions);
          ?>
          <article class="listing-card class-card">
            <div class="listing-meta class-card-top">
              <?php foreach ($classScheduleSummary as $scheduleLabel): ?>
                <span class="meta-chip"><?= htmlspecialchars($scheduleLabel) ?></span>
              <?php endforeach; ?>
            </div>
            <h3><a class="card-title-link" href="<?= htmlspecialchars(sb_item_path('classes', (string) $class['id'], $lang)) ?>"><?= htmlspecialchars($class['title']) ?></a></h3>
            <p class="class-card-summary"><?= htmlspecialchars($class['description']) ?></p>
            <div class="class-card-facts">
              <span class="fact-pill"><strong><?= htmlspecialchars($ui['duration']) ?></strong><?= htmlspecialchars(sb_duration_label($class, $lang)) ?></span>
              <?php if ($primaryMapsUrl !== ''): ?>
                <a class="fact-pill fact-pill-link" href="<?= htmlspecialchars($primaryMapsUrl) ?>" target="_blank" rel="noopener noreferrer">
                  <strong><?= htmlspecialchars($ui['location']) ?></strong><?= htmlspecialchars($primaryLocation) ?>
                </a>
              <?php else: ?>
                <span class="fact-pill"><strong><?= htmlspecialchars($ui['location']) ?></strong><?= htmlspecialchars($primaryLocation) ?></span>
              <?php endif; ?>
              <span class="fact-pill"><strong><?= htmlspecialchars($ui['level']) ?></strong><?= htmlspecialchars($class['level']) ?></span>
            </div>
            <a class="text-link card-inline-link" href="<?= htmlspecialchars(sb_item_path('classes', (string) $class['id'], $lang)) ?>"><?= htmlspecialchars($ui['view_details']) ?></a>
            <div class="listing-footer">
              <strong class="price-tag"><?= htmlspecialchars(sb_price_label($class)) ?></strong>
              <button
                class="btn btn-tertiary listing-cta js-open-inquiry"
                data-type="class"
                data-id="<?= htmlspecialchars($class['id']) ?>"
                data-title="<?= htmlspecialchars($class['title']) ?>"
                data-datetime="<?= htmlspecialchars(implode(' · ', $classScheduleSummary)) ?>"
                data-location="<?= htmlspecialchars($primaryLocation) ?>"
                data-schedules='<?= htmlspecialchars(json_encode($scheduleOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
              >
                <?= htmlspecialchars($ui['reserve_spot']) ?>
              </button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="feature-story" id="events">
      <div class="feature-story-copy">
        <p class="eyebrow"><?= htmlspecialchars($ui['events_eyebrow']) ?></p>
        <h2><?= htmlspecialchars($ui['events_heading']) ?></h2>
        <p><?= htmlspecialchars($ui['events_intro']) ?></p>
      </div>
      <div class="event-stack">
        <?php foreach ($events as $event): ?>
          <article class="event-row special-event-card">
            <div>
              <p class="event-date"><span class="event-date-chip"><?= htmlspecialchars(sb_format_date_range($event, $lang)) ?> · <?= htmlspecialchars(sb_format_time_range($event)) ?></span></p>
              <h3><a class="card-title-link" href="<?= htmlspecialchars(sb_item_path('events', (string) $event['id'], $lang)) ?>"><?= htmlspecialchars($event['title']) ?></a></h3>
              <p><?= htmlspecialchars($event['description']) ?></p>
              <?php $eventMapsUrl = sb_maps_link($event['maps_url'] ?? null); ?>
              <div class="event-meta-row">
                <span class="event-mini-chip"><?= htmlspecialchars(sb_duration_label($event, $lang)) ?></span>
                <a class="text-link event-inline-link" href="<?= htmlspecialchars(sb_item_path('events', (string) $event['id'], $lang)) ?>"><?= htmlspecialchars($ui['view_details']) ?></a>
              </div>
              <p class="event-location">
                <?php if ($eventMapsUrl !== ''): ?>
                  <a class="location-link" href="<?= htmlspecialchars($eventMapsUrl) ?>" target="_blank" rel="noopener noreferrer">
                    <span><?= htmlspecialchars($event['location']) ?></span>
                    <small><?= htmlspecialchars($ui['open_maps']) ?></small>
                  </a>
                <?php else: ?>
                  <?= htmlspecialchars($event['location']) ?>
                <?php endif; ?>
              </p>
            </div>
            <div class="event-actions">
              <div class="event-summary">
                <strong><?= htmlspecialchars(sb_price_label($event)) ?></strong>
                <span><?= htmlspecialchars(sb_duration_label($event, $lang)) ?></span>
              </div>
              <button
                class="btn btn-tertiary event-cta js-open-inquiry"
                data-type="event"
                data-id="<?= htmlspecialchars($event['id']) ?>"
                data-title="<?= htmlspecialchars($event['title']) ?>"
                data-datetime="<?= htmlspecialchars(sb_format_date_range($event, $lang) . ' · ' . sb_format_time_range($event)) ?>"
                data-location="<?= htmlspecialchars($event['location']) ?>"
              >
                <?= htmlspecialchars($ui['join_event']) ?>
              </button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="section-grid shop-section" id="shop">
      <div class="section-heading">
        <p class="eyebrow"><?= htmlspecialchars($ui['shop_eyebrow']) ?></p>
        <h2><?= htmlspecialchars($ui['shop_heading']) ?></h2>
      </div>
      <div class="product-grid">
        <?php foreach ($products as $index => $product): ?>
          <article class="product-card curated-product-card">
            <div class="product-media"<?= shine_bright_product_media_style($product) !== '' ? ' style="' . shine_bright_product_media_style($product) . '"' : '' ?>></div>
            <div class="product-copy">
            <div class="product-chip-row">
              <p class="product-category"><?= htmlspecialchars($product['category']) ?></p>
              <?php if (($product['detail'] ?? '') !== ''): ?>
                <p class="product-use-chip"><?= htmlspecialchars($product['detail']) ?></p>
              <?php endif; ?>
            </div>
            <h3><a class="card-title-link" href="<?= htmlspecialchars(sb_item_path('products', (string) $product['id'], $lang)) ?>"><?= htmlspecialchars($product['title']) ?></a></h3>
            <p class="product-summary"><?= htmlspecialchars((string) (($product['short_description'] ?? '') !== '' ? $product['short_description'] : $product['description'])) ?></p>
            <div class="listing-footer">
              <div class="product-footer-meta">
                <strong class="product-price-tag"><?= htmlspecialchars(sb_price_label($product)) ?></strong>
                <a class="text-link product-inline-link" href="<?= htmlspecialchars(sb_item_path('products', (string) $product['id'], $lang)) ?>"><?= htmlspecialchars($ui['view_details']) ?></a>
              </div>
              <button
                class="btn btn-tertiary product-cta js-open-inquiry"
                data-type="product"
                data-id="<?= htmlspecialchars($product['id']) ?>"
                data-title="<?= htmlspecialchars($product['title']) ?>"
              >
                <?= htmlspecialchars($ui['order_product']) ?>
              </button>
            </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="founder-section" id="founder">
      <div class="founder-portrait" <?= $founderImageUrl !== '' ? 'style="background-image:url(\'' . htmlspecialchars($founderImageUrl) . '\')"' : '' ?> aria-hidden="true"></div>
      <div class="founder-copy">
        <p class="eyebrow"><?= htmlspecialchars($ui['founder_eyebrow']) ?></p>
        <h2><?= htmlspecialchars($brand['founder_name']) ?></h2>
        <p class="founder-role"><?= htmlspecialchars($brand['founder_title']) ?></p>
        <p><?= htmlspecialchars($brand['founder_story']) ?></p>
      </div>
    </section>

    <section class="testimonial-strip">
      <?php foreach ($testimonials as $testimonial): ?>
        <blockquote class="testimonial">
          <p>“<?= htmlspecialchars($testimonial['quote']) ?>”</p>
          <footer><?= htmlspecialchars($testimonial['name']) ?></footer>
        </blockquote>
      <?php endforeach; ?>
    </section>

    <section class="contact-section" id="contact">
      <div class="contact-copy">
        <p class="eyebrow"><?= htmlspecialchars($ui['contact_eyebrow']) ?></p>
        <h2><?= htmlspecialchars($ui['contact_heading']) ?></h2>
        <p class="contact-body"><?= htmlspecialchars($ui['contact_body']) ?></p>
      </div>
      <div class="contact-actions">
        <a class="btn btn-primary" href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $brand['contact_phone'])) ?>"><?= htmlspecialchars($ui['phone_cta']) ?><span class="btn-subline"><?= htmlspecialchars($brand['contact_phone']) ?></span></a>
        <?php if ($brand['contact_email'] !== ''): ?>
          <a class="btn btn-secondary" href="mailto:<?= htmlspecialchars($brand['contact_email']) ?>"><?= htmlspecialchars($brand['contact_email']) ?></a>
        <?php endif; ?>
        <?php if ($brand['instagram'] !== ''): ?>
          <a class="text-link" href="<?= htmlspecialchars($brand['instagram']) ?>" target="_blank" rel="noopener noreferrer">Instagram</a>
        <?php endif; ?>
        <button
          class="btn btn-secondary js-open-inquiry"
          type="button"
          data-type="general"
          data-id="contact"
          data-title="<?= htmlspecialchars($brand['name']) ?>"
        >
          <?= htmlspecialchars($ui['contact_inquiry_cta']) ?>
        </button>
      </div>
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
      var siteHeader = document.getElementById('site-header');
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

      function setError(field, message) {
        if (field) {
          field.setAttribute('aria-invalid', 'true');
        }
        feedback.textContent = message;
      }

      function clearErrors() {
        [nameInput, emailInput, phoneInput, quantityInput, scheduleInput, messageInput].forEach(function (field) {
          if (field) {
            field.removeAttribute('aria-invalid');
          }
        });
        feedback.textContent = '';
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

      function updateLangLinks() {
        var hash = window.location.hash || '';
        document.querySelectorAll('[data-lang-link]').forEach(function (link) {
          var langTarget = link.getAttribute('data-lang-link');
          link.href = '/?lang=' + langTarget + hash;
        });
      }

      function syncHeaderState() {
        if (!siteHeader) {
          return;
        }

        siteHeader.classList.toggle('is-scrolled', window.scrollY > 24);
      }

      syncHeaderState();
      window.addEventListener('scroll', syncHeaderState, { passive: true });
      updateLangLinks();
      window.addEventListener('hashchange', updateLangLinks);

      function setMenuState(isOpen) {
        if (!menuToggle || !mobileMenu || !mobileMenuBackdrop) {
          return;
        }

        menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        mobileMenu.hidden = !isOpen;
        mobileMenuBackdrop.hidden = !isOpen;
        document.body.classList.toggle('menu-open', isOpen);
      }

      if (menuToggle && mobileMenu && mobileMenuBackdrop) {
        menuToggle.addEventListener('click', function () {
          setMenuState(menuToggle.getAttribute('aria-expanded') !== 'true');
        });

        if (mobileMenuClose) {
          mobileMenuClose.addEventListener('click', function () {
            setMenuState(false);
          });
        }

        mobileMenuBackdrop.addEventListener('click', function () {
          setMenuState(false);
        });

        mobileMenu.querySelectorAll('a').forEach(function (link) {
          link.addEventListener('click', function () {
            setMenuState(false);
          });
        });

        window.addEventListener('keydown', function (event) {
          if (event.key === 'Escape') {
            setMenuState(false);
          }
        });
      }

      function heroToneFromLuminance(luminance) {
        return luminance < 150 ? 'light' : 'dark';
      }

      function setHeroTone(hero, tone) {
        if (!hero || (tone !== 'light' && tone !== 'dark')) {
          return;
        }
        hero.setAttribute('data-hero-tone', tone);
      }

      function sampleCanvasTone(source, width, height, cropX, cropY, cropWidth, cropHeight) {
        var canvas = document.createElement('canvas');
        canvas.width = 48;
        canvas.height = 48;
        var ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (!ctx) {
          return null;
        }

        try {
          ctx.drawImage(source, cropX, cropY, cropWidth, cropHeight, 0, 0, canvas.width, canvas.height);
          var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
          var total = 0;
          var count = 0;
          for (var i = 0; i < imageData.length; i += 4) {
            var alpha = imageData[i + 3] / 255;
            if (alpha < 0.1) {
              continue;
            }
            var red = imageData[i];
            var green = imageData[i + 1];
            var blue = imageData[i + 2];
            total += 0.2126 * red + 0.7152 * green + 0.0722 * blue;
            count += 1;
          }
          if (!count) {
            return null;
          }
          return heroToneFromLuminance(total / count);
        } catch (error) {
          return null;
        }
      }

      function applyAutoHeroTone(hero) {
        if (!hero || hero.getAttribute('data-hero-text-mode') !== 'auto') {
          return;
        }

        var video = hero.querySelector('.hero-bg-video');
        if (video && video.readyState >= 2 && video.videoWidth > 0 && video.videoHeight > 0) {
          var videoTone = sampleCanvasTone(
            video,
            video.videoWidth,
            video.videoHeight,
            0,
            video.videoHeight * 0.08,
            video.videoWidth * 0.44,
            video.videoHeight * 0.82
          );
          if (videoTone) {
            setHeroTone(hero, videoTone);
            return;
          }
        }

        var heroBackground = hero.querySelector('.hero-background');
        if (!heroBackground) {
          return;
        }

        var backgroundImage = window.getComputedStyle(heroBackground).backgroundImage || '';
        var matches = backgroundImage.match(/url\((["']?)(.*?)\1\)/);
        if (!matches || !matches[2]) {
          return;
        }

        var image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = function () {
          var imageTone = sampleCanvasTone(
            image,
            image.naturalWidth,
            image.naturalHeight,
            0,
            image.naturalHeight * 0.08,
            image.naturalWidth * 0.44,
            image.naturalHeight * 0.82
          );
          if (imageTone) {
            setHeroTone(hero, imageTone);
          }
        };
        image.src = matches[2];
      }

      var hero = document.querySelector('.hero[data-hero-text-mode]');
      if (hero) {
        applyAutoHeroTone(hero);
        var heroVideo = hero.querySelector('.hero-bg-video');
        if (heroVideo) {
          ['loadeddata', 'canplay', 'playing'].forEach(function (eventName) {
            heroVideo.addEventListener(eventName, function () {
              applyAutoHeroTone(hero);
            }, { passive: true });
          });
        }
      }

      function dialogConfig(kind, itemTitle) {
        if (kind === 'class') {
          return {
            kind: 'class',
            title: copy.reserveDialogTitle + itemTitle,
            submit: copy.reserveSubmit,
            placeholder: copy.placeholderClass,
            success: copy.successClass,
            needsQuantity: false,
            typeLabel: copy.typeLabelClass,
            context: copy.reserveContext,
            icon: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7.5C7 5.57 8.57 4 10.5 4S14 5.57 14 7.5 12.43 11 10.5 11 7 9.43 7 7.5Zm8.5 12.5h-10a1 1 0 0 1-.89-1.45l2.01-3.98A2 2 0 0 1 8.4 13.5h4.2a2 2 0 0 1 1.78 1.07l2.01 3.98A1 1 0 0 1 15.5 20Zm1.75-10.25a2.75 2.75 0 1 0 0-5.5 2.75 2.75 0 0 0 0 5.5Zm-.25 3.25h1.02a1.7 1.7 0 0 1 1.5.86l1.14 2.02a.9.9 0 0 1-.78 1.34h-1.88"/></svg>'
          };
        }
        if (kind === 'event') {
          return {
            kind: 'event',
            title: copy.eventDialogTitle + itemTitle,
            submit: copy.eventSubmit,
            placeholder: copy.placeholderEvent,
            success: copy.successEvent,
            needsQuantity: false,
            typeLabel: copy.typeLabelEvent,
            context: copy.eventContext,
            icon: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v2M17 3v2M4.5 9.5h15M6 5.5h12A1.5 1.5 0 0 1 19.5 7v11A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V7A1.5 1.5 0 0 1 6 5.5Zm2.5 7h3v3h-3z"/></svg>'
          };
        }
        if (kind === 'product') {
          return {
            kind: 'product',
            title: copy.productDialogTitle + itemTitle,
            submit: copy.productSubmit,
            placeholder: copy.placeholderProduct,
            success: copy.successProduct,
            needsQuantity: true,
            typeLabel: copy.typeLabelProduct,
            context: copy.productContext,
            icon: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8V6.75A4.75 4.75 0 0 1 11.75 2h.5A4.75 4.75 0 0 1 17 6.75V8h1a1.5 1.5 0 0 1 1.5 1.5v9A2.5 2.5 0 0 1 17 21H7a2.5 2.5 0 0 1-2.5-2.5v-9A1.5 1.5 0 0 1 6 8h1Zm2 0h6V6.75A2.75 2.75 0 0 0 12.25 4h-.5A2.75 2.75 0 0 0 9 6.75V8Z"/></svg>'
          };
        }
        return {
          kind: 'general',
          title: copy.generalDialogTitle + itemTitle,
          submit: copy.generalSubmit,
          placeholder: copy.placeholderDefault,
          success: copy.success,
          needsQuantity: false,
          typeLabel: copy.typeLabelGeneral,
          context: copy.generalContext,
          icon: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 6.5A1.5 1.5 0 0 1 6 5h12a1.5 1.5 0 0 1 1.5 1.5v11A1.5 1.5 0 0 1 18 19H6a1.5 1.5 0 0 1-1.5-1.5v-11Zm1.9.5L12 11.3 17.6 7H6.4Zm11.1 1.26-4.9 3.77a1 1 0 0 1-1.22 0L6.5 8.26V17h11V8.26Z"/></svg>'
        };
      }

      function validEmail(value) {
        return value === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
      }

      function validPhone(value) {
        return value === '' || /^[0-9+\s().-]{6,}$/.test(value);
      }

      function syncQuantityField(needsQuantity) {
        if (!quantityLabel || !quantityInput) {
          return;
        }
        quantityLabel.hidden = !needsQuantity;
        quantityLabel.setAttribute('aria-hidden', needsQuantity ? 'false' : 'true');
        quantityLabel.classList.toggle('is-hidden', !needsQuantity);
        quantityInput.disabled = !needsQuantity;
        if (!needsQuantity) {
          quantityInput.value = '1';
        }
      }

      function syncScheduleField(button, kind) {
        if (!scheduleLabel || !scheduleInput) {
          return;
        }

        scheduleInput.innerHTML = '';
        var hasSchedules = false;
        if (kind === 'class') {
          var schedules = [];
          try {
            schedules = JSON.parse(button.getAttribute('data-schedules') || '[]');
          } catch (error) {
            schedules = [];
          }

          hasSchedules = Array.isArray(schedules) && schedules.length > 0;
          if (hasSchedules) {
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
        }

        scheduleLabel.hidden = !hasSchedules;
        scheduleLabel.setAttribute('aria-hidden', hasSchedules ? 'false' : 'true');
        scheduleLabel.classList.toggle('is-hidden', !hasSchedules);
        scheduleInput.disabled = !hasSchedules;
        scheduleInput.required = hasSchedules;
      }

      function syncContextDetails(button, kind) {
        if (!dialogContextDetails || !dialogContextDateTime || !dialogContextLocation) {
          return;
        }
        var dateTime = button.getAttribute('data-datetime') || '';
        var location = button.getAttribute('data-location') || '';
        var showDetails = (kind === 'class' || kind === 'event') && (dateTime !== '' || location !== '');
        dialogContextDetails.hidden = !showDetails;
        if (!showDetails) {
          dialogContextDateTime.textContent = '';
          dialogContextLocation.textContent = '';
          return;
        }
        dialogContextDateTime.textContent = dateTime;
        dialogContextLocation.textContent = location;
      }

      function openDialog(button) {
        var kind = button.getAttribute('data-type') || 'general';
        var itemId = button.getAttribute('data-id') || '';
        var itemTitle = button.getAttribute('data-title') || <?= json_encode($brand['name']) ?>;
        var config = dialogConfig(kind, itemTitle);
        typeInput.value = kind;
        itemIdInput.value = itemId;
        itemTitleInput.value = itemTitle;
        title.textContent = config.title;
        submitButton.textContent = config.submit;
        if (dialogContext) {
          dialogContext.hidden = false;
          dialogContext.dataset.kind = config.kind;
          if (dialogContextIcon) {
            dialogContextIcon.innerHTML = config.icon;
          }
          dialogContextType.textContent = config.typeLabel;
          dialogContextItem.textContent = itemTitle;
          dialogContextCopy.textContent = config.context;
          syncContextDetails(button, kind);
        }
        if (messageInput) {
          messageInput.placeholder = config.placeholder;
        }
        syncQuantityField(config.needsQuantity);
        syncScheduleField(button, kind);
        clearErrors();
        form.reset();
        typeInput.value = kind;
        itemIdInput.value = itemId;
        itemTitleInput.value = itemTitle;
        syncQuantityField(config.needsQuantity);
        syncScheduleField(button, kind);
        if (dialog && typeof dialog.showModal === 'function') {
          dialog.showModal();
        }
      }

      document.querySelectorAll('.js-open-inquiry').forEach(function (button) {
        button.addEventListener('click', function () {
          openDialog(button);
        });
      });

      function closeDialog() {
        if (dialog && dialog.open) {
          dialog.close();
        }
      }

      document.getElementById('dialog-close').addEventListener('click', closeDialog);
      document.getElementById('dialog-cancel').addEventListener('click', closeDialog);

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        clearErrors();

        if (!nameInput.value.trim()) {
          setError(nameInput, requiredNameMessage);
          nameInput.focus();
          return;
        }

        if (!emailInput.value.trim() && !phoneInput.value.trim()) {
          setError(phoneInput, contactRequiredMessage);
          phoneInput.focus();
          return;
        }

        if (scheduleInput && !scheduleInput.disabled && !scheduleInput.value) {
          setError(scheduleInput, scheduleRequiredMessage);
          scheduleInput.focus();
          return;
        }

        if (!validEmail(emailInput.value.trim())) {
          setError(emailInput, copy.errorInvalidEmail);
          emailInput.focus();
          return;
        }

        if (!validPhone(phoneInput.value.trim())) {
          setError(phoneInput, copy.errorInvalidPhone);
          phoneInput.focus();
          return;
        }

        feedback.textContent = copy.sending;
        submitButton.disabled = true;

        var payload = new FormData(form);
        payload.set('source_path', window.location.pathname + window.location.search + window.location.hash);
        fetch('/submit.php', {
          method: 'POST',
          body: payload
        })
          .then(function (response) {
            return response.json().then(function (data) {
              if (!response.ok || !data.ok) {
                var requestError = new Error(data.error || copy.errorDefault);
                requestError.field = data.field || '';
                throw requestError;
              }
              return data;
            });
          })
          .then(function (data) {
            var kind = typeInput.value || 'general';
            var config = dialogConfig(kind, itemTitleInput.value || <?= json_encode($brand['name']) ?>);
            if (kind === 'class' && data && data.reservation) {
              feedback.textContent = '';
              showClassSuccessCard(data);
            } else {
              feedback.textContent = config.success;
            }
            form.reset();
            syncQuantityField(config.needsQuantity);
            syncScheduleField({ getAttribute: function () { return '[]'; } }, 'general');
            if (kind !== 'class') {
              window.setTimeout(closeDialog, 1000);
            }
          })
          .catch(function (error) {
            feedback.textContent = error.message;
            if (error.field === 'email' && emailInput) {
              emailInput.setAttribute('aria-invalid', 'true');
              emailInput.focus();
            }
            if (error.field === 'phone' && phoneInput) {
              phoneInput.setAttribute('aria-invalid', 'true');
              phoneInput.focus();
            }
          })
          .finally(function () {
            submitButton.disabled = false;
          });
      });

      document.querySelectorAll('.js-hero-video').forEach(function (node) {
        var button = node.querySelector('.hero-video-trigger');
        if (!button) {
          return;
        }

        button.addEventListener('click', function () {
          var id = node.getAttribute('data-youtube-id');
          if (!id) {
            return;
          }

          var iframe = document.createElement('iframe');
          iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&mute=1&playsinline=1&rel=0&modestbranding=1';
          iframe.title = <?= json_encode(($brand['name'] ?? 'Shine Bright Yoga') . ' hero video') ?>;
          iframe.loading = 'lazy';
          iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
          iframe.allowFullscreen = true;
          node.innerHTML = '';
          node.appendChild(iframe);
          node.classList.add('is-playing');
        }, { once: true });
      });
      syncQuantityField(false);
      syncScheduleField({ getAttribute: function () { return '[]'; } }, 'general');
    })();
  </script>
</body>
</html>
