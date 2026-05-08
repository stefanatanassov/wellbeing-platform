<?php
require_once dirname(__DIR__) . '/content.php';

shine_bright_require_api_token();

$method = shine_bright_api_request_method();
$lang = shine_bright_normalize_lang(isset($_GET['lang']) && is_string($_GET['lang']) ? $_GET['lang'] : 'bg');
$content = shine_bright_load_content();
$langContent = $content[$lang] ?? [];

if ($method === 'GET') {
    shine_bright_api_json_response([
        'ok' => true,
        'lang' => $lang,
        'meta' => $langContent['meta'] ?? [],
        'brand' => $langContent['brand'] ?? [],
        'ui' => $langContent['ui'] ?? [],
    ]);
}

if ($method !== 'PATCH') {
    shine_bright_api_json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$body = shine_bright_api_read_json_body();
$allowedGroups = ['meta', 'brand', 'ui'];

foreach ($allowedGroups as $group) {
    if (!isset($body[$group]) || !is_array($body[$group])) {
        continue;
    }

    foreach ($body[$group] as $key => $value) {
        if (!is_string($key)) {
            continue;
        }

        $content[$lang][$group][$key] = is_scalar($value) || $value === null
            ? trim((string) $value)
            : ($content[$lang][$group][$key] ?? '');
    }
}

shine_bright_save_content($content);

shine_bright_api_json_response([
    'ok' => true,
    'lang' => $lang,
    'meta' => $content[$lang]['meta'] ?? [],
    'brand' => $content[$lang]['brand'] ?? [],
    'ui' => $content[$lang]['ui'] ?? [],
]);
