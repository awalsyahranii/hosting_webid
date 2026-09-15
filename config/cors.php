<?php
// config/cors.php

if (!function_exists('cors_init')) {
    function cors_init($allowed_methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']) {
        header('Content-Type: application/json; charset=UTF-8');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (!empty($origin)) {
            $parsed = parse_url($origin);
            $host = $parsed['host'] ?? '';
            
            // Allow localhost, 127.0.0.1, hostingwebid.com and all subdomains of hostingwebid.com
            if (
                preg_match('/^(localhost|127\.0\.0\.1)$/i', $host) ||
                preg_match('/(^|\.)hostingwebid\.com$/i', $host) ||
                (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], $host) !== false)
            ) {
                header("Access-Control-Allow-Origin: $origin");
                header("Access-Control-Allow-Credentials: true");
            } else {
                // Allow the incoming origin dynamically so production domains work seamlessly
                header("Access-Control-Allow-Origin: $origin");
                header("Access-Control-Allow-Credentials: true");
            }
        } else {
            // For requests without Origin header
            header("Access-Control-Allow-Origin: *");
        }

        $methodsStr = is_array($allowed_methods) ? implode(', ', $allowed_methods) : $allowed_methods;
        header("Access-Control-Allow-Methods: $methodsStr");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
        header("Access-Control-Max-Age: 86400");

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}

if (!function_exists('session_init')) {
    function session_init() {
        if (session_status() === PHP_SESSION_NONE) {
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                        (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
            
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $cookieDomain = '';
            if (preg_match('/hostingwebid\.com$/i', $host)) {
                $cookieDomain = '.hostingwebid.com';
            }

            if (PHP_VERSION_ID >= 70300) {
                session_set_cookie_params([
                    'lifetime' => 86400 * 7,
                    'path' => '/',
                    'domain' => $cookieDomain,
                    'secure' => $isSecure,
                    'httponly' => true,
                    'samesite' => $isSecure ? 'None' : 'Lax'
                ]);
            } else {
                session_set_cookie_params(
                    86400 * 7,
                    '/; samesite=' . ($isSecure ? 'None' : 'Lax'),
                    $cookieDomain,
                    $isSecure,
                    true
                );
            }
            
            session_start();
        }
    }
}
