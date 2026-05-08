<?php
require_once __DIR__ . '/content.php';

$lang = shine_bright_resolve_lang();
$content = shine_bright_load_content();
$brand = $content[$lang]['brand'] ?? $content['bg']['brand'];
$student = shine_bright_require_student_auth();
$allPacks = array_values(array_filter(shine_bright_load_visit_packs(), static fn (array $pack): bool => (string) ($pack['client_id'] ?? '') === (string) ($student['id'] ?? '')));
$packs = array_values(array_filter($allPacks, static fn (array $pack): bool => shine_bright_visit_pack_runtime_status($pack) === 'active' && shine_bright_visit_pack_remaining($pack) > 0));
$selectedPackId = trim((string) ($_GET['pack'] ?? ''));
$selectedPack = $selectedPackId !== '' ? shine_bright_find_record_by_id($packs, $selectedPackId) : ($packs[0] ?? null);
$qrToken = '';
if ($selectedPack) {
    $codes = shine_bright_load_qr_checkin_codes();
    $createdCode = shine_bright_create_qr_checkin_code($codes, (string) ($student['id'] ?? ''), (string) ($selectedPack['id'] ?? ''));
    shine_bright_save_qr_checkin_codes($codes);
    $qrToken = (string) ($createdCode['code'] ?? '');
}
$expiresAt = time() + 600;

