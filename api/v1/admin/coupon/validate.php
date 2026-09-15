<?php
// api/v1/admin/coupon/validate.php

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


/* =====================================================
   CEK LOGIN
===================================================== */

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


/* =====================================================
   CEK USER ID
===================================================== */

$userId = $_SESSION['user_id'] ?? null;

if (
    empty($userId) ||
    !is_numeric($userId)
) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'code' => 401,
        'message' => 'User belum login'
    ]);

    exit;
}


/* =====================================================
   BACA JSON BODY
===================================================== */

$input = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($input)) {
    $input = [];
}


/* =====================================================
   INPUT
===================================================== */

$code = trim(
    $input['code'] ?? ''
);

$subtotal =
    $input['subtotal'] ?? 0;


/* =====================================================
   LOAD CONTROLLER
===================================================== */

$controllerPath =
    __DIR__ .
    '/../../../../controller/dashboardusers/coupon/CouponController.php';


if (!file_exists($controllerPath)) {

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' =>
            'CouponController.php tidak ditemukan'
    ]);

    exit;
}

require_once $controllerPath;


/* =====================================================
   CONTROLLER
===================================================== */

$couponController =
    new CouponController();


$result =
    $couponController->validateCoupon(
        $code,
        $subtotal
    );


/* =====================================================
   RESPONSE
===================================================== */

http_response_code(
    $result['code'] ?? 500
);

echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE
);