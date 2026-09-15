<?php
// api/v1/hosting/plans.php

require_once __DIR__ . '/../../../config/cors.php';
cors_init(['GET', 'OPTIONS']);
session_init();

require_once __DIR__ . '/../../../service/database/db.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM hosting_plans WHERE status = 'active' ORDER BY id ASC");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'code' => 200,
        'message' => 'Daftar paket hosting berhasil diambil',
        'data' => $plans
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Gagal mengambil paket hosting: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
