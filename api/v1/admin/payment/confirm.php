<?php
// api/v1/admin/payment/confirm.php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'code' => 405, 'message' => 'Method not allowed']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION['logged_in'] ?? false) !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'code' => 401, 'message' => 'Belum login']);
    exit;
}

if (($_SESSION['role'] ?? null) !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'code' => 403, 'message' => 'Akses hanya untuk admin']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$paymentId = is_array($input) ? ($input['payment_id'] ?? null) : ($_GET['payment_id'] ?? null);

require_once __DIR__ . '/../../../../controller/admin/payment/PaymentController.php';

try {
    $controller = new AdminPaymentController();
    $result = $controller->confirm($paymentId);

    http_response_code($result['code'] ?? 500);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'code' => 500, 'message' => 'Gagal mengonfirmasi pembayaran']);
}