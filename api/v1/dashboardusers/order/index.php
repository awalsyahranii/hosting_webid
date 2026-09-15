<?php
// api/v1/dashboardusers/order/index.php

require_once __DIR__ . '/../../../../config/cors.php';
cors_init(['GET', 'OPTIONS']);
session_init();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Method tidak diizinkan'
    ]);
    exit;
}


// =====================================================
// CONTROLLER
// =====================================================

$controllerPath =
    __DIR__ .
    '/../../../../controller/dashboardusers/order/OrderController.php';


if (!file_exists($controllerPath)) {

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'OrderController.php tidak ditemukan'
    ]);

    exit;
}


require_once $controllerPath;


// =====================================================
// USER ID
// =====================================================

$userId =
    $_GET['user_id']
    ?? null;


if (empty($userId)) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'code' => 401,
        'message' => 'User ID wajib diisi'
    ]);

    exit;
}


// =====================================================
// GET ORDERS
// =====================================================

try {

    $controller =
        new OrderController();


    $result =
        $controller->getOrders(
            (int) $userId
        );


    http_response_code(
        $result['code'] ?? 500
    );


    echo json_encode(
        $result,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_PRETTY_PRINT
    );

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Terjadi kesalahan server',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}


exit;