<?php
// api/v1/article/detail.php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../controller/dashboardusers/article/ArticleController.php';

$slug = !empty($_GET['slug']) ? trim($_GET['slug']) : null;

$controller = new ArticleController();
$result = $controller->getBySlug($slug);

http_response_code($result['code'] ?? 200);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
