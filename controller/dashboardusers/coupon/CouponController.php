<?php
// controller/dashboardusers/coupon/CouponController.php

require_once __DIR__ . '/../../../models/dashboardusers/coupon/Coupon.php';

class CouponController
{
    private $couponModel;

    public function __construct()
    {
        $this->couponModel = new Coupon();
    }

    public function validateCoupon($code, $subtotal)
    {
        $code = strtoupper(trim($code));
        $subtotal = (float) $subtotal;

        if (empty($code)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Kode kupon wajib diisi'
            ];
        }

        if ($subtotal < 0) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Subtotal tidak valid'
            ];
        }

        $coupon = $this->couponModel->getValidCoupon($code);

        if (!$coupon) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Kode kupon tidak ditemukan atau tidak aktif'
            ];
        }

        // Cek tanggal mulai
        if (
            !empty($coupon['start_date']) &&
            strtotime($coupon['start_date']) > time()
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Kupon belum dapat digunakan'
            ];
        }

        // Cek tanggal berakhir
        if (
            !empty($coupon['end_date']) &&
            strtotime($coupon['end_date']) < time()
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Kupon sudah kedaluwarsa'
            ];
        }

        // Cek batas penggunaan
        if (
            $coupon['usage_limit'] !== null &&
            (int) $coupon['used_count'] >= (int) $coupon['usage_limit']
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Kuota penggunaan kupon sudah habis'
            ];
        }

        // Cek minimum pembelian
        if (
            $subtotal < (float) $coupon['min_purchase']
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Minimum pembelian untuk kupon ini adalah Rp ' .
                    number_format(
                        $coupon['min_purchase'],
                        0,
                        ',',
                        '.'
                    )
            ];
        }

        // Hitung diskon
        if ($coupon['discount_type'] === 'percentage') {

            $discount = $subtotal *
                ((float) $coupon['discount_value'] / 100);

        } else {

            $discount = (float) $coupon['discount_value'];
        }

        // Diskon tidak boleh lebih besar dari subtotal
        $discount = min($discount, $subtotal);

        // Cek maksimum diskon
        if (
            $coupon['max_discount'] !== null &&
            $discount > (float) $coupon['max_discount']
        ) {
            $discount = (float) $coupon['max_discount'];
        }

        // Pastikan diskon tidak melebihi subtotal
        $discount = min($discount, $subtotal);

        $afterDiscount = $subtotal - $discount;

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Kupon berhasil digunakan',
            'data' => [
                'coupon' => [
                    'id' => (int) $coupon['id'],
                    'code' => $coupon['code'],
                    'discount_type' => $coupon['discount_type'],
                    'discount_value' => $coupon['discount_value']
                ],

                'subtotal' => number_format(
                    $subtotal,
                    2,
                    '.',
                    ''
                ),

                'discount' => number_format(
                    $discount,
                    2,
                    '.',
                    ''
                ),

                'after_discount' => number_format(
                    $afterDiscount,
                    2,
                    '.',
                    ''
                )
            ]
        ];
    }
}