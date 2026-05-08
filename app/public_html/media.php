<?php
require_once __DIR__ . '/bootstrap.php';

$requested = $_GET['file'] ?? '';
if (!is_string($requested) || trim($requested) === '') {
    http_response_code(404);
    exit;
}

$requested = trim($requested);
$requested = ltrim($requested, '/');
$requested = preg_replace('/\.\.+/', '', $requested) ?? '';

if ($requested === '' || str_contains($requested, "\0")) {
    http_response_code(404);
    exit;
}

$path = SHINE_BRIGHT_MEDIA_DIR . '/' . $requested;
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($path) ?: 'application/octet-stream';
$lastModified = filemtime($path) ?: time();

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=31536000, immutable');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
readfile($path);
exit;
