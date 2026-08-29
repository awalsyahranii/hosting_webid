<?php
// models/dashboardusers/coupon/Coupon.php

require_once __DIR__ . '/../../../service/database/db.php';

class Coupon
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getValidCoupon($code)
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                code,
                discount_type,
                discount_value,
                min_purchase,
                max_discount,
                usage_limit,
                used_count,
                start_date,
                end_date,
                status
            FROM coupons
            WHERE code = :code
              AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute([
            ':code' => strtoupper(trim($code))
        ]);

        return $stmt->fetch();
    }
}