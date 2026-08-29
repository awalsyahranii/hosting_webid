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
        try {

            $coupons = $this->couponModel->getAll();

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Data voucher berhasil diambil',
                'data' => $coupons
            ];

        } catch (PDOException $e) {

            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Terjadi kesalahan database'
            ];
        }
    }


    /**
     * =========================
     * GET COUPON BY ID
     * =========================
     */
    public function getById($id)
    {
        if (
            empty($id) ||
            !is_numeric($id) ||
            (int) $id <= 0
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'ID voucher tidak valid'
            ];
        }

        try {

            $coupon =
                $this->couponModel->getById((int) $id);

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

        } catch (PDOException $e) {

            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Terjadi kesalahan database'
            ];
        }
    }


    /**
     * =========================
     * CREATE COUPON
     * =========================
     */
    public function create($data)
    {
        /**
         * CODE
         */
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

        if (!preg_match('/^[A-Z0-9_-]+$/', $code)) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Kode voucher hanya boleh menggunakan huruf, angka, tanda - atau _'
            ];
        }


        /**
         * CEK DUPLIKAT
         */
        try {

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

        } catch (PDOException $e) {

            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Terjadi kesalahan database'
            ];
        }


        /**
         * DISCOUNT TYPE
         */
        $discountType =
            $data['discount_type'] ?? '';

        if (!in_array(
            $discountType,
            ['percentage', 'fixed'],
            true
        )) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Tipe diskon harus percentage atau fixed'
            ];
        }


        /**
         * DISCOUNT VALUE
         */
        $discountValue =
            isset($data['discount_value']) &&
            $data['discount_value'] !== ''
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


        /**
         * MIN PURCHASE
         */
        $minPurchase =
            isset($data['min_purchase']) &&
            $data['min_purchase'] !== ''
                ? (float) $data['min_purchase']
                : 0;

        if ($minPurchase < 0) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Minimum pembelian tidak valid'
            ];
        }


        /**
         * MAX DISCOUNT
         */
        $maxDiscount =
            isset($data['max_discount']) &&
            $data['max_discount'] !== ''
                ? (float) $data['max_discount']
                : null;

        if (
            $maxDiscount !== null &&
            $maxDiscount < 0
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Maksimum diskon tidak valid'
            ];
        }


        /**
         * DATE
         */
        $startDate =
            !empty($data['start_date'])
                ? trim($data['start_date'])
                : null;

        $endDate =
            !empty($data['end_date'])
                ? trim($data['end_date'])
                : null;


        if (
            $startDate !== null &&
            strtotime($startDate) === false
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Format tanggal mulai tidak valid'
            ];
        }


        if (
            $endDate !== null &&
            strtotime($endDate) === false
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Format tanggal berakhir tidak valid'
            ];
        }


        if (
            $startDate !== null &&
            $endDate !== null &&
            strtotime($startDate) > strtotime($endDate)
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Tanggal mulai tidak boleh lebih besar dari tanggal berakhir'
            ];
        }


        /**
         * USAGE LIMIT
         */
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


        /**
         * STATUS
         */
        $status =
            $data['status'] ?? 'active';

        if (!in_array(
            $status,
            ['active', 'inactive'],
            true
        )) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Status voucher tidak valid'
            ];
        }


        /**
         * SIMPAN
         */
        try {

            $success =
                $this->couponModel->create([
                    'code' =>
                        $code,

                    'discount_type' =>
                        $discountType,

                    'discount_value' =>
                        $discountValue,

                    'min_purchase' =>
                        $minPurchase,

                    'max_discount' =>
                        $maxDiscount,

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
        /**
         * VALIDASI ID
         */
        if (
            empty($id) ||
            !is_numeric($id) ||
            (int) $id <= 0
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'ID voucher tidak valid'
            ];
        }

        $id = (int) $id;


        try {

            /**
             * CEK VOUCHER
             */
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


            /**
             * CODE
             */
            $code =
                strtoupper(
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


            if (!preg_match(
                '/^[A-Z0-9_-]+$/',
                $code
            )) {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Format kode voucher tidak valid'
                ];
            }


            /**
             * CEK DUPLIKAT
             *
             * Penting:
             * Voucher yang sedang diedit
             * tidak dianggap duplikat.
             */
            $sameCode =
                $this->couponModel->getByCode($code);

            if (
                $sameCode &&
                (int) $sameCode['id'] !== $id
            ) {

                return [
                    'status' => 'error',
                    'code' => 409,
                    'message' =>
                        'Kode voucher sudah digunakan'
                ];
            }


            /**
             * DISCOUNT TYPE
             */
            $discountType =
                $data['discount_type'] ?? '';

            if (!in_array(
                $discountType,
                ['percentage', 'fixed'],
                true
            )) {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Tipe diskon harus percentage atau fixed'
                ];
            }


            /**
             * DISCOUNT VALUE
             */
            $discountValue =
                isset($data['discount_value']) &&
                $data['discount_value'] !== ''
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


            /**
             * MIN PURCHASE
             */
            $minPurchase =
                isset($data['min_purchase']) &&
                $data['min_purchase'] !== ''
                    ? (float) $data['min_purchase']
                    : 0;

            if ($minPurchase < 0) {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Minimum pembelian tidak valid'
                ];
            }


            /**
             * MAX DISCOUNT
             */
            $maxDiscount =
                isset($data['max_discount']) &&
                $data['max_discount'] !== ''
                    ? (float) $data['max_discount']
                    : null;

            if (
                $maxDiscount !== null &&
                $maxDiscount < 0
            ) {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Maksimum diskon tidak valid'
                ];
            }


            /**
             * DATE
             */
            $startDate =
                !empty($data['start_date'])
                    ? trim($data['start_date'])
                    : null;

            $endDate =
                !empty($data['end_date'])
                    ? trim($data['end_date'])
                    : null;


            if (
                $startDate !== null &&
                strtotime($startDate) === false
            ) {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Format tanggal mulai tidak valid'
                ];
            }


            if (
                $endDate !== null &&
                strtotime($endDate) === false
            ) {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Format tanggal berakhir tidak valid'
                ];
            }


            if (
                $startDate !== null &&
                $endDate !== null &&
                strtotime($startDate) > strtotime($endDate)
            ) {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Tanggal mulai tidak boleh lebih besar dari tanggal berakhir'
                ];
            }


            /**
             * USAGE LIMIT
             */
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


            /**
             * STATUS
             */
            $status =
                $data['status'] ?? 'active';

            if (!in_array(
                $status,
                ['active', 'inactive'],
                true
            )) {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Status voucher tidak valid'
                ];
            }


            /**
             * UPDATE
             */
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

                        'min_purchase' =>
                            $minPurchase,

                        'max_discount' =>
                            $maxDiscount,

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
            !is_numeric($id) ||
            (int) $id <= 0
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'ID voucher tidak valid'
            ];
        }

        $id = (int) $id;


        try {

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


            if ($coupon['status'] === 'inactive') {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Voucher sudah dalam status inactive'
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
     * TOGGLE STATUS
     * =========================
     */
    public function toggleStatus($id)
    {
        if (
            empty($id) ||
            !is_numeric($id) ||
            (int) $id <= 0
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'ID voucher tidak valid'
            ];
        }

        $id = (int) $id;


        try {

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


            $newStatus =
                $coupon['status'] === 'active'
                    ? 'inactive'
                    : 'active';


            return [
                'status' => 'success',
                'code' => 200,
                'message' =>
                    'Status voucher berhasil diubah',
                'data' => [
                    'id' => $id,
                    'status' => $newStatus
                ]
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
}