<?php

namespace backend\modules\companyManagement\models;

use Yii;

class GodaddyDnsService
{
    private string $apiKey;
    private string $apiSecret;
    private string $domain;
    private string $baseUrl = 'https://api.godaddy.com';

    public function __construct()
    {
        $config = Yii::$app->params['godaddy'] ?? [];
        $this->apiKey    = $config['apiKey'] ?? '';
        $this->apiSecret = $config['apiSecret'] ?? '';
        $this->domain    = $config['domain'] ?? 'aqssat.co';

        if (empty($this->apiKey) || empty($this->apiSecret)) {
            throw new \RuntimeException('GoDaddy API credentials not configured in params-local.php');
        }
    }

    /**
     * Create an A record for a subdomain pointing to the given IP.
     */
    public function createARecord(string $subdomain, string $ip): array
    {
        $url = "{$this->baseUrl}/v1/domains/{$this->domain}/records";

        $payload = json_encode([[
            'type' => 'A',
            'name' => $subdomain,
            'data' => $ip,
            'ttl'  => 600,
        ]]);

        $result = $this->request('PATCH', $url, $payload);

        if ($result['httpCode'] === 200 || $result['httpCode'] === 204) {
            return ['success' => true, 'message' => "تم إنشاء سجل A: {$subdomain} -> {$ip}"];
        }

        $body = json_decode($result['body'], true);
        $errorMsg = $body['message'] ?? ($body['code'] ?? "HTTP {$result['httpCode']}");

        return ['success' => false, 'message' => "GoDaddy API error: {$errorMsg}"];
    }

    /**
     * Check if a DNS record already exists for a subdomain.
     */
    public function getARecord(string $subdomain): ?array
    {
        $url = "{$this->baseUrl}/v1/domains/{$this->domain}/records/A/{$subdomain}";
        $result = $this->request('GET', $url);

        if ($result['httpCode'] === 200) {
            $records = json_decode($result['body'], true);
            return !empty($records) ? $records[0] : null;
        }

        return null;
    }

    /**
     * Verify DNS propagation by performing a lookup.
     */
    public function verifyDns(string $fqdn, string $expectedIp): bool
    {
        $resolved = gethostbyname($fqdn);
        return $resolved === $expectedIp;
    }

    /**
     * List all A records for the domain.
     */
    public function listARecords(): array
    {
        $url = "{$this->baseUrl}/v1/domains/{$this->domain}/records/A";
        $result = $this->request('GET', $url);

        if ($result['httpCode'] === 200) {
            return json_decode($result['body'], true) ?: [];
        }

        return [];
    }

    private function request(string $method, string $url, ?string $body = null): array
    {
        $ch = curl_init($url);

        $headers = [
            "Authorization: sso-key {$this->apiKey}:{$this->apiSecret}",
            "Content-Type: application/json",
            "Accept: application/json",
        ];

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ];

        if ($method === 'PATCH') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            $opts[CURLOPT_POSTFIELDS] = $body;
        } elseif ($method === 'PUT') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
            $opts[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Yii::error("GoDaddy API cURL error: {$error}", 'godaddy');
        }

        return [
            'httpCode' => $httpCode,
            'body'     => $response,
            'error'    => $error,
        ];
    }
}
