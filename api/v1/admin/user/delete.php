<?php
// api/v1/admin/user/delete.php

require_once __DIR__ . '/../../../../config/cors.php';
cors_init(['DELETE', 'POST', 'OPTIONS']);
session_init();

if (
    $_SERVER['REQUEST_METHOD'] !== 'DELETE' &&
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Method not allowed'
    ]);
    exit;
}


if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'code' => 401,
        'message' => 'Belum login'
    ]);

    exit;
}


if (
    !isset($_SESSION['role']) ||
    (!in_array($_SESSION['role'] ?? '', ['admin', 'super_admin']))
) {

    http_response_code(403);

    echo json_encode([
        'status' => 'error',
        'code' => 403,
        'message' =>
            'Akses hanya untuk admin'
    ]);

    exit;
}


$id =
    $_GET['id'] ??
    $_POST['id'] ??
    null;


if (!$id) {

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (is_array($input)) {
        $id = $input['id'] ?? null;
    }
}


if (!$id) {

    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' =>
            'ID user wajib diisi'
    ]);

    exit;
}


$controllerPath =
    __DIR__ .
    '/../../../../controller/admin/user/UserController.php';


if (!file_exists($controllerPath)) {

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' =>
            'UserController.php tidak ditemukan'
    ]);

    exit;
}


require_once $controllerPath;


$userController =
    new UserController();


$result =
    $userController->delete(
        $id,
        $_SESSION['user_id']
    );


http_response_code(
    $result['code'] ?? 200
);


echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE
);

exit;