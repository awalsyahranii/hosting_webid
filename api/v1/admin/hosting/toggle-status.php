<?php
// api/v1/admin/hosting/toggle-status.php

require_once __DIR__ . '/../../../../config/cors.php';
cors_init(['POST', 'OPTIONS']);
session_init();

require_once __DIR__ . '/../../../../service/database/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$hostingId = (int) ($input['id'] ?? $_GET['id'] ?? 0);
$nextStatus = $input['status'] ?? 'active';

if (!$hostingId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'code' => 400, 'message' => 'ID hosting tidak valid']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE user_hostings SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $nextStatus, ':id' => $hostingId]);

    echo json_encode(['status' => 'success', 'code' => 200, 'message' => 'Status hosting berhasil diubah', 'data' => ['id' => $hostingId, 'status' => $nextStatus]]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'code' => 500, 'message' => $e->getMessage()]);
}
