<?php
// api/v1/article/index.php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../controller/dashboardusers/article/ArticleController.php';

$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : null;
$category = !empty($_GET['category']) ? trim($_GET['category']) : null;
$search = !empty($_GET['search']) ? trim($_GET['search']) : (!empty($_GET['q']) ? trim($_GET['q']) : null);

$controller = new ArticleController();
$result = $controller->getAll($limit, $category, $search);

http_response_code($result['code'] ?? 200);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
