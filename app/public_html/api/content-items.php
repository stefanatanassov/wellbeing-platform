<?php
require_once dirname(__DIR__) . '/content.php';

shine_bright_require_api_token();

$method = shine_bright_api_request_method();
$lang = shine_bright_normalize_lang(isset($_GET['lang']) && is_string($_GET['lang']) ? $_GET['lang'] : 'bg');
$section = isset($_GET['section']) && is_string($_GET['section']) ? trim($_GET['section']) : '';
$id = isset($_GET['id']) && is_string($_GET['id']) ? trim($_GET['id']) : '';

if (!shine_bright_content_section_exists($section)) {
    shine_bright_api_json_response([
        'ok' => false,
        'error' => 'Unsupported section.',
        'allowed_sections' => shine_bright_content_sections(),
    ], 422);
}

$content = shine_bright_load_content();

if ($method === 'GET') {
    if ($id !== '') {
        $item = shine_bright_find_content_item($content, $lang, $section, $id);
        if (!$item) {
            shine_bright_api_json_response(['ok' => false, 'error' => 'Item not found.'], 404);
        }

        shine_bright_api_json_response([
            'ok' => true,
            'lang' => $lang,
            'section' => $section,
            'item' => $item,
        ]);
    }

    shine_bright_api_json_response([
        'ok' => true,
        'lang' => $lang,
        'section' => $section,
        'items' => shine_bright_content_section_items($content, $lang, $section),
    ]);
}

$body = shine_bright_api_read_json_body();

if ($method === 'POST') {
    $payload = isset($body['item']) && is_array($body['item']) ? $body['item'] : $body;
    if (!is_array($payload) || trim((string) ($payload['title'] ?? $payload['quote'] ?? '')) === '') {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Item payload is required.'], 422);
    }

    $normalized = shine_bright_normalize_content_item($section, $payload);
    if (shine_bright_find_content_item($content, $lang, $section, $normalized['id'])) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Item already exists.'], 409);
    }

    $saved = shine_bright_upsert_content_item($content, $lang, $section, $payload);
    shine_bright_save_content($content);

    shine_bright_api_json_response([
        'ok' => true,
        'lang' => $lang,
        'section' => $section,
        'item' => $saved,
    ], 201);
}

if ($method === 'PATCH') {
    $patchId = trim((string) ($body['id'] ?? $id));
    if ($patchId === '') {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Item id is required.'], 422);
    }

    $existing = shine_bright_find_content_item($content, $lang, $section, $patchId);
    if (!$existing) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Item not found.'], 404);
    }

    $payload = isset($body['item']) && is_array($body['item']) ? $body['item'] : $body;
    unset($payload['id']);
    $merged = array_merge($existing, array_filter($payload, static fn ($value) => is_scalar($value) || $value === null));
    $merged['id'] = $patchId;

    $saved = shine_bright_upsert_content_item($content, $lang, $section, $merged);
    shine_bright_save_content($content);

    shine_bright_api_json_response([
        'ok' => true,
        'lang' => $lang,
        'section' => $section,
        'item' => $saved,
    ]);
}

if ($method === 'DELETE') {
    $deleteId = trim((string) ($body['id'] ?? $id));
    if ($deleteId === '') {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Item id is required.'], 422);
    }

    if (!shine_bright_delete_content_item($content, $lang, $section, $deleteId)) {
        shine_bright_api_json_response(['ok' => false, 'error' => 'Item not found.'], 404);
    }

    shine_bright_save_content($content);
    shine_bright_api_json_response([
        'ok' => true,
        'lang' => $lang,
        'section' => $section,
        'deleted_id' => $deleteId,
    ]);
}

shine_bright_api_json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
