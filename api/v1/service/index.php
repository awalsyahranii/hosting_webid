<?php
// api/v1/service/index.php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../service/database/db.php';
require_once __DIR__ . '/../../../controller/users/user/AuthController.php';
require_once __DIR__ . '/../../../models/dashboardusers/order/Order.php';

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
    echo json_encode([
        'status' => 'error',
        'code' => 401,
        'message' => 'User belum login'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance()->getConnection();
$type = strtolower(trim($_GET['type'] ?? ''));

try {
    if ($type === 'hosting') {
        // Fetch from user_hostings first
        $stmt = $db->prepare("
            SELECT 
                uh.id,
                uh.user_id,
                uh.order_id,
                uh.plan_id,
                COALESCE(hp.name, 'Paket Cloud Hosting') AS plan_name,
                COALESCE(hp.disk_space, '10 GB NVMe') AS disk_space,
                COALESCE(hp.bandwidth, 'Unlimited') AS bandwidth,
                COALESCE(hp.cpu_cores, '2 Cores') AS cpu_cores,
                COALESCE(hp.ram, '2 GB') AS ram,
                uh.domain_name,
                COALESCE(uh.cpanel_username, CONCAT('cp_', SUBSTRING(MD5(uh.domain_name), 1, 6))) AS cpanel_username,
                COALESCE(uh.server_ip, '103.147.154.21') AS server_ip,
                uh.billing_cycle AS billing_period,
                uh.start_date,
                uh.expiry_date,
                uh.status AS service_status,
                'hosting' AS service_category,
                COALESCE(o.order_number, CONCAT('ORD-HST-', uh.id)) AS order_number,
                COALESCE(hp.price_yearly, 150000) AS price
            FROM user_hostings uh
            LEFT JOIN hosting_plans hp ON hp.id = uh.plan_id
            LEFT JOIN orders o ON o.id = uh.order_id
            WHERE uh.user_id = :user_id
            ORDER BY uh.id DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        $hostings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If user_hostings is empty, check order_items with hosting/website category
        if (empty($hostings)) {
            $stmtOrderItems = $db->prepare("
                SELECT 
                    oi.id,
                    o.user_id,
                    oi.order_id,
                    1 AS plan_id,
                    'Paket Cloud Hosting Starter' AS plan_name,
                    '5 GB NVMe' AS disk_space,
                    'Unlimited' AS bandwidth,
                    '1 Core' AS cpu_cores,
                    '1 GB' AS ram,
                    oi.domain_name,
                    CONCAT('cp_', SUBSTRING(MD5(oi.domain_name), 1, 6)) AS cpanel_username,
                    '103.147.154.21' AS server_ip,
                    oi.billing_period,
                    oi.start_date,
                    oi.expiry_date,
                    oi.service_status,
                    'hosting' AS service_category,
                    o.order_number,
                    oi.price
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.user_id = :user_id
                  AND (oi.service_category IN ('hosting', 'cloud_hosting') OR oi.domain_name LIKE '%hosting%')
                ORDER BY oi.id DESC
            ");
            $stmtOrderItems->execute([':user_id' => $userId]);
            $hostings = $stmtOrderItems->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => 'Data hosting berhasil diambil',
            'data' => $hostings
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($type === 'domain') {
        $stmt = $db->prepare("
            SELECT 
                oi.id,
                oi.order_id,
                o.order_number,
                o.user_id,
                oi.domain_id,
                oi.domain_name,
                'domain' AS service_category,
                oi.billing_period,
                oi.start_date,
                oi.expiry_date,
                oi.service_status,
                oi.price,
                o.status AS order_status,
                o.created_at AS order_date
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE o.user_id = :user_id
            ORDER BY oi.id DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => 'Data domain berhasil diambil',
            'data' => $domains
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($type === 'google_ads' || $type === 'google-ads' || $type === 'iklan' || $type === 'ads') {
        $stmt = $db->prepare("
            SELECT 
                oi.id,
                oi.order_id,
                o.order_number,
                o.user_id,
                oi.domain_id,
                oi.domain_name,
                'google_ads' AS service_category,
                oi.billing_period,
                oi.start_date,
                oi.expiry_date,
                oi.service_status,
                oi.price,
                o.status AS order_status,
                o.created_at AS order_date
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE o.user_id = :user_id
              AND (oi.service_category IN ('google_ads', 'google-ads', 'iklan', 'ads') OR oi.domain_name LIKE '%Google Ads%' OR oi.domain_name LIKE '%Iklan%')
            ORDER BY oi.id DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => 'Data Google Ads berhasil diambil',
            'data' => $ads
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($type === 'seo_pbn' || $type === 'seo-pbn' || $type === 'seo' || $type === 'pbn') {
        $stmt = $db->prepare("
            SELECT 
                oi.id,
                oi.order_id,
                o.order_number,
                o.user_id,
                oi.domain_id,
                oi.domain_name,
                'seo_pbn' AS service_category,
                oi.billing_period,
                oi.start_date,
                oi.expiry_date,
                oi.service_status,
                oi.price,
                o.status AS order_status,
                o.created_at AS order_date
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE o.user_id = :user_id
              AND (oi.service_category IN ('seo_pbn', 'seo-pbn') OR oi.domain_name LIKE '%SEO%' OR oi.domain_name LIKE '%PBN%')
            ORDER BY oi.id DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        $seo = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => 'Data SEO PBN berhasil diambil',
            'data' => $seo
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($type === 'website' || $type === 'web') {
        $stmt = $db->prepare("
            SELECT 
                oi.id,
                oi.order_id,
                o.order_number,
                o.user_id,
                oi.domain_id,
                oi.domain_name,
                'website' AS service_category,
                oi.billing_period,
                oi.start_date,
                oi.expiry_date,
                oi.service_status,
                oi.price,
                o.status AS order_status,
                o.created_at AS order_date
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE o.user_id = :user_id
              AND (oi.service_category IN ('website', 'web') OR oi.domain_name LIKE '%Website%' OR oi.domain_name LIKE '%Jasa%')
            ORDER BY oi.id DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        $websites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => 'Data Website berhasil diambil',
            'data' => $websites
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Default: Return all services
    $services = (new Order())->getServicesByUser($userId);
    echo json_encode([
        'status' => 'success',
        'code' => 200,
        'message' => 'Data service berhasil diambil',
        'data' => $services
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Gagal memuat layanan',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
