<?php
// controller/admin/dashboard/DashboardController.php

require_once __DIR__ . '/../../../models/admin/dashboard/Dashboard.php';

class DashboardController
{
    private Dashboard $dashboardModel;

    public function __construct()
    {
        $this->dashboardModel = new Dashboard();
    }


    /**
     * =========================
     * GET DASHBOARD
     * =========================
     */
    public function getDashboard(): array
    {
        try {

            $data =
                $this->dashboardModel
                    ->getDashboardData();

            return [
                'status' => 'success',
                'code' => 200,
                'message' =>
                    'Data dashboard admin berhasil diambil',
                'data' => $data
            ];

        } catch (Throwable $e) {

            error_log(
                'Dashboard error: ' .
                $e->getMessage()
            );

            return [
                'status' => 'error',
                'code' => 500,
                'message' =>
                    'Gagal mengambil data dashboard'
            ];
        }
    }
}