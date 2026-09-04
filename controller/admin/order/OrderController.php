<?php
// controller/admin/order/OrderController.php

require_once __DIR__ . '/../../../models/dashboardusers/order/Order.php';

class AdminOrderController
{
    private $orderModel;

    public function __construct()
    {
        $this->orderModel = new Order();
    }

    /**
     * ==========================================
     * GET SEMUA ORDER
     * ==========================================
     */
    public function getAllOrders()
    {
        $orders = $this->orderModel->getAllOrders();

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data order berhasil diambil',
            'data' => [
                'orders' => $orders,
                'total' => count($orders)
            ]
        ];
    }

    /**
     * ==========================================
     * GET SEMUA SERVICE
     * ==========================================
     */
    public function getAllServices()
    {
        $services = $this->orderModel->getAllServices();

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data layanan berhasil diambil',
            'data' => [
                'services' => $services,
                'total' => count($services)
            ]
        ];
    }

    /**
     * ==========================================
     * GET RINGKASAN SERVICE
     * ==========================================
     */
    public function getServiceSummary()
    {
        $summary = $this->orderModel->getServiceSummary();

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Ringkasan layanan berhasil diambil',
            'data' => [
                'summary' => $summary
            ]
        ];
    }

    /**
 * =====================================================
 * LAPORAN SERVICE
 * BERDASARKAN KATEGORI DAN STATUS
 * =====================================================
 */
public function getServiceReport(
    $category = null,
    $status = null
) {
    /**
     * Validasi kategori
     */
    if (
        !empty($category) &&
        !in_array(
            $category,
            ['website', 'iklan'],
            true
        )
    ) {
        return [
            'status' => 'error',
            'code' => 400,
            'message' => 'Kategori service tidak valid'
        ];
    }


    /**
     * Validasi status
     */
    if (
        !empty($status) &&
        !in_array(
            $status,
            ['active', 'expired', 'cancelled'],
            true
        )
    ) {
        return [
            'status' => 'error',
            'code' => 400,
            'message' => 'Status service tidak valid'
        ];
    }


    $services =
        $this->orderModel->getServiceReport(
            $category,
            $status
        );


    return [
        'status' => 'success',
        'code' => 200,
        'message' =>
            'Laporan service berhasil diambil',

        'data' => [
            'filters' => [
                'category' => $category,
                'status' => $status
            ],

            'services' => $services,

            'total' => count($services)
        ]
    ];
}
}