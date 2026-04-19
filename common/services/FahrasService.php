<?php

namespace common\services;

use common\services\dto\FahrasVerdict;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * ═══════════════════════════════════════════════════════════════════
 *  FahrasService — Tayseer ↔ Fahras integration component.
 * ═══════════════════════════════════════════════════════════════════
 *
 * Responsibilities:
 *   • Issue verdict requests to {baseUrl}/admin/api/check.php.
 *   • Issue by-name candidate searches to /admin/api/search.php.
 *   • Apply hard timeouts and per-(idNumber, nameHash) caching to
 *     keep the wizard snappy and the Fahras backend protected.
 *   • Always return a {@see FahrasVerdict} — never throw across the
 *     boundary, so the controllers can reason about the outcome
 *     declaratively instead of wrapping every call in try/catch.
 *
 * Fail-closed contract:
 *   When `failurePolicy === 'closed'` (the default) any transport
 *   error, non-2xx status, malformed body, or timeout produces
 *   `FahrasVerdict::failure()` with `verdict = error`. Controllers
 *   should treat that as a block (this is why the wizard refuses
 *   to advance when Fahras is unavailable).
 *
 * Configuration is read from `Yii::$app->params['fahras']`:
 *   enabled, baseUrl, token, clientId, timeoutSec, cacheTtlSec,
 *   failurePolicy, overridePerm, logViewPerm.
 *
 * Registration (backend/config/main.php):
 *   'components' => [
 *       'fahras' => ['class' => \common\services\FahrasService::class],
 *   ],
 *
 * Usage:
 *   $verdict = Yii::$app->fahras->check('1234567890', 'محمد أحمد');
 *   if ($verdict->blocks(Yii::$app->fahras->failurePolicy)) { ... }
 */
class FahrasService extends Component
{
    /* ────── Configuration (with safe defaults) ───────────────── */

    public bool   $enabled        = true;
    public string $baseUrl        = '';
    public ?string $token         = null;
    public string $clientId       = 'tayseer';
    public int    $timeoutSec     = 8;
    public int    $cacheTtlSec    = 300;
    public string $failurePolicy  = 'closed';
    public string $overridePerm   = 'customer.fahras.override';
    public string $logViewPerm    = 'customer.fahras.log.view';

    /** Set true in tests to bypass the cache layer. */
    public bool   $bypassCache    = false;

    /** When false, accept http:// (otherwise refuse non-https for safety). */
    public bool   $requireHttps   = true;

    private string $cacheKeyPrefix = 'fahras:v1';

    /* ────── Lifecycle ───────────────────────────────────────── */

    public function init(): void
    {
        parent::init();

        // Pull params if the caller didn't override at registration time.
        $params = Yii::$app->params['fahras'] ?? [];
        foreach ([
            'enabled','baseUrl','token','clientId','timeoutSec',
            'cacheTtlSec','failurePolicy','overridePerm','logViewPerm',
        ] as $k) {
            if (array_key_exists($k, $params) && $params[$k] !== null) {
                $this->{$k} = $params[$k];
            }
        }

        $this->baseUrl       = rtrim((string)$this->baseUrl, '/');
        $this->failurePolicy = in_array($this->failurePolicy, ['open','closed'], true)
            ? $this->failurePolicy : 'closed';

        if ($this->enabled) {
            if ($this->baseUrl === '') {
                throw new InvalidConfigException('FahrasService: baseUrl is required when enabled=true.');
            }
            if ($this->requireHttps
                && stripos($this->baseUrl, 'https://') !== 0
                && stripos($this->baseUrl, 'http://localhost') !== 0
                && stripos($this->baseUrl, 'http://127.0.0.1') !== 0
            ) {
                throw new InvalidConfigException('FahrasService: baseUrl must use HTTPS in production.');
            }
        }
    }

    /* ────── Public API ──────────────────────────────────────── */

    /**
     * Verdict lookup. Returns a verdict for any input — never throws.
     * If integration is disabled returns VERDICT_NO_RECORD (i.e., allow).
     */
    public function check(string $idNumber, ?string $name = null, ?string $phone = null): FahrasVerdict
    {
        if (!$this->enabled) {
            return FahrasVerdict::noRecord();
        }
        $idNumber = self::cleanId($idNumber);
        $name     = self::cleanText((string)$name);
        $phone    = self::cleanText((string)$phone, 30);

        if ($idNumber === '' && $name === '') {
            return FahrasVerdict::failure('يجب إدخال الرقم الوطني أو الاسم لإجراء الفحص.');
        }
        if ($idNumber !== '' && !preg_match('/^\d{5,20}$/', $idNumber)) {
            return FahrasVerdict::failure('الرقم الوطني المُدخل غير صالح.');
        }

        $cacheKey = $this->buildCacheKey($idNumber, $name);
        if (!$this->bypassCache && Yii::$app->has('cache')) {
            $cached = Yii::$app->cache->get($cacheKey);
            if ($cached instanceof FahrasVerdict) {
                $cached->fromCache = true;
                return $cached;
            }
        }

        $verdict = $this->doRequest('/admin/api/check.php', [
            'token'     => $this->token,
            'client'    => $this->clientId,
            'id_number' => $idNumber,
            'name'      => $name,
            'phone'     => $phone,
        ]);

        // Cache successful verdicts only — never cache transient errors.
        if (!$this->bypassCache
            && Yii::$app->has('cache')
            && $verdict->verdict !== FahrasVerdict::VERDICT_ERROR
        ) {
            Yii::$app->cache->set($cacheKey, $verdict, $this->cacheTtlSec);
        }

        return $verdict;
    }

