<?php
// controller/dashboardusers/order/OrderController.php

require_once __DIR__ . '/../../../models/dashboardusers/order/Order.php';
require_once __DIR__ . '/../../../models/dashboardusers/cart/Cart.php';
require_once __DIR__ . '/../../../models/dashboardusers/coupon/Coupon.php';

class OrderController
{
    private $orderModel;
    private $cartModel;
    private $couponModel;


    /**
     * =====================================================
     * CONSTRUCTOR
     * =====================================================
     */
    public function __construct()
    {
        $this->orderModel =
            new Order();

        $this->cartModel =
            new Cart();

        $this->couponModel =
            new Coupon();
    }


    /**
     * =====================================================
     * CREATE ORDER
     * =====================================================
     */
    public function createOrder(
        $userId,
        $couponCode = null
    ) {

        /**
         * =================================================
         * VALIDASI USER
         * =================================================
         */
        if (empty($userId)) {

            return [
                'status' => 'error',
                'code' => 401,
                'message' =>
                    'User belum login'
            ];
        }


        /**
         * =================================================
         * AMBIL CART AKTIF USER
         * =================================================
         */
        $cart =
            $this->cartModel
                ->getActiveCartByUser(
                    $userId
                );


        if (empty($cart)) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Keranjang kosong'
            ];
        }


        /**
         * =================================================
         * HITUNG SUBTOTAL
         * =================================================
         */
        $subtotal = 0;


        foreach ($cart as $item) {

            $subtotal +=
                (float) (
                    $item['price'] ?? 0
                );
        }


        /**
         * =================================================
         * VALIDASI SUBTOTAL
         * =================================================
         */
        if ($subtotal < 0) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Subtotal tidak valid'
            ];
        }


        /**
         * =================================================
         * VOUCHER
         * =================================================
         */
        $coupon = null;

        $discount = 0;


        if (
            !empty($couponCode)
        ) {

            $couponCode =
                strtoupper(
                    trim(
                        $couponCode
                    )
                );


            /**
             * Ambil voucher aktif
             */
            $coupon =
                $this->couponModel
                    ->getValidCoupon(
                        $couponCode
                    );


            if (!$coupon) {

                return [
                    'status' => 'error',
                    'code' => 404,
                    'message' =>
                        'Kode kupon tidak ditemukan atau tidak aktif'
                ];
            }


            /**
             * ---------------------------------------------
             * TANGGAL MULAI
             * ---------------------------------------------
             */
            if (
                !empty(
                    $coupon['start_date']
                ) &&
                strtotime(
                    $coupon['start_date']
                ) > time()
            ) {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Kupon belum dapat digunakan'
                ];
            }


            /**
             * ---------------------------------------------
             * TANGGAL BERAKHIR
             * ---------------------------------------------
             */
            if (
                !empty(
                    $coupon['end_date']
                ) &&
                strtotime(
                    $coupon['end_date']
                ) < time()
            ) {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Kupon sudah kedaluwarsa'
                ];
            }


            /**
             * ---------------------------------------------
             * USAGE LIMIT
             * ---------------------------------------------
             */
            if (
                $coupon['usage_limit'] !== null &&
                (int) $coupon['used_count'] >=
                (int) $coupon['usage_limit']
            ) {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Kuota penggunaan kupon sudah habis'
                ];
            }


            /**
             * ---------------------------------------------
             * MINIMUM PEMBELIAN
             * ---------------------------------------------
             */
            $minimumPurchase =
                (float) (
                    $coupon['min_purchase']
                    ?? 0
                );


            if (
                $subtotal <
                $minimumPurchase
            ) {

                return [
                    'status' => 'error',
                    'code' => 400,
                    'message' =>
                        'Minimum pembelian untuk kupon ini adalah Rp ' .
                        number_format(
                            $minimumPurchase,
                            0,
                            ',',
                            '.'
                        )
                ];
            }


            /**
             * ---------------------------------------------
             * HITUNG DISKON
             * ---------------------------------------------
             */
            $discountType =
                $coupon['discount_type']
                ?? 'percentage';


            $discountValue =
                (float) (
                    $coupon['discount_value']
                    ?? 0
                );


            if (
                $discountType ===
                'percentage'
            ) {

                $discount =
                    $subtotal *
                    (
                        $discountValue /
                        100
                    );

            } else {

                $discount =
                    $discountValue;
            }


            /**
             * ---------------------------------------------
             * MAX DISCOUNT
             * ---------------------------------------------
             */
            if (
                $coupon['max_discount'] !== null &&
                $coupon['max_discount'] !== ''
            ) {

                $maxDiscount =
                    (float) (
                        $coupon['max_discount']
                    );


                if (
                    $discount >
                    $maxDiscount
                ) {

                    $discount =
                        $maxDiscount;
                }
            }


            /**
             * ---------------------------------------------
             * DISCOUNT MAKSIMAL SUBTOTAL
             * ---------------------------------------------
             */
            $discount =
                min(
                    $discount,
                    $subtotal
                );


            if ($discount < 0) {
                $discount = 0;
            }
        }


        /**
         * =================================================
         * SUBTOTAL SETELAH DISKON
         * =================================================
         */
        $afterDiscount =
            max(
                $subtotal -
                $discount,
                0
            );


        /**
         * =================================================
         * PPN 11%
         *
         * PPN dihitung setelah diskon.
         * =================================================
         */
        $ppn =
            $afterDiscount *
            0.11;


        /**
         * =================================================
         * TOTAL
         * =================================================
         */
        $total =
            $afterDiscount +
            $ppn;


        /**
         * =================================================
         * NOMOR ORDER
         * =================================================
         */
        $orderNumber =
            'ORD-' .
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
         * TANGGAL LAYANAN
         *
         * Service belum aktif saat order dibuat.
         * =================================================
         */
        $startDate = null;

        $expiryDate = null;


        /**
         * =================================================
         * SIMPAN ORDER
         *
         * Status order = pending
         * =================================================
         */
        $orderId =
            $this->orderModel
                ->createOrder(
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
                'message' =>
                    'Gagal membuat order'
            ];
        }


        /**
         * =================================================
         * SIMPAN ORDER ITEM
         * =================================================
         */
        foreach ($cart as $item) {

            $serviceCategory =
                strtolower(
                    trim(
                        $item['service_category']
                        ?? 'website'
                    )
                );


            $billingPeriod =
                strtolower(
                    trim(
                        $item['billing_period']
                        ?? 'yearly'
                    )
                );


            $result =
                $this->orderModel
                    ->addOrderItem(
                        $orderId,
                        $item['domain_id'] ?? 0,
                        $item['domain_name'] ?? '',
                        $serviceCategory,
                        $billingPeriod,
                        $startDate,
                        $expiryDate,
                        $item['price'] ?? 0
                    );


            if (!$result) {

                return [
                    'status' => 'error',
                    'code' => 500,
                    'message' =>
                        'Gagal menyimpan item order'
                ];
            }
        }


        /**
         * =================================================
         * RESPONSE
         * =================================================
         */
        return [

            'status' =>
                'success',

            'code' =>
                201,

            'message' =>
                'Order berhasil dibuat dan menunggu pembayaran',

            'data' => [

                'order_id' =>
                    (int) $orderId,

                'order_number' =>
                    $orderNumber,

                'subtotal' =>
                    number_format(
                        $subtotal,
                        2,
                        '.',
                        ''
                    ),

                'discount' =>
                    number_format(
                        $discount,
                        2,
                        '.',
                        ''
                    ),

                'after_discount' =>
                    number_format(
                        $afterDiscount,
                        2,
                        '.',
                        ''
                    ),

                'ppn' =>
                    number_format(
                        $ppn,
                        2,
                        '.',
                        ''
                    ),

                'total' =>
                    number_format(
                        $total,
                        2,
                        '.',
                        ''
                    ),

                'status' =>
                    'pending',

                'service_status' =>
                    'pending',

                'start_date' =>
                    null,

                'expiry_date' =>
                    null,

                'coupon' =>
                    $coupon
                        ? [
                            'id' =>
                                (int) $coupon['id'],

                            'code' =>
                                $coupon['code']
                        ]
                        : null
            ]
        ];
    }


    /**
     * =====================================================
     * HITUNG TANGGAL EXPIRED
     *
     * Dipakai nanti setelah payment dikonfirmasi admin.
     * =====================================================
     */
    private function calculateExpiryDate(
        $startDate,
        $billingPeriod
    ) {

        try {

            $date =
                new DateTime(
                    $startDate
                );

        } catch (Exception $e) {

            $date =
                new DateTime();
        }


        switch (
            strtolower(
                trim(
                    $billingPeriod
                )
            )
        ) {

            case 'weekly':

                $date->modify(
                    '+7 days'
                );

                break;


            case 'monthly':

                $date->modify(
                    '+1 month'
                );

                break;


            case 'yearly':

                $date->modify(
                    '+1 year'
                );

                break;


            default:

                $date->modify(
                    '+1 year'
                );

                break;
        }


        return $date->format(
            'Y-m-d H:i:s'
        );
    }


    /**
     * =====================================================
     * GET SEMUA ORDER USER
     * =====================================================
     */
    public function getOrders(
        $userId
    ) {

        if (empty($userId)) {

            return [
                'status' => 'error',
                'code' => 401,
                'message' =>
                    'User belum login'
            ];
        }


        $orders =
            $this->orderModel
                ->getOrdersByUser(
                    $userId
                );


        return [
            'status' => 'success',
            'code' => 200,
            'message' =>
                'Data order berhasil diambil',

            'data' => [
                'orders' =>
                    $orders,

                'total' =>
                    count($orders)
            ]
        ];
    }


    /**
     * =====================================================
     * GET SEMUA SERVICE USER
     * =====================================================
     */
    public function getServices(
        $userId
    ) {

        if (empty($userId)) {

            return [
                'status' => 'error',
                'code' => 401,
                'message' =>
                    'User belum login'
            ];
        }


        $services =
            $this->orderModel
                ->getServicesByUser(
                    $userId
                );


        return [
            'status' => 'success',
            'code' => 200,
            'message' =>
                'Data layanan berhasil diambil',

            'data' => [
                'services' =>
                    $services,

                'total' =>
                    count($services)
            ]
        ];
    }


    /**
     * =====================================================
     * GET DETAIL ORDER
     * =====================================================
     */
    public function getOrderDetail(
        $orderId,
        $userId
    ) {

        if (
            empty($orderId) ||
            empty($userId)
        ) {

            return [
                'status' => 'error',
                'code' => 400,
                'message' =>
                    'Order ID dan User ID wajib diisi'
            ];
        }


        $order =
            $this->orderModel
                ->findById(
                    $orderId,
                    $userId
                );


        if (!$order) {

            return [
                'status' => 'error',
                'code' => 404,
                'message' =>
                    'Order tidak ditemukan'
            ];
        }


        $items =
            $this->orderModel
                ->getOrderItems(
                    $orderId
                );


        return [
            'status' => 'success',
            'code' => 200,
            'message' =>
                'Detail order berhasil diambil',

            'data' => [
                'order' =>
                    $order,

                'items' =>
                    $items
            ]
        ];
    }
}