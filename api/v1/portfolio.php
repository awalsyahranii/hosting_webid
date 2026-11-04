<?php
// api/v1/portfolio.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dbHost = 'localhost';
$dbName = 'hosting_web';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Helper to format row
function formatPortfolio($row) {
    return [
        'id' => $row['portfolio_id'] ?: 'portfolio-' . $row['id'],
        'db_id' => (int)$row['id'],
        'domain' => $row['domain'],
        'url' => $row['url'] ?: 'https://' . $row['domain'],
        'category' => $row['category'],
        'categoryTag' => $row['category_tag'],
        'promoTag' => $row['promo_tag'],
        'gradient' => $row['gradient'],
        'bannerTitle' => $row['banner_title'],
        'bannerDesc' => $row['banner_desc'] ?: '',
        'rating' => $row['rating'],
        'originalPrice' => $row['original_price'],
        'discountBadge' => $row['discount_badge'],
        'price' => $row['price'],
        'orderPrice' => $row['order_price'],
        'packageTitle' => $row['package_title'],
        'feat1' => $row['feat1'],
        'feat2' => $row['feat2'],
        'image' => $row['image'] ?: '',
        'useScreenshot' => (bool)$row['use_screenshot'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
    ];
}

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM portfolios ORDER BY id ASC");
        $rows = $stmt->fetchAll();
        $portfolios = array_map('formatPortfolio', $rows);
        echo json_encode([
            'success' => true,
            'count' => count($portfolios),
            'portfolios' => $portfolios
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Read JSON input for POST/PUT/DELETE
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: [];

if ($method === 'POST') {
    $domain = trim($data['domain'] ?? ($data['url'] ?? ''));
    if (!$domain) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Domain atau URL wajib diisi.']);
        exit;
    }

    $portfolioId = $data['id'] ?? ('portfolio-' . time() . '-' . substr(md5(uniqid()), 0, 5));
    $cleanDomain = preg_replace('#^https?://#i', '', $domain);
    $cleanDomain = preg_replace('#/.*$#', '', $cleanDomain);
    $url = $data['url'] ?? ('https://' . $cleanDomain);

    $category = $data['category'] ?? 'Bisnis & Jasa';
    $categoryTag = $data['categoryTag'] ?? 'PRODUKSI & JASA';
    $promoTag = $data['promoTag'] ?? 'Hemat 50%';
    $gradient = $data['gradient'] ?? 'linear-gradient(135deg, #064e3b 0%, #047857 50%, #065f46 100%)';
    $bannerTitle = $data['bannerTitle'] ?? ('Website ' . $cleanDomain);
    $bannerDesc = $data['bannerDesc'] ?? 'Layanan website siap online dan bergaransi';
    $rating = $data['rating'] ?? '5.0/5 - 40 Ulasan';
    $originalPrice = $data['originalPrice'] ?? 'Rp 1.500.000';
    $discountBadge = $data['discountBadge'] ?? 'Hemat 50%';
    $price = $data['price'] ?? 'Mulai Rp 750.000';
    $orderPrice = $data['orderPrice'] ?? 'Pesan Rp 750rb';
    $packageTitle = $data['packageTitle'] ?? 'Jasa Pembuatan Website';
    $feat1 = $data['feat1'] ?? 'Tampilan Bisa Request/Custom';
    $feat2 = $data['feat2'] ?? 'Termasuk Domain & Cloud Server NVMe';
    $image = $data['image'] ?? '';
    $useScreenshot = !empty($data['useScreenshot']) ? 1 : 0;
    $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    try {
        $stmt = $pdo->prepare("INSERT INTO portfolios 
            (portfolio_id, domain, url, category, category_tag, promo_tag, gradient, banner_title, banner_desc, rating, original_price, discount_badge, price, order_price, package_title, feat1, feat2, image, use_screenshot, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $portfolioId, $cleanDomain, $url, $category, $categoryTag, $promoTag, $gradient, $bannerTitle, $bannerDesc, $rating, $originalPrice, $discountBadge, $price, $orderPrice, $packageTitle, $feat1, $feat2, $image, $useScreenshot, $status
        ]);
        
        $newId = $pdo->lastInsertId();
        $stmtGet = $pdo->prepare("SELECT * FROM portfolios WHERE id = ?");
        $stmtGet->execute([$newId]);
        $row = $stmtGet->fetch();

        echo json_encode([
            'success' => true,
            'message' => 'Portofolio berhasil ditambahkan ke database MySQL',
            'portfolio' => formatPortfolio($row)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'PUT') {
    $id = $data['id'] ?? '';
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID Portofolio wajib diberikan.']);
        exit;
    }

    try {
        $stmtCheck = $pdo->prepare("SELECT * FROM portfolios WHERE portfolio_id = ? OR id = ?");
        $stmtCheck->execute([$id, is_numeric($id) ? (int)$id : 0]);
        $existing = $stmtCheck->fetch();

        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Portofolio tidak ditemukan.']);
            exit;
        }

        $fields = [
            'domain' => $data['domain'] ?? $existing['domain'],
            'url' => $data['url'] ?? $existing['url'],
            'category' => $data['category'] ?? $existing['category'],
            'category_tag' => $data['categoryTag'] ?? $existing['category_tag'],
            'promo_tag' => $data['promoTag'] ?? $existing['promo_tag'],
            'gradient' => $data['gradient'] ?? $existing['gradient'],
            'banner_title' => $data['bannerTitle'] ?? $existing['banner_title'],
            'banner_desc' => $data['bannerDesc'] ?? $existing['banner_desc'],
            'rating' => $data['rating'] ?? $existing['rating'],
            'original_price' => $data['originalPrice'] ?? $existing['original_price'],
            'discount_badge' => $data['discountBadge'] ?? $existing['discount_badge'],
            'price' => $data['price'] ?? $existing['price'],
            'order_price' => $data['orderPrice'] ?? $existing['order_price'],
            'package_title' => $data['packageTitle'] ?? $existing['package_title'],
            'feat1' => $data['feat1'] ?? $existing['feat1'],
            'feat2' => $data['feat2'] ?? $existing['feat2'],
            'image' => isset($data['image']) ? $data['image'] : $existing['image'],
            'use_screenshot' => isset($data['useScreenshot']) ? ($data['useScreenshot'] ? 1 : 0) : $existing['use_screenshot'],
            'status' => isset($data['status']) ? ($data['status'] === 'inactive' ? 'inactive' : 'active') : $existing['status'],
        ];

        $stmtUpdate = $pdo->prepare("UPDATE portfolios SET 
            domain = :domain, url = :url, category = :category, category_tag = :category_tag, promo_tag = :promo_tag, 
            gradient = :gradient, banner_title = :banner_title, banner_desc = :banner_desc, rating = :rating, 
            original_price = :original_price, discount_badge = :discount_badge, price = :price, order_price = :order_price, 
            package_title = :package_title, feat1 = :feat1, feat2 = :feat2, image = :image, use_screenshot = :use_screenshot, status = :status 
            WHERE id = :id");
        $fields['id'] = $existing['id'];
        $stmtUpdate->execute($fields);

        $stmtGet = $pdo->prepare("SELECT * FROM portfolios WHERE id = ?");
        $stmtGet->execute([$existing['id']]);
        $row = $stmtGet->fetch();

        echo json_encode([
            'success' => true,
            'message' => 'Portofolio berhasil diperbarui di database MySQL',
            'portfolio' => formatPortfolio($row)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? ($data['id'] ?? '');
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID Portofolio wajib diberikan.']);
        exit;
    }

    try {
        $stmtDel = $pdo->prepare("DELETE FROM portfolios WHERE portfolio_id = ? OR id = ?");
        $stmtDel->execute([$id, is_numeric($id) ? (int)$id : 0]);
        echo json_encode([
            'success' => true,
            'message' => 'Portofolio berhasil dihapus dari database MySQL'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

