<?php
// api/v1/payment/index.php

header(
    'Content-Type: application/json; charset=UTF-8'
);

header(
    'Access-Control-Allow-Origin: http://localhost:3000'
);

header(
    'Access-Control-Allow-Credentials: true'
);

header(
    'Access-Control-Allow-Methods: GET, POST, OPTIONS'
);

header(
    'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'
);


/**
 * =====================================================
 * PREFLIGHT
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
 * LOAD
 * =====================================================
 */
require_once
    __DIR__ .
    '/../../../controller/users/user/AuthController.php';

require_once
    __DIR__ .
    '/../../../controller/dashboardusers/payment/PaymentController.php';


/**
 * =====================================================
 * SESSION
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


$userId =
    $session['data']['user_id']
    ?? null;


if (empty($userId)) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'code' => 401,
        'message' =>
            'User belum login'
    ]);

    exit;
}


$paymentController =
    new PaymentController();


/**
 * =====================================================
 * POST
 * =====================================================
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $input =
        json_decode(
            file_get_contents(
                'php://input'
            ),
            true
        );


    if (!is_array($input)) {

        http_response_code(400);

        echo json_encode([
            'status' => 'error',
            'code' => 400,
            'message' =>
                'Format JSON tidak valid'
        ]);

        exit;
    }


    /**
     * order_id
     */
    $orderId =
        $input['order_id']
        ?? null;


        /**
     * =====================================================
     * payment_method
     * =====================================================
     */
    $paymentMethod =
        $input['payment_method']
        ?? 'transfer';


    $result =
        $paymentController->createPayment(
            $userId,
            $orderId,
            $paymentMethod
        );


    http_response_code(
        $result['code'] ?? 500
    );


    echo json_encode(
        $result,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/**
 * =====================================================
 * GET
 * =====================================================
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
) {

    if (
        isset(
            $_GET['payment_id']
        )
    ) {

        $paymentId =
            $_GET['payment_id'];


        $result =
            $paymentController->getById(
                $userId,
                $paymentId
            );

    } else {

        $result =
            $paymentController->getAll(
                $userId
            );
    }


    http_response_code(
        $result['code'] ?? 500
    );


    echo json_encode(
        $result,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/**
 * =====================================================
 * METHOD NOT ALLOWED
 * =====================================================
 */
http_response_code(405);

echo json_encode([
    'status' => 'error',
    'code' => 405,
    'message' =>
        'Method not allowed'
]);

exit;