<?php
// api/v1/admin/article/index.php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../../controller/users/user/AuthController.php';
require_once __DIR__ . '/../../../../controller/admin/article/ArticleController.php';

$session = (new AuthController())->checkSession();
$userRole = strtolower($session['data']['role'] ?? '');
if (!in_array($userRole, ['admin', 'super_admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'code' => 403, 'message' => 'Akses ditolak: Hanya untuk admin']);
    exit;
}

$search = !empty($_GET['search']) ? trim($_GET['search']) : (!empty($_GET['q']) ? trim($_GET['q']) : null);
$category = !empty($_GET['category']) ? trim($_GET['category']) : null;
$status = !empty($_GET['status']) ? trim($_GET['status']) : null;

$controller = new AdminArticleController();
$result = $controller->getAll($search, $category, $status);

http_response_code($result['code'] ?? 200);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
