<?php

// controller/dashboardusers/domain/DomainController.php

require_once __DIR__ . '/../../../models/dashboardusers/domain/Domain.php';

class DomainController
{
    private string $resellerUrl;
    private string $checkEndpoint;
    private string $apiUsername;
    private string $apiPassword;

    private ?Domain $domainModel = null;

    public function __construct()
    {
        $config = require __DIR__ . '/../../../config/srsx.php';

        $this->resellerUrl = rtrim(
            (string) ($config['reseller_url'] ?? ''),
            '/'
        );

        $this->checkEndpoint = '/' . ltrim(
            (string) ($config['check_endpoint'] ?? '/api/domain/check'),
            '/'
        );

        $this->apiUsername = trim(
            (string) ($config['api_username'] ?? '')
        );

        $this->apiPassword = (string) (
            $config['api_password'] ?? ''
        );
    }


    /**
     * ============================================================
     * CHECK DOMAIN
     * ============================================================
     *
     * Mengecek availability domain melalui API SRS-X.
     *
     * Dokumentasi SRS-X:
     *
     * username = API username
     * password = SHA-256 dari password API
     * domain   = nama domain
     */
    public function checkDomain($domainName): array
    {
        /*
         * --------------------------------------------------------
         * VALIDASI INPUT
         * --------------------------------------------------------
         */

        if (empty($domainName)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Nama domain wajib diisi'
            ];
        }


        /*
         * --------------------------------------------------------
         * BERSIHKAN DOMAIN
         * --------------------------------------------------------
         */

        $domainName = strtolower(
            trim((string) $domainName)
        );

        // Hapus http:// atau https://
        $domainName = preg_replace(
            '#^https?://#i',
            '',
            $domainName
        );

        // Hapus www.
        $domainName = preg_replace(
            '#^www\.#i',
            '',
            $domainName
        );

        // Hapus slash di belakang
        $domainName = rtrim(
            $domainName,
            '/'
        );


        /*
         * --------------------------------------------------------
         * VALIDASI FORMAT DOMAIN
         * --------------------------------------------------------
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
         * --------------------------------------------------------
         * CEK KONFIGURASI
         * --------------------------------------------------------
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
         * --------------------------------------------------------
         * URL API
         * --------------------------------------------------------
         */

        $apiUrl =
            $this->resellerUrl .
            $this->checkEndpoint;


        /*
         * --------------------------------------------------------
         * PASSWORD
         * --------------------------------------------------------
         *
         * SRS-X mengharuskan password API
         * dikirim menggunakan SHA-256.
         *
         * BUKAN password asli.
         */

        $passwordHash = hash(
            'sha256',
            $this->apiPassword
        );


        /*
         * --------------------------------------------------------
         * DATA POST
         * --------------------------------------------------------
         */

        $post = [
            'username' => $this->apiUsername,
            'password' => $passwordHash,
            'domain'   => $domainName
        ];


        /*
         * --------------------------------------------------------
         * CEK CURL
         * --------------------------------------------------------
         */

