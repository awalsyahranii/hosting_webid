<?php
// api/v1/admin/hosting/index.php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../../service/database/db.php';
require_once __DIR__ . '/../../../../controller/users/user/AuthController.php';

$auth = new AuthController();
$session = $auth->checkSession();

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->query("
        SELECT 
            uh.id,
            uh.user_id,
            u.username,
            u.email,
            uh.order_id,
            uh.plan_id,
            COALESCE(hp.name, 'Paket Starter Website') AS plan_name,
            COALESCE(hp.disk_space, '3 GB NVMe') AS disk_space,
            COALESCE(hp.ram, '1 GB') AS ram,
            COALESCE(hp.cpu_cores, '1 Core') AS cpu_cores,
            uh.domain_name,
            COALESCE(uh.cpanel_username, 'cp_user') AS cpanel_username,
            COALESCE(uh.server_ip, '103.147.154.21') AS server_ip,
            uh.billing_cycle AS billing_period,
            uh.start_date,
            uh.expiry_date,
            uh.status AS service_status,
            COALESCE(o.order_number, CONCAT('ORD-HST-', uh.id)) AS order_number,
            COALESCE(NULLIF(oi.price, 0), NULLIF(o.subtotal, 0), hp.price_yearly, 750000) AS price
        FROM user_hostings uh
        LEFT JOIN users u ON u.id = uh.user_id
        LEFT JOIN hosting_plans hp ON hp.id = uh.plan_id
        LEFT JOIN orders o ON o.id = uh.order_id
        LEFT JOIN (
            SELECT order_id, MAX(price) AS price 
            FROM order_items 
            GROUP BY order_id
        ) oi ON oi.order_id = o.id
        ORDER BY uh.id DESC
    ");
    $hostings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'code' => 200,
        'message' => 'Data hosting admin berhasil diambil',
        'data' => $hostings
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'code' => 500, 'message' => $e->getMessage()]);
}
