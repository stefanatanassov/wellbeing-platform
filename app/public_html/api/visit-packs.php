<?php
require_once dirname(__DIR__) . '/runtime-data.php';

shine_bright_require_api_token();

$method = shine_bright_api_request_method();
$id = isset($_GET['id']) && is_string($_GET['id']) ? trim($_GET['id']) : '';
$clientId = isset($_GET['client_id']) && is_string($_GET['client_id']) ? trim($_GET['client_id']) : '';
$statusFilter = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : '';

$clients = shine_bright_load_clients();
$packs = shine_bright_load_visit_packs();
$usage = shine_bright_load_visit_usage();

    if ($method === 'GET') {
    if ($id !== '') {
        $pack = shine_bright_find_record_by_id($packs, $id);
        if (!$pack) {
            shine_bright_api_json_response(['ok' => false, 'error' => 'Visit card not found.'], 404);
        }

        shine_bright_api_json_response([
            'ok' => true,
            'visit_pack' => $pack,
            'runtime_status' => shine_bright_visit_pack_runtime_status($pack),
            'remaining_visits' => shine_bright_visit_pack_remaining($pack),
            'usage_events' => shine_bright_pack_usage_events($usage, $id),
            'client' => shine_bright_find_record_by_id($clients, (string) ($pack['client_id'] ?? '')),
        ]);
    }

    $items = array_values(array_filter($packs, static function (array $pack) use ($clientId, $statusFilter): bool {
        if ($clientId !== '' && (string) ($pack['client_id'] ?? '') !== $clientId) {
            return false;
        }

        if ($statusFilter !== '' && shine_bright_visit_pack_runtime_status($pack) !== $statusFilter) {
            return false;
        }

        return true;
    }));

        $items = array_map(static function (array $pack) use ($clients): array {
        $pack['runtime_status'] = shine_bright_visit_pack_runtime_status($pack);
        $pack['remaining_visits'] = shine_bright_visit_pack_remaining($pack);
        $pack['client'] = shine_bright_find_record_by_id($clients, (string) ($pack['client_id'] ?? ''));
        return $pack;
    }, $items);

    shine_bright_api_json_response([
        'ok' => true,
        'visit_packs' => $items,
    ]);
}

$body = shine_bright_api_read_json_body();

if ($method === 'POST') {
    $payload = isset($body['visit_pack']) && is_array($body['visit_pack']) ? $body['visit_pack'] : $body;
    if (trim((string) ($payload['client_id'] ?? '')) === '' || trim((string) ($payload['title'] ?? '')) === '') {
        shine_bright_api_json_response(['ok' => false, 'error' => 'client_id and title are required.'], 422);
    }

    if (!shine_bright_find_record_by_id($clients, (string) $payload['client_id'])) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Client not found.'], 404);
    }

    $normalized = shine_bright_normalize_visit_pack($payload);
    if (shine_bright_find_record_by_id($packs, $normalized['id'])) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Visit card already exists.'], 409);
    }

    $saved = shine_bright_upsert_visit_pack($packs, $payload);
    shine_bright_save_visit_packs($packs);

    shine_bright_api_json_response([
        'ok' => true,
        'visit_pack' => $saved,
    ], 201);
}

if ($method === 'PATCH') {
    $patchId = trim((string) ($body['id'] ?? $id));
    if ($patchId === '') {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Visit card id is required.'], 422);
    }

    $existing = shine_bright_find_record_by_id($packs, $patchId);
    if (!$existing) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Visit card not found.'], 404);
    }

    $payload = isset($body['visit_pack']) && is_array($body['visit_pack']) ? $body['visit_pack'] : $body;
    unset($payload['id']);
    $merged = array_merge($existing, array_filter($payload, static fn ($value) => is_scalar($value) || is_array($value) || $value === null));
    $merged['id'] = $patchId;

    if (!shine_bright_find_record_by_id($clients, (string) ($merged['client_id'] ?? ''))) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Client not found.'], 404);
    }

    $saved = shine_bright_upsert_visit_pack($packs, $merged);
    shine_bright_save_visit_packs($packs);

    shine_bright_api_json_response([
        'ok' => true,
        'visit_pack' => $saved,
    ]);
}

if ($method === 'DELETE') {
    $deleteId = trim((string) ($body['id'] ?? $id));
    if ($deleteId === '') {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Visit card id is required.'], 422);
    }

    if (!shine_bright_visit_pack_delete_allowed($usage, $deleteId)) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Visit card has usage history and cannot be deleted.'], 409);
    }

    if (!shine_bright_delete_visit_pack($packs, $deleteId)) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Visit card not found.'], 404);
    }

    shine_bright_save_visit_packs($packs);
    shine_bright_api_json_response([
        'ok' => true,
        'deleted_id' => $deleteId,
    ]);
}

shine_bright_api_json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
