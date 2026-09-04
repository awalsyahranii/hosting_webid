<?php
// api/v1/admin/order/index.php

header('Content-Type: application/json; charset=UTF-8');

header(
    'Access-Control-Allow-Origin: http://localhost:3000'
);

header(
    'Access-Control-Allow-Credentials: true'
);

header(
    'Access-Control-Allow-Methods: GET, OPTIONS'
);

header(
    'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'
);


/**
 * =========================================================
 * OPTIONS / PREFLIGHT
 * =========================================================
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {

    http_response_code(200);

    exit;
}


/**
 * =========================================================
 * METHOD
 * =========================================================
 */
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
 * SESSION ADMIN
 * =========================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
 * CEK ROLE ADMIN
 * =========================================================
 */
if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
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
        'message' => 'Admin OrderController.php tidak ditemukan',
        'path' => $controllerPath
    ]);

    exit;
}


require_once $controllerPath;


/**
 * =========================================================
 * AMBIL DATA ORDER
 * =========================================================
 */
try {

    $controller = new AdminOrderController();

    $result = $controller->getAllOrders();


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