<?php
// api/v1/admin/renewal/index.php

require_once __DIR__ . '/../../../../config/cors.php';
cors_init(['GET', 'OPTIONS']);
session_init();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'code' => 405, 'message' => 'Method not allowed']);
    exit;
}

if (($_SESSION['logged_in'] ?? false) !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'code' => 401, 'message' => 'Belum login']);
    exit;
}

if (!in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'code' => 403, 'message' => 'Akses hanya untuk admin']);
    exit;
}

require_once __DIR__ . '/../../../../controller/admin/renewal/RenewalController.php';

try {
    $filters = [
        'month' => $_GET['month'] ?? null,
        'year' => $_GET['year'] ?? null,
        'category' => $_GET['category'] ?? null,
        'status' => $_GET['status'] ?? null,
        'search' => $_GET['search'] ?? null
    ];

    $controller = new RenewalController();
    $result = $controller->getRenewals($filters);

    http_response_code($result['code'] ?? 200);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'code' => 500, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
}
