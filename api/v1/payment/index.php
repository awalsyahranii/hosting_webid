<?php
// api/v1/payment/index.php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../service/database/db.php';
require_once __DIR__ . '/../../../controller/users/user/AuthController.php';
require_once __DIR__ . '/../../../controller/dashboardusers/payment/PaymentController.php';

$auth = new AuthController();
$session = $auth->checkSession();

$userId = null;
if (($session['code'] ?? 500) === 200 && !empty($session['data']['user_id'])) {
    $userId = (int) $session['data']['user_id'];
}

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 1. By payment_number
    if (!empty($_GET['payment_number'])) {
        $stmt = $db->prepare("
            SELECT p.*, o.order_number, o.status AS order_status
            FROM payments p
            JOIN orders o ON o.id = p.order_id
            WHERE p.payment_number = :pnum
            LIMIT 1
        ");
        $stmt->execute([':pnum' => $_GET['payment_number']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode([
                'status' => 'success',
                'code' => 200,
                'message' => 'Status payment',
                'data' => $row
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    // 2. By order_id
    if (!empty($_GET['order_id'])) {
        $stmt = $db->prepare("
            SELECT p.*, o.order_number, o.status AS order_status
            FROM payments p
            JOIN orders o ON o.id = p.order_id
            WHERE p.order_id = :oid
            ORDER BY p.id DESC
            LIMIT 1
        ");
        $stmt->execute([':oid' => (int)$_GET['order_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode([
                'status' => 'success',
                'code' => 200,
                'message' => 'Status payment',
                'data' => $row
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    // 3. By payment_id (can be payment.id or order.id)
    if (!empty($_GET['payment_id'])) {
        $stmt = $db->prepare("
            SELECT p.*, o.order_number, o.status AS order_status
            FROM payments p
            JOIN orders o ON o.id = p.order_id
            WHERE p.id = :pid OR p.order_id = :pid
            ORDER BY p.id DESC
            LIMIT 1
        ");
        $stmt->execute([':pid' => (int)$_GET['payment_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode([
                'status' => 'success',
                'code' => 200,
                'message' => 'Status payment',
                'data' => $row
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    // Default: list all user payments
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'code' => 401, 'message' => 'User belum login']);
        exit;
    }

    $paymentController = new PaymentController();
    $result = $paymentController->getAll($userId);
    http_response_code($result['code'] ?? 500);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'code' => 401, 'message' => 'User belum login']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['order_id'] ?? null;
    $paymentMethod = $input['payment_method'] ?? 'transfer';

    $paymentController = new PaymentController();
    $result = $paymentController->createPayment($userId, $orderId, $paymentMethod);
    http_response_code($result['code'] ?? 500);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'code' => 405, 'message' => 'Method not allowed']);
