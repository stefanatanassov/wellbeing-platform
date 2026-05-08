<?php

$shineBrightConfig = [];
$shineBrightConfigCandidates = [];

$shineBrightConfigPath = getenv('SHINE_BRIGHT_CONFIG_PATH');
if (is_string($shineBrightConfigPath) && trim($shineBrightConfigPath) !== '') {
    $shineBrightConfigCandidates[] = trim($shineBrightConfigPath);
}

$shineBrightConfigCandidates[] = dirname(__DIR__) . '/shine-bright-config.php';
$shineBrightConfigCandidates[] = __DIR__ . '/config.local.php';

foreach ($shineBrightConfigCandidates as $candidate) {
    if (!is_string($candidate) || $candidate === '' || !is_file($candidate)) {
        continue;
    }

    $loadedConfig = require $candidate;
    if (is_array($loadedConfig)) {
        $shineBrightConfig = array_replace($shineBrightConfig, $loadedConfig);
    }
}

function shine_bright_config_value(array $config, string $envName, string $key, mixed $default): mixed
{
    $envValue = getenv($envName);
    if ($envValue !== false && trim((string) $envValue) !== '') {
        return is_string($envValue) ? trim($envValue) : $envValue;
    }

    if (array_key_exists($key, $config) && $config[$key] !== null && $config[$key] !== '') {
        return $config[$key];
    }

    return $default;
}

define('SHINE_BRIGHT_DATA_DIR', (string) shine_bright_config_value($shineBrightConfig, 'SHINE_BRIGHT_DATA_DIR', 'data_dir', __DIR__ . '/data'));
define('SHINE_BRIGHT_MEDIA_DIR', (string) shine_bright_config_value($shineBrightConfig, 'SHINE_BRIGHT_MEDIA_DIR', 'media_dir', __DIR__ . '/media'));
define('SHINE_BRIGHT_CONTENT_PATH', SHINE_BRIGHT_DATA_DIR . '/content.json');
define('SHINE_BRIGHT_INQUIRIES_PATH', SHINE_BRIGHT_DATA_DIR . '/inquiries.jsonl');
define('SHINE_BRIGHT_RESERVATIONS_PATH', SHINE_BRIGHT_DATA_DIR . '/reservations.json');
define('SHINE_BRIGHT_API_TOKEN_PATH', SHINE_BRIGHT_DATA_DIR . '/api-token.txt');
define('SHINE_BRIGHT_IP_SALT', (string) shine_bright_config_value($shineBrightConfig, 'SHINE_BRIGHT_IP_SALT', 'ip_salt', 'shine-bright-demo-salt'));
define('SHINE_BRIGHT_ADMIN_TOKEN', (string) shine_bright_config_value($shineBrightConfig, 'SHINE_BRIGHT_ADMIN_TOKEN', 'admin_token', 'hash123'));
define('SHINE_BRIGHT_ADMIN_EMAIL', (string) shine_bright_config_value($shineBrightConfig, 'SHINE_BRIGHT_ADMIN_EMAIL', 'admin_email', 'maria@shinebrightyoga.com'));
define('SHINE_BRIGHT_ADMIN_PASSWORD_HASH', (string) shine_bright_config_value($shineBrightConfig, 'SHINE_BRIGHT_ADMIN_PASSWORD_HASH', 'admin_password_hash', ''));
define('SHINE_BRIGHT_ALLOW_ADMIN_TOKEN_FALLBACK', filter_var(shine_bright_config_value($shineBrightConfig, 'SHINE_BRIGHT_ALLOW_ADMIN_TOKEN_FALLBACK', 'allow_admin_token_fallback', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
define('SHINE_BRIGHT_QR_SECRET', (string) shine_bright_config_value($shineBrightConfig, 'SHINE_BRIGHT_QR_SECRET', 'qr_secret', hash('sha256', SHINE_BRIGHT_IP_SALT . '|' . SHINE_BRIGHT_ADMIN_TOKEN)));
define('SHINE_BRIGHT_MAIL_FROM_NAME', (string) shine_bright_config_value($shineBrightConfig, 'SHINE_BRIGHT_MAIL_FROM_NAME', 'mail_from_name', 'Shine Bright Yoga'));
define('SHINE_BRIGHT_MAIL_FROM_EMAIL', (string) shine_bright_config_value($shineBrightConfig, 'SHINE_BRIGHT_MAIL_FROM_EMAIL', 'mail_from_email', 'maria@shinebrightyoga.com'));
define('SHINE_BRIGHT_SITE_BASE_URL', rtrim((string) shine_bright_config_value($shineBrightConfig, 'SHINE_BRIGHT_SITE_BASE_URL', 'site_base_url', 'https://www.shinebrightyoga.com'), '/'));

function shine_bright_ensure_data_dir(): void
{
    if (!is_dir(SHINE_BRIGHT_DATA_DIR)) {
        mkdir(SHINE_BRIGHT_DATA_DIR, 0775, true);
    }
}

function shine_bright_ensure_media_dir(): void
{
    if (!is_dir(SHINE_BRIGHT_MEDIA_DIR)) {
        mkdir(SHINE_BRIGHT_MEDIA_DIR, 0775, true);
    }
}

function shine_bright_admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('shine_bright_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => str_starts_with(SHINE_BRIGHT_SITE_BASE_URL, 'https://'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function shine_bright_admin_password_configured(): bool
{
    return trim(SHINE_BRIGHT_ADMIN_PASSWORD_HASH) !== '';
}

function shine_bright_admin_login_url(string $lang = 'bg', string $redirect = ''): string
{
    $params = ['lang' => shine_bright_normalize_lang($lang)];
    if (trim($redirect) !== '') {
        $params['redirect'] = $redirect;
    }

    return './admin-login.php?' . http_build_query($params);
}

function shine_bright_admin_logout_url(string $lang = 'bg'): string
{
    return './admin-logout.php?lang=' . urlencode(shine_bright_normalize_lang($lang));
}

function shine_bright_admin_request_token(): string
{
    if (isset($_SERVER['HTTP_X_ADMIN_TOKEN']) && is_string($_SERVER['HTTP_X_ADMIN_TOKEN'])) {
        return trim($_SERVER['HTTP_X_ADMIN_TOKEN']);
    }

    if (isset($_POST['token']) && is_string($_POST['token'])) {
        return trim($_POST['token']);
    }

    if (isset($_GET['token']) && is_string($_GET['token'])) {
        return trim($_GET['token']);
    }

    return '';
}

function shine_bright_admin_token_matches(string $token): bool
{
    return $token !== '' && hash_equals(SHINE_BRIGHT_ADMIN_TOKEN, $token);
}

function shine_bright_admin_is_authenticated(): bool
{
    shine_bright_admin_session_start();
    return !empty($_SESSION['shine_bright_admin_authenticated']);
}

function shine_bright_admin_login(string $email, string $password): bool
{
    if (!shine_bright_admin_password_configured()) {
        return false;
    }

    $normalizedEmail = strtolower(trim($email));
    if ($normalizedEmail === '' || !hash_equals(strtolower(SHINE_BRIGHT_ADMIN_EMAIL), $normalizedEmail)) {
        return false;
    }

    if (!password_verify($password, SHINE_BRIGHT_ADMIN_PASSWORD_HASH)) {
        return false;
    }

    shine_bright_admin_session_start();
    session_regenerate_id(true);
    $_SESSION['shine_bright_admin_authenticated'] = true;
    $_SESSION['shine_bright_admin_email'] = SHINE_BRIGHT_ADMIN_EMAIL;
    $_SESSION['shine_bright_admin_auth_method'] = 'password';
    $_SESSION['shine_bright_admin_logged_in_at'] = gmdate('c');

    return true;
}

function shine_bright_admin_authenticate_via_token(string $token): bool
{
    if (!SHINE_BRIGHT_ALLOW_ADMIN_TOKEN_FALLBACK || !shine_bright_admin_token_matches($token)) {
        return false;
    }

    shine_bright_admin_session_start();
    session_regenerate_id(true);
    $_SESSION['shine_bright_admin_authenticated'] = true;
    $_SESSION['shine_bright_admin_email'] = SHINE_BRIGHT_ADMIN_EMAIL;
    $_SESSION['shine_bright_admin_auth_method'] = 'token';
    $_SESSION['shine_bright_admin_logged_in_at'] = gmdate('c');

    return true;
}

function shine_bright_admin_logout(): void
{
    shine_bright_admin_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) ($params['secure'] ?? false), (bool) ($params['httponly'] ?? false));
    }
    session_destroy();
}

function shine_bright_current_request_path_with_query(array $removeKeys = []): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $parts = parse_url($uri);
    $path = (string) ($parts['path'] ?? '/');
    $query = [];
    if (isset($parts['query'])) {
        parse_str((string) $parts['query'], $query);
    }

    foreach ($removeKeys as $removeKey) {
        unset($query[$removeKey]);
    }

    $queryString = http_build_query($query);
    return $path . ($queryString !== '' ? '?' . $queryString : '');
}

