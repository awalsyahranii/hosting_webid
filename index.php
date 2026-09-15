<?php
// index.php (Root API Entry)
require_once __DIR__ . '/config/cors.php';
cors_init(['GET', 'OPTIONS']);

header('Content-Type: application/json; charset=UTF-8');

echo json_encode([
    'status' => 'success',
    'code' => 200,
    'message' => 'Hosting Web ID Backend API is running',
    'version' => '1.0.0',
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
exit;
