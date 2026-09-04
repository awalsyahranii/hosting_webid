<?php
// api/v1/order/create.php

header('Content-Type: application/json; charset=UTF-8');

header(
    'Access-Control-Allow-Origin: http://localhost:3000'
);

header(
    'Access-Control-Allow-Credentials: true'
);

header(
    'Access-Control-Allow-Methods: POST, OPTIONS'
);

header(
    'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'
);


/**
 * =====================================================
 * HANDLE PREFLIGHT
 * =====================================================
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'OPTIONS'
) {
    http_response_code(200);
    exit;
}


/**
 * =====================================================
 * METHOD CHECK
 * =====================================================
 */
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {

    http_response_code(405);

    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Method not allowed'
    ]);

    exit;
}


/**
 * =====================================================
 * LOAD CONTROLLER
 * =====================================================
 */
require_once
    __DIR__ .
    '/../../../controller/users/user/AuthController.php';

require_once
    __DIR__ .
    '/../../../controller/dashboardusers/order/OrderController.php';


/**
 * =====================================================
 * CEK SESSION USER
 * =====================================================
 */
$auth =
    new AuthController();


$session =
    $auth->checkSession();


if (
    ($session['code'] ?? 500)
    !== 200
) {

    http_response_code(
        $session['code'] ?? 401
    );

    echo json_encode(
        $session,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/**
 * =====================================================
 * USER ID
 * =====================================================
 */
$userId =
    $session['data']['user_id']
    ?? null;


if (
    empty($userId)
) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'code' => 401,
        'message' =>
            'User belum login'
    ]);

    exit;
}


/**
 * =====================================================
 * BACA JSON REQUEST
 * =====================================================
 */
$input =
    json_decode(
        file_get_contents('php://input'),
        true
    );


if (
    !is_array($input)
) {
    $input = [];
}


/**
 * =====================================================
 * AMBIL COUPON CODE
 * =====================================================
 */
$couponCode =
    $input['coupon_code']
    ?? null;


if (
    is_string($couponCode)
) {

    $couponCode =
        strtoupper(
            trim(
                $couponCode
            )
        );

} else {

    $couponCode = null;
}


/**
 * =====================================================
 * BUAT ORDER
 * =====================================================
 */
$orderController =
    new OrderController();


$result =
    $orderController->createOrder(
        $userId,
        $couponCode
    );


/**
 * =====================================================
 * RESPONSE
 * =====================================================
 */
http_response_code(
    $result['code']
    ?? 500
);


echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE
);

exit;