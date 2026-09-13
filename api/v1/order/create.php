<?php
// api/v1/order/create.php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'code' => 405, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../../service/database/db.php';
require_once __DIR__ . '/../../../controller/users/user/AuthController.php';
require_once __DIR__ . '/../../../controller/dashboardusers/order/OrderController.php';

$auth = new AuthController();
$session = $auth->checkSession();

$userId = $session['data']['user_id'] ?? null;
if (empty($userId)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'code' => 401, 'message' => 'User belum login']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$db = Database::getInstance()->getConnection();
$couponCode = !empty($input['coupon_code']) ? strtoupper(trim($input['coupon_code'])) : null;
$packageName = !empty($input['package_name']) ? trim($input['package_name']) : null;
$domainName = !empty($input['domain_name']) ? trim($input['domain_name']) : null;
$itemPrice = !empty($input['price']) ? (float)$input['price'] : null;
$durationYears = !empty($input['duration_years']) ? (int)$input['duration_years'] : 1;
if ($durationYears < 1) $durationYears = 1;

// Helper discount calculation
function calculateDiscount($db, $couponCode, $subtotal) {
    if (!$couponCode) return 0.0;
    $cStmt = $db->prepare('SELECT * FROM coupons WHERE code = :code AND is_active = 1 LIMIT 1');
    $cStmt->execute([':code' => $couponCode]);
    $coupon = $cStmt->fetch(PDO::FETCH_ASSOC);
    if ($coupon) {
        $discount = 0.0;
        if ($coupon['discount_type'] === 'percentage') {
            $discount = $subtotal * ((float)$coupon['discount_value'] / 100);
        } else {
            $discount = (float)$coupon['discount_value'];
        }
        if ($coupon['max_discount'] !== null && $discount > (float)$coupon['max_discount']) {
            $discount = (float)$coupon['max_discount'];
        }
        return $discount;
    }
    return 0.0;
}

