<?php
// controller/dashboardusers/payment/PaymentController.php

require_once __DIR__ . '/../../../models/dashboardusers/payment/Payment.php';
require_once __DIR__ . '/../../../models/dashboardusers/order/Order.php';

class PaymentController
{
    private $paymentModel;
    private $orderModel;

    public function __construct()
    {
        $this->paymentModel = new Payment();
        $this->orderModel = new Order();
    }

    public function requestConfirmation($userId, $paymentId = null, $orderId = null, $paymentNumber = null)
    {
        if (empty($userId)) {
            return ['status' => 'error', 'code' => 401, 'message' => 'User belum login'];
        }

        if (empty($paymentId) && empty($orderId) && empty($paymentNumber)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID atau nomor payment diperlukan'];
        }

        if (!$this->paymentModel->requestConfirmation($userId, $paymentId, $orderId, $paymentNumber)) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Payment pending tidak ditemukan'];
        }

        return ['status' => 'success', 'code' => 200, 'message' => 'Konfirmasi pembayaran dicatat'];
    }

    /**
     * =====================================================
     * CREATE PAYMENT
     * =====================================================
     */
    public function createPayment($userId, $orderId, $paymentMethod)
    {
        if (empty($userId)) {
            return ['status' => 'error', 'code' => 401, 'message' => 'User belum login'];
        }

        if (empty($orderId) || !is_numeric($orderId) || (int) $orderId <= 0) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID order tidak valid'];
        }

        $paymentMethod = trim(strtolower((string) $paymentMethod));
        $validMethods = ['transfer', 'midtrans', 'qris', 'manual'];
        if (!in_array($paymentMethod, $validMethods, true)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Metode pembayaran tidak valid'];
        }

        $order = $this->orderModel->findById((int) $orderId, (int) $userId);
        if (!$order) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Order tidak ditemukan'];
        }

        if (strtolower(trim((string) $order['status'])) !== 'pending') {
            return [
                'status' => 'error',
                'code' => 409,
                'message' => 'Order tidak dapat dibayar karena status order bukan pending'
            ];
        }

        $existingPayment = $this->paymentModel->findPendingByOrderId((int) $orderId);
        if ($existingPayment) {
            return [
                'status' => 'error',
                'code' => 409,
                'message' => 'Payment untuk order ini sudah dibuat',
                'data' => [
                    'payment_id' => (int) $existingPayment['id'],
                    'payment_number' => $existingPayment['payment_number'],
                    'order_id' => (int) $existingPayment['order_id'],
                    'amount' => $existingPayment['amount'],
                    'payment_method' => $existingPayment['payment_method'],
                    'status' => $existingPayment['status']
                ]
            ];
        }

        $paymentNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $amount = (float) ($order['total'] ?? 0);

        if ($amount <= 0) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Total order tidak valid'];
        }

        $success = $this->paymentModel->createPayment((int) $orderId, $paymentNumber, $amount, $paymentMethod);
        if (!$success) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Gagal membuat payment'];
        }

        $payment = $this->paymentModel->findByPaymentNumber($paymentNumber);

        return [
            'status' => 'success',
            'code' => 201,
            'message' => 'Payment berhasil dibuat dan menunggu konfirmasi',
            'data' => [
                'payment_id' => isset($payment['id']) ? (int) $payment['id'] : null,
                'payment_number' => $paymentNumber,
                'order_id' => (int) $orderId,
                'order_number' => $order['order_number'],
                'amount' => number_format($amount, 2, '.', ''),
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'invoice_id' => null,
                'invoice_number' => null
            ]
        ];
    }

    public function getAll($userId)
    {
        if (empty($userId)) {
            return ['status' => 'error', 'code' => 401, 'message' => 'User belum login'];
        }

        $payments = $this->paymentModel->getAllByUser((int) $userId);
        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data payment berhasil diambil',
            'data' => $payments
        ];
    }

    public function getById($userId, $paymentId)
    {
        if (empty($userId)) {
            return ['status' => 'error', 'code' => 401, 'message' => 'User belum login'];
        }

        if (empty($paymentId) || !is_numeric($paymentId) || (int) $paymentId <= 0) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID payment tidak valid'];
        }

        $payment = $this->paymentModel->findById((int) $paymentId);
        if (!$payment) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Payment tidak ditemukan'];
        }

        $order = $this->orderModel->findById((int) $payment['order_id'], (int) $userId);
        if (!$order) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Payment tidak ditemukan'];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data payment berhasil diambil',
            'data' => $payment
        ];
    }
}
