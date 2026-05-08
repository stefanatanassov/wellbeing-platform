<?php
require_once dirname(__DIR__) . '/content.php';

shine_bright_require_admin_api();

$method = shine_bright_api_request_method();
if ($method !== 'POST') {
    shine_bright_api_json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$input = shine_bright_api_read_json_body();
if ($input === []) {
    $input = $_POST;
}

$action = trim((string) ($input['action'] ?? 'verify'));
$token = trim((string) ($input['token'] ?? ''));
$lang = shine_bright_normalize_lang((string) ($input['lang'] ?? 'bg'));

function sb_qr_allowed_classes_payload(array $content, string $lang, array $pack): array
{
    $classItems = shine_bright_content_section_items($content, $lang, 'classes');
    $allowedIds = shine_bright_visit_pack_allowed_class_ids($pack);
    $items = [];

    foreach ($classItems as $class) {
        $classId = (string) ($class['id'] ?? '');
        if ($classId === '') {
            continue;
        }
        if ($allowedIds !== [] && !in_array($classId, $allowedIds, true)) {
            continue;
        }
        $items[] = [
            'id' => $classId,
            'title' => (string) ($class['title'] ?? $classId),
        ];
    }

    return $items;
}

function sb_qr_class_title(array $content, string $lang, string $classId): string
{
    if ($classId === '') {
        return '';
    }

    $classItems = shine_bright_content_section_items($content, $lang, 'classes');
    foreach ($classItems as $class) {
        if ((string) ($class['id'] ?? '') !== $classId) {
            continue;
        }
        return (string) ($class['title'] ?? $classId);
    }

    return $classId;
}

function sb_qr_verify_pack_payload(string $token, string $lang): array
{
    $students = shine_bright_load_clients();
    $packs = shine_bright_load_visit_packs();
    $studentId = '';
    $packId = '';
    $qrCodes = shine_bright_load_qr_checkin_codes();
    $qrCodeRecord = shine_bright_find_valid_qr_checkin_code($qrCodes, $token);

    if ($qrCodeRecord) {
        $studentId = (string) ($qrCodeRecord['student_id'] ?? '');
        $packId = (string) ($qrCodeRecord['pack_id'] ?? '');
    } else {
        $payload = shine_bright_verify_qr_token($token);
        if (!$payload) {
            throw new RuntimeException($lang === 'en' ? 'Invalid or expired QR code.' : 'Невалиден или изтекъл QR код.');
        }
        $studentId = (string) ($payload['student_id'] ?? '');
        $packId = (string) ($payload['pack_id'] ?? '');
    }

    $student = shine_bright_find_record_by_id($students, $studentId);
    $pack = shine_bright_find_record_by_id($packs, $packId);

    if (!$student || !$pack || (string) ($pack['client_id'] ?? '') !== $studentId) {
        throw new RuntimeException($lang === 'en' ? 'QR code does not match an active student card.' : 'QR кодът не съвпада с активна карта на ученик.');
    }

    $status = shine_bright_visit_pack_runtime_status($pack);
    if (in_array($status, ['cancelled', 'completed', 'expired'], true)) {
        throw new RuntimeException($lang === 'en' ? 'This visit card cannot be used.' : 'Тази карта не може да бъде използвана.');
    }

    $content = shine_bright_load_content();
    $allowedClasses = sb_qr_allowed_classes_payload($content, $lang, $pack);

    return [
        'student' => $student,
        'pack' => $pack,
        'allowed_classes' => $allowedClasses,
        'qr_code_record' => $qrCodeRecord,
    ];
}

try {
    $content = shine_bright_load_content();

    if ($action === 'undo') {
        $usageId = trim((string) ($input['usage_id'] ?? ''));
        $packs = shine_bright_load_visit_packs();
        $usage = shine_bright_load_visit_usage();
        $undone = shine_bright_undo_visit_usage($packs, $usage, $usageId);
        shine_bright_save_visit_packs($packs);
        shine_bright_save_visit_usage($usage);
        $updatedPack = $undone['pack'];
        $event = $undone['event'];

        shine_bright_api_json_response([
            'ok' => true,
            'pack' => [
                'id' => (string) ($updatedPack['id'] ?? ''),
                'title' => (string) ($updatedPack['title'] ?? ''),
                'remaining_visits' => shine_bright_visit_pack_remaining($updatedPack),
                'total_visits' => (int) ($updatedPack['total_visits'] ?? 0),
                'status' => shine_bright_visit_pack_runtime_status($updatedPack),
            ],
            'usage_event' => [
                'id' => (string) ($event['id'] ?? ''),
                'class_id' => (string) ($event['class_id'] ?? ''),
                'class_title' => sb_qr_class_title($content, $lang, (string) ($event['class_id'] ?? '')),
            ],
            'message' => $lang === 'en' ? 'Last check-in was undone.' : 'Последното чекиране е отменено.',
        ]);
    }

    $verified = sb_qr_verify_pack_payload($token, $lang);
    $student = $verified['student'];
    $pack = $verified['pack'];
    $allowedClasses = $verified['allowed_classes'];
    $qrCodeRecord = $verified['qr_code_record'];
    

    if ($action === 'consume') {
        $packs = shine_bright_load_visit_packs();
        $classId = trim((string) ($input['class_id'] ?? ''));
        $note = trim((string) ($input['note'] ?? ''));
        $usageEvent = null;
        $updatedPack = shine_bright_consume_visit_pack($packs, (string) ($pack['id'] ?? ''), [
            'class_id' => $classId,
            'source' => 'qr_admin',
            'note' => $note,
        ], $usageEvent);
        shine_bright_save_visit_packs($packs);
        if ($qrCodeRecord) {
            $qrCodes = shine_bright_load_qr_checkin_codes();
            shine_bright_mark_qr_checkin_code_used($qrCodes, (string) ($qrCodeRecord['id'] ?? ''));
            shine_bright_save_qr_checkin_codes($qrCodes);
        }

        shine_bright_api_json_response([
            'ok' => true,
            'student' => [
                'id' => (string) ($student['id'] ?? ''),
                'name' => trim((string) ($student['name'] ?? '')) ?: (string) ($student['email'] ?? ''),
            ],
            'pack' => [
                'id' => (string) ($updatedPack['id'] ?? ''),
                'title' => (string) ($updatedPack['title'] ?? ''),
                'remaining_visits' => shine_bright_visit_pack_remaining($updatedPack),
                'total_visits' => (int) ($updatedPack['total_visits'] ?? 0),
                'status' => shine_bright_visit_pack_runtime_status($updatedPack),
            ],
            'usage_event' => [
                'id' => (string) (($usageEvent['id'] ?? '') ?: ''),
                'recorded_at' => (string) (($usageEvent['created_at'] ?? '') ?: gmdate('c')),
                'class_id' => $classId,
                'class_title' => sb_qr_class_title($content, $lang, $classId),
            ],
            'message' => $lang === 'en' ? 'One visit recorded.' : 'Едно посещение е записано.',
        ]);
    }

    shine_bright_api_json_response([
        'ok' => true,
        'student' => [
            'id' => (string) ($student['id'] ?? ''),
            'name' => trim((string) ($student['name'] ?? '')) ?: (string) ($student['email'] ?? ''),
            'email' => (string) ($student['email'] ?? ''),
        ],
        'pack' => [
            'id' => (string) ($pack['id'] ?? ''),
            'title' => (string) ($pack['title'] ?? ''),
            'remaining_visits' => shine_bright_visit_pack_remaining($pack),
            'total_visits' => (int) ($pack['total_visits'] ?? 0),
            'status' => shine_bright_visit_pack_runtime_status($pack),
        ],
        'allowed_classes' => $allowedClasses,
    ]);
} catch (Throwable $e) {
    shine_bright_api_json_response(['ok' => false, 'error' => $e->getMessage()], 422);
}
