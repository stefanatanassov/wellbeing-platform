<?php

// Copy this file to shine-bright-config.php and fill in your values.
// The real config file is gitignored — never commit credentials.

return array(
  'data_dir' => __DIR__ . '/shine-bright-runtime/data',
  'media_dir' => __DIR__ . '/shine-bright-runtime/media',
  'admin_token' => '',
  'ip_salt' => '',
  'mail_from_name' => 'Shine Bright Yoga',
  'mail_from_email' => '',
  'admin_email' => '',
  'admin_password_hash' => '',
  'allow_admin_token_fallback' => true,
);
