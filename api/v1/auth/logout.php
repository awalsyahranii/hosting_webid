<?php
// api/v1/auth/logout.php

header('Content-Type: application/json');

header(
    'Access-Control-Allow-Origin: http://localhost:3000'
);

header(
    'Access-Control-Allow-Credentials: true'
);

header(
    'Access-Control-Allow-Methods: POST, OPTIONS'
);

header(
    'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'
);


// =========================
// HANDLE PREFLIGHT
// =========================

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// =========================
// METHOD CHECK
// =========================

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
// START SESSION
// =========================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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