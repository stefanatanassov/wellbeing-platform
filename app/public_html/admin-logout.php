<?php
require_once __DIR__ . '/content.php';

$lang = shine_bright_resolve_lang();
shine_bright_admin_logout();
header('Location: ' . shine_bright_admin_login_url($lang));
exit;
