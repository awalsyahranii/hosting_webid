<?php
// models/dashboardusers/domain/Domain.php

require_once __DIR__ . '/../../../service/database/db.php';

class Domain
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Cari domain berdasarkan nama domain
     */
    public function findByDomainName($domainName)
    {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                domain_name,
                extension,
                price,
                status,
                created_at,
                updated_at
            FROM domains
            WHERE domain_name = :domain_name
            LIMIT 1
        ");

        $stmt->execute([
            ':domain_name' => $domainName
        ]);

        return $stmt->fetch();
    }
    public function getAllDomains()
{
    $stmt = $this->db->prepare("
        SELECT
            id,
            domain_name,
            extension,
            price,
            status,
            created_at,
            updated_at
        FROM domains
        ORDER BY id ASC
    ");

    $stmt->execute();

    return $stmt->fetchAll();
}

public function findById($id)
{
    $stmt = $this->db->prepare("
        SELECT
            id,
            domain_name,
            extension,
            price,
            status,
            created_at,
            updated_at
        FROM domains
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    return $stmt->fetch();
}

    /**
     * Ambil semua ekstensi domain unik beserta harga dan promo
     */
    public function getAllExtensionsWithPrices()
    {
        // Daftar TLD master default dengan harga pasar & promo
        $defaultTlds = [
            ['extension' => '.com', 'price' => 125000, 'promo_price' => 125000, 'original_price' => 209900, 'badge' => 'Populer', 'is_popular' => true],
            ['extension' => '.id', 'price' => 190000, 'promo_price' => 190000, 'original_price' => 219900, 'badge' => 'PILIHAN UTAMA', 'is_popular' => true],
            ['extension' => '.co.id', 'price' => 270000, 'promo_price' => 270000, 'original_price' => 275000, 'badge' => 'Bisnis ID', 'is_popular' => true],
            ['extension' => '.my.id', 'price' => 12000, 'promo_price' => 12000, 'original_price' => 80000, 'badge' => 'HEMAT 85%', 'is_popular' => true],
            ['extension' => '.org', 'price' => 149900, 'promo_price' => 149900, 'original_price' => 229900, 'badge' => 'Organisasi', 'is_popular' => false],
            ['extension' => '.xyz', 'price' => 29900, 'promo_price' => 29900, 'original_price' => 189900, 'badge' => 'HEMAT', 'is_popular' => true],
            ['extension' => '.buzz', 'price' => 29900, 'promo_price' => 29900, 'original_price' => 650000, 'badge' => 'PROMO GILA', 'is_popular' => false],
            ['extension' => '.info', 'price' => 349900, 'promo_price' => 349900, 'original_price' => 349900, 'badge' => 'Informasi', 'is_popular' => false],
            ['extension' => '.net', 'price' => 249900, 'promo_price' => 249900, 'original_price' => 249900, 'badge' => 'Network', 'is_popular' => false],
            ['extension' => '.it.com', 'price' => 114900, 'promo_price' => 114900, 'original_price' => 499900, 'badge' => 'Tech Promo', 'is_popular' => false],
            ['extension' => '.cloud', 'price' => 22900, 'promo_price' => 22900, 'original_price' => 364900, 'badge' => 'Cloud', 'is_popular' => false],
            ['extension' => '.lol', 'price' => 39900, 'promo_price' => 39900, 'original_price' => 650000, 'badge' => 'Fun TLD', 'is_popular' => false],
            ['extension' => '.mom', 'price' => 39900, 'promo_price' => 39900, 'original_price' => 670000, 'badge' => 'Promo', 'is_popular' => false],
            ['extension' => '.online', 'price' => 19900, 'promo_price' => 19900, 'original_price' => 350000, 'badge' => 'Online', 'is_popular' => false],
            ['extension' => '.site', 'price' => 19900, 'promo_price' => 19900, 'original_price' => 350000, 'badge' => 'Site', 'is_popular' => false],
            ['extension' => '.store', 'price' => 29900, 'promo_price' => 29900, 'original_price' => 450000, 'badge' => 'Store', 'is_popular' => false],
            ['extension' => '.tech', 'price' => 29900, 'promo_price' => 29900, 'original_price' => 450000, 'badge' => 'Tech', 'is_popular' => false],
            ['extension' => '.biz.id', 'price' => 12000, 'promo_price' => 12000, 'original_price' => 50000, 'badge' => 'Bisnis', 'is_popular' => false],
            ['extension' => '.web.id', 'price' => 25000, 'promo_price' => 25000, 'original_price' => 60000, 'badge' => 'Web ID', 'is_popular' => false],
        ];

        try {
            // Ambil dari database jika ada kolom yang sesuai
            $stmt = $this->db->prepare("
                SELECT
                    id,
                    domain_name,
                    extension,
                    price,
                    status
                FROM domains
                LIMIT 50
            ");
            $stmt->execute();
            $dbRows = $stmt->fetchAll();

            $dbPriceMap = [];
            foreach ($dbRows as $row) {
                $ext = strtolower(trim((string)($row['extension'] ?? '')));
                if ($ext !== '') {
                    if ($ext[0] !== '.') {
                        $ext = '.' . $ext;
                    }
                    $dbPriceMap[$ext] = [
                        'id' => (int)($row['id'] ?? 0),
                        'price' => (int)($row['price'] ?? 0),
                    ];
                }
            }

            // Gabungkan data DB dengan default catalog
            $result = [];
            foreach ($defaultTlds as $tld) {
                $ext = $tld['extension'];
                $item = $tld;
                $item['id'] = $dbPriceMap[$ext]['id'] ?? 0;
                if (isset($dbPriceMap[$ext]) && $dbPriceMap[$ext]['price'] > 0) {
                    $item['price'] = $dbPriceMap[$ext]['price'];
                }
                $result[] = $item;
            }

            return $result;

        } catch (Throwable $e) {
            // Fallback jika database gagal
            return $defaultTlds;
        }
    }
}