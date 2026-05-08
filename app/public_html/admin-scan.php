<?php
require_once __DIR__ . '/content.php';

shine_bright_require_admin();

$lang = shine_bright_resolve_lang();
$content = shine_bright_load_content();
$brand = $content[$lang]['brand'] ?? $content['bg']['brand'];

function sb_admin_scan_text(string $lang, string $key): string
{
    $isEn = $lang === 'en';
    return match ($key) {
        'title' => $isEn ? 'QR Check-in' : 'QR check-in',
        'intro' => $isEn ? 'Scan a student QR code and confirm one visit.' : 'Сканирайте QR код на ученик и потвърдете едно посещение.',
        'start' => $isEn ? 'Start camera' : 'Стартирай камерата',
        'stop' => $isEn ? 'Stop camera' : 'Спри камерата',
        'toggle_on' => $isEn ? 'Camera: on' : 'Камера: вкл',
        'toggle_off' => $isEn ? 'Camera: off' : 'Камера: изкл',
        'upload' => $isEn ? 'Upload QR image' : 'Качи QR изображение',
        'status_idle' => $isEn ? 'Ready to scan.' : 'Готово за сканиране.',
        'status_opening' => $isEn ? 'Opening camera…' : 'Отваряне на камера…',
        'status_scanning' => $isEn ? 'Scanning…' : 'Сканиране…',
        'status_unsupported' => $isEn ? 'This browser does not support camera QR scanning yet.' : 'Този браузър все още не поддържа QR сканиране с камера.',
        'status_hold_steady' => $isEn ? 'Hold the QR closer and keep it inside the frame.' : 'Приближете QR кода и го задръжте в рамката.',
        'verify' => $isEn ? 'QR result' : 'QR резултат',
        'student' => $isEn ? 'Student' : 'Ученик',
        'card' => $isEn ? 'Card' : 'Карта',
        'remaining' => $isEn ? 'Remaining visits' : 'Оставащи посещения',
        'class' => $isEn ? 'Class (optional)' : 'Клас (по избор)',
        'manual' => $isEn ? 'Manual / no class linked' : 'Ръчно / без свързан клас',
        'note' => $isEn ? 'Note (optional)' : 'Бележка (по избор)',
        'consume' => $isEn ? 'Use 1 visit' : 'Използвай 1 посещение',
        'confirm_intro' => $isEn ? 'Confirm the student and card before recording the visit.' : 'Потвърдете ученика и картата, преди да запишете посещението.',
        'success_title' => $isEn ? 'Check-in recorded' : 'Посещението е записано',
        'success_intro' => $isEn ? 'The visit was added successfully.' : 'Посещението беше добавено успешно.',
        'recorded_at' => $isEn ? 'Recorded at' : 'Записано в',
        'scan_next' => $isEn ? 'Scan next' : 'Сканирай следващ',
        'rescan' => $isEn ? 'Scan again' : 'Сканирай отново',
        'back_to_scan' => $isEn ? 'Back to scanning' : 'Назад към сканиране',
        'undo' => $isEn ? 'Undo last scan' : 'Отмени последното чекиране',
        'back' => $isEn ? 'Back to admin' : 'Назад към админа',
    };
}
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(sb_admin_scan_text($lang, 'title') . ' | ' . ($brand['name'] ?? 'Shine Bright Yoga')) ?></title>
  <style>
    :root{--bg:#f3f5ef;--surface:#fff;--ink:#1d251f;--muted:#5f6b62;--outline:rgba(93,118,102,.14);--primary:#6b816f;--secondary:#eef2ea;--danger:#7a2b1d}
    *{box-sizing:border-box}
    body{margin:0;font-family:ui-sans-serif,system-ui,sans-serif;background:linear-gradient(180deg,#f4f6f1 0%,#edf1eb 100%);color:var(--ink)}
    .wrap{width:min(980px,calc(100% - 24px));margin:24px auto 40px}
    .card{background:var(--surface);border:1px solid var(--outline);border-radius:28px;padding:24px;box-shadow:0 24px 64px rgba(24,35,27,.06)}
    h1{margin:0 0 12px;font-size:clamp(2rem,5vw,3.2rem);line-height:1;letter-spacing:-.04em;font-weight:800}
    p{margin:0;color:var(--muted);line-height:1.55}
    .layout{display:grid;grid-template-columns:1fr;gap:18px;margin-top:18px}
    .scanner-shell{display:grid;gap:14px}
    .scanner-shell[hidden]{display:none}
    .scanner-stage{position:relative;overflow:hidden;border-radius:26px;background:#d9e3d8;border:1px solid var(--outline);min-height:420px}
    video{width:100%;height:100%;min-height:420px;object-fit:cover;display:block;background:#d9e3d8}
    .scanner-overlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none}
    .scanner-frame{width:min(72vw,280px);aspect-ratio:1/1;border-radius:28px;border:2px solid rgba(255,255,255,.9);box-shadow:0 0 0 999px rgba(16,22,18,.14)}
    .scanner-status{position:absolute;left:16px;right:16px;bottom:16px;display:flex;justify-content:center}
    .status{padding:12px 14px;border-radius:16px;background:rgba(247,249,245,.96);border:1px solid var(--outline);font-weight:700;color:var(--muted);backdrop-filter:blur(6px)}
    .actions{display:flex;gap:12px;flex-wrap:wrap}
    .button{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 18px;border-radius:999px;background:var(--secondary);color:var(--ink);text-decoration:none;font-weight:700;border:0;cursor:pointer;font:inherit}
    .button.primary{background:var(--primary);color:#fff}
    .button.ghost{background:rgba(255,255,255,.92)}
    .file-input{position:absolute;left:-9999px}
    .result{display:grid;gap:12px}
    .result[hidden]{display:none}
    .result-card,.success-card{padding:18px;border-radius:22px;background:#f7f9f5;border:1px solid var(--outline)}
    .result-card h2,.success-card h2{margin:0 0 8px;font-size:1.65rem;line-height:1.05;letter-spacing:-.03em}
    .meta{display:grid;gap:12px}
    .meta div{padding:16px 18px;border-radius:20px;background:#f7f9f5}
    .meta strong{display:block;margin-bottom:8px}
    select,textarea{width:100%;padding:12px 14px;border-radius:16px;border:1px solid var(--outline);background:#fbfcfa;font:inherit;color:var(--ink)}
    textarea{min-height:90px;resize:vertical}
    .error{color:var(--danger)}
    .success-card{display:grid;gap:14px;background:#f4f8f2}
    .success-pill{display:inline-flex;align-items:center;justify-content:center;min-height:34px;padding:6px 12px;border-radius:999px;background:#fff;color:var(--primary);font-weight:800;width:max-content}
    .success-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .success-grid div{padding:14px 16px;border-radius:18px;background:#fff}
    .success-grid strong{display:block;margin-bottom:6px}
    @media (max-width:820px){.wrap{width:min(100% - 16px,980px);margin:16px auto 28px}.scanner-stage,video{min-height:360px}}
    @media (max-width:560px){.success-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1><?= htmlspecialchars(sb_admin_scan_text($lang, 'title')) ?></h1>
      <p><?= htmlspecialchars(sb_admin_scan_text($lang, 'intro')) ?></p>
      <div class="layout">
        <div class="scanner-shell" id="scanner-shell">
          <div class="scanner-stage">
            <video id="scanner-video" playsinline muted></video>
            <div class="scanner-overlay"><div class="scanner-frame"></div></div>
            <div class="scanner-status"><div class="status" id="scanner-status"><?= htmlspecialchars(sb_admin_scan_text($lang, 'status_idle')) ?></div></div>
          </div>
          <div class="actions">
            <button class="button primary" id="toggle-scan" type="button"><?= htmlspecialchars(sb_admin_scan_text($lang, 'toggle_on')) ?></button>
            <label class="button ghost" for="scan-upload"><?= htmlspecialchars(sb_admin_scan_text($lang, 'upload')) ?></label>
            <input class="file-input" id="scan-upload" type="file" accept="image/*">
            <a class="button ghost" href="./admin.php?lang=<?= htmlspecialchars($lang) ?>&section=dashboard"><?= htmlspecialchars(sb_admin_scan_text($lang, 'back')) ?></a>
          </div>
        </div>
        <div class="result" id="scan-result" hidden>
          <div class="result-card">
            <h2><?= htmlspecialchars(sb_admin_scan_text($lang, 'verify')) ?></h2>
            <p><?= htmlspecialchars(sb_admin_scan_text($lang, 'confirm_intro')) ?></p>
          </div>
          <div class="meta">
            <div><strong><?= htmlspecialchars(sb_admin_scan_text($lang, 'student')) ?></strong><span id="result-student"></span></div>
            <div><strong><?= htmlspecialchars(sb_admin_scan_text($lang, 'card')) ?></strong><span id="result-pack"></span></div>
            <div><strong><?= htmlspecialchars(sb_admin_scan_text($lang, 'remaining')) ?></strong><span id="result-visits"></span></div>
          </div>
          <label>
            <strong><?= htmlspecialchars(sb_admin_scan_text($lang, 'class')) ?></strong>
            <select id="result-class"></select>
          </label>
          <label>
            <strong><?= htmlspecialchars(sb_admin_scan_text($lang, 'note')) ?></strong>
            <textarea id="result-note"></textarea>
          </label>
          <div class="actions">
            <button class="button primary" id="consume-visit" type="button"><?= htmlspecialchars(sb_admin_scan_text($lang, 'consume')) ?></button>
            <button class="button" id="rescan-confirm" type="button"><?= htmlspecialchars(sb_admin_scan_text($lang, 'back_to_scan')) ?></button>
          </div>
          <div class="status" id="consume-status"></div>
        </div>
        <div class="result" id="scan-success" hidden>
          <div class="success-card">
            <span class="success-pill"><?= htmlspecialchars(sb_admin_scan_text($lang, 'success_title')) ?></span>
            <div>
              <h2 id="success-student"></h2>
              <p><?= htmlspecialchars(sb_admin_scan_text($lang, 'success_intro')) ?></p>
            </div>
            <div class="success-grid">
              <div><strong><?= htmlspecialchars(sb_admin_scan_text($lang, 'card')) ?></strong><span id="success-pack"></span></div>
              <div><strong><?= htmlspecialchars(sb_admin_scan_text($lang, 'remaining')) ?></strong><span id="success-visits"></span></div>
              <div><strong><?= htmlspecialchars(sb_admin_scan_text($lang, 'class')) ?></strong><span id="success-class"></span></div>
              <div><strong><?= htmlspecialchars(sb_admin_scan_text($lang, 'recorded_at')) ?></strong><span id="success-time"></span></div>
            </div>
            <div class="actions">
              <button class="button primary" id="scan-next" type="button"><?= htmlspecialchars(sb_admin_scan_text($lang, 'scan_next')) ?></button>
              <button class="button" id="rescan-success" type="button"><?= htmlspecialchars(sb_admin_scan_text($lang, 'rescan')) ?></button>
              <button class="button" id="undo-last-scan" type="button"><?= htmlspecialchars(sb_admin_scan_text($lang, 'undo')) ?></button>
            </div>
            <div class="status" id="undo-status"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="./assets/vendor/jsQR.js"></script>
  <script>
    (function () {
      var lang = <?= json_encode($lang) ?>;
      var video = document.getElementById('scanner-video');
      var scannerShell = document.getElementById('scanner-shell');
      var statusNode = document.getElementById('scanner-status');
      var resultNode = document.getElementById('scan-result');
      var successNode = document.getElementById('scan-success');
      var consumeStatus = document.getElementById('consume-status');
      var undoStatus = document.getElementById('undo-status');
      var toggleButton = document.getElementById('toggle-scan');
      var uploadInput = document.getElementById('scan-upload');
      var consumeButton = document.getElementById('consume-visit');
      var scanNextButton = document.getElementById('scan-next');
      var rescanConfirmButton = document.getElementById('rescan-confirm');
      var rescanSuccessButton = document.getElementById('rescan-success');
      var undoButton = document.getElementById('undo-last-scan');
      var classSelect = document.getElementById('result-class');
      var noteInput = document.getElementById('result-note');
      var currentToken = '';
      var lastUsageId = '';
      var stream = null;
      var detecting = false;
      var scanTimeoutId = null;
      var detector = ('BarcodeDetector' in window) ? new BarcodeDetector({formats: ['qr_code']}) : null;
      var canvas = document.createElement('canvas');
      var context = canvas.getContext('2d', {willReadFrequently: true});
      var texts = {
        opening: <?= json_encode(sb_admin_scan_text($lang, 'status_opening')) ?>,
        scanning: <?= json_encode(sb_admin_scan_text($lang, 'status_scanning')) ?>,
        idle: <?= json_encode(sb_admin_scan_text($lang, 'status_idle')) ?>,
        unsupported: <?= json_encode(sb_admin_scan_text($lang, 'status_unsupported')) ?>,
        holdSteady: <?= json_encode(sb_admin_scan_text($lang, 'status_hold_steady')) ?>,
        manual: <?= json_encode(sb_admin_scan_text($lang, 'manual')) ?>,
        toggleOn: <?= json_encode(sb_admin_scan_text($lang, 'toggle_on')) ?>,
        toggleOff: <?= json_encode(sb_admin_scan_text($lang, 'toggle_off')) ?>
      };

      function updateToggleLabel() {
        toggleButton.textContent = stream ? texts.toggleOff : texts.toggleOn;
      }

      function resetForRescan() {
        currentToken = '';
        lastUsageId = '';
        noteInput.value = '';
        classSelect.value = '';
        resetPanels();
        scannerShell.hidden = false;
        if (stream) {
          beginDetection();
        } else {
          setStatus(texts.idle, false);
        }
      }

      function resetPanels() {
        resultNode.hidden = true;
        successNode.hidden = true;
        consumeStatus.textContent = '';
        consumeStatus.classList.remove('error');
        undoStatus.textContent = '';
        undoStatus.classList.remove('error');
      }

      function formatRecordedAt(isoValue) {
        if (!isoValue) return '';
        var date = new Date(isoValue);
        if (isNaN(date.getTime())) return isoValue;
        return date.toLocaleString(lang === 'bg' ? 'bg-BG' : 'en-GB', {
          hour: '2-digit',
          minute: '2-digit',
          day: '2-digit',
          month: '2-digit'
        });
      }

      function setStatus(message, isError) {
        statusNode.textContent = message;
        statusNode.classList.toggle('error', !!isError);
      }

      function stopScan() {
        detecting = false;
        if (scanTimeoutId) clearTimeout(scanTimeoutId);
        if (stream) {
          stream.getTracks().forEach(function (track) { track.stop(); });
          stream = null;
        }
        video.srcObject = null;
        setStatus(texts.idle, false);
        updateToggleLabel();
      }

      function beginDetection() {
        if (!stream || detecting) {
          return;
        }
        detecting = true;
        setStatus(texts.scanning, false);
        updateToggleLabel();
        scanFrame();
      }

      async function verifyToken(token) {
        var response = await fetch('./api/qr-checkin.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          credentials: 'same-origin',
          body: JSON.stringify({action: 'verify', token: token, lang: lang})
        });
        var data = await response.json();
        if (!response.ok || !data.ok) {
          throw new Error(data.error || 'Verification failed.');
        }
        currentToken = token;
        lastUsageId = '';
        resetPanels();
        scannerShell.hidden = true;
        document.getElementById('result-student').textContent = data.student.name;
        document.getElementById('result-pack').textContent = data.pack.title;
        document.getElementById('result-visits').textContent = data.pack.remaining_visits + ' / ' + data.pack.total_visits;
        classSelect.innerHTML = '';
        var manualOption = document.createElement('option');
        manualOption.value = '';
        manualOption.textContent = texts.manual;
        classSelect.appendChild(manualOption);
        (data.allowed_classes || []).forEach(function (item) {
          var option = document.createElement('option');
          option.value = item.id;
          option.textContent = item.title;
          classSelect.appendChild(option);
        });
        resultNode.hidden = false;
      }

      function showSuccess(data) {
        resetPanels();
        scannerShell.hidden = true;
        successNode.hidden = false;
        document.getElementById('success-student').textContent = data.student.name;
        document.getElementById('success-pack').textContent = data.pack.title;
        document.getElementById('success-visits').textContent = data.pack.remaining_visits + ' / ' + data.pack.total_visits;
        document.getElementById('success-class').textContent = data.usage_event && data.usage_event.class_title ? data.usage_event.class_title : texts.manual;
        document.getElementById('success-time').textContent = formatRecordedAt(data.usage_event ? data.usage_event.recorded_at : '');
        lastUsageId = data.usage_event && data.usage_event.id ? data.usage_event.id : '';
      }

      async function decodeImageData(width, height) {
        if (!context || !window.jsQR || !width || !height) {
          return '';
        }

        var imageData = context.getImageData(0, 0, width, height);
        var result = window.jsQR(imageData.data, width, height, {inversionAttempts: 'attemptBoth'});
        return result && result.data ? result.data : '';
      }

      async function decodeCurrentFrame() {
        if (detector) {
          var codes = await detector.detect(video);
          if (codes && codes.length) {
            return codes[0].rawValue || '';
          }
        }

        if (!context || !window.jsQR || video.readyState < 2) {
          return '';
        }

        var width = video.videoWidth || 0;
        var height = video.videoHeight || 0;
        if (!width || !height) {
          return '';
        }

        var maxDimension = 960;
        var scale = Math.min(1, maxDimension / Math.max(width, height));
        var targetWidth = Math.max(240, Math.round(width * scale));
        var targetHeight = Math.max(240, Math.round(height * scale));

        canvas.width = targetWidth;
        canvas.height = targetHeight;
        context.drawImage(video, 0, 0, targetWidth, targetHeight);
        return decodeImageData(targetWidth, targetHeight);
      }

      async function scanFrame() {
        if (!detecting) return;
        try {
          var rawValue = await decodeCurrentFrame();
          if (rawValue) {
            detecting = false;
            await verifyToken(rawValue);
            setStatus(texts.idle, false);
            return;
          }
          setStatus(texts.holdSteady, false);
        } catch (error) {
          setStatus(error.message || 'Scan failed.', true);
          detecting = false;
          if (stream) {
            setTimeout(function () {
              if (!detecting && stream && !currentToken && resultNode.hidden && successNode.hidden) {
                beginDetection();
              }
            }, 900);
          }
          return;
        }
        scanTimeoutId = setTimeout(scanFrame, 260);
      }

      toggleButton.addEventListener('click', async function () {
        if ((!detector && !window.jsQR) || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
          setStatus(texts.unsupported, true);
          return;
        }
        if (stream) {
          stopScan();
          return;
        }
        try {
          setStatus(texts.opening, false);
          stream = await navigator.mediaDevices.getUserMedia({
            video: {
              facingMode: {ideal: 'environment'},
              width: {ideal: 1920},
              height: {ideal: 1080}
            }
          });
          video.srcObject = stream;
          await video.play();
          scannerShell.hidden = false;
          beginDetection();
        } catch (error) {
          setStatus(error.message || texts.unsupported, true);
        }
      });

      uploadInput.addEventListener('change', function () {
        var file = uploadInput.files && uploadInput.files[0];
        if (!file || !window.jsQR) {
          return;
        }
        var image = new Image();
        image.onload = async function () {
          canvas.width = image.naturalWidth;
          canvas.height = image.naturalHeight;
          context.drawImage(image, 0, 0);
          try {
            var rawValue = await decodeImageData(canvas.width, canvas.height);
            if (!rawValue) {
              throw new Error(texts.holdSteady);
            }
            await verifyToken(rawValue);
            stopScan();
            setStatus(texts.idle, false);
          } catch (error) {
            setStatus(error.message || texts.holdSteady, true);
            scannerShell.hidden = false;
          }
        };
        image.src = URL.createObjectURL(file);
      });

      consumeButton.addEventListener('click', async function () {
        if (!currentToken) return;
        consumeButton.disabled = true;
        consumeStatus.textContent = '';
        try {
          var response = await fetch('./api/qr-checkin.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({
              action: 'consume',
              token: currentToken,
              class_id: classSelect.value,
              note: noteInput.value,
              lang: lang
            })
          });
          var data = await response.json();
          if (!response.ok || !data.ok) {
            throw new Error(data.error || 'Consume failed.');
          }
          currentToken = '';
          noteInput.value = '';
          classSelect.value = '';
          setStatus(texts.idle, false);
          showSuccess(data);
        } catch (error) {
          consumeStatus.textContent = error.message || 'Consume failed.';
          consumeStatus.classList.add('error');
        } finally {
          consumeButton.disabled = false;
        }
      });

      scanNextButton.addEventListener('click', function () {
        resetForRescan();
      });

      rescanConfirmButton.addEventListener('click', function () {
        resetForRescan();
      });

      rescanSuccessButton.addEventListener('click', function () {
        resetForRescan();
      });

      undoButton.addEventListener('click', async function () {
        if (!lastUsageId) return;
        undoButton.disabled = true;
        undoStatus.textContent = '';
        undoStatus.classList.remove('error');
        try {
          var response = await fetch('./api/qr-checkin.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({
              action: 'undo',
              usage_id: lastUsageId,
              lang: lang
            })
          });
          var data = await response.json();
          if (!response.ok || !data.ok) {
            throw new Error(data.error || 'Undo failed.');
          }
          undoStatus.textContent = data.message || '';
          lastUsageId = '';
        } catch (error) {
          undoStatus.textContent = error.message || 'Undo failed.';
          undoStatus.classList.add('error');
        } finally {
          undoButton.disabled = false;
        }
      });

      updateToggleLabel();
    }());
  </script>
</body>
</html>
