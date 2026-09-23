<?php
// api/v1/admin/article/upload.php

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

// Check uploaded file
$fileKey = null;
if (!empty($_FILES['image'])) {
    $fileKey = 'image';
} elseif (!empty($_FILES['file'])) {
    $fileKey = 'file';
} elseif (!empty($_FILES['thumbnail'])) {
    $fileKey = 'thumbnail';
}

if (!$fileKey || empty($_FILES[$fileKey]['tmp_name']) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' => 'File gambar tidak ditemukan atau terjadi kesalahan saat upload'
    ]);
    exit;
}

$file = $_FILES[$fileKey];

// Max file size: 10MB
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' => 'Ukuran gambar terlalu besar. Maksimal 10MB'
    ]);
    exit;
}

// Allowed extensions & mime types
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExt, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' => 'Format file tidak didukung. Gunakan JPG, PNG, WEBP, GIF, atau SVG'
    ]);
    exit;
}

// Destination folder
$uploadDir = __DIR__ . '/../../../../assets/uploads/articles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Unique filename
$newFileName = 'art_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
$destination = $uploadDir . $newFileName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Gagal menyimpan file gambar ke server'
    ]);
    exit;
}

// Build URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))));
$baseUploadUrl = rtrim($protocol . $host . $scriptDir, '/') . '/assets/uploads/articles/' . $newFileName;

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'code' => 200,
    'message' => 'Gambar berhasil diunggah',
    'data' => [
        'url' => $baseUploadUrl,
        'filename' => $newFileName,
        'size' => $file['size']
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