    /**
     * By-name candidate search (used by the "بحث في الفهرس" modal).
     * Returns the raw rows array; this endpoint does NOT compute a verdict.
     *
     * @return array{ok:bool,results:array,remote_errors:array,error?:string}
     */
    public function searchByName(string $query, int $limit = 20): array
    {
        if (!$this->enabled) {
            return ['ok' => true, 'results' => [], 'remote_errors' => []];
        }
        $query = self::cleanText($query);
        $limit = max(1, min($limit, 50));
        if (mb_strlen($query, 'UTF-8') < 3) {
            return ['ok' => false, 'results' => [], 'remote_errors' => [], 'error' => 'short_query'];
        }

        $raw = $this->httpGet('/admin/api/search.php', [
            'token'  => $this->token,
            'client' => $this->clientId,
            'q'      => $query,
            'limit'  => $limit,
        ]);

        if (!$raw['ok']) {
            return [
                'ok'            => false,
                'results'       => [],
                'remote_errors' => [],
                'error'         => $raw['error'] ?? 'transport_error',
            ];
        }
        $body = $raw['json'] ?? [];
        return [
            'ok'            => (bool)($body['ok'] ?? false),
            'results'       => (array)($body['results'] ?? []),
            'remote_errors' => (array)($body['remote_errors'] ?? []),
            'request_id'    => $body['request_id'] ?? null,
        ];
    }

    /* ────── Private — HTTP plumbing ─────────────────────────── */

    private function doRequest(string $path, array $params): FahrasVerdict
    {
        $startedAt = microtime(true);
        $r = $this->httpGet($path, $params);

        if (!$r['ok']) {
            return FahrasVerdict::failure(
                'تعذّر الاتصال بنظام الفهرس — حاول لاحقاً.',
                $r['error'] ?? 'transport_error',
                $r['status'] ?? null
            );
        }

        $body = $r['json'] ?? null;
        if (!is_array($body)) {
            return FahrasVerdict::failure('استجابة الفهرس غير قابلة للقراءة.', 'invalid_json', $r['status']);
        }
        if (empty($body['ok']) || empty($body['verdict'])) {
            $err = (string)($body['error'] ?? 'unknown_error');
            return FahrasVerdict::failure(
                'رفض الفهرس الطلب: ' . $err,
                $err,
                $r['status']
            );
        }
        $body['http_status'] = $r['status'];
        $body['duration_ms'] = (int)((microtime(true) - $startedAt) * 1000);
        return FahrasVerdict::fromArray($body);
    }

    /**
     * Low-level HTTP GET wrapper. Returns:
     *   ['ok'=>bool, 'status'=>int|null, 'json'=>array|null, 'body'=>string|null, 'error'=>string|null]
     */
    private function httpGet(string $path, array $params): array
    {
        $url = $this->baseUrl . $path . '?' . http_build_query($params);

        if (!function_exists('curl_init')) {
            return ['ok' => false, 'status' => null, 'json' => null, 'body' => null, 'error' => 'curl_missing'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => $this->timeoutSec,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeoutSec),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'X-Tayseer-Client: ' . $this->clientId,
            ],
            CURLOPT_USERAGENT      => 'Tayseer-Fahras/1.0 (+https://tayseer.aqssat.co)',
        ]);

        $body   = curl_exec($ch);
        $errNo  = curl_errno($ch);
        $errMsg = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errNo) {
            // Logged at warning level — token never reaches the log.
            Yii::warning(
                'FahrasService HTTP error: ' . $errMsg . ' (errno=' . $errNo . ') for ' . $path,
                'fahras'
            );
            return ['ok' => false, 'status' => $status ?: null, 'json' => null, 'body' => null, 'error' => 'curl_' . $errNo];
        }

        if ($body === false || $body === '') {
            return ['ok' => false, 'status' => $status, 'json' => null, 'body' => null, 'error' => 'empty_response'];
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            return ['ok' => false, 'status' => $status, 'json' => null, 'body' => $body, 'error' => 'invalid_json'];
        }

        if ($status >= 400) {
            return ['ok' => false, 'status' => $status, 'json' => $json, 'body' => $body, 'error' => 'http_' . $status];
        }
        return ['ok' => true, 'status' => $status, 'json' => $json, 'body' => $body, 'error' => null];
    }

    /* ────── Private — input hygiene ─────────────────────────── */

    private function buildCacheKey(string $idNumber, string $name): string
    {
        $nameHash = $name === '' ? '' : substr(sha1(self::normalizeName($name)), 0, 12);
        return $this->cacheKeyPrefix . ':' . $idNumber . ':' . $nameHash;
    }

    private static function cleanId(string $v): string
    {
        return preg_replace('/\D+/', '', trim($v)) ?? '';
    }

    private static function cleanText(string $v, int $max = 250): string
    {
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string)$v) ?? '';
        return mb_substr(trim($v), 0, $max, 'UTF-8');
    }

    private static function normalizeName(string $name): string
    {
        $name = str_replace(['أ','إ','آ'], 'ا', $name);
        $name = str_replace('ة', 'ه', $name);
        $name = str_replace('ى', 'ي', $name);
        $name = mb_strtolower(trim($name), 'UTF-8');
        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }
}
