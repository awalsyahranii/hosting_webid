<?php
// api/v1/invoice/index.php


require_once __DIR__ . '/../../../config/cors.php';
cors_init(['GET', 'POST', 'OPTIONS']);
session_init();

require_once __DIR__ .
    '/../../../controller/users/user/AuthController.php';

require_once __DIR__ .
    '/../../../controller/dashboardusers/invoice/InvoiceController.php';


// =========================
// CEK SESSION
// =========================

$auth = new AuthController();

$session = $auth->checkSession();

if ($session['code'] !== 200) {

    http_response_code($session['code']);

    echo json_encode($session);

    exit;
}

$userId =
    $session['data']['user_id'] ?? null;

if (empty($userId)) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'code' => 401,
        'message' => 'User belum login'
    ]);

    exit;
}


$invoiceController =
    new InvoiceController();


// =========================
// POST
// CREATE INVOICE
// =========================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {

        http_response_code(400);

        echo json_encode([
            'status' => 'error',
            'code' => 400,
            'message' => 'Format JSON tidak valid'
        ]);

        exit;
    }

    $orderId =
        $input['order_id'] ?? '';

    $result =
        $invoiceController->createInvoice(
            $userId,
            $orderId
        );

    http_response_code($result['code']);

    echo json_encode($result);

    exit;
}


// =========================
// GET
// =========================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Jika ada invoice_id
    if (isset($_GET['invoice_id'])) {

        $invoiceId =
            $_GET['invoice_id'];

        $result =
            $invoiceController->getById(
                $userId,
                $invoiceId
            );

    } else {

        // Jika tidak ada ID,
        // tampilkan semua invoice user

        $result =
            $invoiceController->getAll(
                $userId
            );
    }

    http_response_code($result['code']);

    echo json_encode($result);

    exit;
}


// =========================
// METHOD TIDAK DIIZINKAN
// =========================

http_response_code(405);

echo json_encode([
    'status' => 'error',
    'code' => 405,
    'message' => 'Method not allowed'
]);