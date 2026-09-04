<?php
// controller/dashboardusers/invoice/InvoiceController.php

require_once __DIR__ . '/../../../models/dashboardusers/invoice/Invoice.php';
require_once __DIR__ . '/../../../models/dashboardusers/order/Order.php';

class InvoiceController
{
    private $invoiceModel;
    private $orderModel;

    public function __construct()
    {
        $this->invoiceModel = new Invoice();
        $this->orderModel = new Order();
    }

    /**
     * Membuat invoice berdasarkan order
     */
    public function createInvoice($userId, $orderId)
    {
        if (empty($userId)) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'User belum login'
            ];
        }

        if (empty($orderId) || !is_numeric($orderId)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'ID order tidak valid'
            ];
        }

        // Cari order milik user
        $order = $this->orderModel->findById(
            $orderId,
            $userId
        );

        if (!$order) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Order tidak ditemukan'
            ];
        }

        // Cek apakah invoice sudah ada
        $existingInvoice =
            $this->invoiceModel->findByOrderId($orderId);

        if ($existingInvoice) {
            return [
                'status' => 'error',
                'code' => 409,
                'message' => 'Invoice untuk order ini sudah ada'
            ];
        }

        // Generate nomor invoice
        $invoiceNumber =
            'INV-' .
            date('Ymd') .
            '-' .
            strtoupper(substr(uniqid(), -6));

        // Jatuh tempo 1 hari
        $dueDate = date(
            'Y-m-d H:i:s',
            strtotime('+1 day')
        );

        $success = $this->invoiceModel->createInvoice(
            $order['id'],
            $invoiceNumber,
            $order['subtotal'],
            $order['ppn'],
            $order['discount'],
            $order['total'],
            $dueDate
        );

        if (!$success) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Gagal membuat invoice'
            ];
        }

        // Cari invoice yang baru dibuat untuk mendapatkan ID
        $newInvoice = $this->invoiceModel->findByOrderId($order['id']);
        $newInvoiceId = $newInvoice ? (int)$newInvoice['id'] : null;

        return [
            'status' => 'success',
            'code' => 201,
            'message' => 'Invoice berhasil dibuat',
            'data' => [
                'id' => $newInvoiceId,
                'invoice_number' => $invoiceNumber,
                'order_id' => (int) $order['id'],
                'order_number' => $order['order_number'],
                'subtotal' => number_format(
                    (float) $order['subtotal'],
                    2,
                    '.',
                    ''
                ),
                'ppn' => number_format(
                    (float) $order['ppn'],
                    2,
                    '.',
                    ''
                ),
                'discount' => number_format(
                    (float) $order['discount'],
                    2,
                    '.',
                    ''
                ),
                'total' => number_format(
                    (float) $order['total'],
                    2,
                    '.',
                    ''
                ),
                'status' => 'unpaid',
                'due_date' => $dueDate
            ]
        ];
    }

    /**
     * Mengambil invoice berdasarkan ID
     */
    public function getById($userId, $invoiceId)
    {
        if (empty($userId)) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'User belum login'
            ];
        }

        if (
            empty($invoiceId) ||
            !is_numeric($invoiceId)
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'ID invoice tidak valid'
            ];
        }

        $invoice =
            $this->invoiceModel->findById($invoiceId);

        if (!$invoice) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Invoice tidak ditemukan'
            ];
        }

        // Pastikan invoice milik user
        $order = $this->orderModel->findById(
            $invoice['order_id'],
            $userId
        );

        if (!$order) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Invoice tidak ditemukan'
            ];
        }

        $items =
            $this->orderModel->getOrderItems(
                $invoice['order_id']
            );

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data invoice berhasil diambil',
            'data' => [
                'id' => (int) $invoice['id'],
                'invoice_number' =>
                    $invoice['invoice_number'],
                'order_id' =>
                    (int) $invoice['order_id'],
                'order_number' =>
                    $order['order_number'],
                'items' => $items,
                'subtotal' => $invoice['subtotal'],
                'ppn' => $invoice['ppn'],
                'discount' => $invoice['discount'],
                'total' => $invoice['total'],
                'status' => $invoice['status'],
                'due_date' => $invoice['due_date'],
                'created_at' => $invoice['created_at']
            ]
        ];
    }

    /**
     * Mengambil semua invoice milik user
     */
    public function getAll($userId)
    {
        if (empty($userId)) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'User belum login'
            ];
        }

        $invoices =
            $this->invoiceModel->getAllByUser($userId);

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data invoice berhasil diambil',
            'data' => $invoices
        ];
    }
}