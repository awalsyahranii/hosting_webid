<?php
// api/v1/admin/renewal/create-invoice.php

require_once __DIR__ . '/../../../../config/cors.php';
cors_init(['POST', 'OPTIONS']);
session_init();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'code' => 405, 'message' => 'Method not allowed']);
    exit;
}

if (($_SESSION['logged_in'] ?? false) !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'code' => 401, 'message' => 'Belum login']);
    exit;
}

if (!in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'code' => 403, 'message' => 'Akses hanya untuk admin']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$orderItemId = $input['order_item_id'] ?? null;
$amount = $input['amount'] ?? null;
$billingPeriod = $input['billing_period'] ?? null;
$dueDate = $input['due_date'] ?? null;
$notes = $input['notes'] ?? null;

require_once __DIR__ . '/../../../../controller/admin/renewal/RenewalController.php';

try {
    $controller = new RenewalController();
    $result = $controller->createInvoice($orderItemId, $amount, $billingPeriod, $dueDate, $notes);

    http_response_code($result['code'] ?? 200);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'code' => 500, 'message' => 'Gagal membuat invoice perpanjangan: ' . $e->getMessage()]);
}
