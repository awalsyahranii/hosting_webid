<?php
// controller/dashboardusers/cart/CartController.php

require_once __DIR__ . '/../../../models/dashboardusers/cart/Cart.php';
require_once __DIR__ . '/../../../models/dashboardusers/domain/Domain.php';

class CartController
{
    private $cartModel;
    private $domainModel;

    public function __construct()
    {
        $this->cartModel = new Cart();
        $this->domainModel = new Domain();
    }

    /**
     * Menambahkan domain ke cart.
     */
    public function addToCart($userId, $domainId)
    {
        if (empty($userId)) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'User belum login'
            ];
        }

        if (empty($domainId) || !is_numeric($domainId)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'ID domain tidak valid'
            ];
        }

        // Cari domain
        $domain = $this->domainModel->findById($domainId);

        if (!$domain) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Domain tidak ditemukan'
            ];
        }

        // Pastikan domain tersedia
        if ($domain['status'] !== 'available') {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Domain tidak tersedia'
            ];
        }

        // Cek apakah sudah ada di cart
        $existingCart = $this->cartModel->findActiveCartItem(
            $userId,
            $domainId
        );

        if ($existingCart) {
            return [
                'status' => 'error',
                'code' => 409,
                'message' => 'Domain sudah ada di keranjang'
            ];
        }

        // Tambahkan ke cart
        $result = $this->cartModel->addToCart(
            $userId,
            $domainId,
            $domain['price']
        );

        if (!$result) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Gagal menambahkan domain ke keranjang'
            ];
        }

        return [
            'status' => 'success',
            'code' => 201,
            'message' => 'Domain berhasil ditambahkan ke keranjang',
            'data' => [
                'domain_id' => $domain['id'],
                'domain' => $domain['domain_name'],
                'price' => $domain['price']
            ]
        ];
    }

    /**
     * Mengambil cart aktif milik user.
     */
    public function getCart($userId)
    {
        if (empty($userId)) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'User belum login'
            ];
        }

        $cart = $this->cartModel->getActiveCartByUser($userId);

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data keranjang berhasil diambil',
            'data' => $cart
        ];
    }

    /**
     * Menghapus item dari cart.
     */
    public function deleteCartItem($userId, $cartId)
    {
        if (empty($userId)) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'User belum login'
            ];
        }

        if (empty($cartId) || !is_numeric($cartId)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'ID cart tidak valid'
            ];
        }

        $result = $this->cartModel->deleteCartItem(
            $userId,
            $cartId
        );

        if (!$result) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Gagal menghapus item dari keranjang'
            ];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Item berhasil dihapus dari keranjang'
        ];
    }
}