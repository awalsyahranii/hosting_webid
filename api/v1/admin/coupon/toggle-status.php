<?php

header('Content-Type: application/json');

header(
    'Access-Control-Allow-Origin: http://localhost:3000'
);

header(
    'Access-Control-Allow-Credentials: true'
);

header(
    'Access-Control-Allow-Methods: POST, OPTIONS'
);

header(
    'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'
);


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Method not allowed'
    ]);

    exit;
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


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


require_once __DIR__ .
    '/../../../../controller/admin/coupon/CouponController.php';


$id = $_GET['id'] ?? null;


if (!$id || !is_numeric($id)) {
    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' => 'ID voucher tidak valid'
    ]);

    exit;
}


$couponController =
    new CouponController();


$result =
    $couponController->toggleStatus(
        (int) $id
    );


http_response_code(
    $result['code']
);

echo json_encode(
    $result
);