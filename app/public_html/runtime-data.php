<?php
require_once __DIR__ . '/bootstrap.php';

define('SHINE_BRIGHT_CLIENTS_PATH', SHINE_BRIGHT_DATA_DIR . '/clients.json');
define('SHINE_BRIGHT_VISIT_PACKS_PATH', SHINE_BRIGHT_DATA_DIR . '/visit-packs.json');
define('SHINE_BRIGHT_VISIT_USAGE_PATH', SHINE_BRIGHT_DATA_DIR . '/visit-pack-usage.json');
define('SHINE_BRIGHT_STUDENT_ACTIVATION_TOKENS_PATH', SHINE_BRIGHT_DATA_DIR . '/student-activation-tokens.json');
define('SHINE_BRIGHT_QR_CHECKIN_CODES_PATH', SHINE_BRIGHT_DATA_DIR . '/qr-checkin-codes.json');
define('SHINE_BRIGHT_INQUIRY_META_PATH', SHINE_BRIGHT_DATA_DIR . '/inquiry-meta.json');

function shine_bright_runtime_load_array(string $path): array
{
    shine_bright_ensure_data_dir();

    if (!is_file($path)) {
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return [];
    }

    $json = file_get_contents($path);
    $decoded = is_string($json) ? json_decode($json, true) : null;

    return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
}