function shine_bright_safe_local_redirect(string $target, string $fallback = './admin.php'): string
{
    $target = trim($target);
    if ($target === '' || str_starts_with($target, 'http://') || str_starts_with($target, 'https://') || str_starts_with($target, '//')) {
        return $fallback;
    }

    return str_starts_with($target, '/') ? $target : './' . ltrim($target, './');
}

function shine_bright_require_admin(): string
{
    if (shine_bright_admin_is_authenticated()) {
        return '';
    }

    $token = shine_bright_admin_request_token();
    if (shine_bright_admin_authenticate_via_token($token)) {
        return $token;
    }

    $lang = isset($_REQUEST['lang']) && is_string($_REQUEST['lang']) ? $_REQUEST['lang'] : 'bg';
    $redirect = shine_bright_current_request_path_with_query(['token']);
    header('Location: ' . shine_bright_admin_login_url($lang, $redirect));
    exit;
}

function shine_bright_require_admin_api(): void
{
    if (shine_bright_admin_is_authenticated()) {
        return;
    }

    $token = shine_bright_admin_request_token();
    if (shine_bright_admin_authenticate_via_token($token)) {
        return;
    }

    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function shine_bright_require_api_token(): string
{
    $expectedToken = shine_bright_get_api_token();
    if ($expectedToken === '') {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'API token is not configured.']);
        exit;
    }

    $token = null;

    if (isset($_SERVER['HTTP_X_API_TOKEN']) && is_string($_SERVER['HTTP_X_API_TOKEN'])) {
        $token = $_SERVER['HTTP_X_API_TOKEN'];
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION']) && is_string($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = trim($_SERVER['HTTP_AUTHORIZATION']);
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            $token = trim($matches[1]);
        }
    }

    if (!is_string($token) || !hash_equals($expectedToken, $token)) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        exit;
    }

    return $token;
}

