<?php
require_once __DIR__ . '/content.php';

$lang = shine_bright_resolve_lang();
$content = shine_bright_load_content();
$brand = $content[$lang]['brand'] ?? $content['bg']['brand'];
$student = shine_bright_require_student_auth();
$packs = array_values(array_filter(shine_bright_load_visit_packs(), static fn (array $pack): bool => (string) ($pack['client_id'] ?? '') === (string) ($student['id'] ?? '')));
$classes = shine_bright_content_section_items($content, $lang, 'classes');

function sb_student_class(array $classes, string $classId): ?array
{
    foreach ($classes as $class) {
        if ((string) ($class['id'] ?? '') === $classId) {
            return $class;
        }
    }
    return null;
}

function sb_student_classes(array $classes, array $classIds): array
{
    $results = [];
    foreach ($classIds as $classId) {
        $class = sb_student_class($classes, $classId);
        if ($class) {
            $results[] = $class;
        }
    }
    return $results;
}

function sb_student_status_label(string $lang, string $status): string
{
    if ($lang === 'en') {
        return ucfirst($status);
    }

    return match ($status) {
        'active' => 'Активна',
        'completed' => 'Приключена',
        'expired' => 'Изтекла',
        'cancelled' => 'Отказана',
        default => $status,
    };
}

function sb_student_expiry_label(string $lang, string $expiresOn): string
{
    $expiresOn = trim($expiresOn);
    if ($expiresOn === '') {
        return $lang === 'en' ? 'No expiry' : 'Без срок';
    }

    try {
        $today = new DateTimeImmutable('today');
        $expiry = new DateTimeImmutable($expiresOn);
        $daysLeft = (int) $today->diff($expiry)->format('%r%a');

        if ($daysLeft < 0) {
            return $lang === 'en' ? 'Expired' : 'Изтекла';
        }

        if ($daysLeft === 0) {
            return $lang === 'en' ? 'Last day today' : 'Последен ден днес';
        }

        if ($lang === 'en') {
            return $daysLeft === 1 ? '1 day left' : $daysLeft . ' days left';
        }

        return $daysLeft === 1 ? 'Остава 1 ден' : 'Остават ' . $daysLeft . ' дни';
    } catch (Throwable $e) {
        return $expiresOn;
    }
}

function sb_student_weekday_index(string $weekday): int
{
    return match (strtolower(trim($weekday))) {
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6,
        'sunday' => 7,
        default => 1,
    };
}

function sb_student_next_schedule(array $classes, array $allowedClassIds): ?array
{
    $scopeClasses = $allowedClassIds !== [] ? sb_student_classes($classes, $allowedClassIds) : $classes;
    if ($scopeClasses === []) {
        return null;
    }

    $tz = new DateTimeZone('Europe/Sofia');
    $now = new DateTimeImmutable('now', $tz);
    $best = null;

    foreach ($scopeClasses as $class) {
        foreach (shine_bright_class_schedules($class) as $schedule) {
            $weekday = sb_student_weekday_index((string) ($schedule['weekday'] ?? ''));
            $startTime = trim((string) ($schedule['start_time'] ?? ''));
            if ($startTime === '') {
                continue;
            }

            [$hour, $minute] = array_pad(explode(':', $startTime, 2), 2, '00');
            $daysAhead = ($weekday - (int) $now->format('N') + 7) % 7;
            $candidate = $now
                ->setTime((int) $hour, (int) $minute)
                ->modify('+' . $daysAhead . ' days');

            if ($candidate <= $now) {
                $candidate = $candidate->modify('+7 days');
            }

            if ($best === null || $candidate < $best['starts_at']) {
                $best = [
                    'starts_at' => $candidate,
                    'class' => $class,
                    'schedule' => $schedule,
                ];
            }
        }
    }

    return $best;
}

