<?php
// api/v1/auth/logout.php

require_once __DIR__ . '/../../../config/cors.php';
cors_init(['POST', 'OPTIONS']);
session_init();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Method not allowed'
    ]);
    exit;
}


// =========================
// LOAD CONTROLLER
// =========================

require_once __DIR__ .
    '/../../../controller/users/user/AuthController.php';


// =========================
// LOGOUT
// =========================

$auth = new AuthController();

$result = $auth->logout();


// =========================
// RESPONSE
// =========================

http_response_code($result['code']);

echo json_encode($result);