function shine_bright_api_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function shine_bright_api_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function shine_bright_api_request_method(): string
{
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($method === 'POST' && isset($_POST['_method']) && is_string($_POST['_method'])) {
        $override = strtoupper(trim($_POST['_method']));
        if ($override !== '') {
            $method = $override;
        }
    }

    if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) && is_string($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
        $override = strtoupper(trim($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']));
        if ($override !== '') {
            $method = $override;
        }
    }

    return $method;
}

function shine_bright_get_api_token(): string
{
    $envToken = getenv('SHINE_BRIGHT_API_TOKEN');
    if (is_string($envToken) && trim($envToken) !== '') {
        return trim($envToken);
    }

    if (is_file(SHINE_BRIGHT_API_TOKEN_PATH)) {
        $fileToken = trim((string) file_get_contents(SHINE_BRIGHT_API_TOKEN_PATH));
        if ($fileToken !== '') {
            return $fileToken;
        }
    }

    return '';
}

function shine_bright_normalize_lang(?string $lang): string
{
    return $lang === 'en' ? 'en' : 'bg';
}

function shine_bright_slugify(string $value): string
{
    $value = trim(strtolower($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'item-' . substr(bin2hex(random_bytes(4)), 0, 8);
}

function shine_bright_base64url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function shine_bright_base64url_decode(string $value): string|false
{
    $padding = strlen($value) % 4;
    if ($padding > 0) {
        $value .= str_repeat('=', 4 - $padding);
    }

    return base64_decode(strtr($value, '-_', '+/'), true);
}

function shine_bright_qr_payload(array $student, array $pack, int $ttlSeconds = 600): array
{
    $now = time();

    return [
        'v' => 1,
        'student_id' => (string) ($student['id'] ?? ''),
        'pack_id' => (string) ($pack['id'] ?? ''),
        'iat' => $now,
        'exp' => $now + max(60, $ttlSeconds),
        'nonce' => bin2hex(random_bytes(6)),
    ];
}

function shine_bright_sign_qr_payload(array $payload): string
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        throw new RuntimeException('Unable to encode QR payload.');
    }

    $encoded = shine_bright_base64url_encode($json);
    $signature = hash_hmac('sha256', $encoded, SHINE_BRIGHT_QR_SECRET, true);

    return 'SBY1.' . $encoded . '.' . shine_bright_base64url_encode($signature);
}

function shine_bright_verify_qr_token(string $token): ?array
{
    $token = trim($token);
    if ($token === '' || !str_starts_with($token, 'SBY1.')) {
        return null;
    }

    $parts = explode('.', $token, 3);
    if (count($parts) !== 3) {
        return null;
    }

    [, $encodedPayload, $encodedSignature] = $parts;
    $expectedSignature = hash_hmac('sha256', $encodedPayload, SHINE_BRIGHT_QR_SECRET, true);
    $providedSignature = shine_bright_base64url_decode($encodedSignature);
    if (!is_string($providedSignature) || !hash_equals($expectedSignature, $providedSignature)) {
        return null;
    }

    $decodedPayload = shine_bright_base64url_decode($encodedPayload);
    if (!is_string($decodedPayload)) {
        return null;
    }

    $payload = json_decode($decodedPayload, true);
    if (!is_array($payload)) {
        return null;
    }

    $expiresAt = (int) ($payload['exp'] ?? 0);
    $issuedAt = (int) ($payload['iat'] ?? 0);
    if ($expiresAt <= time() || $issuedAt <= 0 || $expiresAt <= $issuedAt) {
        return null;
    }

    if (trim((string) ($payload['student_id'] ?? '')) === '' || trim((string) ($payload['pack_id'] ?? '')) === '') {
        return null;
    }

    return $payload;
}

function shine_bright_read_inquiries(int $limit = 50): array
{
    if (!is_file(SHINE_BRIGHT_INQUIRIES_PATH)) {
        return [];
    }

    $lines = @file(SHINE_BRIGHT_INQUIRIES_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return [];
    }

    $lines = array_slice(array_reverse($lines), 0, max(1, $limit));
    $records = [];

    foreach ($lines as $line) {
        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
            $records[] = $decoded;
        }
    }

    return $records;
}

