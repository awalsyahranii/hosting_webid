<?php
// api/v1/auth/login.php

header('Content-Type: application/json');

header(
    'Access-Control-Allow-Origin: http://localhost:3000'
);

header('Access-Control-Allow-Credentials: true');

header(
    'Access-Control-Allow-Methods: POST, OPTIONS'
);

header(
    'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'
);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_regenerate_id(true);

require_once __DIR__ . '/../../../controller/users/user/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Method not allowed'
    ]);

    exit;
}

$input = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' => 'Format JSON tidak valid'
    ]);

    exit;
}

$auth = new AuthController();

$result = $auth->login(
    $input['identifier'] ?? '',
    $input['password'] ?? ''
);

http_response_code($result['code']);

echo json_encode($result);