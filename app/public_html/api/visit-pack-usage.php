<?php
require_once dirname(__DIR__) . '/runtime-data.php';

shine_bright_require_api_token();

$method = shine_bright_api_request_method();
$packId = isset($_GET['pack_id']) && is_string($_GET['pack_id']) ? trim($_GET['pack_id']) : '';
$clientId = isset($_GET['client_id']) && is_string($_GET['client_id']) ? trim($_GET['client_id']) : '';
$usage = shine_bright_load_visit_usage();

if ($method === 'GET') {
    $items = array_values(array_filter($usage, static function (array $event) use ($packId, $clientId): bool {
        if ($packId !== '' && (string) ($event['pack_id'] ?? '') !== $packId) {
            return false;
        }

        if ($clientId !== '' && (string) ($event['client_id'] ?? '') !== $clientId) {
            return false;
        }

        return true;
    }));

    shine_bright_api_json_response([
        'ok' => true,
        'usage_events' => $items,
    ]);
}

$body = shine_bright_api_read_json_body();

if ($method === 'POST') {
    $payload = isset($body['usage']) && is_array($body['usage']) ? $body['usage'] : $body;
    $packId = trim((string) ($payload['pack_id'] ?? $packId));
    if ($packId === '') {
        shine_bright_api_json_response(['ok' => false, 'error' => 'pack_id is required.'], 422);
    }

    $packs = shine_bright_load_visit_packs();

    try {
        $updatedPack = shine_bright_consume_visit_pack($packs, $packId, [
            'class_id' => (string) ($payload['class_id'] ?? ''),
            'used_on' => (string) ($payload['used_on'] ?? ''),
            'source' => (string) ($payload['source'] ?? 'manual'),
            'note' => (string) ($payload['note'] ?? ''),
        ]);
    } catch (RuntimeException $e) {
        $message = $e->getMessage();
        $status = str_contains(strtolower($message), 'not found') ? 404 : 409;
        shine_bright_api_json_response(['ok' => false, 'error' => $message], $status);
    }

    shine_bright_save_visit_packs($packs);
    $usage = shine_bright_load_visit_usage();
    $latest = $usage !== [] ? $usage[array_key_last($usage)] : null;

    shine_bright_api_json_response([
        'ok' => true,
        'visit_pack' => $updatedPack,
        'usage_event' => $latest,
        'remaining_visits' => shine_bright_visit_pack_remaining($updatedPack),
        'runtime_status' => shine_bright_visit_pack_runtime_status($updatedPack),
    ], 201);
}

shine_bright_api_json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
