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
     * Normalisasi dan parsing domain input (misal: "https://namaku", "namaku.com", "namaku")
     */
    public function normalizeDomainInput(string $input, string $defaultExt = '.com'): ?array
    {
        $input = strtolower(trim($input));
        $input = preg_replace('~^https?://~i', '', $input);
        $input = preg_replace('~^www.~i', '', $input);
        $input = preg_replace('~[/?#].*$~', '', $input);
        $input = trim($input, "/. \t\n\r\0\x0B");

        if ($input === '') {
            return null;
        }

        // Multi-part extensions
        $multiPartRegex = '/\.(co\.id|it\.com|my\.id|biz\.id|web\.id|or\.id|ac\.id|sch\.id|go\.id|net\.id|mil\.id)$/i';
        // Single part extensions
        $singlePartRegex = '/\.([a-z0-9-]{2,63})$/i';

        $extension = '';
        $baseName = $input;

        if (preg_match($multiPartRegex, $input, $m)) {
            $extension = '.' . strtolower($m[1]);
            $baseName = substr($input, 0, -strlen($extension));
        } elseif (preg_match($singlePartRegex, $input, $m)) {
            $extension = '.' . strtolower($m[1]);
            $baseName = substr($input, 0, -strlen($extension));
        } else {
            $defaultExt = strtolower(trim($defaultExt));
            if ($defaultExt !== '' && $defaultExt[0] !== '.') {
                $defaultExt = '.' . $defaultExt;
            }
            $extension = $defaultExt ?: '.com';
        }

        $baseName = preg_replace('/[^a-z0-9-]/i', '', $baseName);
        $baseName = trim($baseName, '-');

        if ($baseName === '' || strlen($baseName) > 63) {
            return null;
        }

        return [
            'base_name'   => $baseName,
            'extension'   => $extension,
            'full_domain' => $baseName . $extension,
        ];
    }

    /**
     * Helper Fallback: Pengecekan Ketersediaan Domain via DNS over HTTPS (Google / Cloudflare)
     */
    public function checkDomainViaDnsOrDoH(string $domain): bool
    {
        $domain = strtolower(trim($domain));
        
        // 1. Coba DNS over HTTPS (Google DoH - super fast & bypass local DNS limitations)
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "https://dns.google/resolve?name=" . urlencode($domain) . "&type=A",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'HostingWebID-DomainAvailability/1.0',
            ]);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response !== false && $httpCode === 200) {
                $json = json_decode($response, true);
                if (is_array($json)) {
                    $status = (int) ($json['Status'] ?? 0);
                    $hasAnswer = !empty($json['Answer']) && is_array($json['Answer']) && count($json['Answer']) > 0;
                    
                    // Status 0 dengan Answer = Domain sudah terdaftar dan aktif
                    if ($status === 0 && $hasAnswer) {
                        return false;
                    }
                    // Status 3 = NXDOMAIN (Domain belum ada / tersedia)
                    if ($status === 3 || (!$hasAnswer && empty($json['Authority']))) {
                        return true;
                    }
                }
            }
        }

        // 2. Fallback ke checkdnsrr bawaan PHP
        if (function_exists('checkdnsrr')) {
            $hasDns = @checkdnsrr($domain, 'ANY') || 
                      @checkdnsrr($domain, 'A') || 
                      @checkdnsrr($domain, 'NS') || 
                      @checkdnsrr($domain, 'MX') || 
                      @checkdnsrr($domain, 'SOA');
            if ($hasDns) {
                return false;
            }
        }

        // 3. Fallback gethostbyname
        $ip = @gethostbyname($domain);
        if ($ip !== $domain && !empty($ip)) {
            return false;
        }

        return true;
    }

    /**
     * ============================================================
     * CHECK DOMAIN (Single + Multi TLD Suggestions like idwebhost)
     * ============================================================
     */
    public function checkDomain($domainName, $defaultExt = '.com', bool $includeSuggestions = true): array
    {
        if (empty($domainName)) {
            return [
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Nama domain wajib diisi'
            ];
        }

        $parsed = $this->normalizeDomainInput((string) $domainName, (string) $defaultExt);
        if (!$parsed) {
            return [
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Format nama domain tidak valid'
            ];
        }

        $baseName     = $parsed['base_name'];
        $searchedExt  = $parsed['extension'];
        $searchedFull = $parsed['full_domain'];

        $this->domainModel ??= new Domain();
        $catalog = $this->domainModel->getAllExtensionsWithPrices();

        $catalogMap = [];
        foreach ($catalog as $item) {
            $catalogMap[strtolower($item['extension'])] = $item;
        }

        $extsToCheck = [];
        $extsToCheck[$searchedExt] = $catalogMap[$searchedExt] ?? [
            'extension'      => $searchedExt,
            'price'          => 125000,
            'original_price' => 209900,
            'promo_price'    => 125000,
            'badge'          => '',
            'is_popular'     => false
        ];

        if ($includeSuggestions) {
            foreach ($catalog as $item) {
                $ext = strtolower($item['extension']);
                if (!isset($extsToCheck[$ext])) {
                    $extsToCheck[$ext] = $item;
                }
            }
        }

        $checkResults = [];

        // 1. Cek via SRS-X jika credentials tersedia
        $useSrsx = ($this->resellerUrl !== '' && $this->apiUsername !== '' && $this->apiPassword !== '' && function_exists('curl_init'));
        $srsxOutputs = [];

        if ($useSrsx) {
            $apiUrl = $this->resellerUrl . $this->checkEndpoint;
            $passwordHash = hash('sha256', $this->apiPassword);
            $multiHandle = curl_multi_init();
            $curlHandles = [];
            $metaMap = [];

            foreach ($extsToCheck as $ext => $info) {
                $fullDomain = $baseName . $ext;
                $post = [
                    'username' => $this->apiUsername,
                    'password' => $passwordHash,
                    'domain'   => $fullDomain
                ];

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => $apiUrl,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => http_build_query($post, '', '&'),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_TIMEOUT        => 5,
                    CURLOPT_USERAGENT      => 'HostingWebID-DomainChecker/1.0',
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'Accept: application/xml,text/xml,*/*'
                    ],
                ]);

                curl_multi_add_handle($multiHandle, $ch);
                $id = (int)$ch;
                $curlHandles[$id] = $ch;
                $metaMap[$id] = [
                    'full_domain' => $fullDomain,
                    'extension'   => $ext,
                    'info'        => $info
                ];
            }

            $running = null;
            do {
                curl_multi_exec($multiHandle, $running);
                curl_multi_select($multiHandle);
            } while ($running > 0);

            libxml_use_internal_errors(true);

            foreach ($curlHandles as $id => $ch) {
                $resp     = curl_multi_getcontent($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);

                $meta       = $metaMap[$id];
                $ext        = $meta['extension'];
                $fullDomain = $meta['full_domain'];

                $available    = null;
                $providerCode = '';
                $providerMsg  = '';

                if ($resp !== false && $httpCode >= 200 && $httpCode < 300) {
                    $xml = simplexml_load_string($resp);
                    if ($xml !== false) {
                        $providerCode = isset($xml->result->resultCode) ? trim((string)$xml->result->resultCode) : '';
                        $providerMsg  = isset($xml->result->resultMsg) ? trim((string)$xml->result->resultMsg) : '';
                        
                        // Kode 1000: Domain Available
                        if ($providerCode === '1000') {
                            $available = true;
                        } elseif ($providerCode === '2000' || strpos(strtolower($providerMsg), 'already registered') !== false || strpos(strtolower($providerMsg), 'taken') !== false) {
                            $available = false;
                        }
                    }
                }

                $srsxOutputs[$ext] = [
                    'available'     => $available,
                    'provider_code' => $providerCode,
                    'provider_msg'  => $providerMsg,
                ];
            }
            curl_multi_close($multiHandle);
            libxml_clear_errors();
        }

        // Multi DoH Check untuk domain-domain yang belum valid dari registrar
        $dohMulti = curl_multi_init();
        $dohHandles = [];
        $dohMeta = [];

        foreach ($extsToCheck as $ext => $info) {
            $fullDomain = $baseName . $ext;
            $srsxAvail = $srsxOutputs[$ext]['available'] ?? null;

            if ($srsxAvail === null) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => "https://dns.google/resolve?name=" . urlencode($fullDomain) . "&type=A",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 2,
                    CURLOPT_TIMEOUT => 4,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => 'HostingWebID-DomainAvailability/1.0',
                ]);
                curl_multi_add_handle($dohMulti, $ch);
                $id = (int)$ch;
                $dohHandles[$id] = $ch;
                $dohMeta[$id] = $ext;
            }
        }

        if (!empty($dohHandles)) {
            $running = null;
            do {
                curl_multi_exec($dohMulti, $running);
                curl_multi_select($dohMulti);
            } while ($running > 0);

            foreach ($dohHandles as $id => $ch) {
                $resp = curl_multi_getcontent($ch);
                curl_multi_remove_handle($dohMulti, $ch);
                curl_close($ch);

                $ext = $dohMeta[$id];
                $fullDomain = $baseName . $ext;
                $available = true;

                if ($resp !== false) {
                    $json = json_decode($resp, true);
                    if (is_array($json)) {
                        $status = (int) ($json['Status'] ?? 0);
                        $hasAnswer = !empty($json['Answer']) && is_array($json['Answer']) && count($json['Answer']) > 0;
                        if ($status === 0 && $hasAnswer) {
                            $available = false; // Domain registered
                        } elseif ($status === 3) {
                            $available = true; // Domain available
                        }
                    }
                }

                if (!isset($srsxOutputs[$ext])) {
                    $srsxOutputs[$ext] = ['provider_code' => '', 'provider_msg' => ''];
                }
                $srsxOutputs[$ext]['available'] = $available;
            }
            curl_multi_close($dohMulti);
        }

        // Susun hasil pengecekan akhir
        foreach ($extsToCheck as $ext => $info) {
            $fullDomain = $baseName . $ext;
            $srsx = $srsxOutputs[$ext] ?? ['available' => null, 'provider_code' => '', 'provider_msg' => ''];
            
            $available = $srsx['available'];
            if ($available === null) {
                $available = $this->checkDomainViaDnsOrDoH($fullDomain);
            }

            $price         = (int)($info['price'] ?? 125000);
            $originalPrice = (int)($info['original_price'] ?? $price);
            if ($originalPrice < $price) {
                $originalPrice = $price;
            }
            $hasPromo = ($originalPrice > $price);

            $checkResults[$ext] = [
                'domain'                   => $fullDomain,
                'base_name'                => $baseName,
                'extension'                => $ext,
                'available'                => $available,
                'price'                    => $price,
                'original_price'           => $originalPrice,
                'price_formatted'          => 'Rp ' . number_format($price, 0, ',', '.'),
                'original_price_formatted' => 'Rp ' . number_format($originalPrice, 0, ',', '.'),
                'has_promo'                => $hasPromo,
                'badge'                    => $info['badge'] ?? '',
                'is_popular'               => !empty($info['is_popular']),
                'domain_id'                => (int)($info['id'] ?? 0),
                'provider_code'            => $srsx['provider_code'] ?? '',
                'provider_message'         => $srsx['provider_msg'] ?? '',
                'actions'                  => [
                    'can_buy'         => ($available === true),
                    'can_transfer'    => ($available === false),
                    'can_buy_hosting' => true,
                    'buy_text'        => 'Beli',
                    'transfer_text'   => 'Transfer',
                    'hosting_text'    => 'Beli Hosting',
                    'buy_url'         => '/order?domain=' . urlencode($fullDomain),
                    'transfer_url'    => '/order-transfer?domain=' . urlencode($fullDomain),
                    'hosting_url'     => '/order-hosting?domain=' . urlencode($fullDomain),
                ]
            ];
        }

        $searchedResult = $checkResults[$searchedExt] ?? null;
        if (!$searchedResult) {
            $searchedResult = [
                'domain'                   => $searchedFull,
                'base_name'                => $baseName,
                'extension'                => $searchedExt,
                'available'                => true,
                'price'                    => 125000,
                'original_price'           => 209900,
                'price_formatted'          => 'Rp 125.000',
                'original_price_formatted' => 'Rp 209.900',
                'has_promo'                => true,
                'badge'                    => '',
                'is_popular'               => false,
                'domain_id'                => 0,
                'provider_code'            => '',
                'provider_message'         => '',
                'actions'                  => [
                    'can_buy'         => true,
                    'can_transfer'    => false,
                    'can_buy_hosting' => true,
                    'buy_text'        => 'Beli',
                    'transfer_text'   => 'Transfer',
                    'hosting_text'    => 'Beli Hosting',
                    'buy_url'         => '/order?domain=' . urlencode($searchedFull),
                    'transfer_url'    => '/order-transfer?domain=' . urlencode($searchedFull),
                    'hosting_url'     => '/order-hosting?domain=' . urlencode($searchedFull),
                ]
            ];
        }

        $suggestions = [];
        foreach ($checkResults as $ext => $res) {
            if ($ext !== $searchedExt) {
                $suggestions[] = $res;
            }
        }

        // Urutkan suggestions: Available lebih dulu, lalu popular
        usort($suggestions, function ($a, $b) {
            if ($a['available'] === true && $b['available'] !== true) return -1;
            if ($a['available'] !== true && $b['available'] === true) return 1;
            if ($a['is_popular'] && !$b['is_popular']) return -1;
            if (!$a['is_popular'] && $b['is_popular']) return 1;
            return 0;
        });

        $allResults = array_merge([$searchedResult], $suggestions);

        return [
            'status'    => 'success',
            'code'      => 200,
            'available' => $searchedResult['available'],
            'message'   => $searchedResult['available'] === true
                ? 'Domain tersedia!'
                : ($searchedResult['available'] === false ? 'Domain tidak tersedia' : 'Pemeriksaan domain selesai'),
            'data'      => array_merge($searchedResult, [
                'searched'    => $searchedResult,
                'suggestions' => $suggestions,
                'results'     => $allResults,
                'total'       => count($allResults),
            ])
        ];
    }

    /**
     * ============================================================
     * BATCH CHECK DOMAIN
     * ============================================================
     */
    public function batchCheckDomain($baseName): array
    {
        return $this->checkDomain($baseName, '.com', true);
    }

    /**
     * ============================================================
     * METHOD LAIN DARI CONTROLLER
     * ============================================================
     */
    public function getAllDomains(): array
    {
        try {
            $this->domainModel ??= new Domain();

            return [
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Data domain berhasil diambil',
                'data'    => $this->domainModel->getAllDomains()
            ];
        } catch (Throwable $exception) {
            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Gagal mengambil data domain'
            ];
        }
    }

    public function getDomainById($id): array
    {
        if (!is_numeric($id) || (int) $id < 1) {
            return [
                'status'  => 'error',
                'code'    => 400,
                'message' => 'ID domain tidak valid'
            ];
        }

        try {
            $this->domainModel ??= new Domain();
            $domain = $this->domainModel->findById((int) $id);

            if (!$domain) {
                return [
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Domain tidak ditemukan'
                ];
            }

            return [
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Data domain berhasil diambil',
                'data'    => $domain
            ];
        } catch (Throwable $exception) {
            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Gagal mengambil data domain'
            ];
        }
    }
}
