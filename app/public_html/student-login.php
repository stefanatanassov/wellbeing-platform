<?php
require_once __DIR__ . '/content.php';

$lang = shine_bright_resolve_lang();
$content = shine_bright_load_content();
$brand = $content[$lang]['brand'] ?? $content['bg']['brand'];
$student = shine_bright_current_student();

if ($student && (string) ($student['account_status'] ?? '') === 'active') {
    header('Location: ./student-dashboard.php?lang=' . urlencode($lang));
    exit;
}

$error = '';
$email = '';

function sb_student_login_validation_text(string $lang, string $key): string
{
    $isEn = $lang === 'en';

    return match ($key) {
        'required' => $isEn ? 'Please fill out this field.' : 'Моля, попълнете това поле.',
        'email' => $isEn ? 'Enter a valid email address.' : 'Въведете валиден имейл адрес.',
        default => '',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $students = shine_bright_load_clients();
    $student = shine_bright_find_student_by_email($students, $email);

    if (!$student || (string) ($student['account_status'] ?? '') !== 'active' || trim((string) ($student['password_hash'] ?? '')) === '' || !password_verify($password, (string) ($student['password_hash'] ?? ''))) {
        $error = $lang === 'en'
            ? 'Login failed. Check your email and password.'
            : 'Неуспешен вход. Проверете имейла и паролата.';
    } else {
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
  <link rel="manifest" href="./manifest.webmanifest">
  <link rel="icon" href="./assets/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="./assets/app-icons/icon-180.png">
  <meta name="theme-color" content="#6b816f">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="SB Card">
  <title><?= htmlspecialchars(($lang === 'en' ? 'Student Login' : 'Вход за ученици') . ' | ' . ($brand['name'] ?? 'Shine Bright Yoga')) ?></title>
  <style>
    :root{--bg:#f3f5ef;--surface:#fff;--ink:#1d251f;--muted:#5f6b62;--outline:rgba(93,118,102,.18);--primary:#6b816f;--primary-ink:#fff;--secondary:#eef2ea;--error-bg:#f7e3df;--error-ink:#7a2b1d}
    *{box-sizing:border-box}
    body{margin:0;font-family:ui-sans-serif,system-ui,sans-serif;background:linear-gradient(180deg,#f4f6f1 0%,#edf1eb 100%);color:var(--ink)}
    .wrap{width:min(620px,calc(100% - 32px));margin:56px auto}
    .card{background:var(--surface);border:1px solid rgba(93,118,102,.14);border-radius:32px;padding:36px;box-shadow:0 28px 72px rgba(24,35,27,.07)}
    h1{margin:0 0 14px;font-size:clamp(2.2rem,4.8vw,3.6rem);line-height:.98;letter-spacing:-.04em;font-weight:800}
    p{margin:0;line-height:1.6;color:var(--muted);font-size:1rem;max-width:42ch}
    form{display:grid;gap:16px;margin-top:28px}
    label{display:block;font-weight:700;color:var(--ink)}
    input{width:100%;height:58px;padding:0 18px;border-radius:18px;border:1px solid var(--outline);background:#fbfcfa;font:inherit;color:var(--ink);margin-top:10px}
    input:focus{outline:none;border-color:#8fa092;box-shadow:0 0 0 4px rgba(107,129,111,.12)}
    button,a.link{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:0 24px;border-radius:999px;border:0;font:inherit;font-weight:700;text-decoration:none;white-space:nowrap}
    button{background:var(--primary);color:var(--primary-ink);cursor:pointer}
    a.link{background:var(--secondary);color:var(--ink)}
    .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:8px}
    .error{margin-top:18px;padding:13px 15px;border-radius:16px;background:var(--error-bg);color:var(--error-ink)}
    @media (max-width:640px){
      .wrap{width:min(100% - 24px,620px);margin:28px auto}
      .card{padding:24px;border-radius:24px}
      .actions{flex-direction:column}
      .actions > *{width:100%}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1><?= htmlspecialchars($lang === 'en' ? 'Student Login' : 'Вход за ученици') ?></h1>
      <p><?= htmlspecialchars($lang === 'en' ? 'Log in to view your visit cards and remaining visits.' : 'Влезте, за да видите картите си и оставащите посещения.') ?></p>
      <?php if ($error !== ''): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post" novalidate id="student-login-form">
        <label><?= htmlspecialchars($lang === 'en' ? 'Email' : 'Имейл') ?><input type="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($email) ?>" data-required-message="<?= htmlspecialchars(sb_student_login_validation_text($lang, 'required')) ?>" data-type-message="<?= htmlspecialchars(sb_student_login_validation_text($lang, 'email')) ?>"></label>
        <label><?= htmlspecialchars($lang === 'en' ? 'Password' : 'Парола') ?><input type="password" name="password" required autocomplete="current-password" data-required-message="<?= htmlspecialchars(sb_student_login_validation_text($lang, 'required')) ?>"></label>
        <div class="actions">
          <button type="submit"><?= htmlspecialchars($lang === 'en' ? 'Log In' : 'Вход') ?></button>
          <a class="link" href="./?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($lang === 'en' ? 'Back to site' : 'Назад към сайта') ?></a>
        </div>
      </form>
    </div>
  </div>
  <script>
    (function () {
      var form = document.getElementById('student-login-form');
      if (!form) {
        return;
      }

      form.querySelectorAll('input').forEach(function (input) {
        function setMessage() {
          if (input.validity.valueMissing) {
            input.setCustomValidity(input.dataset.requiredMessage || '');
          } else if (input.validity.typeMismatch) {
            input.setCustomValidity(input.dataset.typeMessage || '');
          } else {
            input.setCustomValidity('');
          }
        }

        input.addEventListener('input', setMessage);
        input.addEventListener('invalid', setMessage);
      });
    }());
  </script>
  <script src="./assets/student-app.js"></script>
</body>
</html>
