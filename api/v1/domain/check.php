<?php
// api/v1/domain/check.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../../controller/dashboardusers/domain/DomainController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Method not allowed'
    ]);

    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' => 'Format JSON tidak valid'
    ]);

    exit;
}

$domain = $input['domain'] ?? '';

$domainController = new DomainController();

$result = $domainController->checkDomain($domain);

http_response_code($result['code']);

echo json_encode($result);