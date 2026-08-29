<?php
// models/dashboardusers/order/Order.php

require_once __DIR__ . '/../../../service/database/db.php';

class Order
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Membuat order baru
     */
    public function createOrder(
        $userId,
        $orderNumber,
        $subtotal,
        $ppn,
        $discount,
        $total
    ) {
        $stmt = $this->db->prepare("
            INSERT INTO orders (
                user_id,
                order_number,
                subtotal,
                ppn,
                discount,
                total,
                status,
                created_at,
                updated_at
            )
            VALUES (
                :user_id,
                :order_number,
                :subtotal,
                :ppn,
                :discount,
                :total,
                'pending',
                NOW(),
                NOW()
            )
        ");

        $result = $stmt->execute([
            ':user_id' => $userId,
            ':order_number' => $orderNumber,
            ':subtotal' => $subtotal,
            ':ppn' => $ppn,
            ':discount' => $discount,
            ':total' => $total
        ]);

        if (!$result) {
            return false;
        }

        return $this->db->lastInsertId();
    }

    /**
     * Menambahkan item ke order
     */
    public function addOrderItem(
        $orderId,
        $domainId,
        $domainName,
        $price
    ) {
        $stmt = $this->db->prepare("
            INSERT INTO order_items (
                order_id,
                domain_id,
                domain_name,
                price,
                created_at
            )
            VALUES (
                :order_id,
                :domain_id,
                :domain_name,
                :price,
                NOW()
            )
        ");

        return $stmt->execute([
            ':order_id' => $orderId,
            ':domain_id' => $domainId,
            ':domain_name' => $domainName,
            ':price' => $price
        ]);
    }

    /**
     * Ambil order berdasarkan ID
     */
    public function findById($orderId, $userId)
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                user_id,
                order_number,
                subtotal,
                ppn,
                discount,
                total,
                status,
                created_at,
                updated_at
            FROM orders
            WHERE id = :id
              AND user_id = :user_id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $orderId,
            ':user_id' => $userId
        ]);

        return $stmt->fetch();
    }

    /**
     * Ambil item order
     */
    public function getOrderItems($orderId)
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                order_id,
                domain_id,
                domain_name,
                price,
                created_at
            FROM order_items
            WHERE order_id = :order_id
            ORDER BY id ASC
        ");

        $stmt->execute([
            ':order_id' => $orderId
        ]);

        return $stmt->fetchAll();
    }
}