<?php
// controller/admin/coupon/CouponController.php

require_once __DIR__ . '/../../../models/admin/coupon/Coupon.php';

class CouponController
{
    private $couponModel;

    public function __construct()
    {
        $this->couponModel = new Coupon();
    }


    /**
     * =========================
     * GET ALL COUPONS
     * =========================
     */
    public function getAll()
    {
        $coupons = $this->couponModel->getAll();

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data voucher berhasil diambil',
            'data' => $coupons
        ];
    }


    /**
     * =========================
     * GET COUPON BY ID
     * =========================
     */
    public function getById($id)
    {
        if (empty($id) || !is_numeric($id)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'ID voucher tidak valid'
            ];
        }

        $coupon = $this->couponModel->getById($id);

        if (!$coupon) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Voucher tidak ditemukan'
            ];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data voucher berhasil diambil',
            'data' => $coupon
        ];
    }


    /**
     * =========================
     * CREATE COUPON
     * =========================
     */
    public function create($data)
    {
        // =========================
        // CODE
        // =========================

        $code = strtoupper(
            trim($data['code'] ?? '')
        );

        if ($code === '') {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Kode voucher wajib diisi'
            ];
        }

        // Hanya huruf, angka dan underscore
        if (!preg_match('/^[A-Z0-9_-]+$/', $code)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Kode voucher hanya boleh menggunakan huruf, angka, tanda - atau _'
            ];
        }


        // =========================
        // CEK CODE DUPLIKAT
        // =========================

        $existing =
            $this->couponModel->getByCode($code);

        if ($existing) {
            return [
                'status' => 'error',
                'code' => 409,
                'message' =>
                    'Kode voucher sudah digunakan'
            ];
        }


        // =========================
        // DISCOUNT TYPE
        // =========================

        $discountType =
            $data['discount_type'] ?? '';

        if (
            !in_array(
                $discountType,
                ['percentage', 'fixed'],
                true
            )
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Tipe diskon harus percentage atau fixed'
            ];
        }


        // =========================
        // DISCOUNT VALUE
        // =========================

        $discountValue =
            isset($data['discount_value'])
                ? (float) $data['discount_value']
                : 0;

        if ($discountValue <= 0) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Nilai diskon harus lebih besar dari 0'
            ];
        }


        // Persentase maksimal 100%
        if (
            $discountType === 'percentage' &&
            $discountValue > 100
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Diskon persentase tidak boleh lebih dari 100%'
            ];
        }


        // =========================
        // MINIMUM PURCHASE
        // =========================

        $minimumPurchase =
            isset($data['minimum_purchase']) &&
            $data['minimum_purchase'] !== ''
                ? (float) $data['minimum_purchase']
                : 0;

        if ($minimumPurchase < 0) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Minimum pembelian tidak valid'
            ];
        }


        // =========================
        // MAXIMUM DISCOUNT
        // =========================

        $maximumDiscount =
            isset($data['maximum_discount']) &&
            $data['maximum_discount'] !== ''
                ? (float) $data['maximum_discount']
                : null;

        if (
            $maximumDiscount !== null &&
            $maximumDiscount < 0
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Maksimum diskon tidak valid'
            ];
        }


        // =========================
        // DATE
        // =========================

        $startDate =
            !empty($data['start_date'])
                ? $data['start_date']
                : null;

        $endDate =
            !empty($data['end_date'])
                ? $data['end_date']
                : null;


        // Cek tanggal
        if (
            $startDate &&
            !strtotime($startDate)
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Format tanggal mulai tidak valid'
            ];
        }

        if (
            $endDate &&
            !strtotime($endDate)
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Format tanggal berakhir tidak valid'
            ];
        }


        // Tanggal mulai harus <= tanggal akhir
        if (
            $startDate &&
            $endDate &&
            strtotime($startDate) > strtotime($endDate)
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Tanggal mulai tidak boleh lebih besar dari tanggal berakhir'
            ];
        }


        // =========================
        // USAGE LIMIT
        // =========================

        $usageLimit =
            isset($data['usage_limit']) &&
            $data['usage_limit'] !== ''
                ? (int) $data['usage_limit']
                : null;

        if (
            $usageLimit !== null &&
            $usageLimit < 1
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Batas penggunaan minimal 1'
            ];
        }


        // =========================
        // STATUS
        // =========================

        $status =
            $data['status'] ?? 'active';

        if (
            !in_array(
                $status,
                ['active', 'inactive'],
                true
            )
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Status voucher tidak valid'
            ];
        }


        // =========================
        // SIMPAN
        // =========================

        try {

            $success =
                $this->couponModel->create([
                    'code' =>
                        $code,

                    'discount_type' =>
                        $discountType,

                    'discount_value' =>
                        $discountValue,

                    'minimum_purchase' =>
                        $minimumPurchase,

                    'maximum_discount' =>
                        $maximumDiscount,

                    'start_date' =>
                        $startDate,

                    'end_date' =>
                        $endDate,

                    'usage_limit' =>
                        $usageLimit,

                    'status' =>
                        $status
                ]);

            if (!$success) {
                return [
                    'status' => 'error',
                    'code' => 500,
                    'message' =>
                        'Gagal menyimpan voucher'
                ];
            }

            return [
                'status' => 'success',
                'code' => 201,
                'message' =>
                    'Voucher berhasil ditambahkan'
            ];

        } catch (PDOException $e) {

            return [
                'status' => 'error',
                'code' => 500,
                'message' =>
                    'Terjadi kesalahan database'
            ];
        }
    }


    /**
     * =========================
     * UPDATE COUPON
     * =========================
     */
    public function update($id, $data)
    {
        if (
            empty($id) ||
            !is_numeric($id)
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'ID voucher tidak valid'
            ];
        }

        $existing =
            $this->couponModel->getById($id);

        if (!$existing) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' =>
                    'Voucher tidak ditemukan'
            ];
        }

        $code = strtoupper(
            trim($data['code'] ?? '')
        );

        if ($code === '') {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Kode voucher wajib diisi'
            ];
        }

        if (!preg_match('/^[A-Z0-9_-]+$/', $code)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Format kode voucher tidak valid'
            ];
        }


        // Cek kode digunakan voucher lain
        $sameCode =
            $this->couponModel->getByCode($code);

        if (
            $sameCode &&
            (int) $sameCode['id'] !== (int) $id
        ) {
            return [
                'status' => 'error',
                'code' => 409,
                'message' =>
                    'Kode voucher sudah digunakan'
            ];
        }


        $discountType =
            $data['discount_type'] ?? '';

        if (
            !in_array(
                $discountType,
                ['percentage', 'fixed'],
                true
            )
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Tipe diskon tidak valid'
            ];
        }


        $discountValue =
            (float) (
                $data['discount_value'] ?? 0
            );

        if ($discountValue <= 0) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Nilai diskon harus lebih besar dari 0'
            ];
        }


        if (
            $discountType === 'percentage' &&
            $discountValue > 100
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Diskon persentase maksimal 100%'
            ];
        }


        $minimumPurchase =
            isset($data['minimum_purchase']) &&
            $data['minimum_purchase'] !== ''
                ? (float) $data['minimum_purchase']
                : 0;


        $maximumDiscount =
            isset($data['maximum_discount']) &&
            $data['maximum_discount'] !== ''
                ? (float) $data['maximum_discount']
                : null;


        $startDate =
            !empty($data['start_date'])
                ? $data['start_date']
                : null;


        $endDate =
            !empty($data['end_date'])
                ? $data['end_date']
                : null;


        if (
            $startDate &&
            $endDate &&
            strtotime($startDate) > strtotime($endDate)
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Tanggal mulai tidak boleh lebih besar dari tanggal berakhir'
            ];
        }


        $usageLimit =
            isset($data['usage_limit']) &&
            $data['usage_limit'] !== ''
                ? (int) $data['usage_limit']
                : null;


        if (
            $usageLimit !== null &&
            $usageLimit < 1
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Batas penggunaan tidak valid'
            ];
        }


        $status =
            $data['status'] ?? 'active';

        if (
            !in_array(
                $status,
                ['active', 'inactive'],
                true
            )
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Status voucher tidak valid'
            ];
        }


        try {

            $success =
                $this->couponModel->update(
                    $id,
                    [
                        'code' =>
                            $code,

                        'discount_type' =>
                            $discountType,

                        'discount_value' =>
                            $discountValue,

                        'minimum_purchase' =>
                            $minimumPurchase,

                        'maximum_discount' =>
                            $maximumDiscount,

                        'start_date' =>
                            $startDate,

                        'end_date' =>
                            $endDate,

                        'usage_limit' =>
                            $usageLimit,

                        'status' =>
                            $status
                    ]
                );


            if (!$success) {
                return [
                    'status' => 'error',
                    'code' => 500,
                    'message' =>
                        'Gagal memperbarui voucher'
                ];
            }


            return [
                'status' => 'success',
                'code' => 200,
                'message' =>
                    'Voucher berhasil diperbarui'
            ];

        } catch (PDOException $e) {

            return [
                'status' => 'error',
                'code' => 500,
                'message' =>
                    'Terjadi kesalahan database'
            ];
        }
    }


    /**
     * =========================
     * DEACTIVATE
     * =========================
     */
    public function deactivate($id)
    {
        if (
            empty($id) ||
            !is_numeric($id)
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'ID voucher tidak valid'
            ];
        }

        $coupon =
            $this->couponModel->getById($id);

        if (!$coupon) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' =>
                    'Voucher tidak ditemukan'
            ];
        }

        $success =
            $this->couponModel->deactivate($id);

        if (!$success) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' =>
                    'Gagal menonaktifkan voucher'
            ];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'message' =>
                'Voucher berhasil dinonaktifkan'
        ];
    }


    /**
     * =========================
     * TOGGLE STATUS
     * =========================
     */
    public function toggleStatus($id)
    {
        if (
            empty($id) ||
            !is_numeric($id)
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'ID voucher tidak valid'
            ];
        }

        $coupon =
            $this->couponModel->getById($id);

        if (!$coupon) {
            return [
                'status' => 'error',
                'code' => 404,
                'message' =>
                    'Voucher tidak ditemukan'
            ];
        }

        $success =
            $this->couponModel->toggleStatus($id);

        if (!$success) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' =>
                    'Gagal mengubah status voucher'
            ];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'message' =>
                'Status voucher berhasil diubah'
        ];
    }
}