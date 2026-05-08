<?php
require_once dirname(__DIR__) . '/runtime-data.php';

shine_bright_require_api_token();

$method = shine_bright_api_request_method();
$id = isset($_GET['id']) && is_string($_GET['id']) ? trim($_GET['id']) : '';
$students = shine_bright_load_clients();
$packs = shine_bright_load_visit_packs();

if ($method === 'GET') {
    if ($id !== '') {
        $student = shine_bright_find_record_by_id($students, $id);
        if (!$student) {
            shine_bright_api_json_response(['ok' => false, 'error' => 'Student not found.'], 404);
        }

        $student['pack_count'] = shine_bright_client_pack_count($packs, $id);
        $student['active_pack_count'] = shine_bright_client_active_pack_count($packs, $id);
        shine_bright_api_json_response(['ok' => true, 'student' => $student]);
    }

    $items = array_map(static function (array $student) use ($packs): array {
        $studentId = (string) ($student['id'] ?? '');
        $student['pack_count'] = shine_bright_client_pack_count($packs, $studentId);
        $student['active_pack_count'] = shine_bright_client_active_pack_count($packs, $studentId);
        return $student;
    }, $students);

    shine_bright_api_json_response(['ok' => true, 'students' => $items]);
}

$body = shine_bright_api_read_json_body();

if ($method === 'POST') {
    $payload = isset($body['student']) && is_array($body['student']) ? $body['student'] : $body;
    $email = strtolower(trim((string) ($payload['email'] ?? '')));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Valid email is required.'], 422);
    }
    if (shine_bright_find_student_by_email($students, $email)) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Student email already exists.'], 409);
    }

    $payload['email'] = $email;
    $payload['account_status'] = (string) ($payload['account_status'] ?? 'invited');
    $saved = shine_bright_upsert_client($students, $payload);
    shine_bright_save_clients($students);

    shine_bright_api_json_response(['ok' => true, 'student' => $saved], 201);
}

if ($method === 'PATCH') {
    $patchId = trim((string) ($body['id'] ?? $id));
    if ($patchId === '') {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Student id is required.'], 422);
    }

    $existing = shine_bright_find_record_by_id($students, $patchId);
    if (!$existing) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Student not found.'], 404);
    }

    $payload = isset($body['student']) && is_array($body['student']) ? $body['student'] : $body;
    unset($payload['id']);
    $merged = array_merge($existing, array_filter($payload, static fn ($value) => is_scalar($value) || $value === null));
    $merged['id'] = $patchId;
    $merged['email'] = strtolower(trim((string) ($merged['email'] ?? '')));
    if ($merged['email'] === '' || !filter_var($merged['email'], FILTER_VALIDATE_EMAIL)) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Valid email is required.'], 422);
    }
    $existingByEmail = shine_bright_find_student_by_email($students, $merged['email']);
    if ($existingByEmail && (string) ($existingByEmail['id'] ?? '') !== $patchId) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Student email already exists.'], 409);
    }

    $saved = shine_bright_upsert_client($students, $merged);
    shine_bright_save_clients($students);

    shine_bright_api_json_response(['ok' => true, 'student' => $saved]);
}

if ($method === 'DELETE') {
    $deleteId = trim((string) ($body['id'] ?? $id));
    if ($deleteId === '') {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Student id is required.'], 422);
    }
    if (shine_bright_client_pack_count($packs, $deleteId) > 0) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Cannot delete a student with visit cards.'], 409);
    }
    if (!shine_bright_delete_client($students, $deleteId)) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Student not found.'], 404);
    }

    shine_bright_save_clients($students);
    shine_bright_api_json_response(['ok' => true, 'deleted_id' => $deleteId]);
}

shine_bright_api_json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
