<?php
// models/dashboardusers/payment/Payment.php

require_once __DIR__ . '/../../../service/database/db.php';

class Payment
{
    private $db;

    public function __construct()
    {
        $this->db =
            Database::getInstance()
                ->getConnection();
    }


    /**
     * =====================================================
     * CREATE PAYMENT
     *
     * Payment dibuat berdasarkan order.
     *
     * Invoice BELUM dibuat pada tahap ini.
     * Karena itu invoice_id = NULL.
     *
     * Status awal = pending
     * =====================================================
     */
    public function createPayment(
        $orderId,
        $paymentNumber,
        $amount,
        $paymentMethod
    ) {
        $stmt = $this->db->prepare("
            INSERT INTO payments (
                order_id,
                invoice_id,
                payment_number,
                amount,
                payment_method,
                status,
                paid_at,
                created_at,
                updated_at
            )
            VALUES (
                :order_id,
                NULL,
                :payment_number,
                :amount,
                :payment_method,
                'pending',
                NULL,
                NOW(),
                NOW()
            )
        ");

        return $stmt->execute([
            ':order_id' =>
                (int) $orderId,

            ':payment_number' =>
                $paymentNumber,

            ':amount' =>
                $amount,

            ':payment_method' =>
                $paymentMethod
        ]);
    }


    /**
     * =====================================================
     * FIND PAYMENT BY ID
     * =====================================================
     */
    public function findById($paymentId)
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                order_id,
                invoice_id,
                payment_number,
                amount,
                payment_method,
                status,
                paid_at,
                created_at,
                updated_at
            FROM payments
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' =>
                (int) $paymentId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * =====================================================
     * FIND PAYMENT BY PAYMENT NUMBER
     * =====================================================
     */
    public function findByPaymentNumber(
        $paymentNumber
    ) {
        $stmt = $this->db->prepare("
            SELECT
                id,
                order_id,
                invoice_id,
                payment_number,
                amount,
                payment_method,
                status,
                paid_at,
                created_at,
                updated_at
            FROM payments
            WHERE payment_number = :payment_number
            LIMIT 1
        ");

        $stmt->execute([
            ':payment_number' =>
                $paymentNumber
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * =====================================================
     * FIND PAYMENT PENDING BY ORDER
     *
     * Mencegah user membuat payment pending
     * lebih dari satu untuk order yang sama.
     * =====================================================
     */
    public function findPendingByOrderId(
        $orderId
    ) {
        $stmt = $this->db->prepare("
            SELECT
                id,
                order_id,
                invoice_id,
                payment_number,
                amount,
                payment_method,
                status,
                paid_at,
                created_at,
                updated_at
            FROM payments
            WHERE order_id = :order_id
              AND status = 'pending'
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([
            ':order_id' =>
                (int) $orderId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * =====================================================
     * GET ALL PAYMENT MILIK USER
     * =====================================================
     */
    public function getAllByUser(
        $userId
    ) {
        $stmt = $this->db->prepare("
            SELECT
                payments.id,
                payments.order_id,
                payments.invoice_id,
                payments.payment_number,
                payments.amount,
                payments.payment_method,
                payments.status,
                payments.paid_at,
                payments.created_at,
                payments.updated_at,

                orders.order_number

            FROM payments

            INNER JOIN orders
                ON orders.id =
                   payments.order_id

            WHERE orders.user_id =
                  :user_id

            ORDER BY payments.id DESC
        ");

        $stmt->execute([
            ':user_id' =>
                (int) $userId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}