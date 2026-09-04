<?php
// models/admin/user/User.php

require_once __DIR__ . '/../../../service/database/db.php';

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * =========================
     * GET ALL USERS
     * =========================
     */
    public function getAll($search = '')
    {
        $sql = "
            SELECT
                id,
                username,
                email,
                role,
                created_at,
                updated_at
            FROM users
        ";

        if (!empty($search)) {
            $sql .= "
                WHERE
                    username LIKE :search
                    OR email LIKE :search
                    OR role LIKE :search
            ";
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);

        if (!empty($search)) {
            $stmt->execute([
                ':search' => '%' . $search . '%'
            ]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * =========================
     * GET USER BY ID
     * =========================
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                username,
                email,
                role,
                created_at,
                updated_at
            FROM users
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * =========================
     * FIND USER BY USERNAME
     * =========================
     */
    public function findByUsername($username, $excludeId = null)
    {
        $sql = "
            SELECT
                id,
                username,
                email,
                role
            FROM users
            WHERE username = :username
        ";

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);

        $params = [
            ':username' => $username
        ];

        if ($excludeId !== null) {
            $params[':exclude_id'] = $excludeId;
        }

        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * =========================
     * FIND USER BY EMAIL
     * =========================
     */
    public function findByEmail($email, $excludeId = null)
    {
        $sql = "
            SELECT
                id,
                username,
                email,
                role
            FROM users
            WHERE email = :email
        ";

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);

        $params = [
            ':email' => $email
        ];

        if ($excludeId !== null) {
            $params[':exclude_id'] = $excludeId;
        }

        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * =========================
     * CREATE USER
     * =========================
     */
    public function create($data)
    {
        $password = password_hash(
            $data['password'],
            PASSWORD_DEFAULT
        );

        $stmt = $this->db->prepare("
            INSERT INTO users (
                username,
                email,
                password,
                role,
                created_at,
                updated_at
            )
            VALUES (
                :username,
                :email,
                :password,
                :role,
                NOW(),
                NOW()
            )
        ");

        return $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => $password,
            ':role' => $data['role']
        ]);
    }

    /**
     * =========================
     * UPDATE USER
     * =========================
     */
    public function update($id, $data)
    {
        if (!empty($data['password'])) {

            $password = password_hash(
                $data['password'],
                PASSWORD_DEFAULT
            );

            $stmt = $this->db->prepare("
                UPDATE users
                SET
                    username = :username,
                    email = :email,
                    password = :password,
                    role = :role,
                    updated_at = NOW()
                WHERE id = :id
            ");

            return $stmt->execute([
                ':id' => $id,
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password' => $password,
                ':role' => $data['role']
            ]);
        }

        $stmt = $this->db->prepare("
            UPDATE users
            SET
                username = :username,
                email = :email,
                role = :role,
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':role' => $data['role']
        ]);
    }

    /**
     * =========================
     * DELETE USER
     * =========================
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("
            DELETE FROM users
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id
        ]);
    }
}