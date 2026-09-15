<?php
// api/v1/admin/coupon/update.php

require_once __DIR__ . '/../../../../config/cors.php';
cors_init(['PUT', 'POST', 'OPTIONS']);
session_init();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Method not allowed'
    ]);
    exit;
}


// =========================
// CEK LOGIN
// =========================

if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'code' => 401,
        'message' => 'Belum login'
    ]);

    exit;
}


// =========================
// CEK ADMIN
// =========================

if (
    !isset($_SESSION['role']) ||
    (!in_array($_SESSION['role'] ?? '', ['admin', 'super_admin']))
) {

    http_response_code(403);

    echo json_encode([
        'status' => 'error',
        'code' => 403,
        'message' => 'Akses hanya untuk admin'
    ]);

    exit;
}


// =========================
// LOAD CONTROLLER
// =========================

require_once __DIR__ .
    '/../../../../controller/admin/coupon/CouponController.php';


// =========================
// AMBIL ID
// =========================

$id = $_GET['id'] ?? null;

if ($id === null || !is_numeric($id)) {

    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' => 'ID voucher tidak valid'
    ]);

    exit;
}

$id = (int) $id;

if ($id <= 0) {

    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' => 'ID voucher tidak valid'
    ]);

    exit;
}


// =========================
// AMBIL JSON
// =========================

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


// =========================
// CONTROLLER
// =========================

$couponController =
    new CouponController();

$result =
    $couponController->update(
        $id,
        $input
    );


// =========================
// RESPONSE
// =========================

http_response_code(
    $result['code']
);

echo json_encode(
    $result
);