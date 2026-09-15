<?php
// api/v1/auth/check-session.php

require_once __DIR__ . '/../../../../config/cors.php';
cors_init(['GET', 'OPTIONS']);
session_init();

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