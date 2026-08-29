<?php
// api/v1/order/create.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../../controller/users/user/AuthController.php';
require_once __DIR__ . '/../../../controller/dashboardusers/order/OrderController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Method not allowed'
    ]);

    exit;
}

// Cek session user
$auth = new AuthController();

$session = $auth->checkSession();

if ($session['code'] !== 200) {

    http_response_code($session['code']);

    echo json_encode($session);

    exit;
}

// Ambil user ID
$userId = $session['data']['user_id'] ?? null;

if (empty($userId)) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'code' => 401,
        'message' => 'User belum login'
    ]);

    exit;
}

// Buat order
$orderController = new OrderController();

$result = $orderController->createOrder($userId);

http_response_code($result['code']);

echo json_encode($result);