function sb_student_next_schedule_label(string $lang, array $nextSchedule): string
{
    /** @var DateTimeImmutable $startsAt */
    $startsAt = $nextSchedule['starts_at'];
    $class = $nextSchedule['class'];
    $schedule = $nextSchedule['schedule'];
    $dayLabel = shine_bright_class_schedule_day_label($schedule, $lang);
    $timeLabel = shine_bright_class_schedule_time_label($schedule);
    $title = trim((string) ($class['title'] ?? ''));

    return trim($title . ' · ' . $dayLabel . ($timeLabel !== '' ? ' · ' . $timeLabel : '') . ' · ' . $startsAt->format('d.m'));
}

function sb_student_polar_point(float $cx, float $cy, float $radius, float $angle): array
{
    $radians = deg2rad($angle - 90);
    return [
        'x' => $cx + ($radius * cos($radians)),
        'y' => $cy + ($radius * sin($radians)),
    ];
}

function sb_student_arc_path(float $cx, float $cy, float $radius, float $startAngle, float $endAngle): string
{
    $start = sb_student_polar_point($cx, $cy, $radius, $startAngle);
    $end = sb_student_polar_point($cx, $cy, $radius, $endAngle);
    $largeArc = ($endAngle - $startAngle) > 180 ? 1 : 0;

    return sprintf(
        'M %.3F %.3F A %.3F %.3F 0 %d 1 %.3F %.3F',
        $start['x'],
        $start['y'],
        $radius,
        $radius,
        $largeArc,
        $end['x'],
        $end['y']
    );
}

