<?php
// api/v1/admin/coupon/create.php

require_once __DIR__ . '/../../../../config/cors.php';
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
// CEK LOGIN
// =========================

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


// =========================
// CEK ADMIN
// =========================

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


// =========================
// LOAD CONTROLLER
// =========================

$controllerPath =
    __DIR__ . '/../../../../controller/admin/coupon/CouponController.php';

if (!file_exists($controllerPath)) {
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'CouponController.php tidak ditemukan'
    ]);

    exit;
}

require_once $controllerPath;


// =========================
// BACA JSON
// =========================

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


// =========================
// CONTROLLER
// =========================

$couponController = new CouponController();

$result = $couponController->create($input);


// =========================
// RESPONSE
// =========================

http_response_code($result['code'] ?? 500);

echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE
);