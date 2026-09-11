<?php
// api/v1/article/create.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../controller/landingpage/ArticleController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'code'    => 405,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput ?: '{}', true);

if (!is_array($input) || empty($input)) {
    $input = $_POST;
}

$controller = new ArticleController();
$result = $controller->create($input);

http_response_code((int) ($result['code'] ?? 200));
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);