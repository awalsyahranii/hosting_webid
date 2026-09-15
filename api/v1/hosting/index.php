<?php
// api/v1/hosting/index.php

require_once __DIR__ . '/../../../config/cors.php';
cors_init(['GET', 'POST', 'OPTIONS']);
session_init();

require_once __DIR__ . '/../../../service/database/db.php';
require_once __DIR__ . '/../../../controller/users/user/AuthController.php';

$auth = new AuthController();
$session = $auth->checkSession();

$userId = null;
if (($session['code'] ?? 500) === 200 && !empty($session['data']['user_id'])) {
    $userId = (int) $session['data']['user_id'];
} elseif (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $userId = (int) $_GET['user_id'];
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'code' => 401, 'message' => 'User belum login'], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("
        SELECT 
            uh.id,
            uh.user_id,
            uh.order_id,
            uh.plan_id,
            COALESCE(hp.name, 'Starter Cloud') AS plan_name,
            COALESCE(hp.slug, 'starter') AS plan_slug,
            COALESCE(hp.disk_space, '5 GB NVMe') AS disk_space,
            COALESCE(hp.bandwidth, 'Unlimited') AS bandwidth,
            COALESCE(hp.cpu_cores, '1 Core') AS cpu_cores,
            COALESCE(hp.ram, '1 GB') AS ram,
            uh.domain_name,
            COALESCE(uh.cpanel_username, CONCAT('cp_', SUBSTRING(MD5(uh.domain_name), 1, 6))) AS cpanel_username,
            COALESCE(uh.server_ip, '103.147.154.21') AS server_ip,
            uh.billing_cycle,
            uh.start_date,
            uh.expiry_date,
            uh.status AS service_status,
            COALESCE(o.order_number, CONCAT('ORD-HST-', uh.id)) AS order_number,
            uh.created_at
        FROM user_hostings uh
        LEFT JOIN hosting_plans hp ON hp.id = uh.plan_id
        LEFT JOIN orders o ON o.id = uh.order_id
        WHERE uh.user_id = :user_id
        ORDER BY uh.id DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $hostings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'code' => 200,
        'message' => 'Data hosting user berhasil diambil',
        'data' => $hostings
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Gagal mengambil data hosting: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
