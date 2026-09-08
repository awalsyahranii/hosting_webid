<?php

// controller/dashboardusers/domain/DomainController.php

require_once __DIR__ . '/../../../models/dashboardusers/domain/Domain.php';

class DomainController
{
    private string $resellerUrl;
    private string $apiUsername;
    private string $apiPassword;
    private ?Domain $domainModel = null;

    public function __construct()
    {
        $config = require __DIR__ . '/../../../config/srsx.php';
        $this->resellerUrl =
            rtrim(
                (string) ($config['reseller_url'] ?? ''),
                '/'
            );

        $this->apiUsername =
            trim(
                (string) ($config['api_username'] ?? '')
            );

        $this->apiPassword =
            (string) ($config['api_password'] ?? '');
    }

    /**
     * Mengecek ketersediaan domain melalui
     * Digital Registra / SRS-X
     */
    public function checkDomain($domainName)
    {
        if (empty($domainName)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Nama domain wajib diisi'
            ];
        }

        /*
         * Bersihkan input
         */
        $domainName = strtolower(
            trim((string) $domainName)
        );

        $domainName = preg_replace(
            '#^https?://#i',
            '',
            $domainName
        );

        $domainName = preg_replace(
            '#^www\.#i',
            '',
            $domainName
        );

        $domainName = rtrim(
            $domainName,
            '/'
        );

        /*
         * Validasi sederhana
         */
        if (
            !preg_match(
                '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
                $domainName
            )
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Format nama domain tidak valid'
            ];
        }

        /*
         * Pastikan konfigurasi tersedia
         */
        if (
            $this->resellerUrl === '' ||
            $this->apiUsername === '' ||
            $this->apiPassword === ''
        ) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' =>
                    'Konfigurasi API Digital Registra belum lengkap'
            ];
        }

        /*
         * Endpoint SRS-X
         */
        $apiUrl = $this->resellerUrl . '/api/domain/check';

        /*
         * Request SRS-X
         */
        $post = [
            'username' =>
                $this->apiUsername,

            'password' =>
                hash(
                    'sha256',
                    $this->apiPassword
                ),

            'domain' =>
                $domainName
        ];

        /*
         * CURL
         */
        $ch = curl_init();

        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL =>
                    $apiUrl,

                CURLOPT_POST =>
                    true,

                CURLOPT_CONNECTTIMEOUT => 10,

                CURLOPT_TIMEOUT => 30,

                CURLOPT_RETURNTRANSFER =>
                    true,

                CURLOPT_POSTFIELDS =>
                    http_build_query($post),

                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/xml,text/xml,*/*'
                ],
            ]
        );

        $response =
            curl_exec($ch);

        $curlError =
            curl_error($ch);

        $httpCode =
            (int) curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );

        curl_close($ch);

        /*
         * CURL gagal
         */
        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return [
                'status' => 'error',
                'code' => 502,
                'message' =>
                    'Tidak dapat terhubung ke Digital Registra',
                'provider_http_code' =>
                    $httpCode,
                'provider_error' => $curlError,
                'provider_response' => $response !== false
                    ? substr($response, 0, 500)
                    : null
            ];
        }

        /*
         * Parse XML
         */
        libxml_use_internal_errors(true);

        $xml =
            simplexml_load_string(
                $response
            );

        if ($xml === false) {
            return [
                'status' => 'error',
                'code' => 502,
                'message' =>
                    'Response Digital Registra tidak valid',
                'provider_http_code' => $httpCode,
                'provider_response' => substr($response, 0, 500)
            ];
        }

        /*
         * Ambil response provider
         */
        $resultCode =
            isset($xml->result->resultCode)
                ? trim(
                    (string)
                    $xml->result->resultCode
                )
                : '';

        $resultMessage =
            isset($xml->result->resultMsg)
                ? trim(
                    (string)
                    $xml->result->resultMsg
                )
                : '';

        /*
         * AVAILABLE
         *
         * Dokumentasi SRS-X:
         * 1000 = Domain Available
         */
        $data = [
            'domain' => $domainName,
            'extension' => '.' . strtolower(pathinfo($domainName, PATHINFO_EXTENSION)),
            'provider_code' => $resultCode,
            'provider_message' => $resultMessage,
        ];

        if ($resultCode === '1000') {

            return [
                'status' => 'success',
                'code' => 200,
                'available' => true,
                'message' => 'Domain tersedia',
                'data' => $data
            ];
        }

        /*
         * UNAVAILABLE
         *
         * Kita gunakan 2400 berdasarkan
         * response aktual yang kamu terima
         * dari SRS-X:
         * 2400 = Domain Unavailable
         */
        if ($resultCode === '2400') {

            return [
                'status' => 'success',
                'code' => 200,
                'available' => false,
                'message' => 'Domain tidak tersedia',
                'data' => $data
            ];
        }

        /*
         * ERROR PROVIDER
         *
         * 1001 pada dokumentasi berarti
         * Command Failed, bukan otomatis
         * domain unavailable.
         */
        return [
            'status' => 'error',
            'code' => 502,
            'message' =>
                $resultMessage !== ''
                    ? $resultMessage
                    : 'Digital Registra gagal memproses pengecekan domain',
            'data' => array_merge($data, [
                'available' => null,
            ])
        ];
    }

    public function getAllDomains(): array
    {
        try {
            $this->domainModel ??= new Domain();

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Data domain berhasil diambil',
                'data' => $this->domainModel->getAllDomains(),
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Gagal mengambil data domain',
            ];
        }
    }

    public function getDomainById($id): array
    {
        if (!is_numeric($id) || (int) $id < 1) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'ID domain tidak valid',
            ];
        }

        try {
            $this->domainModel ??= new Domain();

            $domain = $this->domainModel->findById((int) $id);

            if (!$domain) {
                return [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Domain tidak ditemukan',
                ];
            }

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Detail domain berhasil diambil',
                'data' => $domain,
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Gagal mengambil detail domain',
            ];
        }
    }
}