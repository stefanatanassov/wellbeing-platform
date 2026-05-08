<?php
require_once dirname(__DIR__) . '/runtime-data.php';

shine_bright_require_api_token();

$method = shine_bright_api_request_method();
if ($method !== 'POST') {
    shine_bright_api_json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$body = shine_bright_api_read_json_body();
$studentId = trim((string) ($body['student_id'] ?? ''));
$lang = shine_bright_normalize_lang(isset($body['lang']) && is_string($body['lang']) ? $body['lang'] : 'bg');
if ($studentId === '') {
    shine_bright_api_json_response(['ok' => false, 'error' => 'student_id is required.'], 422);
}

$students = shine_bright_load_clients();
$tokens = shine_bright_load_student_activation_tokens();
$student = shine_bright_find_record_by_id($students, $studentId);
if (!$student) {
    shine_bright_api_json_response(['ok' => false, 'error' => 'Student not found.'], 404);
}

$sent = shine_bright_send_student_activation_email($students, $tokens, $studentId, $lang);
if (!$sent) {
    shine_bright_api_json_response(['ok' => false, 'error' => 'Activation email could not be sent.'], 409);
}

shine_bright_api_json_response(['ok' => true, 'student_id' => $studentId, 'sent' => true], 201);
