<?php
// api/v1/cart/add.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../../controller/dashboardusers/cart/CartController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Pastikan session sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan user sudah login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'code' => 401,
        'message' => 'Belum login'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

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

$domainId = $input['domain_id'] ?? '';

$userId = $_SESSION['user_id'];

$cartController = new CartController();

$result = $cartController->addToCart(
    $userId,
    $domainId
);

http_response_code($result['code']);
echo json_encode($result);