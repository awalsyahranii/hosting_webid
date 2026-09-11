<?php
// api/v1/article/detail.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../controller/landingpage/ArticleController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'code'    => 405,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$slug = trim((string) ($_GET['slug'] ?? ''));
$controller = new ArticleController();
$result = $controller->getDetail($slug);

http_response_code((int) ($result['code'] ?? 200));
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);