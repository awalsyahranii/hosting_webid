<?php
// api/v1/admin/hosting/toggle-status.php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
