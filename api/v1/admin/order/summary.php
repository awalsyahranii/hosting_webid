<?php
// api/v1/admin/order/summary.php

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


/**
 * =========================================================
 * CEK LOGIN
 * =========================================================
 */
if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'code' => 401,
        'message' => 'Belum login'
    ]);

    exit;
}


/**
 * =========================================================
 * CEK ADMIN
 * =========================================================
 */
if (
    !isset($_SESSION['role']) ||
    (!in_array($_SESSION['role'] ?? '', ['admin', 'super_admin']))
) {

    http_response_code(403);

    echo json_encode([
        'status' => 'error',
        'code' => 403,
        'message' => 'Akses hanya untuk admin'
    ]);

    exit;
}


/**
 * =========================================================
 * LOAD ADMIN ORDER CONTROLLER
 * =========================================================
 */
$controllerPath =
    __DIR__ .
    '/../../../../controller/admin/order/OrderController.php';


if (!file_exists($controllerPath)) {

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Admin OrderController.php tidak ditemukan'
    ]);

    exit;
}


require_once $controllerPath;


/**
 * =========================================================
 * AMBIL SUMMARY
 * =========================================================
 */
try {

    $controller =
        new AdminOrderController();


    $result =
        $controller->getServiceSummary();


    http_response_code(
        $result['code'] ?? 200
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