function shine_bright_load_reservations(): array
{
    shine_bright_ensure_data_dir();

    if (!is_file(SHINE_BRIGHT_RESERVATIONS_PATH)) {
        file_put_contents(SHINE_BRIGHT_RESERVATIONS_PATH, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return [];
    }

    $json = file_get_contents(SHINE_BRIGHT_RESERVATIONS_PATH);
    $decoded = is_string($json) ? json_decode($json, true) : null;

    return is_array($decoded) ? $decoded : [];
}

function shine_bright_save_reservations(array $reservations): void
{
    shine_bright_ensure_data_dir();
    file_put_contents(SHINE_BRIGHT_RESERVATIONS_PATH, json_encode(array_values($reservations), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function shine_bright_create_reservation(array $reservation): array
{
    $reservations = shine_bright_load_reservations();
    $reservation['id'] = $reservation['id'] ?? 'res-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    $reservations[] = $reservation;
    shine_bright_save_reservations($reservations);

    return $reservation;
}

function shine_bright_weekday_labels(string $lang): array
{
    return $lang === 'en'
        ? [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ]
        : [
            'monday' => 'Понеделник',
            'tuesday' => 'Вторник',
            'wednesday' => 'Сряда',
            'thursday' => 'Четвъртък',
            'friday' => 'Петък',
            'saturday' => 'Събота',
            'sunday' => 'Неделя',
        ];
}

function shine_bright_weekday_from_datetime(?string $value): string
{
    $date = shine_bright_datetime($value);
    if (!$date) {
        return '';
    }

    return strtolower($date->format('l'));
}

function shine_bright_normalize_class_schedule(array $schedule, int $index = 0): array
{
    $weekday = strtolower(trim((string) ($schedule['weekday'] ?? '')));
    $allowedWeekdays = array_keys(shine_bright_weekday_labels('en'));
    if (!in_array($weekday, $allowedWeekdays, true)) {
        $weekday = '';
    }

    $idSeed = trim((string) ($schedule['id'] ?? ''));
    if ($idSeed === '') {
        $idSeed = ($weekday !== '' ? $weekday : 'schedule') . '-' . ($index + 1);
    }

    return [
        'id' => shine_bright_slugify($idSeed),
        'weekday' => $weekday,
        'start_time' => trim((string) ($schedule['start_time'] ?? '')),
        'end_time' => trim((string) ($schedule['end_time'] ?? '')),
        'location' => trim((string) ($schedule['location'] ?? '')),
        'maps_url' => trim((string) ($schedule['maps_url'] ?? '')),
    ];
}

function shine_bright_class_schedules(array $item): array
{
    $raw = $item['schedules'] ?? [];
    if (!is_array($raw)) {
        return [];
    }

    $schedules = [];
    foreach ($raw as $index => $schedule) {
        if (!is_array($schedule)) {
            continue;
        }
        $schedules[] = shine_bright_normalize_class_schedule($schedule, (int) $index);
    }

    return $schedules;
}

function shine_bright_primary_class_schedule(array $item): ?array
{
    $schedules = shine_bright_class_schedules($item);
    return $schedules[0] ?? null;
}

function shine_bright_find_class_schedule(array $item, string $scheduleId): ?array
{
    foreach (shine_bright_class_schedules($item) as $schedule) {
        if ((string) ($schedule['id'] ?? '') === $scheduleId) {
            return $schedule;
        }
    }

    return null;
}

function shine_bright_class_schedule_time_label(array $schedule): string
{
    $start = trim((string) ($schedule['start_time'] ?? ''));
    $end = trim((string) ($schedule['end_time'] ?? ''));
    if ($start === '') {
        return '';
    }

    return $end !== '' ? ($start . ' - ' . $end) : $start;
}

function shine_bright_class_schedule_day_label(array $schedule, string $lang): string
{
    $weekday = trim((string) ($schedule['weekday'] ?? ''));
    if ($weekday === '') {
        return '';
    }

    $labels = shine_bright_weekday_labels($lang);
    return $labels[$weekday] ?? $weekday;
}

function shine_bright_class_schedule_summary(array $item, string $lang, int $limit = 2): array
{
    $schedules = shine_bright_class_schedules($item);
    if ($schedules === []) {
        $fallbackDate = shine_bright_format_date_range($item, $lang);
        $fallbackTime = shine_bright_format_time_range($item);
        return array_values(array_filter([$fallbackDate, $fallbackTime], static fn ($value): bool => $value !== ''));
    }

    $parts = [];
    foreach (array_slice($schedules, 0, max(1, $limit)) as $schedule) {
        $day = shine_bright_class_schedule_day_label($schedule, $lang);
        $time = shine_bright_class_schedule_time_label($schedule);
        $label = trim($day . ($time !== '' ? ' · ' . $time : ''));
        if ($label !== '') {
            $parts[] = $label;
        }
    }

    if (count($schedules) > $limit) {
        $parts[] = $lang === 'en'
            ? '+' . (count($schedules) - $limit) . ' more'
            : '+' . (count($schedules) - $limit) . ' още';
    }

    return $parts;
}

function shine_bright_site_timezone(): DateTimeZone
{
    try {
        return new DateTimeZone('Europe/Sofia');
    } catch (Throwable $e) {
        return new DateTimeZone(date_default_timezone_get() ?: 'UTC');
    }
}

function shine_bright_month_labels(string $lang): array
{
    return $lang === 'en'
        ? [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December']
        : [1 => 'януари', 2 => 'февруари', 3 => 'март', 4 => 'април', 5 => 'май', 6 => 'юни', 7 => 'юли', 8 => 'август', 9 => 'септември', 10 => 'октомври', 11 => 'ноември', 12 => 'декември'];
}

function shine_bright_format_session_date(DateTimeImmutable $date, string $lang): string
{
    $months = shine_bright_month_labels($lang);
    return $date->format('j') . ' ' . ($months[(int) $date->format('n')] ?? $date->format('F'));
}

function shine_bright_build_class_session(array $item, array $schedule, DateTimeImmutable $date, string $lang): array
{
    $timezone = shine_bright_site_timezone();
    $startTime = trim((string) ($schedule['start_time'] ?? ''));
    $endTime = trim((string) ($schedule['end_time'] ?? ''));
    $startDateTime = $date;
    $endDateTime = $date;

    if ($startTime !== '' && preg_match('/^\d{2}:\d{2}$/', $startTime) === 1) {
        [$hour, $minute] = array_map('intval', explode(':', $startTime));
        $startDateTime = $date->setTime($hour, $minute, 0);
    }
    if ($endTime !== '' && preg_match('/^\d{2}:\d{2}$/', $endTime) === 1) {
        [$hour, $minute] = array_map('intval', explode(':', $endTime));
        $endDateTime = $date->setTime($hour, $minute, 0);
    } else {
        $endDateTime = $startDateTime;
    }

    $weekday = (string) ($schedule['weekday'] ?? '');
    $dateLabel = trim(shine_bright_class_schedule_day_label($schedule, $lang) . ', ' . shine_bright_format_session_date($date, $lang));
    $timeLabel = shine_bright_class_schedule_time_label($schedule);
    $summaryLabel = trim($dateLabel . ($timeLabel !== '' ? ' · ' . $timeLabel : ''));

    return [
        'id' => shine_bright_slugify((string) ($item['id'] ?? 'class') . '-' . (string) ($schedule['id'] ?? 'schedule') . '-' . $date->format('Ymd')),
        'class_id' => (string) ($item['id'] ?? ''),
        'class_title' => (string) ($item['title'] ?? ''),
        'schedule_id' => (string) ($schedule['id'] ?? ''),
        'weekday' => $weekday,
        'date' => $date->format('Y-m-d'),
        'starts_at' => $startDateTime->setTimezone($timezone)->format('c'),
        'ends_at' => $endDateTime->setTimezone($timezone)->format('c'),
        'date_label' => $dateLabel,
        'time_label' => $timeLabel,
        'summary_label' => $summaryLabel,
        'location' => (string) ($schedule['location'] ?? ''),
        'maps_url' => (string) ($schedule['maps_url'] ?? ''),
    ];
}

function shine_bright_class_upcoming_sessions(array $item, string $lang, int $daysAhead = 21, int $limit = 8): array
{
    $schedules = shine_bright_class_schedules($item);
    if ($schedules === []) {
        return [];
    }

    $timezone = shine_bright_site_timezone();
    $today = new DateTimeImmutable('today', $timezone);
    $weekdayMap = [
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6,
        'sunday' => 7,
    ];

    $sessions = [];
    for ($offset = 0; $offset <= max(0, $daysAhead); $offset++) {
        $date = $today->modify('+' . $offset . ' day');
        $weekdayNumber = (int) $date->format('N');
        foreach ($schedules as $schedule) {
            $weekday = (string) ($schedule['weekday'] ?? '');
            if (($weekdayMap[$weekday] ?? 0) !== $weekdayNumber) {
                continue;
            }
            $sessions[] = shine_bright_build_class_session($item, $schedule, $date, $lang);
        }
    }

    usort($sessions, static function (array $left, array $right): int {
        return strcmp((string) ($left['starts_at'] ?? ''), (string) ($right['starts_at'] ?? ''));
    });

    return array_slice($sessions, 0, max(1, $limit));
}

function shine_bright_find_class_session(array $item, string $sessionId, string $lang, int $daysAhead = 56): ?array
{
    foreach (shine_bright_class_upcoming_sessions($item, $lang, $daysAhead, 64) as $session) {
        if ((string) ($session['id'] ?? '') === $sessionId) {
            return $session;
        }
    }

    return null;
}

function shine_bright_datetime(?string $value): ?DateTime
{
    if (!is_string($value) || trim($value) === '') {
        return null;
    }

    try {
        return new DateTime($value);
    } catch (Throwable $e) {
        return null;
    }
}

function shine_bright_format_date_range(array $item, string $lang): string
{
    $primarySchedule = shine_bright_primary_class_schedule($item);
    if ($primarySchedule) {
        return shine_bright_class_schedule_day_label($primarySchedule, $lang);
    }

    $start = shine_bright_datetime($item['start_at'] ?? null);
    $end = shine_bright_datetime($item['end_at'] ?? null);

    if (!$start) {
        return (string) ($item['date'] ?? '');
    }

    $bgMonths = [1 => 'януари', 2 => 'февруари', 3 => 'март', 4 => 'април', 5 => 'май', 6 => 'юни', 7 => 'юли', 8 => 'август', 9 => 'септември', 10 => 'октомври', 11 => 'ноември', 12 => 'декември'];
    $enMonths = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
    $months = $lang === 'bg' ? $bgMonths : $enMonths;
    $startLabel = $lang === 'bg'
        ? $start->format('j') . ' ' . $months[(int) $start->format('n')] . ' ' . $start->format('Y')
        : $months[(int) $start->format('n')] . ' ' . $start->format('j, Y');
    if (!$end) {
        return $startLabel;
    }

    if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
        return $startLabel;
    }

    $endLabel = $lang === 'bg'
        ? $end->format('j') . ' ' . $months[(int) $end->format('n')] . ' ' . $end->format('Y')
        : $months[(int) $end->format('n')] . ' ' . $end->format('j, Y');

    return $startLabel . ' - ' . $endLabel;
}

function shine_bright_format_time_range(array $item): string
{
    $primarySchedule = shine_bright_primary_class_schedule($item);
    if ($primarySchedule) {
        return shine_bright_class_schedule_time_label($primarySchedule);
    }

    $start = shine_bright_datetime($item['start_at'] ?? null);
    $end = shine_bright_datetime($item['end_at'] ?? null);

    if (!$start) {
        return (string) ($item['time'] ?? '');
    }

    $label = $start->format('H:i');
    if ($end) {
        $label .= ' - ' . $end->format('H:i');
    }

    return $label;
}

function shine_bright_price_label(array $item): string
{
    $price = trim((string) ($item['price_eur'] ?? ''));
    if ($price === '') {
        return (string) ($item['price'] ?? '');
    }

    return '€' . $price;
}

function shine_bright_update_reservation_record(array &$reservations, string $reservationId, callable $mutator): ?array
{
    foreach ($reservations as $index => $reservation) {
        if (($reservation['id'] ?? '') !== $reservationId) {
            continue;
        }

        $updated = $mutator($reservation);
        if (!is_array($updated)) {
            return null;
        }

        $reservations[$index] = $updated;
        return $updated;
    }

    return null;
}

function shine_bright_find_reservation(array $reservations, string $reservationId): ?array
{
    foreach ($reservations as $reservation) {
        if ((string) ($reservation['id'] ?? '') === $reservationId) {
            return is_array($reservation) ? $reservation : null;
        }
    }

    return null;
}

function shine_bright_send_text_mail(string $to, string $subject, string $body, string $replyTo = ''): bool
{
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $encodedSubject = function_exists('mb_encode_mimeheader')
        ? mb_encode_mimeheader($subject, 'UTF-8')
        : $subject;

    $fromName = function_exists('mb_encode_mimeheader')
        ? mb_encode_mimeheader(SHINE_BRIGHT_MAIL_FROM_NAME, 'UTF-8')
        : SHINE_BRIGHT_MAIL_FROM_NAME;

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $fromName . ' <' . SHINE_BRIGHT_MAIL_FROM_EMAIL . '>',
    ];

    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    return @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
}

function shine_bright_send_multipart_mail(string $to, string $subject, string $textBody, string $htmlBody, string $replyTo = ''): bool
{
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $encodedSubject = function_exists('mb_encode_mimeheader')
        ? mb_encode_mimeheader($subject, 'UTF-8')
        : $subject;

    $fromName = function_exists('mb_encode_mimeheader')
        ? mb_encode_mimeheader(SHINE_BRIGHT_MAIL_FROM_NAME, 'UTF-8')
        : SHINE_BRIGHT_MAIL_FROM_NAME;

    $boundary = '=_shinebright_' . bin2hex(random_bytes(12));
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'From: ' . $fromName . ' <' . SHINE_BRIGHT_MAIL_FROM_EMAIL . '>',
    ];

    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    $message = [];
    $message[] = 'This is a multi-part message in MIME format.';
    $message[] = '--' . $boundary;
    $message[] = 'Content-Type: text/plain; charset=UTF-8';
    $message[] = 'Content-Transfer-Encoding: 8bit';
    $message[] = '';
    $message[] = $textBody;
    $message[] = '--' . $boundary;
    $message[] = 'Content-Type: text/html; charset=UTF-8';
    $message[] = 'Content-Transfer-Encoding: 8bit';
    $message[] = '';
    $message[] = $htmlBody;
    $message[] = '--' . $boundary . '--';

    return @mail($to, $encodedSubject, implode("\r\n", $message), implode("\r\n", $headers));
}

function shine_bright_compose_branded_email_html(
    string $lang,
    string $brandName,
    string $greeting,
    string $title,
    array $introParagraphs,
    ?array $cta = null,
    array $detailRows = [],
    array $footerParagraphs = [],
    ?array $fallback = null
): string {
    $lang = $lang === 'en' ? 'en' : 'bg';
    $escapedBrandName = htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8');
    $escapedGreeting = htmlspecialchars($greeting, ENT_QUOTES, 'UTF-8');
    $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

    $introHtml = '';
    foreach ($introParagraphs as $paragraph) {
        $paragraph = trim((string) $paragraph);
        if ($paragraph === '') {
            continue;
        }
        $introHtml .= '<p style="margin:0 0 10px;font-size:17px;line-height:1.7;color:#435248;">' . htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    $ctaHtml = '';
    if (is_array($cta) && trim((string) ($cta['url'] ?? '')) !== '' && trim((string) ($cta['label'] ?? '')) !== '') {
        $ctaHtml = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;"><tr><td align="center" bgcolor="#6b816f" style="border-radius:999px;"><a href="' .
            htmlspecialchars((string) $cta['url'], ENT_QUOTES, 'UTF-8') .
            '" style="display:inline-block;padding:16px 28px;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;">' .
            htmlspecialchars((string) $cta['label'], ENT_QUOTES, 'UTF-8') .
            '</a></td></tr></table>';
    }

    $detailsHtml = '';
    if ($detailRows !== []) {
        $detailsHtml .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 18px;background:#f7f9f5;border:1px solid rgba(93,118,102,0.12);border-radius:20px;">';
        foreach ($detailRows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            $isLink = !empty($row['is_link']) && trim((string) ($row['href'] ?? '')) !== '';
            if ($label === '' || $value === '') {
                continue;
            }
            $detailsHtml .= '<tr><td style="padding:14px 20px;' . ($isLink ? '' : 'border-bottom:1px solid rgba(93,118,102,0.08);') . '">';
            $detailsHtml .= '<p style="margin:0 0 4px;font-size:12px;line-height:1.5;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#6b816f;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</p>';
            if ($isLink) {
                $detailsHtml .= '<p style="margin:0;font-size:16px;line-height:1.6;"><a href="' . htmlspecialchars((string) $row['href'], ENT_QUOTES, 'UTF-8') . '" style="color:#6b816f;text-decoration:underline;">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</a></p>';
            } else {
                $detailsHtml .= '<p style="margin:0;font-size:16px;line-height:1.6;color:#1b231d;font-weight:700;">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</p>';
            }
            $detailsHtml .= '</td></tr>';
        }
        $detailsHtml .= '</table>';
    }

    $fallbackHtml = '';
    if (is_array($fallback) && trim((string) ($fallback['label'] ?? '')) !== '' && trim((string) ($fallback['url'] ?? '')) !== '') {
        $fallbackHtml = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 18px;background:#f7f9f5;border:1px solid rgba(93,118,102,0.12);border-radius:20px;"><tr><td style="padding:18px 20px;"><p style="margin:0 0 8px;font-size:13px;line-height:1.5;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#6b816f;">' .
            htmlspecialchars((string) $fallback['label'], ENT_QUOTES, 'UTF-8') .
            '</p><p style="margin:0;font-size:14px;line-height:1.7;word-break:break-word;"><a href="' .
            htmlspecialchars((string) $fallback['url'], ENT_QUOTES, 'UTF-8') .
            '" style="color:#6b816f;text-decoration:underline;">' .
            htmlspecialchars((string) $fallback['url'], ENT_QUOTES, 'UTF-8') .
            '</a></p></td></tr></table>';
    }

    $footerHtml = '';
    foreach ($footerParagraphs as $paragraph) {
        $paragraph = trim((string) $paragraph);
        if ($paragraph === '') {
            continue;
        }
        $footerHtml .= '<p style="margin:0 0 8px;font-size:14px;line-height:1.7;color:#5b6b5f;">' . htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    return <<<HTML
<!doctype html>
<html lang="{$lang}">
  <body style="margin:0;padding:0;background:#f3f4ee;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;color:#1b231d;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f4ee;padding:24px 12px;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#ffffff;border:1px solid rgba(93,118,102,0.12);border-radius:28px;overflow:hidden;">
            <tr>
              <td style="padding:28px 32px 18px;background:linear-gradient(180deg,#f7f8f3 0%,#fefefe 100%);">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                  <tr>
                    <td style="width:42px;height:42px;border-radius:14px;background:#738977;color:#ffffff;font-weight:700;font-size:15px;line-height:42px;text-align:center;">SB</td>
                    <td style="padding-left:12px;font-family:Georgia,'Times New Roman',serif;font-size:24px;font-weight:700;color:#1b231d;">{$escapedBrandName}</td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td style="padding:10px 32px 0;">
                <p style="margin:0 0 14px;font-size:16px;line-height:1.6;color:#2d3830;">{$escapedGreeting}</p>
                <h1 style="margin:0 0 14px;font-family:Georgia,'Times New Roman',serif;font-size:34px;line-height:1.08;font-weight:700;color:#1b231d;">{$escapedTitle}</h1>
                {$introHtml}
                {$ctaHtml}
                {$detailsHtml}
                {$fallbackHtml}
                {$footerHtml}
              </td>
            </tr>
            <tr>
              <td style="padding:0 32px 28px;">
                <p style="margin:0;padding-top:18px;border-top:1px solid rgba(93,118,102,0.12);font-size:13px;line-height:1.6;color:#7c8a7f;">{$escapedBrandName}</p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML;
}

function shine_bright_compose_reservation_email(string $mode, string $lang, array $brand, array $reservation, string $adminNote = ''): array
{
    $isEn = $lang === 'en';
    $name = trim((string) ($reservation['customer_name'] ?? ''));
    $title = trim((string) ($reservation['class_title'] ?? ''));
    $date = trim((string) ($reservation['class_date_label'] ?? ''));
    $time = trim((string) ($reservation['class_time_label'] ?? ''));
    $location = trim((string) ($reservation['class_location'] ?? ''));
    $price = trim((string) ($reservation['class_price_label'] ?? ''));
    $mapsUrl = trim((string) ($reservation['class_maps_url'] ?? ''));
    $contactPhone = trim((string) ($brand['contact_phone'] ?? ''));
    $contactEmail = trim((string) ($brand['contact_email'] ?? ''));
    $note = trim($adminNote);
    $lines = [];
    $brandName = trim((string) ($brand['name'] ?? 'Shine Bright Yoga'));
    $greeting = $isEn ? 'Hello ' . $name . ',' : 'Здравейте, ' . $name . ',';
    $introParagraphs = [];
    $footerParagraphs = [];
    $detailRows = [];
    $emailTitle = '';

    if ($mode === 'received') {
        $subject = $isEn
            ? 'We received your reservation request for ' . $title
            : 'Получихме заявката ви за ' . $title;
        $emailTitle = $isEn ? 'Reservation received' : 'Получихме заявката ви';
        $introParagraphs[] = $isEn
            ? 'Thank you. We received your reservation request and Maria will review it shortly.'
            : 'Благодарим ви. Получихме заявката ви за резервация и Мария ще я прегледа скоро.';
    } elseif ($mode === 'confirmed') {
        $subject = $isEn
            ? 'Your reservation is confirmed: ' . $title
            : 'Резервацията ви е потвърдена: ' . $title;
        $emailTitle = $isEn ? 'Reservation confirmed' : 'Резервацията е потвърдена';
        $introParagraphs[] = $isEn
            ? 'Your reservation has been confirmed.'
            : 'Резервацията ви е потвърдена.';
    } elseif ($mode === 'waitlisted') {
        $subject = $isEn
            ? 'You are on the waitlist for ' . $title
            : 'В списъка на изчакване сте за ' . $title;
        $emailTitle = $isEn ? 'Waitlist update' : 'Актуализация за изчакване';
        $introParagraphs[] = $isEn
            ? 'At the moment this class is full, so your request has been moved to the waitlist.'
            : 'Към момента класът е запълнен и заявката ви е преместена в списъка на изчакване.';
    } else {
        $subject = $isEn
            ? 'Update about your reservation for ' . $title
            : 'Актуализация за резервацията ви за ' . $title;
        $emailTitle = $isEn ? 'Reservation update' : 'Актуализация за резервацията';
        $introParagraphs[] = $isEn
            ? 'There is an update about your reservation.'
            : 'Има актуализация по вашата резервация.';
    }

    $lines[] = $greeting;
    $lines[] = '';
    $lines[] = $introParagraphs[0] ?? '';
    $lines[] = '';
    $lines[] = $isEn ? 'Class: ' . $title : 'Клас: ' . $title;
    $detailRows[] = ['label' => $isEn ? 'Class' : 'Клас', 'value' => $title];
    if ($date !== '' || $time !== '') {
        $dateTimeValue = trim($date . ($time !== '' ? ' · ' . $time : ''));
        $lines[] = $isEn ? 'Date and time: ' . $dateTimeValue : 'Дата и час: ' . $dateTimeValue;
        $detailRows[] = ['label' => $isEn ? 'Date and time' : 'Дата и час', 'value' => $dateTimeValue];
    }
    if ($location !== '') {
        $lines[] = $isEn ? 'Location: ' . $location : 'Локация: ' . $location;
        $detailRows[] = ['label' => $isEn ? 'Location' : 'Локация', 'value' => $location];
    }
    if ($mapsUrl !== '') {
        $detailRows[] = [
            'label' => $isEn ? 'Map' : 'Карта',
            'value' => $isEn ? 'Open in Google Maps' : 'Отвори в Google Maps',
            'href' => $mapsUrl,
            'is_link' => true,
        ];
    }
    if ($price !== '') {
        $lines[] = $isEn ? 'Price: ' . $price : 'Цена: ' . $price;
        $detailRows[] = ['label' => $isEn ? 'Price' : 'Цена', 'value' => $price];
    }
    if ($note !== '') {
        $lines[] = '';
        $lines[] = $isEn ? 'Note from Maria:' : 'Бележка от Мария:';
        $lines[] = $note;
        $footerParagraphs[] = ($isEn ? 'Note from Maria: ' : 'Бележка от Мария: ') . $note;
    }
    if ($contactPhone !== '' || $contactEmail !== '') {
        $lines[] = '';
        $lines[] = $isEn ? 'If needed, you can reply to this email or reach Maria at:' : 'При нужда можете да отговорите на този имейл или да се свържете с Мария на:';
        if ($contactPhone !== '') {
            $lines[] = $isEn ? 'Phone: ' . $contactPhone : 'Телефон: ' . $contactPhone;
            $footerParagraphs[] = $isEn ? 'Phone: ' . $contactPhone : 'Телефон: ' . $contactPhone;
        }
        if ($contactEmail !== '') {
            $lines[] = $isEn ? 'Email: ' . $contactEmail : 'Имейл: ' . $contactEmail;
            $footerParagraphs[] = $isEn ? 'Email: ' . $contactEmail : 'Имейл: ' . $contactEmail;
        }
    }
    $lines[] = '';
    $lines[] = $brandName;

    $html = shine_bright_compose_branded_email_html(
        $lang,
        $brandName,
        $greeting,
        $emailTitle,
        $introParagraphs,
        null,
        $detailRows,
        $footerParagraphs
    );

    return [
        'subject' => $subject,
        'body' => implode("\n", $lines),
        'html' => $html,
    ];
}

function shine_bright_upload_media(string $field, array $allowedMimeToExt, string $prefix): ?string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed for ' . $field . '.');
    }

    $tmpName = $file['tmp_name'] ?? '';
    if (!is_string($tmpName) || $tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Invalid upload for ' . $field . '.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpName) ?: '';
    if (!isset($allowedMimeToExt[$mime])) {
        throw new RuntimeException('Unsupported file type for ' . $field . '.');
    }

    shine_bright_ensure_media_dir();
    $extension = $allowedMimeToExt[$mime];
    $filename = $prefix . '-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $extension;
    $targetPath = SHINE_BRIGHT_MEDIA_DIR . '/' . $filename;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Unable to store uploaded file for ' . $field . '.');
    }

    return '/media/' . $filename;
}

function shine_bright_runtime_root_dir(): string
{
    return dirname(__DIR__) . '/shine-bright-runtime';
}

function shine_bright_external_config_path(): string
{
    return dirname(__DIR__) . '/shine-bright-config.php';
}

function shine_bright_runtime_storage_paths(): array
{
    $root = shine_bright_runtime_root_dir();

    return [
        'root' => $root,
        'data_dir' => $root . '/data',
        'media_dir' => $root . '/media',
        'config_path' => shine_bright_external_config_path(),
    ];
}

function shine_bright_normalize_public_media_url(?string $url): string
{
    if (!is_string($url) || trim($url) === '') {
        return '';
    }

    $url = trim($url);
    if (str_starts_with($url, './media/')) {
        return '/media/' . ltrim(substr($url, strlen('./media/')), '/');
    }

    return $url;
}

function shine_bright_copy_tree(string $source, string $target): void
{
    if (!is_dir($source)) {
        return;
    }

    if (!is_dir($target)) {
        mkdir($target, 0775, true);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relativePath = substr($item->getPathname(), strlen($source) + 1);
        $targetPath = $target . '/' . $relativePath;

        if ($item->isDir()) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0775, true);
            }
            continue;
        }

        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        if (!is_file($targetPath)) {
            copy($item->getPathname(), $targetPath);
        }
    }
}

