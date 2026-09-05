<?php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../controller/users/user/AuthController.php';
require_once __DIR__ . '/../../../controller/dashboardusers/payment/PaymentController.php';

$session = (new AuthController())->checkSession();
if (($session['code'] ?? 500) !== 200) {
    http_response_code($session['code'] ?? 401);
    echo json_encode($session, JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$paymentId = is_array($input) ? ($input['payment_id'] ?? null) : null;
$result = (new PaymentController())->requestConfirmation(
    $session['data']['user_id'] ?? null,
    $paymentId
);

http_response_code($result['code'] ?? 500);
echo json_encode($result, JSON_UNESCAPED_UNICODE);