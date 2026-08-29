<?php
// models/users/User.php

require_once __DIR__ . '/../../../service/database/db.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    public function findByEmailOrUsername($identifier) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :identifier OR username = :identifier LIMIT 1");
        $stmt->execute([':identifier' => $identifier]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password, role, created_at) 
            VALUES (:username, :email, :password, :role, NOW())
        ");
        return $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':role' => $data['role'] ?? 'user'
        ]);
    }

    public function updatePassword($email, $newPassword) {
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE email = :email");
        return $stmt->execute([
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':email' => $email
        ]);
    }

    public function updateResetToken($email, $token) {
        $stmt = $this->db->prepare("UPDATE users SET reset_token = :token, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = :email");
        return $stmt->execute([
            ':token' => $token,
            ':email' => $email
        ]);
    }

    public function findByResetToken($token) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_expires > NOW() LIMIT 1");
        $stmt->execute([':token' => $token]);
        return $stmt->fetch();
    }

    public function clearResetToken($email) {
        $stmt = $this->db->prepare("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE email = :email");
        return $stmt->execute([':email' => $email]);
    }

    public function verifyPassword($inputPassword, $hashedPassword) {
        return password_verify($inputPassword, $hashedPassword);
    }
}