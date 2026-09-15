<?php
// config/config.php

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'hosting_web');
define('DB_USER', 'root');
define('DB_PASS', '');

// Konfigurasi Email (untuk lupa sandi)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'your-email@gmail.com');
define('APP_NAME', 'Hosting Web');
define('APP_URL', 'http://localhost/hosting_web');

require_once __DIR__ . '/cors.php';

// Session
session_init();