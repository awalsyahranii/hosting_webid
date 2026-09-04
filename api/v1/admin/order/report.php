<?php
// api/v1/admin/order/report.php

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
 * OPTIONS
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
 * SESSION
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
 * CEK ADMIN
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
 * CONTROLLER
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
        'message' =>
            'Admin OrderController.php tidak ditemukan'
    ]);

    exit;
}


require_once $controllerPath;


/**
 * =========================================================
 * FILTER
 *
 * Contoh:
 *
 * ?category=website
 * ?category=iklan
 * ?status=active
 * ?status=expired
 *
 * Bisa digabung:
 *
 * ?category=website&status=active
 * =========================================================
 */
$category =
    $_GET['category'] ?? null;

$status =
    $_GET['status'] ?? null;


/**
 * =========================================================
 * AMBIL LAPORAN
 * =========================================================
 */
try {

    $controller =
        new AdminOrderController();


    $result =
        $controller->getServiceReport(
            $category,
            $status
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
        'message' =>
            'Terjadi kesalahan server',
        'error' =>
            $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}


exit;