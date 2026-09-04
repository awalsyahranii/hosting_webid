<?php
// controller/admin/user/UserController.php

require_once __DIR__ . '/../../../models/admin/user/User.php';

class UserController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }


    /**
     * =========================
     * GET ALL USERS
     * =========================
     */
    public function getAll($search = '')
    {
        $users = $this->userModel->getAll($search);

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data user berhasil diambil',
            'data' => [
                'users' => $users,
                'total' => count($users)
            ]
        ];
    }


    /**
     * =========================
     * GET USER BY ID
     * =========================
     */
    public function getById($id)
    {
        if (empty($id)) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'ID user wajib diisi'
            ];
        }

        $user = $this->userModel->getById($id);

        if (!$user) {

            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'User tidak ditemukan'
            ];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data user berhasil diambil',
            'data' => [
                'user' => $user
            ]
        ];
    }


    /**
     * =========================
     * CREATE USER
     * =========================
     */
    public function create($data)
    {
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'user';


        if (
            empty($username) ||
            empty($email) ||
            empty($password)
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Username, email, dan password wajib diisi'
            ];
        }


        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Format email tidak valid'
            ];
        }


        if (strlen($password) < 6) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Password minimal 6 karakter'
            ];
        }


        if (
            !in_array(
                $role,
                ['admin', 'user'],
                true
            )
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Role tidak valid'
            ];
        }


        if (
            $this->userModel
                ->findByUsername($username)
        ) {

            return [
                'status' => 'error',
                'code' => 409,
                'message' =>
                    'Username sudah digunakan'
            ];
        }


        if (
            $this->userModel
                ->findByEmail($email)
        ) {

            return [
                'status' => 'error',
                'code' => 409,
                'message' =>
                    'Email sudah terdaftar'
            ];
        }


        $created =
            $this->userModel->create([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => $role
            ]);


        if (!$created) {

            return [
                'status' => 'error',
                'code' => 500,
                'message' =>
                    'Gagal membuat user'
            ];
        }


        return [
            'status' => 'success',
            'code' => 201,
            'message' =>
                'User berhasil dibuat'
        ];
    }


    /**
     * =========================
     * UPDATE USER
     * =========================
     */
    public function update($id, $data)
    {
        if (empty($id)) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'ID user wajib diisi'
            ];
        }


        $user =
            $this->userModel->getById($id);


        if (!$user) {

            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'User tidak ditemukan'
            ];
        }


        $username =
            trim($data['username'] ?? '');

        $email =
            trim($data['email'] ?? '');

        $password =
            $data['password'] ?? '';

        $role =
            $data['role'] ?? 'user';


        if (
            empty($username) ||
            empty($email)
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Username dan email wajib diisi'
            ];
        }


        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Format email tidak valid'
            ];
        }


        if (
            !in_array(
                $role,
                ['admin', 'user'],
                true
            )
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Role tidak valid'
            ];
        }


        if (
            !empty($password) &&
            strlen($password) < 6
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Password minimal 6 karakter'
            ];
        }


        if (
            $this->userModel
                ->findByUsername(
                    $username,
                    $id
                )
        ) {

            return [
                'status' => 'error',
                'code' => 409,
                'message' =>
                    'Username sudah digunakan'
            ];
        }


        if (
            $this->userModel
                ->findByEmail(
                    $email,
                    $id
                )
        ) {

            return [
                'status' => 'error',
                'code' => 409,
                'message' =>
                    'Email sudah digunakan'
            ];
        }


        $updated =
            $this->userModel->update(
                $id,
                [
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                    'role' => $role
                ]
            );


        if (!$updated) {

            return [
                'status' => 'error',
                'code' => 500,
                'message' =>
                    'Gagal memperbarui user'
            ];
        }


        return [
            'status' => 'success',
            'code' => 200,
            'message' =>
                'User berhasil diperbarui'
        ];
    }


    /**
     * =========================
     * DELETE USER
     * =========================
     */
    public function delete($id, $currentUserId)
    {
        if (empty($id)) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'ID user wajib diisi'
            ];
        }


        if (
            (int) $id ===
            (int) $currentUserId
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Admin tidak dapat menghapus akun sendiri'
            ];
        }


        $user =
            $this->userModel->getById($id);


        if (!$user) {

            return [
                'status' => 'error',
                'code' => 404,
                'message' =>
                    'User tidak ditemukan'
            ];
        }


        $deleted =
            $this->userModel->delete($id);


        if (!$deleted) {

            return [
                'status' => 'error',
                'code' => 500,
                'message' =>
                    'Gagal menghapus user'
            ];
        }


        return [
            'status' => 'success',
            'code' => 200,
            'message' =>
                'User berhasil dihapus'
        ];
    }
}