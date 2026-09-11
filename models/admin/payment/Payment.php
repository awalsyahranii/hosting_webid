<?php
require_once __DIR__ . '/../../../service/database/db.php';

class AdminPayment
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        $stmt = $this->db->query("
            SELECT
                p.id,
                p.payment_number,
                p.order_id,
                o.order_number,
                u.username,
                p.amount,
                p.payment_method,
                p.status,
                p.paid_at,
                p.confirmation_requested_at,
                p.created_at,
                u.email,
                GROUP_CONCAT(DISTINCT oi.domain_name ORDER BY oi.id SEPARATOR ', ') AS domains,
                GROUP_CONCAT(DISTINCT oi.service_category ORDER BY oi.id SEPARATOR ', ') AS service_category,
                GROUP_CONCAT(DISTINCT oi.billing_period ORDER BY oi.id SEPARATOR ', ') AS billing_period
            FROM payments p
            INNER JOIN orders o ON o.id = p.order_id
            INNER JOIN users u ON u.id = o.user_id
            LEFT JOIN order_items oi ON oi.order_id = o.id
            WHERE p.status = 'paid' OR p.confirmation_requested_at IS NOT NULL
            GROUP BY p.id
            ORDER BY p.id DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($paymentId)
    {
        $stmt = $this->db->prepare("
            SELECT
                p.id,
                p.payment_number,
                p.order_id,
                p.invoice_id,
                p.amount,
                p.payment_method,
                p.status,
                p.paid_at,
                p.confirmation_requested_at,
                p.created_at,
                o.order_number,
                o.subtotal,
                o.ppn,
                o.discount,
                o.total,
                o.status AS order_status,
                u.username,
                u.email
            FROM payments p
            INNER JOIN orders o ON o.id = p.order_id
            INNER JOIN users u ON u.id = o.user_id
            WHERE p.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => (int) $paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            return null;
        }

        $items = $this->db->prepare("
            SELECT id, domain_name, service_category, billing_period,
                   price, service_status, start_date, expiry_date
            FROM order_items
            WHERE order_id = :order_id
            ORDER BY id ASC
        ");
        $items->execute([':order_id' => (int) $payment['order_id']]);
        $payment['items'] = $items->fetchAll(PDO::FETCH_ASSOC);

        return $payment;
    }

    public function confirm($paymentId)
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT p.*, o.subtotal, o.ppn, o.discount, o.total, o.user_id
                FROM payments p
                INNER JOIN orders o ON o.id = p.order_id
                WHERE p.id = :id
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([':id' => (int) $paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                $this->db->rollBack();
                return ['code' => 404, 'message' => 'Payment tidak ditemukan'];
            }

            if (strtolower((string) $payment['status']) !== 'pending') {
                $this->db->rollBack();
                return ['code' => 409, 'message' => 'Payment sudah dikonfirmasi'];
            }

            $orderId = (int) $payment['order_id'];
            $userId = (int) $payment['user_id'];

            $invoiceStmt = $this->db->prepare(
                'SELECT id FROM invoices WHERE order_id = :order_id LIMIT 1 FOR UPDATE'
            );
            $invoiceStmt->execute([':order_id' => $orderId]);
            $invoice = $invoiceStmt->fetch(PDO::FETCH_ASSOC);

            if (!$invoice) {
                $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                $createInvoice = $this->db->prepare("
                    INSERT INTO invoices
                        (order_id, invoice_number, subtotal, ppn, discount, total,
                         status, due_date, created_at, updated_at)
                    VALUES
                        (:order_id, :invoice_number, :subtotal, :ppn, :discount, :total,
                         'paid', NOW(), NOW(), NOW())
                ");
                $createInvoice->execute([
                    ':order_id' => $orderId,
                    ':invoice_number' => $invoiceNumber,
                    ':subtotal' => $payment['subtotal'],
                    ':ppn' => $payment['ppn'],
                    ':discount' => $payment['discount'],
                    ':total' => $payment['total']
                ]);
                $invoiceId = (int) $this->db->lastInsertId();
            } else {
                $invoiceId = (int) $invoice['id'];
                $updateInvoice = $this->db->prepare(
                    "UPDATE invoices SET status = 'paid', updated_at = NOW() WHERE id = :id"
                );
                $updateInvoice->execute([':id' => $invoiceId]);
            }

            $updatePayment = $this->db->prepare(
                "UPDATE payments SET status = 'paid', invoice_id = :invoice_id,
                 paid_at = NOW(), updated_at = NOW() WHERE id = :id"
            );
            $updatePayment->execute([
                ':invoice_id' => $invoiceId,
                ':id' => (int) $paymentId
            ]);

            $updateOrder = $this->db->prepare(
                "UPDATE orders SET status = 'paid', updated_at = NOW() WHERE id = :id"
            );
            $updateOrder->execute([':id' => $orderId]);

            $updateItems = $this->db->prepare("
                UPDATE order_items
                SET service_status = 'active',
                    start_date = NOW(),
                    expiry_date = CASE LOWER(billing_period)
                        WHEN 'weekly' THEN DATE_ADD(NOW(), INTERVAL 7 DAY)
                        WHEN 'monthly' THEN DATE_ADD(NOW(), INTERVAL 1 MONTH)
                        WHEN 'yearly' THEN DATE_ADD(NOW(), INTERVAL 1 YEAR)
                        ELSE DATE_ADD(NOW(), INTERVAL 1 YEAR)
                    END
                WHERE order_id = :order_id
            ");
            $updateItems->execute([':order_id' => $orderId]);

            // Activate or create in user_hostings
            $checkHosting = $this->db->prepare("SELECT id FROM user_hostings WHERE order_id = :order_id");
            $checkHosting->execute([':order_id' => $orderId]);
            if ($checkHosting->rowCount() > 0) {
                $updateHosting = $this->db->prepare("
                    UPDATE user_hostings
                    SET status = 'active',
                        start_date = CURDATE(),
                        expiry_date = DATE_ADD(CURDATE(), INTERVAL 1 YEAR)
                    WHERE order_id = :order_id
                ");
                $updateHosting->execute([':order_id' => $orderId]);
            } else {
                // If it was a hosting order item
                $itemsStmt = $this->db->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
                $itemsStmt->execute([':order_id' => $orderId]);
                $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($orderItems as $item) {
                    $cat = strtolower((string)$item['service_category']);
                    $dom = (string)$item['domain_name'];
                    $amt = (float)$item['price'];

                    if ($cat === 'hosting' || $cat === 'cloud_hosting' || $amt >= 500000) {
                        $planId = 2; // Default Bisnis Pro
                        if ($amt < 1000000) $planId = 1; // Starter
                        if ($amt >= 1800000) $planId = 3; // Enterprise

                        $cpanelUser = 'cp_' . substr(preg_replace('/[^a-z0-9]/', '', strtolower($dom)), 0, 8);
                        $insertH = $this->db->prepare("
                            INSERT INTO user_hostings 
                            (user_id, order_id, plan_id, domain_name, cpanel_username, server_ip, billing_cycle, start_date, expiry_date, status, created_at)
                            VALUES
                            (:user_id, :order_id, :plan_id, :domain_name, :cpanel_username, '103.147.154.21', 'yearly', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active', NOW())
                        ");
                        $insertH->execute([
                            ':user_id' => $userId,
                            ':order_id' => $orderId,
                            ':plan_id' => $planId,
                            ':domain_name' => $dom,
                            ':cpanel_username' => $cpanelUser
                        ]);
                    }
                }
            }

            $this->db->commit();
            return ['code' => 200, 'invoice_id' => $invoiceId];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