function shine_bright_runtime_save_array(string $path, array $records): void
{
    shine_bright_ensure_data_dir();
    file_put_contents($path, json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function shine_bright_runtime_load_map(string $path): array
{
    shine_bright_ensure_data_dir();

    if (!is_file($path)) {
        file_put_contents($path, json_encode((object) [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return [];
    }

    $json = file_get_contents($path);
    $decoded = is_string($json) ? json_decode($json, true) : null;

    return is_array($decoded) ? $decoded : [];
}

function shine_bright_runtime_save_map(string $path, array $records): void
{
    shine_bright_ensure_data_dir();
    file_put_contents($path, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function shine_bright_client_template(): array
{
    return [
        'id' => '',
        'name' => '',
        'phone' => '',
        'email' => '',
        'notes' => '',
        'account_status' => 'invited',
        'password_hash' => '',
        'last_login_at' => '',
        'created_at' => '',
        'updated_at' => '',
    ];
}

function shine_bright_visit_pack_template(): array
{
    return [
        'id' => '',
        'client_id' => '',
        'applies_to_class_id' => '',
        'applies_to_class_ids' => [],
        'title' => '',
        'total_visits' => 0,
        'used_visits' => 0,
        'starts_on' => '',
        'expires_on' => '',
        'status' => 'active',
        'notes' => '',
        'created_at' => '',
        'updated_at' => '',
    ];
}

function shine_bright_visit_usage_template(): array
{
    return [
        'id' => '',
        'pack_id' => '',
        'client_id' => '',
        'class_id' => '',
        'used_on' => '',
        'source' => 'manual',
        'note' => '',
        'created_at' => '',
    ];
}

function shine_bright_student_activation_token_template(): array
{
    return [
        'id' => '',
        'student_id' => '',
        'token_hash' => '',
        'expires_at' => '',
        'used_at' => '',
        'created_at' => '',
    ];
}

function shine_bright_qr_checkin_code_template(): array
{
    return [
        'id' => '',
        'student_id' => '',
        'pack_id' => '',
        'code' => '',
        'expires_at' => '',
        'used_at' => '',
        'created_at' => '',
    ];
}

function shine_bright_inquiry_meta_template(): array
{
    return [
        'order_status' => 'new',
        'deleted_at' => '',
        'updated_at' => '',
    ];
}

function shine_bright_load_clients(): array
{
    return shine_bright_runtime_load_array(SHINE_BRIGHT_CLIENTS_PATH);
}

function shine_bright_save_clients(array $clients): void
{
    shine_bright_runtime_save_array(SHINE_BRIGHT_CLIENTS_PATH, $clients);
}

function shine_bright_load_visit_packs(): array
{
    return shine_bright_runtime_load_array(SHINE_BRIGHT_VISIT_PACKS_PATH);
}

function shine_bright_save_visit_packs(array $packs): void
{
    shine_bright_runtime_save_array(SHINE_BRIGHT_VISIT_PACKS_PATH, $packs);
}

function shine_bright_load_visit_usage(): array
{
    return shine_bright_runtime_load_array(SHINE_BRIGHT_VISIT_USAGE_PATH);
}

function shine_bright_save_visit_usage(array $usage): void
{
    shine_bright_runtime_save_array(SHINE_BRIGHT_VISIT_USAGE_PATH, $usage);
}

function shine_bright_load_student_activation_tokens(): array
{
    return shine_bright_runtime_load_array(SHINE_BRIGHT_STUDENT_ACTIVATION_TOKENS_PATH);
}

function shine_bright_save_student_activation_tokens(array $tokens): void
{
    shine_bright_runtime_save_array(SHINE_BRIGHT_STUDENT_ACTIVATION_TOKENS_PATH, $tokens);
}

function shine_bright_load_qr_checkin_codes(): array
{
    return shine_bright_runtime_load_array(SHINE_BRIGHT_QR_CHECKIN_CODES_PATH);
}

function shine_bright_save_qr_checkin_codes(array $codes): void
{
    shine_bright_runtime_save_array(SHINE_BRIGHT_QR_CHECKIN_CODES_PATH, $codes);
}

function shine_bright_normalize_inquiry_meta(array $meta): array
{
    $template = shine_bright_inquiry_meta_template();
    $normalized = $template;

    foreach ($template as $field => $defaultValue) {
        $value = $meta[$field] ?? $defaultValue;
        $normalized[$field] = is_string($value) ? trim($value) : $defaultValue;
    }

    $normalized['order_status'] = in_array($normalized['order_status'], ['new', 'confirmed', 'cancelled', 'shipped'], true)
        ? $normalized['order_status']
        : 'new';
    $normalized['updated_at'] = $normalized['updated_at'] !== '' ? $normalized['updated_at'] : gmdate('c');

    return $normalized;
}

function shine_bright_load_inquiry_meta(): array
{
    $records = shine_bright_runtime_load_map(SHINE_BRIGHT_INQUIRY_META_PATH);
    $normalized = [];

    foreach ($records as $id => $meta) {
        if (!is_string($id) || !is_array($meta)) {
            continue;
        }
        $normalized[$id] = shine_bright_normalize_inquiry_meta($meta);
    }

    return $normalized;
}

function shine_bright_save_inquiry_meta(array $records): void
{
    $normalized = [];
    foreach ($records as $id => $meta) {
        if (!is_string($id) || !is_array($meta)) {
            continue;
        }
        $normalized[$id] = shine_bright_normalize_inquiry_meta($meta);
    }

    shine_bright_runtime_save_map(SHINE_BRIGHT_INQUIRY_META_PATH, $normalized);
}

function shine_bright_find_record_by_id(array $records, string $id): ?array
{
    foreach ($records as $record) {
        if ((string) ($record['id'] ?? '') === $id) {
            return $record;
        }
    }

    return null;
}

function shine_bright_normalize_client(array $client): array
{
    $template = shine_bright_client_template();
    $normalized = $template;

    foreach ($template as $field => $defaultValue) {
        $value = $client[$field] ?? $defaultValue;
        $normalized[$field] = is_string($value) ? trim($value) : $defaultValue;
    }

    $normalized['email'] = strtolower($normalized['email']);
    $normalized['account_status'] = in_array($normalized['account_status'], ['invited', 'active', 'disabled'], true)
        ? $normalized['account_status']
        : 'invited';
    $seed = $normalized['id'] !== ''
        ? $normalized['id']
        : ($normalized['name'] !== ''
            ? $normalized['name']
            : ($normalized['email'] !== '' ? $normalized['email'] : 'student'));
    $normalized['id'] = shine_bright_slugify($seed);
    $now = gmdate('c');
    $normalized['created_at'] = $normalized['created_at'] !== '' ? $normalized['created_at'] : $now;
    $normalized['updated_at'] = $now;

    return $normalized;
}

function shine_bright_find_student_by_email(array $students, string $email): ?array
{
    $needle = strtolower(trim($email));
    if ($needle === '') {
        return null;
    }

    foreach ($students as $student) {
        if (strtolower(trim((string) ($student['email'] ?? ''))) === $needle) {
            return $student;
        }
    }

    return null;
}

function shine_bright_humanize_client_id(string $clientId): string
{
    $value = trim(str_replace(['-', '_'], ' ', $clientId));
    if ($value === '') {
        return 'Unnamed student';
    }

    $parts = preg_split('/\s+/', $value) ?: [];
    $parts = array_map(static function (string $part): string {
        $lower = function_exists('mb_strtolower') ? mb_strtolower($part, 'UTF-8') : strtolower($part);
        return function_exists('mb_convert_case')
            ? mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8')
            : ucfirst($lower);
    }, array_filter($parts, static fn (string $part): bool => $part !== ''));

    return $parts !== [] ? implode(' ', $parts) : 'Unnamed student';
}

function shine_bright_client_runtime_index(array $clients, array $packs = [], array $usage = [], array $tokens = []): array
{
    $index = [];
    foreach ($clients as $client) {
        $normalized = shine_bright_normalize_client($client);
        $normalized['_is_runtime_only'] = false;
        $normalized['_runtime_source'] = 'student';
        $index[(string) ($normalized['id'] ?? '')] = $normalized;
    }

    $referencedIds = [];
    foreach ($packs as $pack) {
        $clientId = trim((string) ($pack['client_id'] ?? ''));
        if ($clientId !== '') {
            $referencedIds[$clientId] = true;
        }
    }
    foreach ($usage as $event) {
        $clientId = trim((string) ($event['client_id'] ?? ''));
        if ($clientId !== '') {
            $referencedIds[$clientId] = true;
        }
    }
    foreach ($tokens as $token) {
        $clientId = trim((string) ($token['student_id'] ?? ''));
        if ($clientId !== '') {
            $referencedIds[$clientId] = true;
        }
    }

    foreach (array_keys($referencedIds) as $clientId) {
        if (isset($index[$clientId])) {
            continue;
        }

        $placeholder = shine_bright_client_template();
        $placeholder['id'] = $clientId;
        $placeholder['name'] = shine_bright_humanize_client_id($clientId);
        $placeholder['notes'] = 'Recovered from linked runtime records.';
        $placeholder['account_status'] = 'invited';
        $placeholder['created_at'] = gmdate('c');
        $placeholder['updated_at'] = gmdate('c');
        $placeholder['_is_runtime_only'] = true;
        $placeholder['_runtime_source'] = 'runtime';
        $index[$clientId] = $placeholder;
    }

    uasort($index, static function (array $a, array $b): int {
        $left = trim((string) ($a['name'] ?? $a['id'] ?? ''));
        $right = trim((string) ($b['name'] ?? $b['id'] ?? ''));
        return strcasecmp($left, $right);
    });

    return array_values($index);
}

function shine_bright_contact_lead_index(array $clients, array $reservations = [], array $inquiries = []): array
{
    $emailMap = [];
    $phoneMap = [];
    foreach ($clients as $client) {
        $email = strtolower(trim((string) ($client['email'] ?? '')));
        $phone = trim((string) ($client['phone'] ?? ''));
        if ($email !== '') {
            $emailMap[$email] = true;
        }
        if ($phone !== '') {
            $phoneMap[$phone] = true;
        }
    }

    $index = [];
    $appendLead = static function (array $row, string $nameKey, string $emailKey, string $phoneKey, string $source, string $status) use (&$index, $emailMap, $phoneMap): void {
        $name = trim((string) ($row[$nameKey] ?? ''));
        $email = strtolower(trim((string) ($row[$emailKey] ?? '')));
        $phone = trim((string) ($row[$phoneKey] ?? ''));
        if ($email === '' && $phone === '') {
            return;
        }

        if (($email !== '' && isset($emailMap[$email])) || ($phone !== '' && isset($phoneMap[$phone]))) {
            return;
        }

        $key = $email !== '' ? 'lead-' . shine_bright_slugify($email) : 'lead-' . shine_bright_slugify($phone);
        if (isset($index[$key])) {
            return;
        }

        $index[$key] = [
            'id' => $key,
            'name' => $name !== '' ? $name : ($email !== '' ? $email : shine_bright_humanize_client_id($phone)),
            'email' => $email,
            'phone' => $phone,
            'source' => $source,
            'status' => $status,
        ];
    };

    foreach ($reservations as $reservation) {
        $reservationStatus = (string) ($reservation['status'] ?? '');
        $attendance = (string) ($reservation['attendance'] ?? '');
        $status = $attendance === 'attended'
            ? 'attended'
            : ($reservationStatus !== '' && $reservationStatus !== 'cancelled' ? 'reserved' : 'lead');
        $appendLead($reservation, 'customer_name', 'email', 'phone', 'reservation', $status);
    }
    foreach ($inquiries as $inquiry) {
        $appendLead($inquiry, 'customer_name', 'email', 'phone', 'inquiry', 'lead');
    }

    uasort($index, static function (array $a, array $b): int {
        return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });

    return array_values($index);
}

function shine_bright_student_base_url(string $path): string
{
    return SHINE_BRIGHT_SITE_BASE_URL . '/' . ltrim($path, '/');
}

function shine_bright_create_student_activation_token(array &$tokens, string $studentId, int $ttlHours = 48): array
{
    $rawToken = bin2hex(random_bytes(24));
    $record = shine_bright_student_activation_token_template();
    $record['id'] = 'invite-' . gmdate('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    $record['student_id'] = $studentId;
    $record['token_hash'] = hash('sha256', $rawToken);
    $record['expires_at'] = gmdate('c', time() + ($ttlHours * 3600));
    $record['created_at'] = gmdate('c');
    $tokens[] = $record;

    return ['record' => $record, 'raw_token' => $rawToken];
}

function shine_bright_find_valid_student_activation_token(array $tokens, string $rawToken): ?array
{
    $tokenHash = hash('sha256', trim($rawToken));
    $now = new DateTimeImmutable('now');

    foreach ($tokens as $token) {
        if ((string) ($token['token_hash'] ?? '') !== $tokenHash) {
            continue;
        }

        if ((string) ($token['used_at'] ?? '') !== '') {
            return null;
        }

        $expiresAt = trim((string) ($token['expires_at'] ?? ''));
        if ($expiresAt === '') {
            return null;
        }

        try {
            if (new DateTimeImmutable($expiresAt) < $now) {
                return null;
            }
        } catch (Throwable $e) {
            return null;
        }

        return $token;
    }

    return null;
}

function shine_bright_mark_student_activation_token_used(array &$tokens, string $tokenId): void
{
    foreach ($tokens as $index => $token) {
        if ((string) ($token['id'] ?? '') !== $tokenId) {
            continue;
        }

        $tokens[$index]['used_at'] = gmdate('c');
        return;
    }
}

function shine_bright_invalidate_student_activation_tokens(array &$tokens, string $studentId): void
{
    foreach ($tokens as $index => $token) {
        if ((string) ($token['student_id'] ?? '') !== $studentId) {
            continue;
        }

        if ((string) ($token['used_at'] ?? '') === '') {
            $tokens[$index]['used_at'] = gmdate('c');
        }
    }
}

function shine_bright_cleanup_qr_checkin_codes(array &$codes): void
{
    $now = new DateTimeImmutable('now');
    $codes = array_values(array_filter($codes, static function (array $code) use ($now): bool {
        if (trim((string) ($code['used_at'] ?? '')) !== '') {
            return false;
        }

        $expiresAt = trim((string) ($code['expires_at'] ?? ''));
        if ($expiresAt === '') {
            return false;
        }

        try {
            return new DateTimeImmutable($expiresAt) >= $now;
        } catch (Throwable $e) {
            return false;
        }
    }));
}

function shine_bright_create_qr_checkin_code(array &$codes, string $studentId, string $packId, int $ttlSeconds = 600): array
{
    shine_bright_cleanup_qr_checkin_codes($codes);
    $record = shine_bright_qr_checkin_code_template();
    $record['id'] = 'qr-' . gmdate('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    $record['student_id'] = $studentId;
    $record['pack_id'] = $packId;
    $record['code'] = 'SBY-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
    $record['expires_at'] = gmdate('c', time() + max(60, $ttlSeconds));
    $record['created_at'] = gmdate('c');
    $codes[] = $record;
    return $record;
}

function shine_bright_find_valid_qr_checkin_code(array $codes, string $rawCode): ?array
{
    $needle = strtoupper(trim($rawCode));
    if ($needle === '') {
        return null;
    }

    $now = new DateTimeImmutable('now');
    foreach ($codes as $code) {
        if (strtoupper((string) ($code['code'] ?? '')) !== $needle) {
            continue;
        }
        if (trim((string) ($code['used_at'] ?? '')) !== '') {
            return null;
        }
        $expiresAt = trim((string) ($code['expires_at'] ?? ''));
        if ($expiresAt === '') {
            return null;
        }
        try {
            if (new DateTimeImmutable($expiresAt) < $now) {
                return null;
            }
        } catch (Throwable $e) {
            return null;
        }
        return $code;
    }

    return null;
}

function shine_bright_mark_qr_checkin_code_used(array &$codes, string $codeId): void
{
    foreach ($codes as $index => $code) {
        if ((string) ($code['id'] ?? '') !== $codeId) {
            continue;
        }
        $codes[$index]['used_at'] = gmdate('c');
        return;
    }
}

function shine_bright_student_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('shine_bright_student');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => str_starts_with(SHINE_BRIGHT_SITE_BASE_URL, 'https://'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function shine_bright_student_login(array &$students, array $student): void
{
    shine_bright_student_session_start();
    session_regenerate_id(true);
    $_SESSION['shine_bright_student_id'] = (string) ($student['id'] ?? '');

    foreach ($students as $index => $existing) {
        if ((string) ($existing['id'] ?? '') !== (string) ($student['id'] ?? '')) {
            continue;
        }

        $students[$index]['last_login_at'] = gmdate('c');
        $students[$index]['updated_at'] = gmdate('c');
        shine_bright_save_clients($students);
        return;
    }
}

function shine_bright_current_student(): ?array
{
    shine_bright_student_session_start();
    $studentId = isset($_SESSION['shine_bright_student_id']) && is_string($_SESSION['shine_bright_student_id'])
        ? trim($_SESSION['shine_bright_student_id'])
        : '';

    if ($studentId === '') {
        return null;
    }

    return shine_bright_find_record_by_id(shine_bright_load_clients(), $studentId);
}

function shine_bright_student_logout(): void
{
    shine_bright_student_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) ($params['secure'] ?? false), (bool) ($params['httponly'] ?? false));
    }
    session_destroy();
}

function shine_bright_require_student_auth(): array
{
    $student = shine_bright_current_student();
    if (!$student || (string) ($student['account_status'] ?? '') !== 'active') {
        header('Location: ./student-login.php');
        exit;
    }

    return $student;
}

function shine_bright_compose_student_activation_email(string $lang, array $brand, array $student, string $rawToken): array
{
    $isEn = $lang === 'en';
    $name = trim((string) ($student['name'] ?? ''));
    $email = trim((string) ($student['email'] ?? ''));
    $activationUrl = shine_bright_student_base_url('student-activate.php?token=' . urlencode($rawToken));
    $greeting = $name !== '' ? $name : $email;
    $subject = $isEn ? 'Activate your Shine Bright Yoga account' : 'Активирайте своя профил в Shine Bright Yoga';
    $brandName = trim((string) ($brand['name'] ?? 'Shine Bright Yoga'));
    $intro = $isEn
        ? 'Maria created your access so you can view your visit card online.'
        : 'Мария създаде вашия достъп, за да виждате картата си онлайн.';
    $bodyCopy = $isEn
        ? 'Activate your profile to see your card, open your QR code, and keep everything one tap away.'
        : 'Активирайте профила си, за да виждате картата си, да отваряте своя QR код и да имате всичко на един тап разстояние.';
    $ctaLabel = $isEn ? 'Activate profile' : 'Активирайте профила си';
    $fallbackLabel = $isEn
        ? 'If the button does not open, use this link:'
        : 'Ако бутонът не се отвори, използвайте този линк:';
    $expiry = $isEn ? 'This activation link expires in 48 hours.' : 'Този линк изтича след 48 часа.';
    $installNote = $isEn
        ? 'After the first login, you can add your card to the home screen for faster access.'
        : 'След първия вход можете да добавите картата на началния екран за по-бърз достъп.';

    $lines = [];
    $lines[] = $isEn ? 'Hello ' . $greeting . ',' : 'Здравейте, ' . $greeting . ',';
    $lines[] = '';
    $lines[] = $intro;
    $lines[] = $bodyCopy;
    $lines[] = '';
    $lines[] = $ctaLabel . ':';
    $lines[] = $activationUrl;
    $lines[] = '';
    $lines[] = $expiry;
    $lines[] = $installNote;
    $lines[] = '';
    $lines[] = $brandName;

    $html = shine_bright_compose_branded_email_html(
        $lang,
        $brandName,
        $isEn ? 'Hello ' . $greeting . ',' : 'Здравейте, ' . $greeting . ',',
        $ctaLabel,
        [$intro, $bodyCopy],
        [
            'label' => $ctaLabel,
            'url' => $activationUrl,
        ],
        [],
        [$expiry, $installNote],
        [
            'label' => $fallbackLabel,
            'url' => $activationUrl,
        ]
    );

    return [
        'subject' => $subject,
        'body' => implode("\n", $lines),
        'html' => $html,
    ];
}

function shine_bright_send_student_activation_email(array &$students, array &$tokens, string $studentId, string $lang = 'bg'): bool
{
    $student = shine_bright_find_record_by_id($students, $studentId);
    if (!$student) {
        return false;
    }

    $email = trim((string) ($student['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    shine_bright_invalidate_student_activation_tokens($tokens, $studentId);
    $created = shine_bright_create_student_activation_token($tokens, $studentId);
    $brand = shine_bright_load_content()[$lang]['brand'] ?? (shine_bright_load_content()['bg']['brand'] ?? []);
    $payload = shine_bright_compose_student_activation_email($lang, $brand, $student, $created['raw_token']);
    $sent = shine_bright_send_multipart_mail(
        $email,
        (string) ($payload['subject'] ?? ''),
        (string) ($payload['body'] ?? ''),
        (string) ($payload['html'] ?? ''),
        (string) ($brand['contact_email'] ?? '')
    );

    if ($sent) {
        shine_bright_save_student_activation_tokens($tokens);
    }

    return $sent;
}

function shine_bright_upsert_client(array &$clients, array $client): array
{
    $normalized = shine_bright_normalize_client($client);

    foreach ($clients as $index => $existing) {
        if ((string) ($existing['id'] ?? '') !== $normalized['id']) {
            continue;
        }

        $normalized['created_at'] = (string) ($existing['created_at'] ?? $normalized['created_at']);
        $clients[$index] = $normalized;
        return $normalized;
    }

    $clients[] = $normalized;
    return $normalized;
}

function shine_bright_delete_client(array &$clients, string $clientId): bool
{
    foreach ($clients as $index => $client) {
        if ((string) ($client['id'] ?? '') !== $clientId) {
            continue;
        }

        array_splice($clients, $index, 1);
        return true;
    }

    return false;
}

function shine_bright_delete_student_activation_tokens(array &$tokens, string $studentId): int
{
    $deleted = 0;
    foreach ($tokens as $index => $token) {
        if ((string) ($token['student_id'] ?? '') !== $studentId) {
            continue;
        }

        unset($tokens[$index]);
        $deleted++;
    }

    if ($deleted > 0) {
        $tokens = array_values($tokens);
    }

    return $deleted;
}

function shine_bright_delete_qr_checkin_codes_for_student(array &$codes, string $studentId): int
{
    $deleted = 0;
    foreach ($codes as $index => $code) {
        if ((string) ($code['student_id'] ?? '') !== $studentId) {
            continue;
        }

        unset($codes[$index]);
        $deleted++;
    }

    if ($deleted > 0) {
        $codes = array_values($codes);
    }

    return $deleted;
}

function shine_bright_client_usage_count(array $usage, string $clientId): int
{
    $count = 0;
    foreach ($usage as $event) {
        if ((string) ($event['client_id'] ?? '') === $clientId) {
            $count++;
        }
    }

    return $count;
}

function shine_bright_delete_client_runtime(array &$clients, array &$tokens, array &$codes, string $clientId): bool
{
    $removedClient = shine_bright_delete_client($clients, $clientId);
    $removedTokens = shine_bright_delete_student_activation_tokens($tokens, $clientId);
    $removedCodes = shine_bright_delete_qr_checkin_codes_for_student($codes, $clientId);

    return $removedClient || $removedTokens > 0 || $removedCodes > 0;
}

function shine_bright_normalize_visit_pack(array $pack): array
{
    $template = shine_bright_visit_pack_template();
    $normalized = $template;

    foreach ($template as $field => $defaultValue) {
        if ($field === 'applies_to_class_ids') {
            $value = $pack[$field] ?? [];
            $classIds = [];
            if (is_array($value)) {
                foreach ($value as $classId) {
                    if (!is_scalar($classId)) {
                        continue;
                    }
                    $candidate = trim((string) $classId);
                    if ($candidate === '') {
                        continue;
                    }
                    $classIds[] = shine_bright_slugify($candidate);
                }
            }
            if ($classIds === []) {
                $legacyId = trim((string) ($pack['applies_to_class_id'] ?? ''));
                if ($legacyId !== '') {
                    $classIds[] = shine_bright_slugify($legacyId);
                }
            }
            $normalized[$field] = array_values(array_unique($classIds));
            continue;
        }
        $value = $pack[$field] ?? $defaultValue;
        if (in_array($field, ['total_visits', 'used_visits'], true)) {
            $normalized[$field] = max(0, (int) $value);
            continue;
        }

        $normalized[$field] = is_string($value) ? trim($value) : $defaultValue;
    }

    $seed = $normalized['id'] !== '' ? $normalized['id'] : ($normalized['title'] !== '' ? $normalized['title'] : 'visit-pack-' . gmdate('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6));
    $normalized['id'] = shine_bright_slugify($seed);
    $normalized['status'] = in_array($normalized['status'], ['active', 'cancelled'], true) ? $normalized['status'] : 'active';
    $normalized['used_visits'] = min($normalized['used_visits'], $normalized['total_visits']);
    $normalized['applies_to_class_id'] = $normalized['applies_to_class_ids'][0] ?? '';
    $now = gmdate('c');
    $normalized['created_at'] = $normalized['created_at'] !== '' ? $normalized['created_at'] : $now;
    $normalized['updated_at'] = $now;

    return $normalized;
}

function shine_bright_visit_pack_allowed_class_ids(array $pack): array
{
    $classIds = $pack['applies_to_class_ids'] ?? [];
    if (is_array($classIds) && $classIds !== []) {
        return array_values(array_filter(array_map(static fn ($value): string => trim((string) $value), $classIds), static fn (string $value): bool => $value !== ''));
    }

    $legacyId = trim((string) ($pack['applies_to_class_id'] ?? ''));
    return $legacyId === '' ? [] : [$legacyId];
}

function shine_bright_upsert_visit_pack(array &$packs, array $pack): array
{
    $normalized = shine_bright_normalize_visit_pack($pack);

    foreach ($packs as $index => $existing) {
        if ((string) ($existing['id'] ?? '') !== $normalized['id']) {
            continue;
        }

        $normalized['created_at'] = (string) ($existing['created_at'] ?? $normalized['created_at']);
        $packs[$index] = $normalized;
        return $normalized;
    }

    $packs[] = $normalized;
    return $normalized;
}

function shine_bright_delete_visit_pack(array &$packs, string $packId): bool
{
    foreach ($packs as $index => $pack) {
        if ((string) ($pack['id'] ?? '') !== $packId) {
            continue;
        }

        array_splice($packs, $index, 1);
        return true;
    }

    return false;
}

function shine_bright_pack_usage_events(array $usage, string $packId): array
{
    return array_values(array_filter($usage, static function (array $event) use ($packId): bool {
        return (string) ($event['pack_id'] ?? '') === $packId;
    }));
}

function shine_bright_visit_pack_remaining(array $pack): int
{
    return max(0, ((int) ($pack['total_visits'] ?? 0)) - ((int) ($pack['used_visits'] ?? 0)));
}

function shine_bright_visit_pack_runtime_status(array $pack): string
{
    if ((string) ($pack['status'] ?? 'active') === 'cancelled') {
        return 'cancelled';
    }

    if (shine_bright_visit_pack_remaining($pack) <= 0) {
        return 'completed';
    }

    $expiresOn = trim((string) ($pack['expires_on'] ?? ''));
    if ($expiresOn !== '') {
        try {
            $expiry = new DateTimeImmutable($expiresOn . ' 23:59:59');
            $now = new DateTimeImmutable('now');
            if ($expiry < $now) {
                return 'expired';
            }
        } catch (Throwable $e) {
        }
    }

    return 'active';
}

function shine_bright_visit_pack_is_low(array $pack): bool
{
    $remaining = shine_bright_visit_pack_remaining($pack);
    return $remaining > 0 && $remaining <= 2 && shine_bright_visit_pack_runtime_status($pack) === 'active';
}

function shine_bright_visit_pack_usage_summary(array $pack): string
{
    return shine_bright_visit_pack_remaining($pack) . ' / ' . ((int) ($pack['total_visits'] ?? 0)) . ' left';
}

function shine_bright_create_visit_usage_event(array $event): array
{
    $template = shine_bright_visit_usage_template();
    $normalized = $template;

    foreach ($template as $field => $defaultValue) {
        $value = $event[$field] ?? $defaultValue;
        $normalized[$field] = is_string($value) ? trim($value) : $defaultValue;
    }

    $normalized['id'] = $normalized['id'] !== '' ? $normalized['id'] : 'usage-' . gmdate('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    $normalized['used_on'] = $normalized['used_on'] !== '' ? $normalized['used_on'] : gmdate('Y-m-d');
    $normalized['created_at'] = $normalized['created_at'] !== '' ? $normalized['created_at'] : gmdate('c');

    $usage = shine_bright_load_visit_usage();
    $usage[] = $normalized;
    shine_bright_save_visit_usage($usage);

    return $normalized;
}

function shine_bright_consume_visit_pack(array &$packs, string $packId, array $event = [], ?array &$usageEvent = null): array
{
    foreach ($packs as $index => $pack) {
        if ((string) ($pack['id'] ?? '') !== $packId) {
            continue;
        }

        $runtimeStatus = shine_bright_visit_pack_runtime_status($pack);
        if ($runtimeStatus === 'cancelled') {
            throw new RuntimeException('Cancelled visit cards cannot be used.');
        }

        if ($runtimeStatus === 'expired') {
            throw new RuntimeException('This visit card is expired.');
        }

        if (shine_bright_visit_pack_remaining($pack) <= 0) {
            throw new RuntimeException('No visits remain on this visit card.');
        }

        $restrictedClassIds = shine_bright_visit_pack_allowed_class_ids($pack);
        $eventClassId = trim((string) ($event['class_id'] ?? ''));
        if ($restrictedClassIds !== [] && $eventClassId !== '' && !in_array($eventClassId, $restrictedClassIds, true)) {
            throw new RuntimeException('This visit card is limited to a different class.');
        }

        $pack['used_visits'] = ((int) ($pack['used_visits'] ?? 0)) + 1;
        $pack['updated_at'] = gmdate('c');
        $packs[$index] = shine_bright_normalize_visit_pack($pack);

        $usageEvent = shine_bright_create_visit_usage_event([
            'pack_id' => $packId,
            'client_id' => (string) ($pack['client_id'] ?? ''),
            'class_id' => trim((string) ($event['class_id'] ?? '')),
            'used_on' => trim((string) ($event['used_on'] ?? '')),
            'source' => trim((string) ($event['source'] ?? 'manual')) ?: 'manual',
            'note' => trim((string) ($event['note'] ?? '')),
        ]);

        return $packs[$index];
    }

    throw new RuntimeException('Visit card not found.');
}

function shine_bright_undo_visit_usage(array &$packs, array &$usage, string $usageId, int $maxAgeSeconds = 900): array
{
    $event = null;
    $eventIndex = null;

    foreach ($usage as $index => $item) {
        if ((string) ($item['id'] ?? '') !== $usageId) {
            continue;
        }
        $event = $item;
        $eventIndex = $index;
        break;
    }

    if (!$event || $eventIndex === null) {
        throw new RuntimeException('Visit usage event not found.');
    }

    if ((string) ($event['source'] ?? '') !== 'qr_admin') {
        throw new RuntimeException('Only QR check-ins can be undone here.');
    }

    $createdAt = trim((string) ($event['created_at'] ?? ''));
    if ($createdAt === '') {
        throw new RuntimeException('This check-in cannot be undone.');
    }

    try {
        $created = new DateTimeImmutable($createdAt);
        $now = new DateTimeImmutable('now');
        if (($now->getTimestamp() - $created->getTimestamp()) > $maxAgeSeconds) {
            throw new RuntimeException('The undo window has expired.');
        }
    } catch (Throwable $e) {
        if ($e instanceof RuntimeException) {
            throw $e;
        }
        throw new RuntimeException('This check-in cannot be undone.');
    }

    foreach ($packs as $index => $pack) {
        if ((string) ($pack['id'] ?? '') !== (string) ($event['pack_id'] ?? '')) {
            continue;
        }

        $packs[$index]['used_visits'] = max(0, ((int) ($pack['used_visits'] ?? 0)) - 1);
        $packs[$index]['updated_at'] = gmdate('c');
        $packs[$index] = shine_bright_normalize_visit_pack($packs[$index]);
        array_splice($usage, $eventIndex, 1);

        return [
            'pack' => $packs[$index],
            'event' => $event,
        ];
    }

    throw new RuntimeException('Visit card not found.');
}

function shine_bright_client_pack_count(array $packs, string $clientId): int
{
    $count = 0;
    foreach ($packs as $pack) {
        if ((string) ($pack['client_id'] ?? '') === $clientId) {
            $count++;
        }
    }

    return $count;
}

function shine_bright_client_active_pack_count(array $packs, string $clientId): int
{
    $count = 0;
    foreach ($packs as $pack) {
        if ((string) ($pack['client_id'] ?? '') !== $clientId) {
            continue;
        }

        if (shine_bright_visit_pack_runtime_status($pack) === 'active') {
            $count++;
        }
    }

    return $count;
}

function shine_bright_visit_pack_delete_allowed(array $usage, string $packId): bool
{
    return shine_bright_pack_usage_events($usage, $packId) === [];
}
