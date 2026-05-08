<?php
require_once __DIR__ . '/content.php';

$lang = shine_bright_resolve_lang();
$content = shine_bright_load_content();
$brand = $content[$lang]['brand'] ?? $content['bg']['brand'];
$rawToken = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$tokens = shine_bright_load_student_activation_tokens();
$tokenRecord = $rawToken !== '' ? shine_bright_find_valid_student_activation_token($tokens, $rawToken) : null;
$student = $tokenRecord ? shine_bright_find_record_by_id(shine_bright_load_clients(), (string) ($tokenRecord['student_id'] ?? '')) : null;
$error = '';

function sb_student_validation_text(string $lang, string $key): string
{
    $isEn = $lang === 'en';

    return match ($key) {
        'required' => $isEn ? 'Please fill out this field.' : 'Моля, попълнете това поле.',
        'password_short' => $isEn ? 'Use at least 10 characters.' : 'Използвайте поне 10 символа.',
        'password_mismatch' => $isEn ? 'Passwords do not match.' : 'Паролите не съвпадат.',
        default => '',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenRecord && $student) {
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (strlen($password) < 10) {
        $error = $lang === 'en' ? 'Choose a password with at least 10 characters.' : 'Изберете парола с поне 10 символа.';
    } elseif ($password !== $passwordConfirm) {
        $error = $lang === 'en' ? 'Passwords do not match.' : 'Паролите не съвпадат.';
    } else {
        $students = shine_bright_load_clients();
        foreach ($students as $index => $existing) {
            if ((string) ($existing['id'] ?? '') !== (string) ($student['id'] ?? '')) {
                continue;
            }

            $students[$index]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $students[$index]['account_status'] = 'active';
            $students[$index]['updated_at'] = gmdate('c');
            $student = $students[$index];
            break;
        }

        shine_bright_mark_student_activation_token_used($tokens, (string) ($tokenRecord['id'] ?? ''));
        shine_bright_save_student_activation_tokens($tokens);
        shine_bright_save_clients($students);
        shine_bright_student_login($students, $student);
        header('Location: ./student-dashboard.php?lang=' . urlencode($lang) . '&install=1');
        exit;
    }
}
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(($lang === 'en' ? 'Activate Account' : 'Активиране на профил') . ' | ' . ($brand['name'] ?? 'Shine Bright Yoga')) ?></title>
  <style>
    :root{--bg:#f3f5ef;--surface:#fff;--ink:#1d251f;--muted:#5f6b62;--outline:rgba(93,118,102,.18);--primary:#6b816f;--primary-ink:#fff;--secondary:#eef2ea;--error-bg:#f7e3df;--error-ink:#7a2b1d}
    *{box-sizing:border-box}
    body{margin:0;font-family:ui-sans-serif,system-ui,sans-serif;background:linear-gradient(180deg,#f4f6f1 0%,#edf1eb 100%);color:var(--ink)}
    .wrap{width:min(640px,calc(100% - 32px));margin:56px auto}
    .card{background:var(--surface);border:1px solid rgba(93,118,102,.14);border-radius:32px;padding:36px;box-shadow:0 28px 72px rgba(24,35,27,.07)}
    h1{margin:0 0 14px;font-size:clamp(2.4rem,5vw,4rem);line-height:.96;letter-spacing:-.04em;font-weight:800}
    p{margin:0;line-height:1.6;color:var(--muted);font-size:1rem;max-width:42ch}
    form{display:grid;gap:16px;margin-top:28px}
    label{display:block;font-weight:700;font-size:1rem;color:var(--ink)}
    .field-hint{display:block;margin-top:8px;color:var(--muted);font-size:.92rem}
    input{width:100%;height:58px;padding:0 18px;border-radius:18px;border:1px solid var(--outline);background:#fbfcfa;font:inherit;color:var(--ink);margin-top:10px}
    input:focus{outline:none;border-color:#8fa092;box-shadow:0 0 0 4px rgba(107,129,111,.12)}
    button,a.link{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:0 24px;border-radius:999px;border:0;font:inherit;font-weight:700;text-decoration:none;white-space:nowrap}
    button{background:var(--primary);color:var(--primary-ink);cursor:pointer}
    a.link{background:var(--secondary);color:var(--ink)}
    .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:8px}
    .error{margin:18px 0 0;padding:13px 15px;border-radius:16px;background:var(--error-bg);color:var(--error-ink)}
    @media (max-width:640px){
      .wrap{width:min(100% - 24px,640px);margin:28px auto}
      .card{padding:24px;border-radius:24px}
      .actions{flex-direction:column}
      .actions > *{width:100%}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <?php if (!$tokenRecord || !$student): ?>
        <h1><?= htmlspecialchars($lang === 'en' ? 'Activation Link Invalid' : 'Невалиден линк') ?></h1>
        <p><?= htmlspecialchars($lang === 'en' ? 'This activation link is invalid or expired. Ask Maria to resend it.' : 'Този линк за активиране е невалиден или е изтекъл. Помолете Мария да го изпрати отново.') ?></p>
        <div class="actions"><a class="link" href="./student-login.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($lang === 'en' ? 'Go to login' : 'Към вход') ?></a></div>
      <?php else: ?>
        <h1><?= htmlspecialchars($lang === 'en' ? 'Activate Your Account' : 'Активирайте профила си') ?></h1>
        <p><?= htmlspecialchars($lang === 'en' ? 'Set your password to access your visit cards online.' : 'Задайте своята парола, за да виждате картите си онлайн.') ?></p>
        <?php if ($error !== ''): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" novalidate id="student-activate-form">
          <input type="hidden" name="token" value="<?= htmlspecialchars($rawToken) ?>">
          <label>
            <?= htmlspecialchars($lang === 'en' ? 'Password' : 'Парола') ?>
            <input
              type="password"
              name="password"
              id="student-password"
              required
              minlength="10"
              autocomplete="new-password"
              data-required-message="<?= htmlspecialchars(sb_student_validation_text($lang, 'required')) ?>"
              data-too-short-message="<?= htmlspecialchars(sb_student_validation_text($lang, 'password_short')) ?>"
            >
            <span class="field-hint"><?= htmlspecialchars($lang === 'en' ? 'Use at least 10 characters.' : 'Използвайте поне 10 символа.') ?></span>
          </label>
          <label>
            <?= htmlspecialchars($lang === 'en' ? 'Confirm password' : 'Потвърдете паролата') ?>
            <input
              type="password"
              name="password_confirm"
              id="student-password-confirm"
              required
              minlength="10"
              autocomplete="new-password"
              data-required-message="<?= htmlspecialchars(sb_student_validation_text($lang, 'required')) ?>"
              data-too-short-message="<?= htmlspecialchars(sb_student_validation_text($lang, 'password_short')) ?>"
              data-mismatch-message="<?= htmlspecialchars(sb_student_validation_text($lang, 'password_mismatch')) ?>"
            >
          </label>
          <div class="actions">
            <button type="submit"><?= htmlspecialchars($lang === 'en' ? 'Activate Account' : 'Активирай профила') ?></button>
            <a class="link" href="./student-login.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($lang === 'en' ? 'Back to login' : 'Назад към вход') ?></a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($tokenRecord && $student): ?>
  <script>
    (function () {
      var form = document.getElementById('student-activate-form');
      var password = document.getElementById('student-password');
      var confirmPassword = document.getElementById('student-password-confirm');
      if (!form || !password || !confirmPassword) {
        return;
      }

      function validityMessage(input) {
        if (input.validity.valueMissing) {
          return input.dataset.requiredMessage || '';
        }
        if (input.validity.tooShort) {
          return input.dataset.tooShortMessage || '';
        }
        return '';
      }

      function syncConfirmValidity() {
        if (confirmPassword.value !== '' && password.value !== confirmPassword.value) {
          confirmPassword.setCustomValidity(confirmPassword.dataset.mismatchMessage || '');
        } else {
          confirmPassword.setCustomValidity(validityMessage(confirmPassword));
        }
      }

      [password, confirmPassword].forEach(function (input) {
        input.addEventListener('input', function () {
          input.setCustomValidity(validityMessage(input));
          syncConfirmValidity();
        });
        input.addEventListener('invalid', function () {
          input.setCustomValidity(validityMessage(input));
          syncConfirmValidity();
        });
      });

      form.addEventListener('submit', function () {
        password.setCustomValidity(validityMessage(password));
        syncConfirmValidity();
      });
    }());
  </script>
  <?php endif; ?>
</body>
</html>
