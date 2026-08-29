<?php
// models/dashboardusers/checkout/Checkout.php

require_once __DIR__ . '/../../../service/database/db.php';

class Checkout
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getCheckoutItems($userId)
    {
        $stmt = $this->db->prepare("
            SELECT
                cart.id AS cart_id,
                cart.domain_id,
                domains.domain_name,
                domains.extension,
                cart.price
            FROM cart
            INNER JOIN domains
                ON domains.id = cart.domain_id
            WHERE cart.user_id = :user_id
              AND cart.status = 'active'
            ORDER BY cart.id DESC
        ");

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }
}