// 1. DIRECT PACKAGE ORDER (HOSTING, GOOGLE ADS, SEO PBN, WEBSITE, ETC.)
if ($packageName) {
    $lowerPkg = strtolower($packageName);
    $isGoogleAds = (strpos($lowerPkg, 'ads') !== false || strpos($lowerPkg, 'google') !== false || strpos($lowerPkg, 'iklan') !== false);
    $isSeoPbn = (strpos($lowerPkg, 'pbn') !== false || strpos($lowerPkg, 'seo') !== false);
    $isWebsite = (strpos($lowerPkg, 'website') !== false || strpos($lowerPkg, 'jasa pembuatan') !== false || strpos($lowerPkg, 'pembuatan web') !== false || strpos($lowerPkg, 'toko online') !== false || strpos($lowerPkg, 'landing') !== false);

    if ($isGoogleAds) {
        $serviceCategory = 'google_ads';
        if (strpos($lowerPkg, '14') !== false) {
            $billingPeriod = '14_days';
        } elseif (strpos($lowerPkg, '20') !== false) {
            $billingPeriod = '20_days';
        } elseif (strpos($lowerPkg, '30') !== false) {
            $billingPeriod = '30_days';
        } else {
            $billingPeriod = '30_days';
        }
    } elseif ($isSeoPbn) {
        $serviceCategory = 'seo_pbn';
        $billingPeriod = 'yearly';
    } elseif ($isWebsite) {
        $serviceCategory = 'website';
        $billingPeriod = 'yearly';
    } else {
        $serviceCategory = 'hosting';
        $billingPeriod = 'yearly';
    }

    if (!empty($input['billing_period'])) {
        $billingPeriod = trim($input['billing_period']);
    }

    // Prioritize exact price sent from frontend checkout
    if ($itemPrice !== null && $itemPrice > 0) {
        $basePrice = $itemPrice;
    } else {
        // Fallback pricing
        if ($isGoogleAds) {
            if (strpos($lowerPkg, 'bisnis 30') !== false) $basePrice = 3000000;
            elseif (strpos($lowerPkg, 'bisnis 20') !== false) $basePrice = 2000000;
            elseif (strpos($lowerPkg, 'bisnis 15') !== false) $basePrice = 1500000;
            elseif (strpos($lowerPkg, 'bisnis 10') !== false) $basePrice = 1000000;
            elseif (strpos($lowerPkg, 'pemula 30') !== false) $basePrice = 1500000;
            elseif (strpos($lowerPkg, 'pemula 20') !== false) $basePrice = 1000000;
            elseif (strpos($lowerPkg, 'pemula 14') !== false) $basePrice = 700000;
            else $basePrice = 1000000;
        } elseif ($isSeoPbn) {
            if (strpos($lowerPkg, '3') !== false) $basePrice = 2000000;
            elseif (strpos($lowerPkg, '2') !== false) $basePrice = 1200000;
            else $basePrice = 650000;
        } elseif ($isWebsite) {
            $basePrice = 750000;
        } else {
            if (strpos($lowerPkg, 'starter') !== false) $basePrice = 750000;
            elseif (strpos($lowerPkg, 'elite') !== false || strpos($lowerPkg, 'enterprise') !== false) $basePrice = 2000000;
            else $basePrice = 1500000;
        }
    }

    $subtotal = $basePrice * $durationYears;
    $discount = calculateDiscount($db, $couponCode, $subtotal);
    $afterDiscount = max(0, $subtotal - $discount);
    $ppn = round($afterDiscount * 0.11);
    $total = $afterDiscount + $ppn;

    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    $paymentNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    $db->beginTransaction();
    try {
        $insOrder = $db->prepare('
            INSERT INTO orders (user_id, order_number, subtotal, ppn, discount, total, status, created_at, updated_at)
            VALUES (:user_id, :order_number, :subtotal, :ppn, :discount, :total, "pending", NOW(), NOW())
        ');
        $insOrder->execute([
            ':user_id' => $userId,
            ':order_number' => $orderNumber,
            ':subtotal' => $subtotal,
            ':ppn' => $ppn,
            ':discount' => $discount,
            ':total' => $total
        ]);
        $orderId = (int)$db->lastInsertId();

        $itemTitle = ($domainName && $domainName !== $packageName) ? "{$packageName} ({$domainName})" : $packageName;

        $insItem = $db->prepare('
            INSERT INTO order_items (order_id, domain_id, domain_name, service_category, billing_period, service_status, price, created_at)
            VALUES (:order_id, 0, :domain_name, :service_category, :billing_period, "pending", :price, NOW())
        ');
        $insItem->execute([
            ':order_id' => $orderId,
            ':domain_name' => $itemTitle,
            ':service_category' => $serviceCategory,
            ':billing_period' => $billingPeriod,
            ':price' => $subtotal
        ]);

        $insPayment = $db->prepare('
            INSERT INTO payments (order_id, payment_number, amount, payment_method, status, created_at, updated_at)
            VALUES (:order_id, :payment_number, :amount, "transfer", "pending", NOW(), NOW())
        ');
        $insPayment->execute([
            ':order_id' => $orderId,
            ':payment_number' => $paymentNumber,
            ':amount' => $total
        ]);
        $paymentId = (int)$db->lastInsertId();

        $db->commit();

        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'code' => 201,
            'message' => 'Order ' . $serviceCategory . ' berhasil dibuat',
            'data' => [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'payment_id' => $paymentId,
                'payment_number' => $paymentNumber,
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'discount' => number_format($discount, 2, '.', ''),
                'after_discount' => number_format($afterDiscount, 2, '.', ''),
                'ppn' => number_format($ppn, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
                'status' => 'pending'
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'code' => 500, 'message' => 'Gagal membuat order: ' . $e->getMessage()]);
        exit;
    }
}

// 2. DIRECT DOMAIN ORDER (FROM DOMAIN SEARCH)
if ($domainName && !$packageName) {
    $basePrice = 150000;
    if ($itemPrice !== null && $itemPrice > 0) {
        $basePrice = $itemPrice;
    } else {
        $lower = strtolower($domainName);
        if (strpos($lower, '.co.id') !== false) $basePrice = 270000;
        elseif (strpos($lower, '.id') !== false && strpos($lower, '.my.id') === false && strpos($lower, '.biz.id') === false) $basePrice = 250000;
        elseif (strpos($lower, '.my.id') !== false || strpos($lower, '.biz.id') !== false) $basePrice = 12000;
        elseif (strpos($lower, '.xyz') !== false || strpos($lower, '.buzz') !== false) $basePrice = 29900;
        elseif (strpos($lower, '.net') !== false) $basePrice = 180000;
        elseif (strpos($lower, '.org') !== false) $basePrice = 149900;
        elseif (strpos($lower, '.com') !== false) $basePrice = 150000;
    }

    $subtotal = $basePrice * $durationYears;
    $discount = calculateDiscount($db, $couponCode, $subtotal);
    $afterDiscount = max(0, $subtotal - $discount);
    $ppn = round($afterDiscount * 0.11);
    $total = $afterDiscount + $ppn;

    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    $paymentNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    $db->beginTransaction();
    try {
        $insOrder = $db->prepare('
            INSERT INTO orders (user_id, order_number, subtotal, ppn, discount, total, status, created_at, updated_at)
            VALUES (:user_id, :order_number, :subtotal, :ppn, :discount, :total, "pending", NOW(), NOW())
        ');
        $insOrder->execute([
            ':user_id' => $userId,
            ':order_number' => $orderNumber,
            ':subtotal' => $subtotal,
            ':ppn' => $ppn,
            ':discount' => $discount,
            ':total' => $total
        ]);
        $orderId = (int)$db->lastInsertId();

        $insItem = $db->prepare('
            INSERT INTO order_items (order_id, domain_id, domain_name, service_category, billing_period, service_status, price, created_at)
            VALUES (:order_id, 0, :domain_name, "domain", "yearly", "pending", :price, NOW())
        ');
        $insItem->execute([
            ':order_id' => $orderId,
            ':domain_name' => $domainName,
            ':price' => $subtotal
        ]);

        $insPayment = $db->prepare('
            INSERT INTO payments (order_id, payment_number, amount, payment_method, status, created_at, updated_at)
            VALUES (:order_id, :payment_number, :amount, "transfer", "pending", NOW(), NOW())
        ');
        $insPayment->execute([
            ':order_id' => $orderId,
            ':payment_number' => $paymentNumber,
            ':amount' => $total
        ]);
        $paymentId = (int)$db->lastInsertId();

        $db->commit();

        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'code' => 201,
            'message' => 'Order domain berhasil dibuat',
            'data' => [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'payment_id' => $paymentId,
                'payment_number' => $paymentNumber,
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'discount' => number_format($discount, 2, '.', ''),
                'after_discount' => number_format($afterDiscount, 2, '.', ''),
                'ppn' => number_format($ppn, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
                'status' => 'pending'
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'code' => 500, 'message' => 'Gagal membuat order domain: ' . $e->getMessage()]);
        exit;
    }
}

// 3. CART ORDER (DOMAIN CART)
$orderController = new OrderController();
$result = $orderController->createOrder($userId, $couponCode);

if (($result['status'] ?? '') === 'success' && !empty($result['data']['order_id'])) {
    $orderId = (int)$result['data']['order_id'];
    $totalAmount = (float)($result['data']['total'] ?? 0);
    $paymentNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    $insPayment = $db->prepare('
        INSERT INTO payments (order_id, payment_number, amount, payment_method, status, created_at, updated_at)
        VALUES (:order_id, :payment_number, :amount, "transfer", "pending", NOW(), NOW())
    ');
    $insPayment->execute([
        ':order_id' => $orderId,
        ':payment_number' => $paymentNumber,
        ':amount' => $totalAmount
    ]);
    $paymentId = (int)$db->lastInsertId();

    $result['data']['payment_id'] = $paymentId;
    $result['data']['payment_number'] = $paymentNumber;
}

http_response_code($result['code'] ?? 500);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