function shine_bright_write_external_config(array $paths): void
{
    $config = [
        'data_dir' => $paths['data_dir'],
        'media_dir' => $paths['media_dir'],
        'admin_token' => SHINE_BRIGHT_ADMIN_TOKEN,
        'admin_email' => SHINE_BRIGHT_ADMIN_EMAIL,
        'admin_password_hash' => SHINE_BRIGHT_ADMIN_PASSWORD_HASH,
        'allow_admin_token_fallback' => SHINE_BRIGHT_ALLOW_ADMIN_TOKEN_FALLBACK,
        'ip_salt' => SHINE_BRIGHT_IP_SALT,
        'mail_from_name' => SHINE_BRIGHT_MAIL_FROM_NAME,
        'mail_from_email' => SHINE_BRIGHT_MAIL_FROM_EMAIL,
    ];

    shine_bright_store_external_config($paths['config_path'], $config);
}

function shine_bright_load_external_config_file(string $configPath): array
{
    if (!is_file($configPath)) {
        return [];
    }

    $loaded = require $configPath;
    return is_array($loaded) ? $loaded : [];
}

function shine_bright_store_external_config(string $configPath, array $config): void
{
    $export = "<?php\n\nreturn " . var_export($config, true) . ";\n";
    file_put_contents($configPath, $export);
}

function shine_bright_update_external_config(array $overrides): array
{
    $configPath = shine_bright_external_config_path();
    $current = shine_bright_load_external_config_file($configPath);
    $updated = array_replace($current, $overrides);
    shine_bright_store_external_config($configPath, $updated);
    return $updated;
}

function shine_bright_externalize_runtime_state(): array
{
    $paths = shine_bright_runtime_storage_paths();

    if (!is_dir($paths['root'])) {
        mkdir($paths['root'], 0775, true);
    }

    shine_bright_copy_tree(SHINE_BRIGHT_DATA_DIR, $paths['data_dir']);
    shine_bright_copy_tree(SHINE_BRIGHT_MEDIA_DIR, $paths['media_dir']);
    shine_bright_write_external_config($paths);

    return $paths;
}
