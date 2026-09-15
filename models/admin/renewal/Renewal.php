<?php
// models/admin/renewal/Renewal.php

require_once __DIR__ . '/../../../service/database/db.php';

class Renewal
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Update status expired otomatis untuk service yang sudah lewat expiry_date
     */
    public function updateExpiredStatus()
    {
        $stmt = $this->db->prepare("
            UPDATE order_items
            SET service_status = 'expired'
            WHERE service_status = 'active'
              AND expiry_date IS NOT NULL
              AND expiry_date < NOW()
        ");
        $stmt->execute();

        // user_hostings
        $stmtH = $this->db->prepare("
            UPDATE user_hostings
            SET status = 'suspended'
            WHERE status = 'active'
              AND expiry_date IS NOT NULL
              AND expiry_date < CURDATE()
        ");
        $stmtH->execute();
    }

    /**
     * Normalisasi kategori layanan ke 4 kategori utama
     */
    public static function normalizeCategory($category, $domainName = '')
    {
        $cat = strtolower(trim((string)$category));
        $name = strtolower(trim((string)$domainName));

        if (in_array($cat, ['hosting', 'cloud_hosting']) || strpos($name, 'hosting') !== false) {
            return 'hosting';
        }
        if ($cat === 'domain' || strpos($name, '.com') !== false || strpos($name, '.id') !== false || strpos($name, '.net') !== false || strpos($name, '.co.id') !== false) {
            if ($cat === 'domain') return 'domain';
        }
        if (in_array($cat, ['google_ads', 'google-ads', 'iklan', 'ads']) || strpos($name, 'google ads') !== false || strpos($name, 'iklan') !== false) {
            return 'google_ads';
        }
        if (in_array($cat, ['seo_pbn', 'seo-pbn', 'seo', 'pbn', 'website', 'web']) || strpos($name, 'seo') !== false || strpos($name, 'website') !== false) {
            return 'seo_website';
        }

        return !empty($cat) ? $cat : 'lainnya';
    }

    /**
     * Ambil daftar layanan untuk monitoring perpanjangan dengan filter Bulan, Tahun, Kategori, Status, Search
     */
    public function getRenewals(array $filters = [])
    {
        $this->updateExpiredStatus();

        $month = !empty($filters['month']) ? (int)$filters['month'] : null;
        $year = !empty($filters['year']) ? (int)$filters['year'] : null;
        $category = !empty($filters['category']) ? strtolower(trim($filters['category'])) : null;
        $status = !empty($filters['status']) ? strtolower(trim($filters['status'])) : null;
        $search = !empty($filters['search']) ? trim($filters['search']) : null;

        $sql = "
            SELECT 
                oi.id AS order_item_id,
                oi.order_id,
                o.order_number,
                o.user_id,
                u.username,
                u.email,
                oi.domain_name,
                oi.service_category,
                oi.billing_period,
                oi.start_date,
                oi.expiry_date,
                oi.service_status,
                oi.price,
                o.created_at AS order_date,
                (SELECT p.paid_at FROM payments p WHERE p.order_id = o.id AND p.status = 'paid' ORDER BY p.id DESC LIMIT 1) AS paid_at,
                (SELECT p.payment_method FROM payments p WHERE p.order_id = o.id AND p.status = 'paid' ORDER BY p.id DESC LIMIT 1) AS payment_method,
                DATEDIFF(oi.expiry_date, NOW()) AS days_remaining,
                (SELECT rr.id FROM renewal_requests rr WHERE rr.order_item_id = oi.id AND rr.status IN ('pending', 'invoiced') ORDER BY rr.id DESC LIMIT 1) AS renewal_request_id,
                (SELECT rr.status FROM renewal_requests rr WHERE rr.order_item_id = oi.id AND rr.status IN ('pending', 'invoiced') ORDER BY rr.id DESC LIMIT 1) AS renewal_request_status,
                (SELECT rr.created_at FROM renewal_requests rr WHERE rr.order_item_id = oi.id AND rr.status IN ('pending', 'invoiced') ORDER BY rr.id DESC LIMIT 1) AS renewal_request_date,
                (SELECT rr.notes FROM renewal_requests rr WHERE rr.order_item_id = oi.id AND rr.status IN ('pending', 'invoiced') ORDER BY rr.id DESC LIMIT 1) AS renewal_request_notes,
                (SELECT inv.id FROM invoices inv WHERE inv.order_id = o.id ORDER BY inv.id DESC LIMIT 1) AS latest_invoice_id,
                (SELECT inv.invoice_number FROM invoices inv WHERE inv.order_id = o.id ORDER BY inv.id DESC LIMIT 1) AS latest_invoice_number,
                (SELECT inv.status FROM invoices inv WHERE inv.order_id = o.id ORDER BY inv.id DESC LIMIT 1) AS latest_invoice_status,
                (SELECT inv.total FROM invoices inv WHERE inv.order_id = o.id ORDER BY inv.id DESC LIMIT 1) AS latest_invoice_total
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            INNER JOIN users u ON u.id = o.user_id
            WHERE 1=1
        ";

        $params = [];

        // Filter Bulan (berdasarkan expiry_date)
        if ($month) {
            $sql .= " AND MONTH(oi.expiry_date) = :month ";
            $params[':month'] = $month;
        }

        // Filter Tahun (berdasarkan expiry_date)
        if ($year) {
            $sql .= " AND YEAR(oi.expiry_date) = :year ";
            $params[':year'] = $year;
        }

        // Filter Kategori (Hosting, Domain, Google Ads, SEO/Website)
        if ($category && $category !== 'all') {
            if ($category === 'hosting') {
                $sql .= " AND (oi.service_category IN ('hosting', 'cloud_hosting') OR oi.domain_name LIKE '%Hosting%') ";
            } elseif ($category === 'domain') {
                $sql .= " AND (oi.service_category = 'domain') ";
            } elseif (in_array($category, ['google_ads', 'iklan', 'ads'])) {
                $sql .= " AND (oi.service_category IN ('google_ads', 'google-ads', 'iklan', 'ads') OR oi.domain_name LIKE '%Google Ads%' OR oi.domain_name LIKE '%Iklan%') ";
            } elseif (in_array($category, ['seo_website', 'seo', 'website', 'web'])) {
                $sql .= " AND (oi.service_category IN ('seo_pbn', 'seo-pbn', 'seo', 'pbn', 'website', 'web') OR oi.domain_name LIKE '%SEO%' OR oi.domain_name LIKE '%Website%') ";
            } else {
                $sql .= " AND oi.service_category = :service_category ";
                $params[':service_category'] = $category;
            }
        }

        // Filter Status
        if ($status && $status !== 'all') {
            if ($status === 'expired') {
                $sql .= " AND (oi.service_status = 'expired' OR oi.expiry_date < NOW()) ";
            } elseif ($status === 'expiring_soon') {
                $sql .= " AND oi.expiry_date >= NOW() AND oi.expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) ";
            } elseif ($status === 'active') {
                $sql .= " AND oi.service_status = 'active' AND oi.expiry_date > DATE_ADD(NOW(), INTERVAL 30 DAY) ";
            } elseif ($status === 'requested') {
                $sql .= " AND (SELECT COUNT(*) FROM renewal_requests rr WHERE rr.order_item_id = oi.id AND rr.status = 'pending') > 0 ";
            } elseif ($status === 'invoiced') {
                $sql .= " AND (SELECT COUNT(*) FROM invoices inv WHERE inv.order_id = o.id AND inv.status = 'unpaid') > 0 ";
            }
        }

        // Search
        if ($search) {
            $sql .= " AND (oi.domain_name LIKE :search OR u.username LIKE :search OR u.email LIKE :search OR o.order_number LIKE :search) ";
            $params[':search'] = "%$search%";
        }

        $sql .= " ORDER BY oi.expiry_date ASC, oi.id DESC ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Map and enrich data
        $result = [];
        foreach ($rows as $row) {
            $days = (int)($row['days_remaining'] ?? 0);
            $mainCategory = self::normalizeCategory($row['service_category'], $row['domain_name']);

            $renewalState = 'active';
            if ($days < 0) {
                $renewalState = 'expired';
            } elseif ($days <= 30) {
                $renewalState = 'expiring_soon';
            }

            if (!empty($row['renewal_request_status']) && $row['renewal_request_status'] === 'pending') {
                $renewalState = 'renewal_requested';
            } elseif (!empty($row['latest_invoice_status']) && $row['latest_invoice_status'] === 'unpaid') {
                $renewalState = 'invoice_sent';
            }

            $result[] = [
                'order_item_id' => (int)$row['order_item_id'],
                'order_id' => (int)$row['order_id'],
                'order_number' => $row['order_number'],
                'user_id' => (int)$row['user_id'],
                'username' => $row['username'],
                'email' => $row['email'],
                'domain_name' => $row['domain_name'],
                'service_category' => $row['service_category'],
                'main_category' => $mainCategory,
                'billing_period' => $row['billing_period'] ?? '1 Tahun',
                'price' => (float)$row['price'],
                'start_date' => $row['start_date'],
                'expiry_date' => $row['expiry_date'],
                'service_status' => $row['service_status'],
                'order_date' => $row['order_date'],
                'paid_at' => $row['paid_at'],
                'days_remaining' => $days,
                'renewal_state' => $renewalState,
                'renewal_request' => !empty($row['renewal_request_id']) ? [
                    'id' => (int)$row['renewal_request_id'],
                    'status' => $row['renewal_request_status'],
                    'created_at' => $row['renewal_request_date'],
                    'notes' => $row['renewal_request_notes']
                ] : null,
                'latest_invoice' => !empty($row['latest_invoice_id']) ? [
                    'id' => (int)$row['latest_invoice_id'],
                    'invoice_number' => $row['latest_invoice_number'],
                    'status' => $row['latest_invoice_status'],
                    'total' => (float)$row['latest_invoice_total']
                ] : null
            ];
        }

        return $result;
    }

    /**
     * Hitung ringkasan statistik perpanjangan untuk Admin
     */
    public function getRenewalSummary($month = null, $year = null)
    {
        $this->updateExpiredStatus();

        $monthSql = $month ? " AND MONTH(oi.expiry_date) = " . (int)$month : "";
        $yearSql = $year ? " AND YEAR(oi.expiry_date) = " . (int)$year : "";

        $stmt = $this->db->query("
            SELECT 
                COUNT(*) AS total_services,
                SUM(CASE WHEN oi.expiry_date < NOW() THEN 1 ELSE 0 END) AS total_expired,
                SUM(CASE WHEN oi.expiry_date >= NOW() AND oi.expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS total_expiring_soon,
                SUM(CASE WHEN oi.expiry_date > DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS total_active,
                SUM(CASE WHEN oi.service_category IN ('hosting', 'cloud_hosting') THEN 1 ELSE 0 END) AS count_hosting,
                SUM(CASE WHEN oi.service_category = 'domain' THEN 1 ELSE 0 END) AS count_domain,
                SUM(CASE WHEN oi.service_category IN ('google_ads', 'google-ads', 'iklan', 'ads') THEN 1 ELSE 0 END) AS count_google_ads,
                SUM(CASE WHEN oi.service_category IN ('seo_pbn', 'seo-pbn', 'seo', 'pbn', 'website', 'web') THEN 1 ELSE 0 END) AS count_seo_website
            FROM order_items oi
            WHERE 1=1 $monthSql $yearSql
        ");

        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Pending requests count
        $stmtReq = $this->db->query("SELECT COUNT(*) FROM renewal_requests WHERE status = 'pending'");
        $pendingRequests = (int)$stmtReq->fetchColumn();

        return [
            'total_services' => (int)($stats['total_services'] ?? 0),
            'total_expired' => (int)($stats['total_expired'] ?? 0),
            'total_expiring_soon' => (int)($stats['total_expiring_soon'] ?? 0),
            'total_active' => (int)($stats['total_active'] ?? 0),
            'total_pending_requests' => $pendingRequests,
            'categories' => [
                'hosting' => (int)($stats['count_hosting'] ?? 0),
                'domain' => (int)($stats['count_domain'] ?? 0),
                'google_ads' => (int)($stats['count_google_ads'] ?? 0),
                'seo_website' => (int)($stats['count_seo_website'] ?? 0)
            ]
        ];
    }

    /**
     * Klien mengajukan request invoice perpanjangan
     */
    public function createRenewalRequest($userId, $orderItemId, $notes = '')
    {
        $stmt = $this->db->prepare("
            SELECT oi.*, o.user_id 
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE oi.id = :id AND o.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':id' => (int)$orderItemId, ':user_id' => (int)$userId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Layanan tidak ditemukan atau bukan milik Anda'];
        }

        // Cek apakah sudah ada request pending
        $check = $this->db->prepare("
            SELECT id FROM renewal_requests 
            WHERE order_item_id = :item_id AND status = 'pending'
            LIMIT 1
        ");
        $check->execute([':item_id' => (int)$orderItemId]);
        if ($check->rowCount() > 0) {
            return ['status' => 'error', 'code' => 409, 'message' => 'Permintaan invoice perpanjangan untuk layanan ini sedang diproses oleh admin'];
        }

        $ins = $this->db->prepare("
            INSERT INTO renewal_requests 
            (user_id, order_item_id, service_name, service_category, billing_period, amount, status, notes, created_at, updated_at)
            VALUES 
            (:user_id, :order_item_id, :service_name, :service_category, :billing_period, :amount, 'pending', :notes, NOW(), NOW())
        ");
        $ins->execute([
            ':user_id' => (int)$userId,
            ':order_item_id' => (int)$orderItemId,
            ':service_name' => $item['domain_name'],
            ':service_category' => $item['service_category'],
            ':billing_period' => $item['billing_period'] ?? 'yearly',
            ':amount' => $item['price'],
            ':notes' => $notes
        ]);

        $requestId = (int)$this->db->lastInsertId();

        return [
            'status' => 'success',
            'code' => 201,
            'message' => 'Permintaan invoice perpanjangan berhasil dikirim. Admin akan segera memproses dan mengirimkan invoice ke Anda.',
            'data' => [
                'request_id' => $requestId,
                'service_name' => $item['domain_name'],
                'amount' => (float)$item['price']
            ]
        ];
    }

    /**
     * Admin membuat invoice perpanjangan (Renewal Invoice)
     */
    public function createRenewalInvoice($orderItemId, $customAmount = null, $billingPeriod = null, $dueDate = null, $notes = null)
    {
        $stmt = $this->db->prepare("
            SELECT oi.*, o.user_id, o.order_number, u.username, u.email
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            INNER JOIN users u ON u.id = o.user_id
            WHERE oi.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => (int)$orderItemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Data layanan tidak ditemukan'];
        }

        $amount = ($customAmount !== null && is_numeric($customAmount) && (float)$customAmount > 0)
            ? (float)$customAmount
            : (float)$item['price'];

        $period = !empty($billingPeriod) ? $billingPeriod : ($item['billing_period'] ?? '1 Tahun');
        $due = !empty($dueDate) ? $dueDate : date('Y-m-d H:i:s', strtotime('+7 days'));

        $this->db->beginTransaction();

        try {
            // Generate invoice number unik: INV-REN-YYYYMMDD-XXXX
            $invoiceNumber = 'INV-REN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $insInv = $this->db->prepare("
                INSERT INTO invoices 
                (order_id, invoice_number, subtotal, ppn, discount, total, status, due_date, created_at, updated_at)
                VALUES 
                (:order_id, :invoice_number, :subtotal, 0, 0, :total, 'unpaid', :due_date, NOW(), NOW())
            ");
            $insInv->execute([
                ':order_id' => (int)$item['order_id'],
                ':invoice_number' => $invoiceNumber,
                ':subtotal' => $amount,
                ':total' => $amount,
                ':due_date' => $due
            ]);
            $invoiceId = (int)$this->db->lastInsertId();

            // Update request pending jika ada
            $updReq = $this->db->prepare("
                UPDATE renewal_requests 
                SET status = 'invoiced', invoice_id = :invoice_id, amount = :amount, updated_at = NOW()
                WHERE order_item_id = :item_id AND status = 'pending'
            ");
            $updReq->execute([
                ':invoice_id' => $invoiceId,
                ':amount' => $amount,
                ':item_id' => (int)$orderItemId
            ]);

            $this->db->commit();

            return [
                'status' => 'success',
                'code' => 201,
                'message' => 'Invoice perpanjangan berhasil dibuat',
                'data' => [
                    'invoice_id' => $invoiceId,
                    'invoice_number' => $invoiceNumber,
                    'order_item_id' => (int)$orderItemId,
                    'service_name' => $item['domain_name'],
                    'client_name' => $item['username'],
                    'client_email' => $item['email'],
                    'billing_period' => $period,
                    'amount' => $amount,
                    'due_date' => $due,
                    'print_url' => "/api/v1/admin/renewal/print-invoice.php?invoice_id=$invoiceId"
                ]
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Admin mengonfirmasi pembayaran perpanjangan dan memperpanjang masa berlaku layanan
     */
    public function confirmRenewalInvoice($invoiceId)
    {
        $stmt = $this->db->prepare("
            SELECT inv.*, o.user_id, oi.id AS order_item_id, oi.domain_name, oi.service_category, oi.billing_period, oi.expiry_date, oi.price
            FROM invoices inv
            INNER JOIN orders o ON o.id = inv.order_id
            LEFT JOIN order_items oi ON oi.order_id = o.id
            WHERE inv.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => (int)$invoiceId]);
        $inv = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inv) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Invoice tidak ditemukan'];
        }

        $this->db->beginTransaction();

        try {
            // Update invoice status ke paid
            $updInv = $this->db->prepare("UPDATE invoices SET status = 'paid', updated_at = NOW() WHERE id = :id");
            $updInv->execute([':id' => (int)$invoiceId]);

            // Hitung tanggal kedaluwarsa baru (+1 tahun / +1 bulan / +periode)
            $currentExpiry = $inv['expiry_date'];
            $baseDate = (!empty($currentExpiry) && strtotime($currentExpiry) > time()) ? $currentExpiry : date('Y-m-d H:i:s');
            $period = strtolower((string)$inv['billing_period']);
            $category = strtolower((string)$inv['service_category']);

            if (strpos($period, '14') !== false) {
                $newExpiry = date('Y-m-d H:i:s', strtotime($baseDate . ' +14 days'));
            } elseif (strpos($period, '20') !== false) {
                $newExpiry = date('Y-m-d H:i:s', strtotime($baseDate . ' +20 days'));
            } elseif (strpos($period, '30') !== false || $period === 'monthly' || in_array($category, ['google_ads', 'iklan', 'ads'])) {
                $newExpiry = date('Y-m-d H:i:s', strtotime($baseDate . ' +30 days'));
            } else {
                $newExpiry = date('Y-m-d H:i:s', strtotime($baseDate . ' +1 year'));
            }

            if (!empty($inv['order_item_id'])) {
                $updItem = $this->db->prepare("
                    UPDATE order_items 
                    SET service_status = 'active', expiry_date = :new_expiry 
                    WHERE id = :item_id
                ");
                $updItem->execute([
                    ':new_expiry' => $newExpiry,
                    ':item_id' => (int)$inv['order_item_id']
                ]);
            }

            // Jika hosting, update user_hostings juga
            $updH = $this->db->prepare("
                UPDATE user_hostings 
                SET status = 'active', expiry_date = :expiry_date 
                WHERE order_id = :order_id
            ");
            $updH->execute([
                ':expiry_date' => date('Y-m-d', strtotime($newExpiry)),
                ':order_id' => (int)$inv['order_id']
            ]);

            // Update status renewal_requests jika ada
            $updReq = $this->db->prepare("
                UPDATE renewal_requests 
                SET status = 'paid', updated_at = NOW() 
                WHERE invoice_id = :inv_id OR (order_item_id = :item_id AND status = 'invoiced')
            ");
            $updReq->execute([
                ':inv_id' => (int)$invoiceId,
                ':item_id' => (int)($inv['order_item_id'] ?? 0)
            ]);

            // Buat record payment jika belum ada
            $payStmt = $this->db->prepare("SELECT id FROM payments WHERE invoice_id = :invoice_id LIMIT 1");
            $payStmt->execute([':invoice_id' => (int)$invoiceId]);
            if ($payStmt->rowCount() === 0) {
                $payNumber = 'PAY-REN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
                $insPay = $this->db->prepare("
                    INSERT INTO payments 
                    (order_id, invoice_id, payment_number, amount, payment_method, status, paid_at, created_at, updated_at)
                    VALUES 
                    (:order_id, :invoice_id, :payment_number, :amount, 'transfer_bank', 'paid', NOW(), NOW(), NOW())
                ");
                $insPay->execute([
                    ':order_id' => (int)$inv['order_id'],
                    ':invoice_id' => (int)$invoiceId,
                    ':payment_number' => $payNumber,
                    ':amount' => $inv['total']
                ]);
            }

            $this->db->commit();

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Pembayaran perpanjangan berhasil dikonfirmasi. Layanan aktif hingga ' . date('d M Y', strtotime($newExpiry)),
                'data' => [
                    'invoice_id' => (int)$invoiceId,
                    'new_expiry_date' => $newExpiry,
                    'service_status' => 'active'
                ]
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Ambil detail invoice lengkap untuk faktur cetak / WhatsApp / download PDF
     */
    public function getInvoiceDetail($invoiceId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                inv.id AS invoice_id,
                inv.invoice_number,
                inv.subtotal,
                inv.ppn,
                inv.discount,
                inv.total,
                inv.status AS invoice_status,
                inv.due_date,
                inv.created_at AS invoice_date,
                o.id AS order_id,
                o.order_number,
                u.id AS user_id,
                u.username,
                u.email,
                oi.id AS order_item_id,
                oi.domain_name,
                oi.service_category,
                oi.billing_period,
                oi.start_date,
                oi.expiry_date,
                oi.service_status,
                p.paid_at,
                p.payment_method
            FROM invoices inv
            INNER JOIN orders o ON o.id = inv.order_id
            INNER JOIN users u ON u.id = o.user_id
            LEFT JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN payments p ON p.invoice_id = inv.id
            WHERE inv.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => (int)$invoiceId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
