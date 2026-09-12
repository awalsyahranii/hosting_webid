<?php
// api/v1/admin/payment/index.php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

if ((!in_array($_SESSION['role'] ?? '', ['admin', 'super_admin']))) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'code' => 403, 'message' => 'Akses hanya untuk admin']);
    exit;
}

require_once __DIR__ . '/../../../../controller/admin/payment/PaymentController.php';

try {
    $controller = new AdminPaymentController();
    $result = isset($_GET['payment_id'])
        ? $controller->getById($_GET['payment_id'])
        : $controller->getAll();

    http_response_code($result['code'] ?? 500);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'code' => 500, 'message' => 'Terjadi kesalahan server']);
}