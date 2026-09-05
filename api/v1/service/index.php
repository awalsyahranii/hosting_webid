<?php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../controller/users/user/AuthController.php';
require_once __DIR__ . '/../../../models/dashboardusers/order/Order.php';

$session = (new AuthController())->checkSession();
if (($session['code'] ?? 500) !== 200) {
    http_response_code($session['code'] ?? 401);
    echo json_encode($session, JSON_UNESCAPED_UNICODE);
    exit;
}

$services = (new Order())->getServicesByUser($session['data']['user_id']);
echo json_encode([
    'status' => 'success',
    'code' => 200,
    'message' => 'Data service berhasil diambil',
    'data' => $services
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);