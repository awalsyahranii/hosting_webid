<?php
// models/admin/coupon/Coupon.php

require_once __DIR__ . '/../../../service/database/db.php';

class Coupon
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * =========================
     * GET ALL COUPONS
     * =========================
     */
    public function getAll()
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
                status,
                created_at,
                updated_at
            FROM coupons
            ORDER BY id DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * =========================
     * GET COUPON BY ID
     * =========================
     */
    public function getById($id)
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
                status,
                created_at,
                updated_at
            FROM coupons
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => (int) $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * =========================
     * GET COUPON BY CODE
     * =========================
     */
    public function getByCode($code)
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
            LIMIT 1
        ");

        $stmt->execute([
            ':code' => strtoupper(trim($code))
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * =========================
     * GET COUPON BY CODE
     * EXCEPT ID TERTENTU
     * =========================
     *
     * Digunakan saat UPDATE.
     *
     * Contoh:
     * Edit voucher ID 2
     * Code tetap DISKON20
     *
     * Jangan dianggap duplikat dengan dirinya sendiri.
     */
    public function getByCodeExceptId($code, $id)
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
              AND id != :id
            LIMIT 1
        ");

        $stmt->execute([
            ':code' => strtoupper(trim($code)),
            ':id'   => (int) $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * =========================
     * CREATE COUPON
     * =========================
     */
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO coupons (
                code,
                discount_type,
                discount_value,
                min_purchase,
                max_discount,
                start_date,
                end_date,
                usage_limit,
                status
            )
            VALUES (
                :code,
                :discount_type,
                :discount_value,
                :min_purchase,
                :max_discount,
                :start_date,
                :end_date,
                :usage_limit,
                :status
            )
        ");

        return $stmt->execute([
            ':code' => strtoupper(
                trim($data['code'] ?? '')
            ),

            ':discount_type' =>
                $data['discount_type'] ?? 'percentage',

            ':discount_value' =>
                $data['discount_value'] ?? 0,

            ':min_purchase' =>
                $data['min_purchase'] ?? 0,

            ':max_discount' =>
                ($data['max_discount'] ?? '') !== ''
                    ? $data['max_discount']
                    : null,

            ':start_date' =>
                !empty($data['start_date'])
                    ? $data['start_date']
                    : null,

            ':end_date' =>
                !empty($data['end_date'])
                    ? $data['end_date']
                    : null,

            ':usage_limit' =>
                ($data['usage_limit'] ?? '') !== ''
                    ? $data['usage_limit']
                    : null,

            ':status' =>
                $data['status'] ?? 'active'
        ]);
    }


    /**
     * =========================
     * UPDATE COUPON
     * =========================
     */
    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE coupons
            SET
                code = :code,
                discount_type = :discount_type,
                discount_value = :discount_value,
                min_purchase = :min_purchase,
                max_discount = :max_discount,
                start_date = :start_date,
                end_date = :end_date,
                usage_limit = :usage_limit,
                status = :status
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => (int) $id,

            ':code' =>
                strtoupper(
                    trim($data['code'] ?? '')
                ),

            ':discount_type' =>
                $data['discount_type'] ?? 'percentage',

            ':discount_value' =>
                $data['discount_value'] ?? 0,

            ':min_purchase' =>
                $data['min_purchase'] ?? 0,

            ':max_discount' =>
                ($data['max_discount'] ?? '') !== ''
                    ? $data['max_discount']
                    : null,

            ':start_date' =>
                !empty($data['start_date'])
                    ? $data['start_date']
                    : null,

            ':end_date' =>
                !empty($data['end_date'])
                    ? $data['end_date']
                    : null,

            ':usage_limit' =>
                ($data['usage_limit'] ?? '') !== ''
                    ? $data['usage_limit']
                    : null,

            ':status' =>
                $data['status'] ?? 'active'
        ]);
    }


    /**
     * =========================
     * DEACTIVATE COUPON
     * =========================
     */
    public function deactivate($id)
    {
        $stmt = $this->db->prepare("
            UPDATE coupons
            SET status = 'inactive'
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => (int) $id
        ]);
    }


    /**
     * =========================
     * TOGGLE STATUS
     * =========================
     */
    public function toggleStatus($id)
    {
        $stmt = $this->db->prepare("
            UPDATE coupons
            SET status =
                CASE
                    WHEN status = 'active'
                    THEN 'inactive'
                    ELSE 'active'
                END
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => (int) $id
        ]);
    }
}