<?php
require_once __DIR__ . '/content.php';

header('Content-Type: application/json; charset=utf-8');

function sb_post(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function sb_submit_messages(string $lang): array
{
    if ($lang === 'en') {
        return [
            'method' => 'Method not allowed.',
            'name' => 'Name is required.',
            'contact' => 'Email or phone is required.',
            'email' => 'Enter a valid email address.',
            'phone' => 'Enter a valid phone number.',
            'session' => 'Choose a class date and time.',
            'duplicate_reservation' => 'You already have a reservation for this date and time.',
            'init' => 'Unable to initialize storage.',
            'save' => 'Unable to save inquiry.',
        ];
    }

    return [
        'method' => 'Непозволен метод.',
        'name' => 'Името е задължително.',
        'contact' => 'Имейл или телефон са задължителни.',
        'email' => 'Въведете валиден имейл адрес.',
        'phone' => 'Въведете валиден телефонен номер.',
        'session' => 'Изберете дата и час за класа.',
        'duplicate_reservation' => 'Вече имате резервация за тази дата и час.',
        'init' => 'Не успяхме да инициализираме записа.',
        'save' => 'Не успяхме да запазим запитването.',
    ];
}

function sb_valid_email(string $value): bool
{
    return $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function sb_same_session_email_reservation_exists(array $reservations, string $sessionId, string $email): bool
{
    $sessionId = trim($sessionId);
    $email = strtolower(trim($email));
    if ($sessionId === '' || $email === '') {
        return false;
    }

    foreach ($reservations as $reservation) {
        if (strtolower(trim((string) ($reservation['email'] ?? ''))) !== $email) {
            continue;
        }
        if (trim((string) ($reservation['session_id'] ?? '')) !== $sessionId) {
            continue;
        }
        if ((string) ($reservation['status'] ?? 'new') === 'cancelled') {
            continue;
        }
        return true;
    }

    return false;
}

function sb_valid_phone(string $value): bool
{
    return $value === '' || preg_match('/^[0-9+\s().-]{6,}$/', $value) === 1;
}

$lang = sb_post('lang');
if ($lang !== 'en') {
    $lang = 'bg';
}
$messages = sb_submit_messages($lang);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => $messages['method']]);
    exit;
}

$inquiryType = sb_post('inquiry_type');
$itemId = sb_post('item_id');
$itemTitle = sb_post('item_title');
$customerName = sb_post('customer_name');
$email = sb_post('email');
$phone = sb_post('phone');
$message = sb_post('message');
$quantity = max(1, min(20, (int) ($_POST['quantity'] ?? 1)));
$sourcePath = sb_post('source_path');
$sessionId = sb_post('session_id');
$scheduleId = sb_post('schedule_id');

if ($customerName === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $messages['name']]);
    exit;
}

if ($inquiryType === 'class' && $sessionId === '' && $scheduleId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $messages['session'], 'field' => 'session_id']);
    exit;
}

if ($email === '' && $phone === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $messages['contact']]);
    exit;
}

if (!sb_valid_email($email)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $messages['email'], 'field' => 'email']);
    exit;
}

if (!sb_valid_phone($phone)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $messages['phone'], 'field' => 'phone']);
    exit;
}

if ($inquiryType === '') {
    $inquiryType = 'general';
}

shine_bright_ensure_data_dir();
if (!is_dir(SHINE_BRIGHT_DATA_DIR)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $messages['init']]);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ipHash = $ip === '' ? null : hash('sha256', $ip . SHINE_BRIGHT_IP_SALT);
$ua = isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$record = [
    'ts' => gmdate('c'),
    'inquiry_type' => $inquiryType,
    'item_id' => $itemId,
    'item_title' => $itemTitle,
    'session_id' => $sessionId,
    'schedule_id' => $scheduleId,
    'quantity' => $quantity,
    'customer_name' => $customerName,
    'email' => $email,
    'phone' => $phone,
    'message' => $message,
    'source_path' => $sourcePath !== '' ? $sourcePath : '/',
    'ip_hash' => $ipHash,
    'ua' => $ua,
];

