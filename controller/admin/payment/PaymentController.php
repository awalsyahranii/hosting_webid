<?php
// controller/admin/payment/PaymentController.php

require_once __DIR__ . '/../../../models/admin/payment/Payment.php';

class AdminPaymentController
{
    private $paymentModel;

    public function __construct()
    {
        $this->paymentModel = new AdminPayment();
    }

    public function getAll()
    {
        $payments = $this->paymentModel->getAll();
        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data payment berhasil diambil',
            'data' => ['payments' => $payments, 'total' => count($payments)]
        ];
    }

    public function getById($paymentId)
    {
        if (empty($paymentId) || !is_numeric($paymentId)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID payment tidak valid'];
        }

        $payment = $this->paymentModel->findById($paymentId);
        if (!$payment) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Payment tidak ditemukan'];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Detail payment berhasil diambil',
            'data' => $payment
        ];
    }

    public function confirm($paymentId)
    {
        if (empty($paymentId) || !is_numeric($paymentId)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID payment tidak valid'];
        }

        $result = $this->paymentModel->confirm($paymentId);
        if (($result['code'] ?? 500) !== 200) {
            return ['status' => 'error', 'code' => $result['code'], 'message' => $result['message']];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Pembayaran dikonfirmasi; order dan service diaktifkan',
            'data' => ['payment_id' => (int) $paymentId, 'invoice_id' => $result['invoice_id']]
        ];
    }
}