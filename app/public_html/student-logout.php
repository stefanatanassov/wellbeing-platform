<?php
require_once __DIR__ . '/content.php';

$lang = shine_bright_resolve_lang();
shine_bright_student_logout();
header('Location: ./student-login.php?lang=' . urlencode($lang));
exit;
