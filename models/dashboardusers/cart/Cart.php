<?php
// models/dashboardusers/cart/Cart.php

require_once __DIR__ . '/../../../service/database/db.php';

class Cart
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Mengecek apakah domain sudah ada
     * di cart aktif milik user.
     */
    public function findActiveCartItem($userId, $domainId)
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                user_id,
                domain_id,
                price,
                status,
                created_at,
                updated_at
            FROM cart
            WHERE user_id = :user_id
              AND domain_id = :domain_id
              AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':domain_id' => $domainId
        ]);

        return $stmt->fetch();
    }

    /**
     * Menambahkan domain ke cart.
     */
    public function addToCart($userId, $domainId, $price)
    {
        $stmt = $this->db->prepare("
            INSERT INTO cart (
                user_id,
                domain_id,
                price,
                status,
                created_at,
                updated_at
            )
            VALUES (
                :user_id,
                :domain_id,
                :price,
                'active',
                NOW(),
                NOW()
            )
        ");

        return $stmt->execute([
            ':user_id' => $userId,
            ':domain_id' => $domainId,
            ':price' => $price
        ]);
    }

    /**
     * Mengambil semua item cart aktif milik user.
     */
    public function getActiveCartByUser($userId)
    {
        $stmt = $this->db->prepare("
            SELECT
                cart.id,
                cart.user_id,
                cart.domain_id,
                domains.domain_name,
                domains.extension,
                cart.price,
                cart.status,
                cart.created_at,
                cart.updated_at
            FROM cart
            INNER JOIN domains
                ON domains.id = cart.domain_id
            WHERE cart.user_id = :user_id
              AND cart.status = 'active'
            ORDER BY cart.id DESC
        ");

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Menghapus item cart milik user.
     */
    public function deleteCartItem($userId, $cartId)
    {
        $stmt = $this->db->prepare("
            DELETE FROM cart
            WHERE id = :cart_id
              AND user_id = :user_id
              AND status = 'active'
        ");

        return $stmt->execute([
            ':cart_id' => $cartId,
            ':user_id' => $userId
        ]);
    }
}