?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="manifest" href="./manifest.webmanifest">
  <link rel="icon" href="./assets/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="./assets/app-icons/icon-180.png">
  <meta name="theme-color" content="#6b816f">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="SB Card">
  <title><?= htmlspecialchars(($lang === 'en' ? 'Your Visit Cards' : 'Вашите карти') . ' | ' . ($brand['name'] ?? 'Shine Bright Yoga')) ?></title>
  <style>
    :root{--bg:#f3f5ef;--surface:#fff;--ink:#1d251f;--muted:#5f6b62;--outline:rgba(93,118,102,.14);--primary:#6b816f;--secondary:#eef2ea;--used:#dfe6df}
    *{box-sizing:border-box}
    body{margin:0;font-family:ui-sans-serif,system-ui,sans-serif;background:linear-gradient(180deg,#f4f6f1 0%,#edf1eb 100%);color:var(--ink)}
    .wrap{width:min(1080px,calc(100% - 32px));margin:40px auto 56px}
    .top{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:24px}
    .card{background:var(--surface);border:1px solid var(--outline);border-radius:32px;padding:32px;box-shadow:0 28px 72px rgba(24,35,27,.06)}
    .install-card{display:grid;gap:12px;padding:18px 20px;border-radius:24px;background:linear-gradient(135deg,#eff5ee 0%,#f8faf6 100%);border:1px solid rgba(93,118,102,.16);box-shadow:0 16px 40px rgba(24,35,27,.05)}
    .install-card[hidden]{display:none}
    .install-title{display:flex;align-items:center;gap:10px;font-weight:800;font-size:1.06rem}
    .install-title::before{content:"";width:18px;height:18px;display:inline-block;background:currentColor;opacity:.78;-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 3 4 7v6c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V7l-8-4Zm1 6v3h3v2h-3v3h-2v-3H8v-2h3V9h2Z'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 3 4 7v6c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V7l-8-4Zm1 6v3h3v2h-3v3h-2v-3H8v-2h3V9h2Z'/%3E%3C/svg%3E") center/contain no-repeat}
    .grid{display:grid;gap:18px}
    h1{margin:0 0 14px;font-size:clamp(2.3rem,4.5vw,3.8rem);line-height:.98;letter-spacing:-.04em;font-weight:800}
    h2,h3{margin:0}
    p{line-height:1.6;color:var(--muted)}
    .pill{display:inline-flex;padding:7px 12px;border-radius:999px;background:var(--secondary);color:#5d7666;font-weight:700;font-size:.84rem;text-transform:lowercase}
    .meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:18px}
    .meta div{padding:16px 18px;border-radius:20px;background:#f7f9f5}
    .meta strong{display:block;margin-bottom:8px;font-size:1rem}
    .meta a{color:inherit;text-decoration:none;font-weight:600}
    .meta a:hover{text-decoration:underline}
    .maps-link{display:inline-flex;align-items:center;gap:8px;font-weight:700}
    .maps-link::before{content:"";width:16px;height:16px;display:inline-block;flex:0 0 16px;background:currentColor;opacity:.82;-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 21s6-5.33 6-11a6 6 0 1 0-12 0c0 5.67 6 11 6 11Zm0-8a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 21s6-5.33 6-11a6 6 0 1 0-12 0c0 5.67 6 11 6 11Zm0-8a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z'/%3E%3C/svg%3E") center/contain no-repeat}
    .summary{margin-top:18px;display:grid;gap:12px}
    .card-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:16px}
    .visits-card{position:relative;padding:22px;border-radius:22px;background:#f7f9f5}
    .visits-card-header{margin-bottom:18px}
    .visits-card-header strong{display:block}
    .visits-expiry-badge{position:absolute;right:18px;bottom:18px;display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:8px 12px;border-radius:999px;background:#fff;border:1px solid rgba(93,118,102,.14);color:var(--primary);font-size:.88rem;font-weight:800;letter-spacing:-.02em;box-shadow:0 10px 24px rgba(24,35,27,.05)}
    .visits-qr-link{position:absolute;left:18px;bottom:18px;display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:14px;background:#fff;border:1px solid rgba(93,118,102,.14);color:var(--primary);box-shadow:0 10px 24px rgba(24,35,27,.05);text-decoration:none}
    .visits-qr-link::before{content:"";width:20px;height:20px;display:block;background:currentColor;-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M3 3h7v7H3V3Zm2 2v3h3V5H5Zm9-2h7v7h-7V3Zm2 2v3h3V5h-3ZM3 14h7v7H3v-7Zm2 2v3h3v-3H5Zm12-2h2v2h-2v-2Zm-3 3h2v2h-2v-2Zm3 0h2v2h-2v-2Zm-6 0h2v2h-2v-2Zm6 3h2v2h-2v-2Zm-3 0h2v2h-2v-2Zm-3 0h2v2h-2v-2Zm9-9h2v2h-2v-2Zm0 3h2v2h-2v-2Z'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M3 3h7v7H3V3Zm2 2v3h3V5H5Zm9-2h7v7h-7V3Zm2 2v3h3V5h-3ZM3 14h7v7H3v-7Zm2 2v3h3v-3H5Zm12-2h2v2h-2v-2Zm-3 3h2v2h-2v-2Zm3 0h2v2h-2v-2Zm-6 0h2v2h-2v-2Zm6 3h2v2h-2v-2Zm-3 0h2v2h-2v-2Zm-3 0h2v2h-2v-2Zm9-9h2v2h-2v-2Zm0 3h2v2h-2v-2Z'/%3E%3C/svg%3E") center/contain no-repeat}
    .visits-qr-link:hover{background:#f7f9f5}
    .visits-visual{display:grid;place-items:center}
    .visits-circle{position:relative;width:196px;height:196px;display:grid;place-items:center}
    .visits-ring{position:absolute;inset:0;width:100%;height:100%}
    .visits-ring-track{fill:none;stroke:#edf2ec;stroke-width:14}
    .visits-ring-segment{fill:none;stroke:var(--used);stroke-width:12;stroke-linecap:butt}
    .visits-ring-segment.is-remaining{stroke:#6b816f}
    .visits-circle-inner{position:relative;width:132px;height:132px;border-radius:50%;display:grid;place-items:center;background:#f7f9f5;border:1px solid rgba(93,118,102,.08);text-align:center;z-index:1}
    .visits-circle strong{display:block;font-size:3.1rem;line-height:1;font-weight:800;color:var(--ink)}
    .visits-circle span{display:block;margin-top:6px;font-size:1.05rem;letter-spacing:-.02em;font-weight:700;color:var(--muted)}
    a.link{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 18px;border-radius:999px;background:var(--secondary);color:var(--ink);text-decoration:none;font-weight:700}
    button.link{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 18px;border-radius:999px;background:var(--secondary);color:var(--ink);text-decoration:none;font-weight:700;border:0;font:inherit;cursor:pointer}
    a.link.primary{background:var(--primary);color:#fff}
    button.link.primary{background:var(--primary);color:#fff}
    .install-actions{display:flex;gap:12px;flex-wrap:wrap}
    .install-sheet{position:fixed;inset:0;display:grid;place-items:center;padding:16px;background:rgba(19,27,21,.38);z-index:40}
    .install-sheet[hidden]{display:none}
    .install-sheet-card{width:min(520px,100%);background:#fff;border:1px solid var(--outline);border-radius:28px;padding:24px;box-shadow:0 24px 70px rgba(24,35,27,.18);display:grid;gap:14px}
    .install-steps{display:grid;gap:10px;margin:0;padding:0;list-style:none}
    .install-step{padding:14px 16px;border-radius:18px;background:#f7f9f5}
    .install-step strong{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:999px;background:#fff;color:var(--primary);margin-right:10px}
    .share-hint{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;background:#fff;border:1px solid rgba(93,118,102,.16);font-weight:800;color:var(--primary);box-shadow:0 10px 24px rgba(24,35,27,.05)}
    .share-hint::before{content:"";width:16px;height:16px;display:inline-block;background:currentColor;-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 3l4 4h-3v6h-2V7H8l4-4Zm-7 9h2v7h10v-7h2v9H5v-9Z'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 3l4 4h-3v6h-2V7H8l4-4Zm-7 9h2v7h10v-7h2v9H5v-9Z'/%3E%3C/svg%3E") center/contain no-repeat}
    .install-native{display:none}
    .install-native.is-visible{display:inline-flex}
    @media (max-width:700px){
      .meta{grid-template-columns:1fr}
      .top{flex-direction:column;align-items:flex-start}
      .card{padding:24px;border-radius:24px}
      .install-actions{display:grid}
      .install-actions > *{width:100%}
      .visits-circle{margin:0 auto}
      .visits-circle{width:220px;height:220px}
      .visits-circle-inner{width:148px;height:148px}
      .visits-circle strong{font-size:3.4rem}
      .visits-expiry-badge{right:14px;bottom:14px}
      .visits-qr-link{left:14px;bottom:14px}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div>
        <h1><?= htmlspecialchars($lang === 'en' ? 'Your Visit Cards' : 'Вашите карти') ?></h1>
        <p><?= htmlspecialchars($lang === 'en' ? 'A simple view of your active cards and remaining visits.' : 'Изчистен изглед на активните ви карти и оставащите посещения.') ?></p>
      </div>
      <a class="link" href="./student-logout.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($lang === 'en' ? 'Log out' : 'Изход') ?></a>
    </div>

    <div class="install-card" data-install-card hidden>
      <div class="install-title"><?= htmlspecialchars($lang === 'en' ? 'Add to home screen' : 'Добавете на началния екран') ?></div>
      <p><?= htmlspecialchars($lang === 'en' ? 'Keep your card one tap away for faster opening, QR access, and a more app-like experience.' : 'Дръжте картата на един тап разстояние за по-бързо отваряне, QR достъп и по-близко до app усещане.') ?></p>
      <div class="install-actions">
        <button class="link primary" type="button" data-install-action><?= htmlspecialchars($lang === 'en' ? 'Add to home screen' : 'Добави на началния екран') ?></button>
        <button class="link" type="button" data-install-dismiss><?= htmlspecialchars($lang === 'en' ? 'Later' : 'По-късно') ?></button>
      </div>
    </div>

    <div class="grid">
      <?php if ($packs === []): ?>
        <div class="card">
          <h2><?= htmlspecialchars($lang === 'en' ? 'No cards yet' : 'Все още няма карти') ?></h2>
          <p><?= htmlspecialchars($lang === 'en' ? 'Maria can assign a visit card to your account as soon as it is ready.' : 'Мария може да свърже карта към профила ви веднага щом е готова.') ?></p>
        </div>
      <?php else: ?>
        <?php foreach ($packs as $pack): ?>
          <?php
            $status = shine_bright_visit_pack_runtime_status($pack);
            $remainingVisits = shine_bright_visit_pack_remaining($pack);
            $totalVisits = max(1, (int) ($pack['total_visits'] ?? 0));
            $allowedClassIds = shine_bright_visit_pack_allowed_class_ids($pack);
            $allowedClasses = sb_student_classes($classes, $allowedClassIds);
            $class = count($allowedClasses) === 1 ? $allowedClasses[0] : null;
            $primarySchedule = $class ? shine_bright_primary_class_schedule($class) : null;
            $mapsUrl = trim((string) ($primarySchedule['maps_url'] ?? ''));
            $location = trim((string) ($primarySchedule['location'] ?? ''));
            $nextSchedule = sb_student_next_schedule($classes, $allowedClassIds);
            $nextScheduleLocation = $nextSchedule ? trim((string) (($nextSchedule['schedule']['location'] ?? ''))) : '';
            $nextScheduleMapsUrl = $nextSchedule ? trim((string) (($nextSchedule['schedule']['maps_url'] ?? ''))) : '';
            $expiryLabel = sb_student_expiry_label($lang, (string) ($pack['expires_on'] ?? ''));
            $expiryBadge = $lang === 'en'
                ? $expiryLabel
                : (preg_replace('/^Остават?\s+/u', '', $expiryLabel) ?: $expiryLabel);
          ?>
          <article class="card">
            <span class="pill"><?= htmlspecialchars(sb_student_status_label($lang, $status)) ?></span>
            <h2 style="margin-top:12px;"><?= htmlspecialchars((string) ($pack['title'] ?? 'Visit Card')) ?></h2>
            <p>
              <?= htmlspecialchars($allowedClasses !== []
                ? implode(', ', array_map(static fn (array $allowedClass): string => (string) ($allowedClass['title'] ?? ''), $allowedClasses))
                : ($lang === 'en' ? 'Valid for all classes' : 'Валидна за всички класове')) ?>
            </p>
            <div class="summary">
              <div class="visits-card">
                <div class="visits-card-header">
                  <strong><?= htmlspecialchars($lang === 'en' ? 'Visits progress' : 'Прогрес на посещенията') ?></strong>
                </div>
                <?php if ($status === 'active' && $remainingVisits > 0): ?>
                  <a class="visits-qr-link" href="./student-qr.php?lang=<?= htmlspecialchars($lang) ?>&pack=<?= htmlspecialchars(urlencode((string) ($pack['id'] ?? ''))) ?>" aria-label="<?= htmlspecialchars($lang === 'en' ? 'Show QR code' : 'Покажи QR код') ?>" title="<?= htmlspecialchars($lang === 'en' ? 'Show QR code' : 'Покажи QR код') ?>"></a>
                <?php endif; ?>
                <div class="visits-visual">
                  <div class="visits-circle" aria-hidden="true">
                    <svg class="visits-ring" viewBox="0 0 196 196" aria-hidden="true" focusable="false">
                      <?php
                        $radius = 70;
                        $segmentGapDegrees = $totalVisits <= 6 ? 20 : ($totalVisits <= 10 ? 16 : 12);
                        $segmentSweep = max(6, (360 / $totalVisits) - $segmentGapDegrees);
                      ?>
                      <circle class="visits-ring-track" cx="98" cy="98" r="<?= htmlspecialchars((string) $radius) ?>"></circle>
                      <?php for ($i = 0; $i < $totalVisits; $i++): ?>
                        <?php
                          $startAngle = ($i * (360 / $totalVisits)) + ($segmentGapDegrees / 2);
                          $endAngle = $startAngle + $segmentSweep;
                        ?>
                        <path
                          class="visits-ring-segment<?= $i < $remainingVisits ? ' is-remaining' : '' ?>"
                          d="<?= htmlspecialchars(sb_student_arc_path(98, 98, $radius, $startAngle, $endAngle)) ?>"
                        ></path>
                      <?php endfor; ?>
                    </svg>
                    <div class="visits-circle-inner">
                      <div>
                        <strong><?= htmlspecialchars((string) $remainingVisits) ?></strong>
                        <span>/ <?= htmlspecialchars((string) $totalVisits) ?></span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="visits-expiry-badge"><?= htmlspecialchars($expiryBadge) ?></div>
              </div>
            </div>
            <div class="meta">
              <?php if ($nextSchedule): ?>
              <div class="next-class-card">
                <strong><?= htmlspecialchars($lang === 'en' ? 'Next class you can attend' : 'Следващ клас, който можете да посетите') ?></strong>
                <div><?= htmlspecialchars(sb_student_next_schedule_label($lang, $nextSchedule)) ?></div>
                <?php if ($nextScheduleLocation !== ''): ?>
                  <div style="margin-top:8px;">
                    <?php if ($nextScheduleMapsUrl !== ''): ?>
                      <a class="maps-link" href="<?= htmlspecialchars($nextScheduleMapsUrl) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($nextScheduleLocation) ?></a>
                    <?php else: ?>
                      <?= htmlspecialchars($nextScheduleLocation) ?>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
              <?php endif; ?>
              <?php if ($location !== ''): ?>
              <div>
                <strong><?= htmlspecialchars($lang === 'en' ? 'Location' : 'Локация') ?></strong>
                <div>
                    <?php if ($mapsUrl !== ''): ?>
                      <a class="maps-link" href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($location) ?></a>
                    <?php else: ?>
                      <?= htmlspecialchars($location) ?>
                  <?php endif; ?>
                </div>
              </div>
              <?php endif; ?>
            </div>
            <?php if ($status === 'active' && $remainingVisits > 0): ?>
              <div class="card-actions">
                <a class="link primary" href="./student-qr.php?lang=<?= htmlspecialchars($lang) ?>&pack=<?= htmlspecialchars(urlencode((string) ($pack['id'] ?? ''))) ?>"><?= htmlspecialchars($lang === 'en' ? 'Show QR code' : 'Покажи QR код') ?></a>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="install-sheet" data-ios-sheet hidden>
    <div class="install-sheet-card">
      <h2><?= htmlspecialchars($lang === 'en' ? 'Add to Home Screen' : 'Добавяне на началния екран') ?></h2>
      <p><?= htmlspecialchars($lang === 'en' ? 'On iPhone, use the browser share menu to save the card as an app.' : 'На iPhone използвайте менюто за споделяне в браузъра, за да запазите картата като app.') ?></p>
      <div class="install-steps">
        <div class="install-step"><strong>1</strong><span class="share-hint"><?= htmlspecialchars($lang === 'en' ? 'Share' : 'Сподели') ?></span></div>
        <div class="install-step"><strong>2</strong><?= htmlspecialchars($lang === 'en' ? 'Choose “Add to Home Screen”.' : 'Изберете “Add to Home Screen”.') ?></div>
        <div class="install-step"><strong>3</strong><?= htmlspecialchars($lang === 'en' ? 'Open Shine Bright Card from your home screen next time.' : 'Следващия път отворете Shine Bright Card директно от началния екран.') ?></div>
      </div>
      <div class="install-actions">
        <button class="link primary install-native" type="button" data-native-install><?= htmlspecialchars($lang === 'en' ? 'Install now' : 'Инсталирай сега') ?></button>
        <button class="link primary" type="button" data-ios-close><?= htmlspecialchars($lang === 'en' ? 'Got it' : 'Разбрах') ?></button>
      </div>
    </div>
  </div>
  <script src="./assets/student-app.js"></script>
</body>
</html>
