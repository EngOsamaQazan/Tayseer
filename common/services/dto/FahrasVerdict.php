<?php

namespace common\services\dto;

/**
 * Immutable value object describing a single Fahras verdict.
 *
 * Lifecycle:
 *   • Built by FahrasService::check() / ::searchByName().
 *   • Carried back to the controller layer, persisted to
 *     `os_fahras_check_log`, and serialised to the wizard front-end.
 *
 * Status meanings:
 *   - VERDICT_CAN_SELL       → green light, allow customer creation.
 *   - VERDICT_CONTACT_FIRST  → yellow, allow but show warning + ask
 *                              the rep to contact the entitled company.
 *   - VERDICT_CANNOT_SELL    → red, block creation (override allowed
 *                              for users with the `overridePerm` permission).
 *   - VERDICT_NO_RECORD      → green, no record was found at all.
 *   - VERDICT_ERROR          → soft failure (network/HTTP/parse). The
 *                              caller decides whether to honour
 *                              `failurePolicy` of "closed" or "open".
 */
final class FahrasVerdict
{
    public const VERDICT_CAN_SELL      = 'can_sell';
    public const VERDICT_CONTACT_FIRST = 'contact_first';
    public const VERDICT_CANNOT_SELL   = 'cannot_sell';
    public const VERDICT_NO_RECORD     = 'no_record';
    public const VERDICT_ERROR         = 'error';

    public string  $verdict;
    public string  $reasonCode;
    public string  $reasonAr;
    /** @var array<int,array<string,mixed>> */
    public array   $matches;
    public ?string $requestId;
    public ?int    $httpStatus;
    public ?string $rawError;
    public bool    $fromCache;
    public int     $durationMs;
    /** @var array<int,array<string,string>> */
    public array   $remoteErrors;
    /**
     * Always-on diagnostic envelope returned by Fahras `check.php` from
     * commit 80acada onward. Carries per-source row counts, retry flags,
     * HTTP codes, engine input/group counts, and the `promoted` bit
     * (true when a partial-data response was fail-closed to `error`).
     *
     * Surfaced verbatim in the wizard's "تفاصيل تشخيصية" disclosure so
     * a rep can compare two consecutive calls' diag blocks at a glance
     * — pinpointing exactly which source flipped between requests.
     *
     * @var array<string,mixed>|null
     */
    public ?array  $diag;

    private function __construct() {}

    /**
     * @param array<int,array<string,mixed>> $matches
     * @param array<int,array<string,string>> $remoteErrors
     */
    public static function fromApi(
        string $verdict,
        string $reasonCode,
        string $reasonAr,
        array $matches = [],
        array $remoteErrors = [],
        ?string $requestId = null,
        ?int $httpStatus = 200,
        int $durationMs = 0,
        ?array $diag = null
    ): self {
        $v = new self();
        $v->verdict      = self::normalize($verdict);
        $v->reasonCode   = $reasonCode ?: 'UNKNOWN';
        $v->reasonAr     = $reasonAr ?: '';
        $v->matches      = $matches;
        $v->remoteErrors = $remoteErrors;
        $v->requestId    = $requestId;
        $v->httpStatus   = $httpStatus;
        $v->fromCache    = false;
        $v->durationMs   = $durationMs;
        $v->rawError     = null;
        $v->diag         = $diag;
        return $v;
    }

    public static function failure(string $reasonAr, ?string $rawError = null, ?int $httpStatus = null): self
    {
        $v = new self();
        $v->verdict      = self::VERDICT_ERROR;
        $v->reasonCode   = 'CHECK_FAILED';
        $v->reasonAr     = $reasonAr ?: 'تعذّر الفحص في نظام الفهرس.';
        $v->matches      = [];
        $v->remoteErrors = [];
        $v->requestId    = null;
        $v->httpStatus   = $httpStatus;
        $v->fromCache    = false;
        $v->durationMs   = 0;
        $v->rawError     = $rawError;
        $v->diag         = null;
        return $v;
    }

    public static function noRecord(): self
    {
        return self::fromApi(
            self::VERDICT_NO_RECORD,
            'NO_RECORD',
            'لا يوجد سجل لهذا العميل في الفهرس — يمكن إضافته.'
        );
    }

    public static function fromArray(array $a): self
    {
        return self::fromApi(
            (string)($a['verdict'] ?? self::VERDICT_NO_RECORD),
            (string)($a['reason_code'] ?? 'NO_RECORD'),
            (string)($a['reason_ar'] ?? ''),
            (array)($a['matches'] ?? []),
            (array)($a['remote_errors'] ?? []),
            isset($a['request_id']) ? (string)$a['request_id'] : null,
            isset($a['http_status']) ? (int)$a['http_status'] : 200,
            (int)($a['duration_ms'] ?? 0),
            isset($a['_diag']) && is_array($a['_diag']) ? $a['_diag'] : null
        );
    }

    /** True when the wizard MUST block (cannot_sell or — under closed policy — error). */
    public function blocks(string $failurePolicy = 'closed'): bool
    {
        if ($this->verdict === self::VERDICT_CANNOT_SELL) return true;
        if ($this->verdict === self::VERDICT_ERROR && $failurePolicy === 'closed') return true;
        return false;
    }

    /** True when we should display a soft warning. */
    public function warns(): bool
    {
        return $this->verdict === self::VERDICT_CONTACT_FIRST
            || $this->verdict === self::VERDICT_ERROR;
    }

    /** Safe array form for JSON responses to the browser. */
    public function toArray(): array
    {
        return [
            'verdict'       => $this->verdict,
            'reason_code'   => $this->reasonCode,
            'reason_ar'     => $this->reasonAr,
            'matches'       => $this->matches,
            'remote_errors' => $this->remoteErrors,
            'request_id'    => $this->requestId,
            'http_status'   => $this->httpStatus,
            'from_cache'    => $this->fromCache,
            'duration_ms'   => $this->durationMs,
            '_diag'         => $this->diag,
        ];
    }

    private static function normalize(string $v): string
    {
        $v = strtolower(trim($v));
        $allowed = [
            self::VERDICT_CAN_SELL,
            self::VERDICT_CONTACT_FIRST,
            self::VERDICT_CANNOT_SELL,
            self::VERDICT_NO_RECORD,
            self::VERDICT_ERROR,
        ];
        return in_array($v, $allowed, true) ? $v : self::VERDICT_ERROR;
    }
}
