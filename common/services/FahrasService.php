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
    /**
     * Verdict cache TTL in seconds.
     *
     * NOTE — kept as a configuration knob for backward compatibility, but the
     * `check()` path no longer reads or writes the cache regardless of value.
     * Verdicts describe a fast-changing fact (whether ANY company has just
     * recorded a contract for this national ID); a stale "no_record" served
     * from cache had been bypassing the same-company / blocking logic in
     * production. We now always go to the wire so the rep sees ground truth.
     * Set to 0 explicitly via params if you want the legacy behaviour to
     * remain visibly disabled in your config.
     */
    public int    $cacheTtlSec    = 0;
    public string $failurePolicy  = 'closed';
    public string $overridePerm   = 'customer.fahras.override';
    public string $logViewPerm    = 'customer.fahras.log.view';

    /**
     * Canonical Fahras account name for THIS Tayseer install (e.g. "جدل",
     * "نماء", "وتر", "بسيل", "زجل", "عالم المجد"). Configured per-environment
     * via params/params-local. When set, the wizard's verdict layer uses it
     * to detect "customer is already ours, suggest adding a contract instead
     * of re-creating the customer". When null, the optimisation is skipped.
     */
    public ?string $companyName  = null;

    /** Set true in tests to bypass the cache layer. */
    public bool   $bypassCache    = false;

    /** When false, accept http:// (otherwise refuse non-https for safety). */
    public bool   $requireHttps   = true;

    /* ────── Lifecycle ───────────────────────────────────────── */

    public function init(): void
    {
        parent::init();

        // Pull params if the caller didn't override at registration time.
        $params = Yii::$app->params['fahras'] ?? [];
        foreach ([
            'enabled','baseUrl','token','clientId','timeoutSec',
            'cacheTtlSec','failurePolicy','overridePerm','logViewPerm',
            'companyName',
        ] as $k) {
            if (array_key_exists($k, $params) && $params[$k] !== null) {
                $this->{$k} = $params[$k];
            }
        }
        // Normalise the company name once at boot so callers don't have to
        // think about whitespace / case folding (Arabic strings are largely
        // case-insensitive but trimming + collapsing whitespace is safer).
        if ($this->companyName !== null) {
            $this->companyName = self::normaliseCompany($this->companyName);
            if ($this->companyName === '') $this->companyName = null;
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

        // ── No cache, on purpose ──────────────────────────────────────────
        // Verdicts express a fast-changing fact: whether ANY company has,
        // possibly milliseconds ago, just registered a contract for this
        // national ID. A cached "no_record" served seconds after the customer
        // was actually inserted on the Fahras side caused the same-company
        // CTA + hard-block logic to silently no-op in production.
        // Going to the wire on every call is cheap (Fahras responds in
        // ~150-400 ms) and gives the rep ground truth at the exact moment
        // they finish typing the id + name. The previous cache layer
        // (`Yii::$app->cache->get/set` + `buildCacheKey`) is intentionally
        // gone; `cacheTtlSec` and `bypassCache` are kept only so external
        // configs and the test harness keep type-checking.

        $verdict = $this->doRequest('/admin/api/check.php', [
            'token'     => $this->token,
            'client'    => $this->clientId,
            'id_number' => $idNumber,
            'name'      => $name,
            'phone'     => $phone,
        ]);

        // `fromCache` is now structurally always false. We still surface the
        // field in the DTO + log table for schema stability.
        $verdict->fromCache = false;

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
        // NOTE: do NOT call curl_close() — it has been a no-op since PHP 8.0
        // (handles are objects freed on scope exit) and PHP 8.5 emits
        // E_DEPRECATED for it, which corrupts the JSON response on this
        // very AJAX action. Letting $ch go out of scope is the correct
        // and forward-compatible way to release the handle.
        unset($ch);

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

    /**
     * Reduce a Fahras `account` string to the same shape used by
     * Fahras's own canonicalAccountName(). Mirrors the alias map so
     * verbose forms like "شركة جدل للتقسيط" collapse to "جدل" — without
     * round-tripping through Fahras itself.
     *
     * Used both at boot (to canonicalise the configured companyName) and
     * by sameCompanyOnly() to compare match.account values regardless of
     * which data source produced them.
     */
    public static function normaliseCompany(string $name): string
    {
        $name = trim((string)$name);
        if ($name === '') return '';
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        // Canonical short-form aliases mirroring
        // Fahras's _getAccountAliasMap(). Keep in sync on both sides.
        static $aliases = [
            'زجل'                       => 'زجل',
            'جدل'                       => 'جدل',
            'شركة جدل'                  => 'جدل',
            'شركة جدل للتقسيط'          => 'جدل',
            'نماء'                      => 'نماء',
            'شركة نماء'                 => 'نماء',
            'شركة نماء للتقسيط'         => 'نماء',
            'وتر'                       => 'وتر',
            'شركة وتر'                  => 'وتر',
            'شركة وتر للتقسيط'          => 'وتر',
            'المجد'                     => 'عالم المجد',
            'عالم المجد'                => 'عالم المجد',
            'عالم المجد للتقسيط'        => 'عالم المجد',
            'شركة عالم المجد'           => 'عالم المجد',
            'شركة عالم المجد للتقسيط'   => 'عالم المجد',
            'بسيل'                      => 'بسيل',
            'عمار'                      => 'بسيل',
            'شركة بسيل'                 => 'بسيل',
            'شركة بسيل للتقسيط'         => 'بسيل',
        ];
        if (isset($aliases[$name])) return $aliases[$name];

        // Defensive regex fallback: «شركة X للتقسيط» / «شركة X» → X.
        if (preg_match('/^شركة\s+(.+?)\s+للتقسيط$/u', $name, $m)) {
            $core = trim($m[1]);
            return $aliases[$core] ?? $core;
        }
        if (preg_match('/^شركة\s+(.+)$/u', $name, $m)) {
            $core = trim($m[1]);
            return $aliases[$core] ?? $core;
        }
        return $name;
    }

    /**
     * Inspect a verdict's matches and decide whether they ALL belong
     * to this Tayseer instance's own company. Used to short-circuit a
     * `cannot_sell` verdict into a "create new contract for existing
     * customer" CTA — see WizardController::actionFahrasCheck().
     *
     * Returns false when:
     *   • $companyName is not configured (the optimisation is opt-in).
     *   • The match list is empty.
     *   • At least one match.account belongs to a different company.
     */
    public function isSameCompanyOnly(FahrasVerdict $verdict): bool
    {
        if ($this->companyName === null || $this->companyName === '') return false;
        if (empty($verdict->matches)) return false;

        foreach ($verdict->matches as $m) {
            $acc = self::normaliseCompany((string)($m['account'] ?? ''));
            if ($acc === '' || $acc !== $this->companyName) {
                return false;
            }
        }
        return true;
    }
}
