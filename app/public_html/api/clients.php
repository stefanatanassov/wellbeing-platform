<?php
require_once dirname(__DIR__) . '/runtime-data.php';

shine_bright_require_api_token();

$method = shine_bright_api_request_method();
$id = isset($_GET['id']) && is_string($_GET['id']) ? trim($_GET['id']) : '';
$clients = shine_bright_load_clients();
$packs = shine_bright_load_visit_packs();

if ($method === 'GET') {
    if ($id !== '') {
        $client = shine_bright_find_record_by_id($clients, $id);
        if (!$client) {
            shine_bright_api_json_response(['ok' => false, 'error' => 'Client not found.'], 404);
        }

        shine_bright_api_json_response([
            'ok' => true,
            'client' => $client,
            'pack_count' => shine_bright_client_pack_count($packs, $id),
            'active_pack_count' => shine_bright_client_active_pack_count($packs, $id),
        ]);
    }

    $items = array_map(static function (array $client) use ($packs): array {
        $clientId = (string) ($client['id'] ?? '');
        $client['pack_count'] = shine_bright_client_pack_count($packs, $clientId);
        $client['active_pack_count'] = shine_bright_client_active_pack_count($packs, $clientId);
        return $client;
    }, $clients);

    shine_bright_api_json_response([
        'ok' => true,
        'clients' => $items,
    ]);
}

$body = shine_bright_api_read_json_body();

if ($method === 'POST') {
    $payload = isset($body['client']) && is_array($body['client']) ? $body['client'] : $body;
    if (trim((string) ($payload['name'] ?? '')) === '') {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Client name is required.'], 422);
    }

    $normalized = shine_bright_normalize_client($payload);
    if (shine_bright_find_record_by_id($clients, $normalized['id'])) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Client already exists.'], 409);
    }

    $saved = shine_bright_upsert_client($clients, $payload);
    shine_bright_save_clients($clients);

    shine_bright_api_json_response([
        'ok' => true,
        'client' => $saved,
    ], 201);
}

if ($method === 'PATCH') {
    $patchId = trim((string) ($body['id'] ?? $id));
    if ($patchId === '') {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Client id is required.'], 422);
    }

    $existing = shine_bright_find_record_by_id($clients, $patchId);
    if (!$existing) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Client not found.'], 404);
    }

    $payload = isset($body['client']) && is_array($body['client']) ? $body['client'] : $body;
    unset($payload['id']);
    $merged = array_merge($existing, array_filter($payload, static fn ($value) => is_scalar($value) || $value === null));
    $merged['id'] = $patchId;

    $saved = shine_bright_upsert_client($clients, $merged);
    shine_bright_save_clients($clients);

    shine_bright_api_json_response([
        'ok' => true,
        'client' => $saved,
    ]);
}

if ($method === 'DELETE') {
    $deleteId = trim((string) ($body['id'] ?? $id));
    if ($deleteId === '') {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Client id is required.'], 422);
    }

    if (shine_bright_client_pack_count($packs, $deleteId) > 0) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Cannot delete a client with visit cards.'], 409);
    }

    if (!shine_bright_delete_client($clients, $deleteId)) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Client not found.'], 404);
    }

    shine_bright_save_clients($clients);
    shine_bright_api_json_response([
        'ok' => true,
        'deleted_id' => $deleteId,
    ]);
}

shine_bright_api_json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
