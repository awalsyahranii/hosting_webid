<?php
// models/admin/dashboard/Dashboard.php

require_once __DIR__ . '/../../../service/database/db.php';

class Dashboard
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * =========================================================
     * CEK APAKAH TABLE ADA
     * =========================================================
     */
    private function tableExists($table)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = :table
        ");

        $stmt->execute([
            ':table' => $table
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }


    /**
     * =========================================================
     * CEK APAKAH COLUMN ADA
     * =========================================================
     */
    private function columnExists($table, $column)
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
            AND table_name = :table
            AND column_name = :column
        ");

        $stmt->execute([
            ':table'  => $table,
            ':column' => $column
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }


    /**
     * =========================================================
     * CARI TABLE PERTAMA YANG TERSEDIA
     * =========================================================
     */
    private function findTable(array $tables)
    {
        foreach ($tables as $table) {
            if ($this->tableExists($table)) {
                return $table;
            }
        }

        return null;
    }


    /**
     * =========================================================
     * TOTAL USERS
     * =========================================================
     */
    public function getTotalUsers()
    {
        if (!$this->tableExists('users')) {
            return 0;
        }

        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM users
        ");

        return (int) $stmt->fetchColumn();
    }


    /**
     * =========================================================
     * TOTAL DOMAINS
     * =========================================================
     */
    public function getTotalDomains()
    {
        if (!$this->tableExists('domains')) {
            return 0;
        }

        $stmt = $this->db->query("
            SELECT COUNT(*)
            FROM domains
        ");

        return (int) $stmt->fetchColumn();
    }


    /**
     * =========================================================
     * TOTAL ORDERS
     * =========================================================
     */
    public function getTotalOrders()
    {
        if (!$this->tableExists('orders')) {
            return 0;
        }

        $stmt = $this->db->query("
            SELECT COUNT(*)
            FROM orders
        ");

        return (int) $stmt->fetchColumn();
    }


    /**
     * =========================================================
     * TOTAL INVOICES
     * =========================================================
     */
    public function getTotalInvoices()
    {
        if (!$this->tableExists('invoices')) {
            return 0;
        }

        $stmt = $this->db->query("
            SELECT COUNT(*)
            FROM invoices
        ");

        return (int) $stmt->fetchColumn();
    }


    /**
     * =========================================================
     * TOTAL REVENUE
     *
     * Prioritas:
     * 1. payments
     * 2. invoices dengan status paid
     * 3. orders dengan status completed/paid
     * =========================================================
     */
    public function getTotalRevenue()
    {
        // -----------------------------------------------------
        // PAYMENT
        // -----------------------------------------------------

        if ($this->tableExists('payments')) {

            $amountColumn = null;

            foreach ([
                'amount',
                'total',
                'payment_amount',
                'paid_amount'
            ] as $column) {

                if ($this->columnExists(
                    'payments',
                    $column
                )) {
                    $amountColumn = $column;
                    break;
                }
            }

            if ($amountColumn !== null) {

                $statusCondition = '';

                if ($this->columnExists(
                    'payments',
                    'status'
                )) {

                    $statusCondition = "
                        WHERE LOWER(status) IN (
                            'paid',
                            'success',
                            'completed',
                            'completed_payment'
                        )
                    ";
                }

                $stmt = $this->db->query("
                    SELECT COALESCE(
                        SUM($amountColumn),
                        0
                    )
                    FROM payments
                    $statusCondition
                ");

                return (float) $stmt->fetchColumn();
            }
        }


        // -----------------------------------------------------
        // INVOICE
        // -----------------------------------------------------

        if ($this->tableExists('invoices')) {

            $amountColumn = null;

            foreach ([
                'total',
                'total_amount',
                'amount',
                'grand_total'
            ] as $column) {

                if ($this->columnExists(
                    'invoices',
                    $column
                )) {
                    $amountColumn = $column;
                    break;
                }
            }

            if ($amountColumn !== null) {

                $statusCondition = '';

                if ($this->columnExists(
                    'invoices',
                    'status'
                )) {

                    $statusCondition = "
                        WHERE LOWER(status) IN (
                            'paid',
                            'success',
                            'completed'
                        )
                    ";
                }

                $stmt = $this->db->query("
                    SELECT COALESCE(
                        SUM($amountColumn),
                        0
                    )
                    FROM invoices
                    $statusCondition
                ");

                return (float) $stmt->fetchColumn();
            }
        }


        // -----------------------------------------------------
        // ORDERS
        // -----------------------------------------------------

        if ($this->tableExists('orders')) {

            $amountColumn = null;

            foreach ([
                'amount',
                'total',
                'total_amount',
                'grand_total'
            ] as $column) {

                if ($this->columnExists(
                    'orders',
                    $column
                )) {
                    $amountColumn = $column;
                    break;
                }
            }

            if ($amountColumn !== null) {

                $statusCondition = '';

                if ($this->columnExists(
                    'orders',
                    'status'
                )) {

                    $statusCondition = "
                        WHERE LOWER(status) IN (
                            'paid',
                            'success',
                            'completed'
                        )
                    ";
                }

                $stmt = $this->db->query("
                    SELECT COALESCE(
                        SUM($amountColumn),
                        0
                    )
                    FROM orders
                    $statusCondition
                ");

                return (float) $stmt->fetchColumn();
            }
        }

        return 0;
    }


    /**
     * =========================================================
     * ACTIVE HOSTING
     * =========================================================
     */
    public function getActiveHosting()
    {
        $table = $this->findTable([
            'hostings',
            'hosting',
            'hosting_services',
            'services'
        ]);

        if (!$table) {
            return 0;
        }

        if (!$this->columnExists(
            $table,
            'status'
        )) {
            return 0;
        }

        $stmt = $this->db->query("
            SELECT COUNT(*)
            FROM `$table`
            WHERE LOWER(status) IN (
                'active',
                'aktif'
            )
        ");

        return (int) $stmt->fetchColumn();
    }


    /**
     * =========================================================
     * ACTIVE DOMAINS
     * =========================================================
     */
    public function getActiveDomains()
    {
        if (!$this->tableExists('domains')) {
            return 0;
        }

        if (!$this->columnExists(
            'domains',
            'status'
        )) {

            return 0;
        }

        $stmt = $this->db->query("
            SELECT COUNT(*)
            FROM domains
            WHERE LOWER(status) IN (
                'active',
                'aktif'
            )
        ");

        return (int) $stmt->fetchColumn();
    }


    /**
     * =========================================================
     * NEW TICKETS
     * =========================================================
     */
    public function getNewTickets()
    {
        $table = $this->findTable([
            'tickets',
            'ticket',
            'support_tickets'
        ]);

        if (!$table) {
            return 0;
        }

        if (!$this->columnExists(
            $table,
            'status'
        )) {
            return 0;
        }

        $stmt = $this->db->query("
            SELECT COUNT(*)
            FROM `$table`
            WHERE LOWER(status) IN (
                'open',
                'new',
                'pending',
                'baru'
            )
        ");

        return (int) $stmt->fetchColumn();
    }


    /**
     * =========================================================
     * REVENUE OVERVIEW 7 HARI
     * =========================================================
     */
    public function getRevenueOverview()
    {
        $result = [];

        for ($i = 6; $i >= 0; $i--) {

            $date = date(
                'Y-m-d',
                strtotime("-$i days")
            );

            $revenue = $this->getRevenueByDate(
                $date
            );

            $result[] = [
                'date' => $date,
                'label' => date(
                    'D',
                    strtotime($date)
                ),
                'revenue' => (float) $revenue
            ];
        }

        return $result;
    }


    /**
     * =========================================================
     * REVENUE PER TANGGAL
     * =========================================================
     */
    private function getRevenueByDate($date)
    {
        // -----------------------------------------------------
        // PAYMENT
        // -----------------------------------------------------

        if ($this->tableExists('payments')) {

            $amountColumn = null;

            foreach ([
                'amount',
                'total',
                'payment_amount',
                'paid_amount'
            ] as $column) {

                if ($this->columnExists(
                    'payments',
                    $column
                )) {
                    $amountColumn = $column;
                    break;
                }
            }

            if ($amountColumn !== null) {

                $dateColumn = 'created_at';

                foreach ([
                    'paid_at',
                    'payment_date',
                    'created_at'
                ] as $column) {

                    if ($this->columnExists(
                        'payments',
                        $column
                    )) {
                        $dateColumn = $column;
                        break;
                    }
                }

                $statusCondition = '';

                if ($this->columnExists(
                    'payments',
                    'status'
                )) {

                    $statusCondition = "
                        AND LOWER(status) IN (
                            'paid',
                            'success',
                            'completed'
                        )
                    ";
                }

                $stmt = $this->db->prepare("
                    SELECT COALESCE(
                        SUM($amountColumn),
                        0
                    )
                    FROM payments
                    WHERE DATE($dateColumn) = :date
                    $statusCondition
                ");

                $stmt->execute([
                    ':date' => $date
                ]);

                return (float) $stmt->fetchColumn();
            }
        }


        // -----------------------------------------------------
        // INVOICE
        // -----------------------------------------------------

        if ($this->tableExists('invoices')) {

            $amountColumn = null;

            foreach ([
                'total',
                'total_amount',
                'amount',
                'grand_total'
            ] as $column) {

                if ($this->columnExists(
                    'invoices',
                    $column
                )) {
                    $amountColumn = $column;
                    break;
                }
            }

            if ($amountColumn !== null) {

                $stmt = $this->db->prepare("
                    SELECT COALESCE(
                        SUM($amountColumn),
                        0
                    )
                    FROM invoices
                    WHERE DATE(created_at) = :date
                    AND LOWER(status) IN (
                        'paid',
                        'success',
                        'completed'
                    )
                ");

                $stmt->execute([
                    ':date' => $date
                ]);

                return (float) $stmt->fetchColumn();
            }
        }

        return 0;
    }


    /**
     * =========================================================
     * RECENT ORDERS
     * =========================================================
     */
    public function getRecentOrders($limit = 5)
    {
        if (!$this->tableExists('orders')) {
            return [];
        }

        $amountColumn = null;

        foreach ([
            'amount',
            'total',
            'total_amount',
            'grand_total'
        ] as $column) {

            if ($this->columnExists(
                'orders',
                $column
            )) {
                $amountColumn = $column;
                break;
            }
        }

        if ($amountColumn === null) {
            $amountColumn = '0';
        }


        $customerColumn = null;

        foreach ([
            'user_id',
            'customer_id'
        ] as $column) {

            if ($this->columnExists(
                'orders',
                $column
            )) {
                $customerColumn = $column;
                break;
            }
        }


        // -----------------------------------------------------
        // JIKA ADA RELASI USER
        // -----------------------------------------------------

        if (
            $customerColumn !== null &&
            $this->tableExists('users') &&
            $this->columnExists(
                'users',
                'id'
            )
        ) {

            $customerSelect = "
                u.username AS customer
            ";

            $join = "
                LEFT JOIN users u
                    ON u.id = o.$customerColumn
            ";

        } else {

            $customerSelect = "
                o.$customerColumn AS customer
            ";

            $join = '';
        }


        $stmt = $this->db->prepare("
            SELECT
                o.id,
                $customerSelect,
                o.$amountColumn AS amount,
                o.status,
                o.created_at
            FROM orders o
            $join
            ORDER BY o.id DESC
            LIMIT :limit
        ");

        $stmt->bindValue(
            ':limit',
            (int) $limit,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * =========================================================
     * GET ALL DASHBOARD DATA
     * =========================================================
     */
    public function getDashboardData()
    {
        return [

            'total_users' =>
                $this->getTotalUsers(),

            'total_domains' =>
                $this->getTotalDomains(),

            'total_orders' =>
                $this->getTotalOrders(),

            'total_invoices' =>
                $this->getTotalInvoices(),

            'total_revenue' =>
                $this->getTotalRevenue(),

            'active_hosting' =>
                $this->getActiveHosting(),

            'active_domains' =>
                $this->getActiveDomains(),

            'new_tickets' =>
                $this->getNewTickets(),

            'revenue_overview' =>
                $this->getRevenueOverview(),

            'recent_orders' =>
                $this->getRecentOrders(5)
        ];
    }
}