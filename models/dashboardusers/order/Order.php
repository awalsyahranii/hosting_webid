<?php

// models/dashboardusers/order/Order.php

require_once __DIR__ . '/../../../service/database/db.php';

class Order
{
    private $db;


    /**
     * =====================================================
     * CONSTRUCTOR
     * =====================================================
     */
    public function __construct()
    {
        $this->db =
            Database::getInstance()
                ->getConnection();
    }


    /**
     * =====================================================
     * CREATE ORDER
     * =====================================================
     */
    public function createOrder(
        $userId,
        $orderNumber,
        $subtotal,
        $ppn,
        $discount,
        $total
    ) {
        $stmt = $this->db->prepare("
            INSERT INTO orders (
                user_id,
                order_number,
                subtotal,
                ppn,
                discount,
                total,
                status,
                created_at,
                updated_at
            )
            VALUES (
                :user_id,
                :order_number,
                :subtotal,
                :ppn,
                :discount,
                :total,
                'pending',
                NOW(),
                NOW()
            )
        ");

        $result = $stmt->execute([
            ':user_id' =>
                (int) $userId,

            ':order_number' =>
                $orderNumber,

            ':subtotal' =>
                $subtotal,

            ':ppn' =>
                $ppn,

            ':discount' =>
                $discount,

            ':total' =>
                $total
        ]);


        if (!$result) {
            return false;
        }


        return $this->db->lastInsertId();
    }


    /**
     * =====================================================
     * ADD ORDER ITEM
     *
     * Saat order dibuat:
     * - service_status = pending
     * - start_date = NULL
     * - expiry_date = NULL
     *
     * Service baru menjadi active setelah
     * pembayaran dikonfirmasi oleh admin.
     * =====================================================
     */
    public function addOrderItem(
        $orderId,
        $domainId,
        $domainName,
        $serviceCategory,
        $billingPeriod,
        $startDate,
        $expiryDate,
        $price
    ) {
        $stmt = $this->db->prepare("
            INSERT INTO order_items (
                order_id,
                domain_id,
                domain_name,
                service_category,
                billing_period,
                start_date,
                expiry_date,
                service_status,
                price,
                created_at
            )
            VALUES (
                :order_id,
                :domain_id,
                :domain_name,
                :service_category,
                :billing_period,
                :start_date,
                :expiry_date,
                'pending',
                :price,
                NOW()
            )
        ");


        return $stmt->execute([
            ':order_id' =>
                (int) $orderId,

            ':domain_id' =>
                (int) $domainId,

            ':domain_name' =>
                $domainName,

            ':service_category' =>
                $serviceCategory,

            ':billing_period' =>
                $billingPeriod,

            ':start_date' =>
                $startDate,

            ':expiry_date' =>
                $expiryDate,

            ':price' =>
                $price
        ]);
    }


    /**
     * =====================================================
     * GET SEMUA ORDER USER
     * =====================================================
     */
    public function getOrdersByUser($userId)
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                user_id,
                order_number,
                subtotal,
                ppn,
                discount,
                total,
                status,
                created_at,
                updated_at
            FROM orders
            WHERE user_id = :user_id
            ORDER BY id DESC
        ");


        $stmt->execute([
            ':user_id' =>
                (int) $userId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * =====================================================
     * GET ORDER BY ID
     * =====================================================
     */
    public function findById(
        $orderId,
        $userId
    ) {
        $stmt = $this->db->prepare("
            SELECT
                id,
                user_id,
                order_number,
                subtotal,
                ppn,
                discount,
                total,
                status,
                created_at,
                updated_at
            FROM orders
            WHERE id = :id
              AND user_id = :user_id
            LIMIT 1
        ");


        $stmt->execute([
            ':id' =>
                (int) $orderId,

            ':user_id' =>
                (int) $userId
        ]);


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * =====================================================
     * GET ORDER ITEMS
     * =====================================================
     */
    public function getOrderItems($orderId)
    {
        $this->updateExpiredServices();


        $stmt = $this->db->prepare("
            SELECT
                id,
                order_id,
                domain_id,
                domain_name,
                service_category,
                billing_period,
                start_date,
                expiry_date,
                service_status,
                price,
                created_at
            FROM order_items
            WHERE order_id = :order_id
            ORDER BY id ASC
        ");


        $stmt->execute([
            ':order_id' =>
                (int) $orderId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * =====================================================
     * GET SERVICES BY USER
     * =====================================================
     */
    public function getServicesByUser($userId)
    {
        $this->updateExpiredServices();


        $stmt = $this->db->prepare("
            SELECT
                oi.id,
                oi.order_id,
                o.order_number,
                o.user_id,

                oi.domain_id,
                oi.domain_name,

                oi.service_category,
                oi.billing_period,

                oi.start_date,
                oi.expiry_date,

                oi.service_status,
                oi.price,

                o.status AS order_status,
                o.created_at AS order_date

            FROM order_items oi

            INNER JOIN orders o
                ON o.id = oi.order_id

            WHERE o.user_id = :user_id

            ORDER BY oi.id DESC
        ");


        $stmt->execute([
            ':user_id' =>
                (int) $userId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * =====================================================
     * GET ALL SERVICES UNTUK ADMIN
     * =====================================================
     */
    public function getAllServices()
    {
        $this->updateExpiredServices();


        $stmt = $this->db->prepare("
            SELECT
                oi.id,
                oi.order_id,

                o.order_number,
                o.user_id,

                u.username,
                u.email,

                oi.domain_id,
                oi.domain_name,

                oi.service_category,
                oi.billing_period,

                oi.start_date,
                oi.expiry_date,

                oi.service_status,
                oi.price,

                o.status AS order_status,
                o.created_at AS order_date

            FROM order_items oi

            INNER JOIN orders o
                ON o.id = oi.order_id

            INNER JOIN users u
                ON u.id = o.user_id

            ORDER BY oi.id DESC
        ");


        $stmt->execute();


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * =====================================================
     * GET SERVICE BY ID
     * =====================================================
     */
    public function getServiceById(
        $itemId,
        $userId = null
    ) {
        $this->updateExpiredServices();


        if ($userId !== null) {

            $stmt = $this->db->prepare("
                SELECT
                    oi.id,
                    oi.order_id,
                    o.order_number,
                    o.user_id,

                    oi.domain_id,
                    oi.domain_name,

                    oi.service_category,
                    oi.billing_period,

                    oi.start_date,
                    oi.expiry_date,

                    oi.service_status,
                    oi.price,

                    o.status AS order_status,
                    o.created_at AS order_date

                FROM order_items oi

                INNER JOIN orders o
                    ON o.id = oi.order_id

                WHERE oi.id = :item_id
                  AND o.user_id = :user_id

                LIMIT 1
            ");


            $stmt->execute([
                ':item_id' =>
                    (int) $itemId,

                ':user_id' =>
                    (int) $userId
            ]);

        } else {

            $stmt = $this->db->prepare("
                SELECT
                    oi.id,
                    oi.order_id,
                    o.order_number,
                    o.user_id,

                    oi.domain_id,
                    oi.domain_name,

                    oi.service_category,
                    oi.billing_period,

                    oi.start_date,
                    oi.expiry_date,

                    oi.service_status,
                    oi.price,

                    o.status AS order_status,
                    o.created_at AS order_date

                FROM order_items oi

                INNER JOIN orders o
                    ON o.id = oi.order_id

                WHERE oi.id = :item_id

                LIMIT 1
            ");


            $stmt->execute([
                ':item_id' =>
                    (int) $itemId
            ]);
        }


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * =====================================================
     * UPDATE SERVICE EXPIRED
     *
     * Hanya service yang sudah ACTIVE
     * yang boleh berubah menjadi EXPIRED.
     * =====================================================
     */
    public function updateExpiredServices()
    {
        $stmt = $this->db->prepare("
            UPDATE order_items

            SET service_status = 'expired'

            WHERE service_status = 'active'

              AND expiry_date IS NOT NULL

              AND expiry_date < NOW()
        ");


        return $stmt->execute();
    }


    /**
     * =====================================================
     * GET SERVICE SUMMARY
     * =====================================================
     */
    public function getServiceSummary()
    {
        $this->updateExpiredServices();


        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_services,


                SUM(
                    CASE
                        WHEN service_category = 'website'
                        THEN 1
                        ELSE 0
                    END
                ) AS total_website,


                SUM(
                    CASE
                        WHEN service_category = 'iklan'
                        THEN 1
                        ELSE 0
                    END
                ) AS total_iklan,


                SUM(
                    CASE
                        WHEN service_status = 'active'
                        THEN 1
                        ELSE 0
                    END
                ) AS total_active,


                SUM(
                    CASE
                        WHEN service_status = 'expired'
                        THEN 1
                        ELSE 0
                    END
                ) AS total_expired,


                SUM(
                    CASE
                        WHEN service_status = 'cancelled'
                        THEN 1
                        ELSE 0
                    END
                ) AS total_cancelled


            FROM order_items
        ");


        $stmt->execute();


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * =====================================================
     * GET ORDER SUMMARY
     * =====================================================
     */
    public function getOrderSummary()
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_orders,


                SUM(
                    CASE
                        WHEN status = 'pending'
                        THEN 1
                        ELSE 0
                    END
                ) AS pending_orders,


                SUM(
                    CASE
                        WHEN status = 'paid'
                        THEN 1
                        ELSE 0
                    END
                ) AS paid_orders,


                SUM(
                    CASE
                        WHEN status = 'cancelled'
                        THEN 1
                        ELSE 0
                    END
                ) AS cancelled_orders,


                COALESCE(
                    SUM(total),
                    0
                ) AS total_revenue


            FROM orders
        ");


        $stmt->execute();


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * =====================================================
     * JUMLAH SERVICE AKTIF
     * =====================================================
     */
    public function getActiveServiceCount()
    {
        $this->updateExpiredServices();


        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM order_items
            WHERE service_status = 'active'
              AND expiry_date IS NOT NULL
              AND expiry_date >= NOW()
        ");


        $stmt->execute();


        return (int)
            $stmt->fetchColumn();
    }


    /**
     * =====================================================
     * JUMLAH SERVICE EXPIRED
     * =====================================================
     */
    public function getExpiredServiceCount()
    {
        $this->updateExpiredServices();


        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM order_items
            WHERE service_status = 'expired'

               OR (
                    expiry_date IS NOT NULL
                    AND expiry_date < NOW()
               )
        ");


        $stmt->execute();


        return (int)
            $stmt->fetchColumn();
    }


    /**
     * =====================================================
     * GET SEMUA ORDER UNTUK ADMIN
     * =====================================================
     */
    public function getAllOrders()
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                user_id,
                order_number,
                subtotal,
                ppn,
                discount,
                total,
                status,
                created_at,
                updated_at
            FROM orders
            ORDER BY id DESC
        ");


        $stmt->execute();


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
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

        /*
         * Update service yang sudah
         * melewati tanggal expired.
         */
        $this->updateExpiredServices();


        $sql = "
            SELECT
                oi.id,
                oi.order_id,
                o.order_number,
                o.user_id,

                u.username,
                u.email,

                oi.domain_id,
                oi.domain_name,

                oi.service_category,
                oi.billing_period,

                oi.start_date,
                oi.expiry_date,

                oi.service_status,

                /* Harga asli layanan */
                oi.price AS price,

                /* Data transaksi dari orders */
                o.subtotal AS order_subtotal,
                o.discount AS order_discount,
                o.ppn AS order_ppn,
                o.total AS order_total,

                o.status AS order_status,
                o.created_at AS order_date

            FROM order_items oi

            INNER JOIN orders o
                ON o.id = oi.order_id

            INNER JOIN users u
                ON u.id = o.user_id

            WHERE 1 = 1
        ";

        $params = [];


        /**
         * -------------------------------------------------
         * FILTER KATEGORI
         * -------------------------------------------------
         */
        if (!empty($category)) {

            $sql .= "
                AND oi.service_category =
                    :service_category
            ";

            $params[
                ':service_category'
            ] = $category;
        }


        /**
         * -------------------------------------------------
         * FILTER STATUS
         * -------------------------------------------------
         */
        if (!empty($status)) {

            $sql .= "
                AND oi.service_status =
                    :service_status
            ";

            $params[
                ':service_status'
            ] = $status;
        }


        /**
         * -------------------------------------------------
         * URUTKAN DATA TERBARU
         * -------------------------------------------------
         */
        $sql .= "
            ORDER BY oi.id DESC
        ";


        $stmt = $this->db->prepare(
            $sql
        );


        $stmt->execute(
            $params
        );


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}