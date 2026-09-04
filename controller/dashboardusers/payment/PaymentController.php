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

    /**
     * =====================================================
     * CREATE PAYMENT
     *
     * Payment dibuat berdasarkan ORDER.
     *
     * Alur:
     * Order Pending
     * -> Payment Pending
     * -> Admin Konfirmasi
     *
     * Invoice belum dibuat pada tahap ini.
     * =====================================================
     */
    public function createPayment(
        $userId,
        $orderId,
        $paymentMethod
    ) {
        /**
         * CEK USER
         */
        if (empty($userId)) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'User belum login'
            ];
        }

        /**
         * CEK ORDER ID
         */
        if (
            empty($orderId) ||
            !is_numeric($orderId) ||
            (int) $orderId <= 0
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'ID order tidak valid'
            ];
        }

        /**
         * CEK PAYMENT METHOD
         */
        $paymentMethod =
            strtolower(
                trim(
                    (string) $paymentMethod
                )
            );

        if ($paymentMethod === '') {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Metode pembayaran wajib diisi'
            ];
        }
            /**
             * =====================================================
             * METODE PEMBAYARAN
             *
             * Sistem menggunakan transfer bank
             * ke rekening milik admin.
             * =====================================================
             */
            if ($paymentMethod !== 'transfer') {
                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Metode pembayaran tidak valid'
                ];
            }
        /**
         * =================================================
         * CARI ORDER
         * =================================================
         */
        $order =
            $this->orderModel->findById(
                (int) $orderId,
                (int) $userId
            );

        if (!$order) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Order tidak ditemukan'
            ];
        }

        /**
         * Order harus pending
         */
        if (
            strtolower(
                trim(
                    (string) $order['status']
                )
            ) !== 'pending'
        ) {
            return [
                'status' => 'error',
                'code' => 409,
                'message' =>
                    'Order tidak dapat dibayar karena status order bukan pending'
            ];
        }

        /**
         * =================================================
         * CEK PAYMENT YANG SUDAH ADA
         * =================================================
         */
        $existingPayment =
            $this->paymentModel->findPendingByOrderId(
                (int) $orderId
            );

        if ($existingPayment) {
            return [
                'status' => 'error',
                'code' => 409,
                'message' =>
                    'Payment untuk order ini sudah dibuat',
                'data' => [
                    'payment_id' =>
                        (int) $existingPayment['id'],

                    'payment_number' =>
                        $existingPayment['payment_number'],

                    'order_id' =>
                        (int) $existingPayment['order_id'],

                    'amount' =>
                        $existingPayment['amount'],

                    'payment_method' =>
                        $existingPayment['payment_method'],

                    'status' =>
                        $existingPayment['status']
                ]
            ];
        }

        /**
         * =================================================
         * NOMOR PAYMENT
         * =================================================
         */
        $paymentNumber =
            'PAY-' .
            date('Ymd') .
            '-' .
            strtoupper(
                substr(
                    uniqid(),
                    -6
                )
            );

        /**
         * =================================================
         * NOMINAL PAYMENT
         *
         * Ambil dari orders.total.
         *
         * Contoh:
         * subtotal 150000
         * discount 15000
         * ppn      14850
         * total    149850
         *
         * Jadi yang dibayar user = 149850.
         * =================================================
         */
        $amount =
            (float) (
                $order['total'] ?? 0
            );

        if ($amount <= 0) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Total order tidak valid'
            ];
        }

        /**
         * =================================================
         * CREATE PAYMENT
         * =================================================
         *
         * invoice_id = NULL
         * karena invoice belum dibuat.
         */
        $success =
            $this->paymentModel->createPayment(
                (int) $orderId,
                $paymentNumber,
                $amount,
                $paymentMethod
            );

        if (!$success) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' =>
                    'Gagal membuat payment'
            ];
        }

        /**
         * Ambil payment yang baru dibuat
         */
        $payment =
            $this->paymentModel->findByPaymentNumber(
                $paymentNumber
            );

        /**
         * =================================================
         * RESPONSE
         * =================================================
         */
        return [
            'status' => 'success',
            'code' => 201,
            'message' =>
                'Payment berhasil dibuat dan menunggu konfirmasi',
            'data' => [
                'payment_id' =>
                    isset($payment['id'])
                        ? (int) $payment['id']
                        : null,

                'payment_number' =>
                    $paymentNumber,

                'order_id' =>
                    (int) $orderId,

                'order_number' =>
                    $order['order_number'],

                'amount' =>
                    number_format(
                        $amount,
                        2,
                        '.',
                        ''
                    ),

                'payment_method' =>
                    $paymentMethod,

                'status' =>
                    'pending',

                /**
                 * Invoice belum dibuat
                 */
                'invoice_id' =>
                    null,

                'invoice_number' =>
                    null
            ]
        ];
    }


    /**
     * =====================================================
     * GET ALL PAYMENT USER
     * =====================================================
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

        $payments =
            $this->paymentModel->getAllByUser(
                (int) $userId
            );

        return [
            'status' => 'success',
            'code' => 200,
            'message' =>
                'Data payment berhasil diambil',
            'data' => $payments
        ];
    }


    /**
     * =====================================================
     * GET PAYMENT BY ID
     * =====================================================
     */
    public function getById(
        $userId,
        $paymentId
    ) {
        if (empty($userId)) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'User belum login'
            ];
        }

        if (
            empty($paymentId) ||
            !is_numeric($paymentId) ||
            (int) $paymentId <= 0
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'ID payment tidak valid'
            ];
        }

        $payment =
            $this->paymentModel->findById(
                (int) $paymentId
            );

        if (!$payment) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' =>
                    'Payment tidak ditemukan'
            ];
        }

        /**
         * Pastikan payment milik user
         */
        $order =
            $this->orderModel->findById(
                (int) $payment['order_id'],
                (int) $userId
            );

        if (!$order) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' =>
                    'Payment tidak ditemukan'
            ];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'message' =>
                'Data payment berhasil diambil',
            'data' => $payment
        ];
    }
}