function sb_student_qr_text(string $lang, string $key): string
{
    $isEn = $lang === 'en';
    return match ($key) {
        'title' => $isEn ? 'Your QR code' : 'Вашият QR код',
        'intro' => $isEn ? 'Show this code to Maria for quick check-in.' : 'Покажете този код на Мария за бърз check-in.',
        'choose' => $isEn ? 'Choose a card' : 'Изберете карта',
        'expires' => $isEn ? 'QR refreshes in' : 'QR се обновява след',
        'refresh' => $isEn ? 'Refresh code' : 'Обнови кода',
        'back' => $isEn ? 'Back to cards' : 'Назад към картите',
        'no_cards' => $isEn ? 'No active cards available for QR check-in yet.' : 'Все още няма активни карти за QR check-in.',
        'valid_for' => $isEn ? 'Valid for' : 'Валидна за',
        default => '',
    };
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
  <title><?= htmlspecialchars(sb_student_qr_text($lang, 'title') . ' | ' . ($brand['name'] ?? 'Shine Bright Yoga')) ?></title>
  <style>
    :root{--bg:#f3f5ef;--surface:#fff;--ink:#1d251f;--muted:#5f6b62;--outline:rgba(93,118,102,.14);--primary:#6b816f;--secondary:#eef2ea}
    *{box-sizing:border-box}
    body{margin:0;font-family:ui-sans-serif,system-ui,sans-serif;background:linear-gradient(180deg,#f4f6f1 0%,#edf1eb 100%);color:var(--ink)}
    .wrap{width:min(760px,calc(100% - 24px));margin:24px auto 40px}
    .card{background:var(--surface);border:1px solid var(--outline);border-radius:28px;padding:24px;box-shadow:0 24px 64px rgba(24,35,27,.06)}
    h1{margin:0 0 12px;font-size:clamp(2rem,6vw,3.2rem);line-height:1;letter-spacing:-.04em;font-weight:800}
    p{margin:0;color:var(--muted);line-height:1.55}
    .qr-stage{display:grid;gap:20px;margin-top:20px}
    .qr-box{display:grid;place-items:center;padding:20px;border-radius:24px;background:#f7f9f5;border:1px solid var(--outline);min-height:340px}
    #qrcode{display:grid;place-items:center}
    #qrcode img,#qrcode canvas{max-width:100%;height:auto;border-radius:18px}
    .meta{display:grid;gap:12px}
    .meta div{padding:16px 18px;border-radius:20px;background:#f7f9f5}
    .meta strong{display:block;margin-bottom:8px}
    .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:16px}
    .button{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 18px;border-radius:999px;background:var(--secondary);color:var(--ink);text-decoration:none;font-weight:700;border:0;cursor:pointer;font:inherit}
    .button.primary{background:var(--primary);color:#fff}
    select{width:100%;padding:12px 14px;border-radius:16px;border:1px solid var(--outline);background:#fbfcfa;font:inherit;color:var(--ink);margin-top:10px}
    .timer{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:8px 12px;border-radius:999px;background:#fff;border:1px solid var(--outline);font-weight:800;color:var(--primary)}
    @media (max-width:640px){.wrap{width:min(100% - 16px,760px);margin:16px auto 28px}.card{padding:20px}.actions{display:grid}.button{width:100%}.qr-box{min-height:280px}}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1><?= htmlspecialchars(sb_student_qr_text($lang, 'title')) ?></h1>
      <p><?= htmlspecialchars(sb_student_qr_text($lang, 'intro')) ?></p>
      <?php if ($packs === [] || !$selectedPack): ?>
        <div class="qr-stage">
          <div class="meta"><div><strong><?= htmlspecialchars(sb_student_qr_text($lang, 'title')) ?></strong><span><?= htmlspecialchars(sb_student_qr_text($lang, 'no_cards')) ?></span></div></div>
          <div class="actions"><a class="button" href="./student-dashboard.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars(sb_student_qr_text($lang, 'back')) ?></a></div>
        </div>
      <?php else: ?>
        <div class="qr-stage">
          <?php if (count($packs) > 1): ?>
            <label>
              <strong><?= htmlspecialchars(sb_student_qr_text($lang, 'choose')) ?></strong>
              <select onchange="if (this.value) window.location.href=this.value;">
                <?php foreach ($packs as $pack): ?>
                  <?php $href = './student-qr.php?lang=' . urlencode($lang) . '&pack=' . urlencode((string) ($pack['id'] ?? '')); ?>
                  <option value="<?= htmlspecialchars($href) ?>"<?= (string) ($pack['id'] ?? '') === (string) ($selectedPack['id'] ?? '') ? ' selected' : '' ?>><?= htmlspecialchars((string) ($pack['title'] ?? 'Visit Card')) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          <?php endif; ?>
          <div class="qr-box">
            <div id="qrcode" data-token="<?= htmlspecialchars($qrToken) ?>"></div>
          </div>
          <div class="meta">
            <div>
              <strong><?= htmlspecialchars((string) ($selectedPack['title'] ?? 'Visit Card')) ?></strong>
              <span><?= htmlspecialchars(sb_student_qr_text($lang, 'valid_for')) ?>:
                <?php
                  $classItems = shine_bright_content_section_items($content, $lang, 'classes');
                  $allowedIds = shine_bright_visit_pack_allowed_class_ids($selectedPack);
                  $labels = [];
                  foreach ($classItems as $class) {
                      $classId = (string) ($class['id'] ?? '');
                      if ($allowedIds !== [] && !in_array($classId, $allowedIds, true)) {
                          continue;
                      }
                      $labels[] = (string) ($class['title'] ?? $classId);
                  }
                  echo htmlspecialchars($labels !== [] ? implode(', ', $labels) : ($lang === 'en' ? 'All classes' : 'Всички класове'));
                ?>
              </span>
            </div>
          </div>
          <div class="actions">
            <span class="timer"><span id="qr-timer">10:00</span></span>
            <a class="button primary" href="./student-qr.php?lang=<?= htmlspecialchars($lang) ?>&pack=<?= htmlspecialchars(urlencode((string) ($selectedPack['id'] ?? ''))) ?>"><?= htmlspecialchars(sb_student_qr_text($lang, 'refresh')) ?></a>
            <a class="button" href="./student-dashboard.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars(sb_student_qr_text($lang, 'back')) ?></a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($qrToken !== ''): ?>
    <script src="./assets/vendor/qrcode.min.js"></script>
    <script>
      (function () {
        var qrNode = document.getElementById('qrcode');
        if (!qrNode || !window.QRCode) return;
        new QRCode(qrNode, {
          text: qrNode.dataset.token || '',
          width: 280,
          height: 280,
          colorDark: '#1d251f',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.M
        });

        var timerNode = document.getElementById('qr-timer');
        var remaining = 600;
        if (!timerNode) return;
        function render() {
          var minutes = Math.floor(remaining / 60);
          var seconds = remaining % 60;
          timerNode.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        }
        render();
        setInterval(function () {
          remaining = Math.max(0, remaining - 1);
          render();
        }, 1000);
      }());
    </script>
  <?php endif; ?>
  <script src="./assets/student-app.js"></script>
</body>
</html>
