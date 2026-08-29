<?php
// controller/users/AuthController.php

require_once __DIR__ . '/../../../models/users/user/post.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function login($identifier, $password) {
        // Validasi input
        if (empty($identifier) || empty($password)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Email/Username dan password wajib diisi'
            ];
        }

        // Cari user
        $user = $this->userModel->findByEmailOrUsername($identifier);
        
        if (!$user) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'Username/Email tidak ditemukan'
            ];
        }

        // Verifikasi password
        if (!$this->userModel->verifyPassword($password, $user['password'])) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'Password salah'
            ];
        }

        // Hapus password
        unset($user['password']);
        unset($user['reset_token']);
        unset($user['reset_expires']);

        // Regenerasi session setelah login berhasil
        session_regenerate_id(true);

        // Simpan session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'role' => $user['role']
            ]
        ];
    }

    public function register($data) {
        // Validasi
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Username, email, dan password wajib diisi'
            ];
        }

        // Validasi email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Format email tidak valid'
            ];
        }

        // Cek username
        if ($this->userModel->findByUsername($data['username'])) {
            return [
                'status' => 'error',
                'code' => 409,
                'message' => 'Username sudah digunakan'
            ];
        }

        // Cek email
        if ($this->userModel->findByEmail($data['email'])) {
            return [
                'status' => 'error',
                'code' => 409,
                'message' => 'Email sudah terdaftar'
            ];
        }

        // Simpan user
        $result = $this->userModel->create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'] ?? 'user'
        ]);

        if ($result) {
            return [
                'status' => 'success',
                'code' => 201,
                'message' => 'Registrasi berhasil, silakan login'
            ];
        }

        return [
            'status' => 'error',
            'code' => 500,
            'message' => 'Registrasi gagal, silakan coba lagi'
        ];
    }

        public function logout()
        {
            // Mulai session jika belum aktif
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Kosongkan semua data session
            $_SESSION = [];

            // Hapus cookie PHPSESSID
            if (ini_get("session.use_cookies")) {

                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            // Hancurkan session
            session_destroy();

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Logout berhasil'
            ];
        }
    public function forgotPassword($email) {
        if (empty($email)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Email wajib diisi'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Format email tidak valid'
            ];
        }

        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Email tidak terdaftar'
            ];
        }

        // Generate token
        $token = bin2hex(random_bytes(32));
        $this->userModel->updateResetToken($email, $token);

        // Kirim email (implementasi sederhana)
        $resetLink = APP_URL . "/api/v1/auth/reset-password.php?token=" . $token;
        $subject = "Reset Password - " . APP_NAME;
        $message = "Halo " . $user['username'] . ",\n\n";
        $message .= "Klik link berikut untuk mereset password Anda:\n";
        $message .= $resetLink . "\n\n";
        $message .= "Link ini berlaku selama 1 jam.\n\n";
        $message .= "Jika Anda tidak meminta reset password, abaikan email ini.\n\n";
        $message .= "Terima kasih,\n" . APP_NAME;

        // Simulasi kirim email (gunakan PHPMailer untuk production)
        $headers = "From: " . SMTP_FROM . "\r\n";
        $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        if (mail($email, $subject, $message, $headers)) {
            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Link reset password telah dikirim ke email Anda'
            ];
        }

        return [
            'status' => 'error',
            'code' => 500,
            'message' => 'Gagal mengirim email, silakan coba lagi'
        ];
    }

    public function resetPassword($token, $newPassword) {
        if (empty($token) || empty($newPassword)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Token dan password baru wajib diisi'
            ];
        }

        if (strlen($newPassword) < 6) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Password minimal 6 karakter'
            ];
        }

        $user = $this->userModel->findByResetToken($token);
        
        if (!$user) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Token tidak valid atau sudah kadaluarsa'
            ];
        }

        // Update password
        $this->userModel->updatePassword($user['email'], $newPassword);
        $this->userModel->clearResetToken($user['email']);

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Password berhasil direset, silakan login'
        ];
    }

    public function checkSession() {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            return [
                'status' => 'success',
                'code' => 200,
                'data' => [
                    'user_id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'email' => $_SESSION['email'],
                    'role' => $_SESSION['role'],
                    'logged_in' => true
                ]
            ];
        }

        return [
            'status' => 'error',
            'code' => 401,
            'message' => 'Belum login'
        ];
    }
}