<?php
// api/v1/auth/login.php

header('Content-Type: application/json');

function log_debug($message, $data = null) {
    $logFile = __DIR__ . '/../../../debug_login.log';
    $timestamp = date('Y-m-d H:i:s');
    $dataStr = $data ? ' | Data: ' . json_encode($data) : '';
    file_put_contents($logFile, "[$timestamp] $message$dataStr\n", FILE_APPEND);
}

log_debug("Login API requested", [
    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    'origin' => $_SERVER['HTTP_ORIGIN'] ?? '',
    'uri' => $_SERVER['REQUEST_URI'] ?? ''
]);

require_once __DIR__ . '/../../../config/cors.php';
cors_init(['POST', 'OPTIONS']);
session_init();
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

$rawInput = file_get_contents('php://input');
log_debug("Raw JSON input", ['raw' => $rawInput]);

$input = json_decode(
    $rawInput,
    true
);

if (!is_array($input)) {
    http_response_code(400);

    $errorResponse = [
        'status' => 'error',
        'code' => 400,
        'message' => 'Format JSON tidak valid'  
    ];
    log_debug("Invalid JSON parsed", $errorResponse);
    echo json_encode($errorResponse);

    exit;
}

log_debug("Parsed credentials", [
    'identifier' => $input['identifier'] ?? '',
    'password_provided' => empty($input['password']) ? 'no' : 'yes'
]);

$auth = new AuthController();

$result = $auth->login(
    $input['identifier'] ?? '',
    $input['password'] ?? ''
);

log_debug("Login result", $result);

http_response_code($result['code']);

echo json_encode($result);