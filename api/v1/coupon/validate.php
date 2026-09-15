<?php
// api/v1/coupon/validate.php

require_once __DIR__ . '/../../../config/cors.php';
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

$code = $input['code'] ?? '';
$subtotal = $input['subtotal'] ?? 0;

require_once __DIR__ .
    '/../../../controller/dashboardusers/coupon/CouponController.php';

$couponController = new CouponController();

$result = $couponController->validateCoupon(
    $code,
    $subtotal
);

http_response_code($result['code']);

echo json_encode($result);