        if (!function_exists('curl_init')) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' =>
                    'PHP cURL belum aktif'
            ];
        }


        /*
         * --------------------------------------------------------
         * CURL
         * --------------------------------------------------------
         */

        $ch = curl_init();

        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL =>
                    $apiUrl,

                CURLOPT_POST =>
                    true,

                CURLOPT_POSTFIELDS =>
                    http_build_query(
                        $post,
                        '',
                        '&'
                    ),

                CURLOPT_RETURNTRANSFER =>
                    true,

                CURLOPT_CONNECTTIMEOUT =>
                    10,

                CURLOPT_TIMEOUT =>
                    30,

                CURLOPT_USERAGENT =>
                    'HostingWebID-DomainChecker/1.0',

                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/xml,text/xml,*/*'
                ],
            ]
        );


        /*
         * --------------------------------------------------------
         * EKSEKUSI
         * --------------------------------------------------------
         */

        $response = curl_exec($ch);

        $curlError = curl_error($ch);

        $httpCode = (int) curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        curl_close($ch);


        /*
         * --------------------------------------------------------
         * CURL ERROR / HTTP ERROR
         * --------------------------------------------------------
         */

        if (
            $response === false ||
            $httpCode < 200 ||
            $httpCode >= 300
        ) {
            return [
                'status' => 'error',

                'code' => 502,

                'message' =>
                    (
                        $httpCode === 401 ||
                        $httpCode === 403
                    )
                        ? 'Akses API Digital Registra ditolak. Periksa kredensial API atau whitelist IP.'
                        : 'Tidak dapat terhubung ke Digital Registra',

                'provider_http_code' =>
                    $httpCode,

                'provider_error' =>
                    $curlError,

                'provider_response' =>
                    $response !== false
                        ? substr($response, 0, 1000)
                        : null
            ];
        }


        /*
         * --------------------------------------------------------
         * PARSE XML
         * --------------------------------------------------------
         */

        libxml_use_internal_errors(true);

        $xml = simplexml_load_string(
            $response
        );


        if ($xml === false) {

            $xmlErrors = [];

            foreach (
                libxml_get_errors()
                as $error
            ) {
                $xmlErrors[] =
                    trim($error->message);
            }

            libxml_clear_errors();

            return [
                'status' => 'error',

                'code' => 502,

                'message' =>
                    'Response Digital Registra tidak valid',

                'provider_http_code' =>
                    $httpCode,

                'provider_response' =>
                    substr($response, 0, 1000),

                'xml_error' =>
                    $xmlErrors
            ];
        }


        /*
         * --------------------------------------------------------
         * AMBIL RESULT CODE
         * --------------------------------------------------------
         */

        $resultCode =
            isset($xml->result->resultCode)
                ? trim(
                    (string)
                    $xml->result->resultCode
                )
                : '';


        /*
         * --------------------------------------------------------
         * AMBIL RESULT MESSAGE
         * --------------------------------------------------------
         */

        $resultMessage =
            isset($xml->result->resultMsg)
                ? trim(
                    (string)
                    $xml->result->resultMsg
                )
                : '';


        /*
         * --------------------------------------------------------
         * DATA RESPONSE
         * --------------------------------------------------------
         */

        $extension =
            pathinfo(
                $domainName,
                PATHINFO_EXTENSION
            );

        $data = [
            'domain' =>
                $domainName,

            'extension' =>
                $extension !== ''
                    ? '.' . strtolower($extension)
                    : '',

            'provider_code' =>
                $resultCode,

            'provider_message' =>
                $resultMessage
        ];


        /*
         * --------------------------------------------------------
         * DOMAIN AVAILABLE
         * --------------------------------------------------------
         *
         * SRS-X:
         * 1000 = Domain Available
         */

        if ($resultCode === '1000') {

            return [
                'status' => 'success',

                'code' => 200,

                'available' => true,

                'message' =>
                    'Domain tersedia',

                'data' => $data
            ];
        }


        /*
         * --------------------------------------------------------
         * DOMAIN UNAVAILABLE
         * --------------------------------------------------------
         *
         * Jika SRS-X mengembalikan kode 2400,
         * anggap domain tidak tersedia.
         */

        if ($resultCode === '2400') {

            return [
                'status' => 'success',

                'code' => 200,

                'available' => false,

                'message' =>
                    'Domain tidak tersedia',

                'data' => $data
            ];
        }


        /*
         * --------------------------------------------------------
         * RESPONSE PROVIDER LAIN
         * --------------------------------------------------------
         */

        return [
            'status' => 'error',

            'code' => 502,

            'message' =>
                $resultMessage !== ''
                    ? $resultMessage
                    : 'Digital Registra gagal memproses pengecekan domain',

            'data' =>
                array_merge(
                    $data,
                    [
                        'available' => null
                    ]
                )
        ];
    }


    /**
     * ============================================================
     * BATCH CHECK DOMAIN
     * ============================================================
     *
     * Mengecek availability domain di semua TLD sekaligus
     * menggunakan curl_multi untuk request paralel.
     */
    public function batchCheckDomain($baseName): array
    {
        /*
         * --------------------------------------------------------
         * VALIDASI INPUT
         * --------------------------------------------------------
         */

        if (empty($baseName)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Nama domain wajib diisi'
            ];
        }

        $baseName = strtolower(
            trim((string) $baseName)
        );

        // Hapus http:// atau https://
        $baseName = preg_replace(
            '#^https?://#i',
            '',
            $baseName
        );

        // Hapus www.
        $baseName = preg_replace(
            '#^www\.#i',
            '',
            $baseName
        );

        // Hapus slash di belakang
        $baseName = rtrim($baseName, '/');

        // Hapus ekstensi jika user memasukkan .com dll
        $baseName = preg_replace(
            '/\.(?:co\.id|it\.com|com|net|org|id|xyz|info|online|cloud|site|store|tech|dev|io|biz|us|me|tv|cc|buzz|lol|mom)$/i',
            '',
            $baseName
        );

        // Validasi nama domain dasar
        if (
            !preg_match(
                '/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/i',
                $baseName
            )
        ) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Format nama domain tidak valid'
            ];
        }


        /*
         * --------------------------------------------------------
         * CEK KONFIGURASI
         * --------------------------------------------------------
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

        if (!function_exists('curl_init')) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'PHP cURL belum aktif'
            ];
        }


        /*
         * --------------------------------------------------------
         * AMBIL SEMUA EKSTENSI DARI DATABASE
         * --------------------------------------------------------
         */

        $this->domainModel ??= new Domain();

        $extensions = $this->domainModel
            ->getAllExtensionsWithPrices();

        if (empty($extensions)) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Belum ada data ekstensi domain di database'
            ];
        }


        /*
         * --------------------------------------------------------
         * SETUP CURL MULTI
         * --------------------------------------------------------
         */

        $apiUrl =
            $this->resellerUrl .
            $this->checkEndpoint;

        $passwordHash = hash(
            'sha256',
            $this->apiPassword
        );

        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $domainMap = [];

        foreach ($extensions as $ext) {

            $extension = $ext['extension'] ?? '';
            $fullDomain = $baseName . $extension;

            $post = [
                'username' => $this->apiUsername,
                'password' => $passwordHash,
                'domain'   => $fullDomain
            ];

            $ch = curl_init();

            curl_setopt_array(
                $ch,
                [
                    CURLOPT_URL =>
                        $apiUrl,

                    CURLOPT_POST =>
                        true,

                    CURLOPT_POSTFIELDS =>
                        http_build_query(
                            $post,
                            '',
                            '&'
                        ),

                    CURLOPT_RETURNTRANSFER =>
                        true,

                    CURLOPT_CONNECTTIMEOUT =>
                        10,

                    CURLOPT_TIMEOUT =>
                        30,

                    CURLOPT_USERAGENT =>
                        'HostingWebID-DomainChecker/1.0',

                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'Accept: application/xml,text/xml,*/*'
                    ],
                ]
            );

            curl_multi_add_handle(
                $multiHandle,
                $ch
            );

            $index = (int) $ch;
            $curlHandles[$index] = $ch;

            $domainMap[$index] = [
                'domain'      => $fullDomain,
                'extension'   => $extension,
                'price'       => (int) ($ext['price'] ?? 0),
                'promo_price' => isset($ext['promo_price']) && $ext['promo_price'] !== null && $ext['promo_price'] !== ''
                    ? (int) $ext['promo_price']
                    : null,
                'domain_id'   => (int) ($ext['id'] ?? 0),
            ];
        }


        /*
         * --------------------------------------------------------
         * EKSEKUSI CURL MULTI
         * --------------------------------------------------------
         */

        $running = null;

        do {
            curl_multi_exec(
                $multiHandle,
                $running
            );

            curl_multi_select($multiHandle);

        } while ($running > 0);


        /*
         * --------------------------------------------------------
         * PARSE SEMUA RESPONSE
         * --------------------------------------------------------
         */

        $results = [];

        libxml_use_internal_errors(true);

        foreach ($curlHandles as $index => $ch) {

            $response = curl_multi_getcontent($ch);
            $httpCode = (int) curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );

            curl_multi_remove_handle(
                $multiHandle,
                $ch
            );

            curl_close($ch);

            $info = $domainMap[$index];

            // Default: error / unknown
            $available = null;
            $providerCode = '';
            $providerMessage = '';

            if (
                $response !== false &&
                $httpCode >= 200 &&
                $httpCode < 300
            ) {
                $xml = simplexml_load_string(
                    $response
                );

                if ($xml !== false) {

                    $providerCode =
                        isset($xml->result->resultCode)
                            ? trim(
                                (string)
                                $xml->result->resultCode
                            )
                            : '';

                    $providerMessage =
                        isset($xml->result->resultMsg)
                            ? trim(
                                (string)
                                $xml->result->resultMsg
                            )
                            : '';

                    if ($providerCode === '1000') {
                        $available = true;
                    } elseif ($providerCode === '2400') {
                        $available = false;
                    }
                }
            }

            $results[] = [
                'domain'           => $info['domain'],
                'extension'        => $info['extension'],
                'available'        => $available,
                'price'            => $info['price'],
                'promo_price'      => $info['promo_price'],
                'domain_id'        => $info['domain_id'],
                'provider_code'    => $providerCode,
                'provider_message' => $providerMessage,
            ];
        }

        libxml_clear_errors();

        curl_multi_close($multiHandle);


        /*
         * --------------------------------------------------------
         * URUTKAN: Available dulu, lalu unavailable
         * --------------------------------------------------------
         */

        usort($results, function ($a, $b) {
            if ($a['available'] === true && $b['available'] !== true) {
                return -1;
            }
            if ($a['available'] !== true && $b['available'] === true) {
                return 1;
            }
            return 0;
        });


        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Batch domain check selesai',
            'data' => [
                'query' => $baseName,
                'total' => count($results),
                'results' => $results
            ]
        ];
    }


    /*
     * ============================================================
     * METHOD LAIN DARI CONTROLLER KAMU
     * ============================================================
     */

    public function getAllDomains(): array
    {
        try {

            $this->domainModel ??=
                new Domain();

            return [
                'status' => 'success',

                'code' => 200,

                'message' =>
                    'Data domain berhasil diambil',

                'data' =>
                    $this->domainModel
                        ->getAllDomains()
            ];

        } catch (Throwable $exception) {

            return [
                'status' => 'error',

                'code' => 500,

                'message' =>
                    'Gagal mengambil data domain'
            ];
        }
    }


    public function getDomainById($id): array
    {
        if (
            !is_numeric($id) ||
            (int) $id < 1
        ) {
            return [
                'status' => 'error',

                'code' => 400,

                'message' =>
                    'ID domain tidak valid'
            ];
        }

        try {

            $this->domainModel ??=
                new Domain();

            $domain =
                $this->domainModel->findById(
                            (int) $id
                        );

            if (!$domain) {
                return [
                    'status' => 'error',

                    'code' => 404,

                    'message' =>
                        'Domain tidak ditemukan'
                ];
            }

            return [
                'status' => 'success',

                'code' => 200,

                'message' =>
                    'Data domain berhasil diambil',

                'data' => $domain
            ];

        } catch (Throwable $exception) {

            return [
                'status' => 'error',

                'code' => 500,

                'message' =>
                    'Gagal mengambil data domain'
            ];
        }
    }
}