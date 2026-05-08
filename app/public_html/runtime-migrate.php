<?php
require_once __DIR__ . '/content.php';

shine_bright_require_admin();

try {
    $paths = shine_bright_externalize_runtime_state();

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'message' => 'Runtime state externalized.',
        'runtime_root' => $paths['root'],
        'data_dir' => $paths['data_dir'],
        'media_dir' => $paths['media_dir'],
        'config_path' => $paths['config_path'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
exit;
