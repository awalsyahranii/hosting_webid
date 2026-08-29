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
}