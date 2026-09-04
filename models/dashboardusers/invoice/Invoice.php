<?php
// models/dashboardusers/invoice/Invoice.php

require_once __DIR__ . '/../../../service/database/db.php';

class Invoice
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Membuat invoice dari order
     */
    public function createInvoice(
        $orderId,
        $invoiceNumber,
        $subtotal,
        $ppn,
        $discount,
        $total,
        $dueDate
    ) {
        $stmt = $this->db->prepare("
            INSERT INTO invoices (
                order_id,
                invoice_number,
                subtotal,
                ppn,
                discount,
                total,
                status,
                due_date,
                created_at,
                updated_at
            )
            VALUES (
                :order_id,
                :invoice_number,
                :subtotal,
                :ppn,
                :discount,
                :total,
                'unpaid',
                :due_date,
                NOW(),
                NOW()
            )
        ");

        return $stmt->execute([
            ':order_id' => $orderId,
            ':invoice_number' => $invoiceNumber,
            ':subtotal' => $subtotal,
            ':ppn' => $ppn,
            ':discount' => $discount,
            ':total' => $total,
            ':due_date' => $dueDate
        ]);
    }

    /**
     * Cari invoice berdasarkan order
     */
    public function findByOrderId($orderId)
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                order_id,
                invoice_number,
                subtotal,
                ppn,
                discount,
                total,
                status,
                due_date,
                created_at,
                updated_at
            FROM invoices
            WHERE order_id = :order_id
            LIMIT 1
        ");

        $stmt->execute([
            ':order_id' => $orderId
        ]);

        return $stmt->fetch();
    }

    /**
     * Cari invoice berdasarkan ID
     */
    public function findById($invoiceId)
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                order_id,
                invoice_number,
                subtotal,
                ppn,
                discount,
                total,
                status,
                due_date,
                created_at,
                updated_at
            FROM invoices
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $invoiceId
        ]);

        return $stmt->fetch();
    }

    /**
     * Ambil semua invoice milik user
     */
    public function getAllByUser($userId)
    {
        $stmt = $this->db->prepare("
            SELECT
                invoices.id,
                invoices.order_id,
                invoices.invoice_number,
                invoices.subtotal,
                invoices.ppn,
                invoices.discount,
                invoices.total,
                invoices.status,
                invoices.due_date,
                invoices.created_at,
                invoices.updated_at
            FROM invoices
            INNER JOIN orders
                ON orders.id = invoices.order_id
            WHERE orders.user_id = :user_id
            ORDER BY invoices.id DESC
        ");

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }
}