<?php
require_once __DIR__ . '/content.php';

$lang = shine_bright_resolve_lang();
$content = shine_bright_load_content();
$brand = $content[$lang]['brand'] ?? $content['bg']['brand'];
$redirect = shine_bright_safe_local_redirect((string) ($_GET['redirect'] ?? $_POST['redirect'] ?? './admin.php?lang=' . $lang), './admin.php?lang=' . $lang);
$error = '';
$email = '';
$usedTokenBootstrap = false;

if (shine_bright_admin_is_authenticated()) {
    header('Location: ' . $redirect);
    exit;
}

$requestToken = shine_bright_admin_request_token();
if ($requestToken !== '' && shine_bright_admin_authenticate_via_token($requestToken)) {
    header('Location: ' . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (shine_bright_admin_login($email, $password)) {
        header('Location: ' . $redirect);
        exit;
    }

    $error = $lang === 'en'
        ? 'Login failed. Check your email and password.'
        : 'Неуспешен вход. Проверете имейла и паролата.';
}

function sb_admin_login_text(string $lang, string $key): string
{
    $isEn = $lang === 'en';

    return match ($key) {
        'title' => $isEn ? 'Admin Login' : 'Вход за админ',
        'intro' => $isEn ? 'Sign in to manage classes, students, visit cards, and bookings.' : 'Влезте, за да управлявате класове, ученици, карти посещения и записвания.',
        'email' => $isEn ? 'Email' : 'Имейл',
        'password' => $isEn ? 'Password' : 'Парола',
        'submit' => $isEn ? 'Log In' : 'Вход',
        'back' => $isEn ? 'Back to site' : 'Назад към сайта',
        'required' => $isEn ? 'Please fill out this field.' : 'Моля, попълнете това поле.',
        'email_invalid' => $isEn ? 'Enter a valid email address.' : 'Въведете валиден имейл адрес.',
        'not_configured' => $isEn
            ? 'Password login is not configured yet. Use the existing secure admin link once to start a session, then set a password in server config.'
            : 'Входът с парола все още не е конфигуриран. Използвайте съществуващата защитена админ връзка веднъж, за да стартирате сесия, след което задайте парола в server config.',
        default => '',
    };
}
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(sb_admin_login_text($lang, 'title') . ' | ' . ($brand['name'] ?? 'Shine Bright Yoga')) ?></title>
  <style>
    :root{--bg:#f3f5ef;--surface:#fff;--ink:#1d251f;--muted:#5f6b62;--outline:rgba(93,118,102,.18);--primary:#6b816f;--primary-ink:#fff;--secondary:#eef2ea;--warning-bg:#f4efe1;--warning-ink:#6b5b22;--error-bg:#f7e3df;--error-ink:#7a2b1d}
    *{box-sizing:border-box}
    body{margin:0;font-family:ui-sans-serif,system-ui,sans-serif;background:linear-gradient(180deg,#f4f6f1 0%,#edf1eb 100%);color:var(--ink)}
    .wrap{width:min(640px,calc(100% - 32px));margin:56px auto}
    .card{background:var(--surface);border:1px solid rgba(93,118,102,.14);border-radius:32px;padding:36px;box-shadow:0 28px 72px rgba(24,35,27,.07)}
    h1{margin:0 0 14px;font-size:clamp(2.2rem,4.8vw,3.6rem);line-height:.98;letter-spacing:-.04em;font-weight:800}
    p{margin:0;line-height:1.6;color:var(--muted);font-size:1rem;max-width:44ch}
    form{display:grid;gap:16px;margin-top:28px}
    label{display:block;font-weight:700;color:var(--ink)}
    input{width:100%;height:58px;padding:0 18px;border-radius:18px;border:1px solid var(--outline);background:#fbfcfa;font:inherit;color:var(--ink);margin-top:10px}
    input:focus{outline:none;border-color:#8fa092;box-shadow:0 0 0 4px rgba(107,129,111,.12)}
    button,a.link{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:0 24px;border-radius:999px;border:0;font:inherit;font-weight:700;text-decoration:none;white-space:nowrap}
    button{background:var(--primary);color:var(--primary-ink);cursor:pointer}
    a.link{background:var(--secondary);color:var(--ink)}
    .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:8px}
    .message{margin-top:18px;padding:13px 15px;border-radius:16px}
    .message.error{background:var(--error-bg);color:var(--error-ink)}
    .message.warning{background:var(--warning-bg);color:var(--warning-ink)}
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
      <h1><?= htmlspecialchars(sb_admin_login_text($lang, 'title')) ?></h1>
      <p><?= htmlspecialchars(sb_admin_login_text($lang, 'intro')) ?></p>
      <?php if (!shine_bright_admin_password_configured()): ?>
        <div class="message warning"><?= htmlspecialchars(sb_admin_login_text($lang, 'not_configured')) ?></div>
      <?php endif; ?>
      <?php if ($error !== ''): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post" novalidate id="admin-login-form">
        <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
        <label><?= htmlspecialchars(sb_admin_login_text($lang, 'email')) ?><input type="email" name="email" required autocomplete="username" value="<?= htmlspecialchars($email) ?>" data-required-message="<?= htmlspecialchars(sb_admin_login_text($lang, 'required')) ?>" data-type-message="<?= htmlspecialchars(sb_admin_login_text($lang, 'email_invalid')) ?>"></label>
        <label><?= htmlspecialchars(sb_admin_login_text($lang, 'password')) ?><input type="password" name="password" required autocomplete="current-password" data-required-message="<?= htmlspecialchars(sb_admin_login_text($lang, 'required')) ?>"></label>
        <div class="actions">
          <button type="submit"><?= htmlspecialchars(sb_admin_login_text($lang, 'submit')) ?></button>
          <a class="link" href="./?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars(sb_admin_login_text($lang, 'back')) ?></a>
        </div>
      </form>
    </div>
  </div>
  <script>
    (function () {
      var form = document.getElementById('admin-login-form');
      if (!form) return;
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
</body>
</html>
