<?php
// api/v1/admin/article/update.php

require_once __DIR__ . '/../../../../config/cors.php';
cors_init(['POST', 'OPTIONS']);
session_init();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'code' => 405, 'message' => 'Method not allowed']);
    exit;
}

if (($_SESSION['logged_in'] ?? false) !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'code' => 401, 'message' => 'Belum login']);
    exit;
}

$userRole = strtolower($_SESSION['role'] ?? '');
if (!in_array($userRole, ['admin', 'super_admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'code' => 403, 'message' => 'Akses ditolak: Hanya untuk admin']);
    exit;
}

require_once __DIR__ . '/../../../../controller/admin/article/ArticleController.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}

$id = $input['id'] ?? ($_GET['id'] ?? null);

$controller = new AdminArticleController();
$result = $controller->update($id, $input);

http_response_code($result['code'] ?? 200);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
