<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../../controller/users/user/AuthController.php';
require_once __DIR__ . '/../../../controller/dashboardusers/cart/CartController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);

    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Method not allowed'
    ]);

    exit;
}

// Cek session user
$auth = new AuthController();
$session = $auth->checkSession();

if ($session['code'] !== 200) {
    http_response_code($session['code']);
    echo json_encode($session);
    exit;
}

// Ambil user ID dari session
$userId = $session['data']['user_id'] ?? null;

// Ambil JSON body
$input = json_decode(
    file_get_contents('php://input'),
    true
);

// Pastikan JSON valid
if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' => 'Format JSON tidak valid'
    ]);

    exit;
}

// Ambil cart ID
$cartId = $input['cart_id'] ?? '';

// Validasi cart ID
if (empty($cartId) || !is_numeric($cartId)) {
    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' => 'ID cart tidak valid'
    ]);

    exit;
}

// Jalankan controller
$cartController = new CartController();

$result = $cartController->deleteCartItem(
    $userId,
    $cartId
);

http_response_code($result['code']);

echo json_encode($result);