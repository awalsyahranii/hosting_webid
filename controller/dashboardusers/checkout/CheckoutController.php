<?php
// controller/dashboardusers/checkout/CheckoutController.php

require_once __DIR__ . '/../../../models/dashboardusers/checkout/Checkout.php';

class CheckoutController
{
    private $checkoutModel;

    public function __construct()
    {
        $this->checkoutModel = new Checkout();
    }

    /**
     * =========================
     * GET CHECKOUT
     * =========================
     */
    public function getCheckout($userId)
    {
        // =========================
        // CEK USER
        // =========================

        if (empty($userId) || !is_numeric($userId)) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'User belum login'
            ];
        }

        // =========================
        // AMBIL ITEM CHECKOUT
        // =========================

        $items = $this->checkoutModel->getCheckoutItems($userId);

        // Jika cart kosong
        if (!$items) {
            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Keranjang kosong',
                'data' => [
                    'items' => [],
                    'subtotal' => '0.00',
                    'ppn' => '0.00',
                    'total' => '0.00'
                ]
            ];
        }

        // =========================
        // HITUNG SUBTOTAL
        // =========================

        $subtotal = 0;

        foreach ($items as &$item) {
            $item['price'] = (float) $item['price'];

            $subtotal += $item['price'];
        }

        unset($item);

        // =========================
        // HITUNG PPN
        // =========================

        $ppn = $subtotal * 0.11;

        // =========================
        // HITUNG TOTAL
        // =========================

        $total = $subtotal + $ppn;

        // =========================
        // RESPONSE
        // =========================

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data checkout berhasil diambil',
            'data' => [
                'items' => $items,
                'subtotal' => number_format(
                    $subtotal,
                    2,
                    '.',
                    ''
                ),
                'ppn' => number_format(
                    $ppn,
                    2,
                    '.',
                    ''
                ),
                'total' => number_format(
                    $total,
                    2,
                    '.',
                    ''
                )
            ]
        ];
    }
}