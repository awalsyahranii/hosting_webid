<?php
// api/v1/admin/user/index.php

header('Content-Type: application/json; charset=UTF-8');

header(
    'Access-Control-Allow-Origin: http://localhost:3000'
);

header(
    'Access-Control-Allow-Credentials: true'
);

header(
    'Access-Control-Allow-Methods: GET, OPTIONS'
);

header(
    'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'
);


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Method not allowed'
    ]);

    exit;
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    $_SESSION['role'] !== 'admin'
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


$search =
    trim($_GET['search'] ?? '');


if (isset($_GET['id'])) {

    $result =
        $userController->getById(
            $_GET['id']
        );

} else {

    $result =
        $userController->getAll(
            $search
        );
}


http_response_code(
    $result['code'] ?? 200
);


echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

exit;