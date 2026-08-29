<?php
// controller/dashboardusers/order/OrderController.php

require_once __DIR__ . '/../../../models/dashboardusers/order/Order.php';
require_once __DIR__ . '/../../../models/dashboardusers/cart/Cart.php';

class OrderController
{
    private $orderModel;
    private $cartModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->cartModel = new Cart();
    }

    /**
     * Membuat order dari cart aktif user
     */
    public function createOrder($userId)
    {
        if (empty($userId)) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'User belum login'
            ];
        }

        // Ambil cart aktif
        $cart = $this->cartModel->getActiveCartByUser($userId);

        if (empty($cart)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Keranjang kosong'
            ];
        }

        // Hitung subtotal
        $subtotal = 0;

        foreach ($cart as $item) {
            $subtotal += (float) $item['price'];
        }

        // PPN 11%
        $ppn = $subtotal * 0.11;

        // Untuk tahap pertama belum menggunakan kupon
        $discount = 0;

        $total = $subtotal + $ppn - $discount;

        // Generate nomor order
        $orderNumber =
            'ORD-' .
            date('Ymd') .
            '-' .
            strtoupper(substr(uniqid(), -6));

        // Buat order
        $orderId = $this->orderModel->createOrder(
            $userId,
            $orderNumber,
            $subtotal,
            $ppn,
            $discount,
            $total
        );

        if (!$orderId) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Gagal membuat order'
            ];
        }

        // Simpan item order
        foreach ($cart as $item) {

            $result = $this->orderModel->addOrderItem(
                $orderId,
                $item['domain_id'],
                $item['domain_name'],
                $item['price']
            );

            if (!$result) {
                return [
                    'status' => 'error',
                    'code' => 500,
                    'message' => 'Gagal menyimpan item order'
                ];
            }
        }

        return [
            'status' => 'success',
            'code' => 201,
            'message' => 'Order berhasil dibuat',
            'data' => [
                'order_id' => (int) $orderId,
                'order_number' => $orderNumber,
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'ppn' => number_format($ppn, 2, '.', ''),
                'discount' => number_format($discount, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
                'status' => 'pending'
            ]
        ];
    }
}