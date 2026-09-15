<?php
// api/v1/payment/request-confirmation.php

require_once __DIR__ . '/../../../config/cors.php';
cors_init(['POST', 'OPTIONS']);
session_init();

require_once __DIR__ . '/../../../controller/users/user/AuthController.php';
require_once __DIR__ . '/../../../controller/dashboardusers/payment/PaymentController.php';

$session = (new AuthController())->checkSession();
if (($session['code'] ?? 500) !== 200) {
    http_response_code($session['code'] ?? 401);
    echo json_encode($session, JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$paymentId = $input['payment_id'] ?? null;
$orderId = $input['order_id'] ?? null;
$paymentNumber = $input['payment_number'] ?? null;

$result = (new PaymentController())->requestConfirmation(
    $session['data']['user_id'] ?? null,
    $paymentId,
    $orderId,
    $paymentNumber
);

http_response_code($result['code'] ?? 500);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
