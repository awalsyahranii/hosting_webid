<?php
// api/v1/auth/check-session.php

header('Content-Type: application/json');

header(
    'Access-Control-Allow-Origin: http://localhost:3000'
);

header(
    'Access-Control-Allow-Credentials: true'
);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo json_encode([
    'status' => 'success',
    'session_id' => session_id(),
    'session' => [
        'logged_in' => $_SESSION['logged_in'] ?? null,
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ]
]);