if ($inquiryType === 'class') {
    $content = shine_bright_load_content();
    $brand = $content[$lang]['brand'] ?? [];
    $classItem = shine_bright_find_public_content_item($content, $lang, 'classes', $itemId);
    $selectedSession = is_array($classItem) && $sessionId !== ''
        ? shine_bright_find_class_session($classItem, $sessionId, $lang, 56)
        : null;
    $selectedSchedule = null;
    if ($selectedSession && is_array($classItem)) {
        $selectedSchedule = shine_bright_find_class_schedule($classItem, (string) ($selectedSession['schedule_id'] ?? ''));
    }
    if (!$selectedSession && is_array($classItem) && $scheduleId !== '') {
        $selectedSchedule = shine_bright_find_class_schedule($classItem, $scheduleId);
        if ($selectedSchedule) {
            foreach (shine_bright_class_upcoming_sessions($classItem, $lang, 56, 64) as $session) {
                if ((string) ($session['schedule_id'] ?? '') === (string) ($selectedSchedule['id'] ?? '')) {
                    $selectedSession = $session;
                    break;
                }
            }
        }
    }
    if (!$selectedSession && is_array($classItem)) {
        $upcomingSessions = shine_bright_class_upcoming_sessions($classItem, $lang, 56, 1);
        $selectedSession = $upcomingSessions[0] ?? null;
        if ($selectedSession) {
            $selectedSchedule = shine_bright_find_class_schedule($classItem, (string) ($selectedSession['schedule_id'] ?? ''));
        }
    }

    if (!$selectedSession) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => $messages['session'], 'field' => 'session_id']);
        exit;
    }

    if ($email !== '') {
        $existingReservations = shine_bright_load_reservations();
        if (sb_same_session_email_reservation_exists($existingReservations, (string) ($selectedSession['id'] ?? ''), $email)) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => $messages['duplicate_reservation'], 'field' => 'email']);
            exit;
        }
    }

    $scheduleDayLabel = (string) ($selectedSession['date_label'] ?? '');
    $scheduleTimeLabel = (string) ($selectedSession['time_label'] ?? '');
    $scheduleSummaryLabel = (string) ($selectedSession['summary_label'] ?? trim($scheduleDayLabel . ($scheduleTimeLabel !== '' ? ' · ' . $scheduleTimeLabel : '')));
    $reservation = shine_bright_create_reservation([
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
        'lang' => $lang,
        'status' => 'new',
        'attendance' => 'pending',
        'class_id' => $itemId,
        'session_id' => (string) ($selectedSession['id'] ?? ''),
        'session_label' => $scheduleSummaryLabel,
        'session_starts_at' => (string) ($selectedSession['starts_at'] ?? ''),
        'schedule_id' => (string) ($selectedSchedule['id'] ?? $selectedSession['schedule_id'] ?? ''),
        'schedule_label' => $scheduleSummaryLabel,
        'class_title' => (string) (($classItem['title'] ?? '') !== '' ? $classItem['title'] : $itemTitle),
        'class_date_label' => $scheduleDayLabel,
        'class_time_label' => $scheduleTimeLabel,
        'class_location' => (string) ($selectedSession['location'] ?? $selectedSchedule['location'] ?? ''),
        'class_maps_url' => (string) ($selectedSession['maps_url'] ?? $selectedSchedule['maps_url'] ?? ''),
        'class_price_label' => is_array($classItem) ? shine_bright_price_label($classItem) : '',
        'spots' => 1,
        'customer_name' => $customerName,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
        'source_path' => $sourcePath !== '' ? $sourcePath : '/',
        'admin_note' => '',
        'ack_email_sent_at' => null,
        'ack_email_status' => $email !== '' ? 'pending' : 'not_applicable',
        'status_email_sent_at' => null,
        'status_email_status' => 'not_applicable',
        'status_email_last' => '',
    ]);

    if ($email !== '') {
        $emailPayload = shine_bright_compose_reservation_email('received', $lang, $brand, $reservation);
        $mailSent = shine_bright_send_multipart_mail(
            $email,
            $emailPayload['subject'],
            $emailPayload['body'],
            (string) ($emailPayload['html'] ?? ''),
            (string) ($brand['contact_email'] ?? '')
        );

        if ($mailSent) {
            $reservations = shine_bright_load_reservations();
            shine_bright_update_reservation_record($reservations, (string) ($reservation['id'] ?? ''), function (array $current): array {
                $current['ack_email_sent_at'] = gmdate('c');
                $current['ack_email_status'] = 'sent';
                return $current;
            });
            shine_bright_save_reservations($reservations);
        } else {
            $reservations = shine_bright_load_reservations();
            shine_bright_update_reservation_record($reservations, (string) ($reservation['id'] ?? ''), function (array $current): array {
                $current['ack_email_status'] = 'failed';
                return $current;
            });
            shine_bright_save_reservations($reservations);
        }
    }

    echo json_encode([
        'ok' => true,
        'reservation' => [
            'kind' => 'class',
            'class_title' => (string) ($reservation['class_title'] ?? ''),
            'session_label' => (string) ($reservation['session_label'] ?? ''),
            'location' => (string) ($reservation['class_location'] ?? ''),
            'maps_url' => (string) ($selectedSession['maps_url'] ?? $selectedSchedule['maps_url'] ?? ''),
        ],
    ]);
    exit;
}

$payload = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

if (file_put_contents(SHINE_BRIGHT_INQUIRIES_PATH, $payload, FILE_APPEND | LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $messages['save']]);
    exit;
}

echo json_encode(['ok' => true]);
