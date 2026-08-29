<?php
// controller/dashboardusers/domain/DomainController.php

require_once __DIR__ . '/../../../models/dashboardusers/domain/Domain.php';

class DomainController
{
    private $domainModel;

    public function __construct()
    {
        $this->domainModel = new Domain();
    }

    /**
     * Mengecek ketersediaan domain
     */
    public function checkDomain($domainName)
    {
        // Validasi
        if (empty($domainName)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Nama domain wajib diisi'
            ];
        }

        // Bersihkan input
        $domainName = strtolower(trim($domainName));

        // Hilangkan http://, https://, dan www.
        $domainName = preg_replace('#^https?://#', '', $domainName);
        $domainName = preg_replace('#^www\.#', '', $domainName);

        // Hilangkan slash di belakang
        $domainName = rtrim($domainName, '/');

        // Cari domain
        $domain = $this->domainModel->findByDomainName($domainName);

        // Domain tidak ditemukan
        if (!$domain) {
            return [
                'status' => 'success',
                'code' => 200,
                'available' => true,
                'message' => 'Domain tersedia',
                'data' => [
                    'domain' => $domainName,
                    'price' => null
                ]
            ];
        }

        // Domain ditemukan tetapi tidak tersedia
        if ($domain['status'] === 'unavailable') {
            return [
                'status' => 'success',
                'code' => 200,
                'available' => false,
                'message' => 'Domain tidak tersedia',
                'data' => [
                    'domain' => $domain['domain_name'],
                    'price' => $domain['price']
                ]
            ];
        }

        // Domain tersedia
        return [
            'status' => 'success',
            'code' => 200,
            'available' => true,
            'message' => 'Domain tersedia',
            'data' => [
                'id' => $domain['id'],
                'domain' => $domain['domain_name'],
                'extension' => $domain['extension'],
                'price' => $domain['price']
            ]
        ];
    }
    public function getAllDomains()
{
    $domains = $this->domainModel->getAllDomains();

    return [
        'status' => 'success',
        'code' => 200,
        'message' => 'Data domain berhasil diambil',
        'data' => $domains
    ];
}

public function getDomainById($id)
{
    if (empty($id) || !is_numeric($id)) {
        return [
            'status' => 'error',
            'code' => 400,
            'message' => 'ID domain tidak valid'
        ];
    }

    $domain = $this->domainModel->findById($id);

    if (!$domain) {
        return [
            'status' => 'error',
            'code' => 404,
            'message' => 'Domain tidak ditemukan'
        ];
    }

    return [
        'status' => 'success',
        'code' => 200,
        'message' => 'Data domain berhasil diambil',
        'data' => $domain
    ];
}

}