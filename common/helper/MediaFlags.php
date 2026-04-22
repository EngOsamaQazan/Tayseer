<?php

namespace common\helper;

use Yii;

/**
 * ─────────────────────────────────────────────────────────────────────
 * MediaFlags — feature-flag gateway for the Unify-Media rollout.
 * ─────────────────────────────────────────────────────────────────────
 *
 * Every adopter controller asks this class — never `Yii::$app->params`
 * directly — whether it should use the new {@see common\services\media\MediaService}
 * or stay on the legacy MediaHelper-driven path. Two reasons it lives
 * in its own class:
 *
 *   1. **Typo safety.** `Yii::$app->params['media']['conrollers']['wizard']`
 *      silently returns null, which `if ($flag)` reads as false — i.e.
 *      a typo would silently regress a tenant to the legacy path. This
 *      class enumerates every legal flag in {@see CONTROLLER_FLAGS}, so
 *      asking for an unknown one throws an exception during dev
 *      instead of vanishing into the night.
 *
 *   2. **Single seam for kill-switching.** The global `media.use_unified`
 *      is checked here ONCE; flipping it instantly disables every
 *      adopter without us having to remember to AND it into 10
 *      controllers.
 *
 * Usage in a controller:
 *
 *     if (MediaFlags::useUnified(MediaFlags::CTRL_WIZARD)) {
 *         $result = Yii::$app->media->store($file, $ctx);
 *     } else {
 *         MediaHelper::legacyUpload(...);
 *     }
 *
 * Override surface (per environment, in `params-local.php`):
 *
 *     'media' => [
 *         'use_unified' => true,
 *         'controllers' => ['wizard' => true, 'smart_media' => true],
 *         'async_jobs'  => true,
 *         'dedup_enabled' => true,
 *     ],
 */
final class MediaFlags
{
    // ── Controller identifiers ────────────────────────────────────
    // These constants mirror the keys under params['media']['controllers'].
    // The mirror is enforced by the smoke check in {@see assertKnown()}.

    public const CTRL_WIZARD          = 'wizard';
    public const CTRL_SMART_MEDIA     = 'smart_media';
    public const CTRL_LAWYERS         = 'lawyers';
    public const CTRL_EMPLOYEE        = 'employee';
    public const CTRL_COMPANIES       = 'companies';
    public const CTRL_DOCUMENT_HOLDER = 'document_holder';
    public const CTRL_JUDICIARY       = 'judiciary';
    public const CTRL_JUDICIARY_ACTS  = 'judiciary_acts';
    public const CTRL_MOVEMENT        = 'movement';
    public const CTRL_MEDIA_API       = 'media_api';

    /**
     * Every legal controller flag. Adding a new adopter controller?
     * Add its const above AND its key in `common/config/params.php`
     * AND the name here, all in one PR — the assertion below catches
     * incomplete additions in CI / dev.
     */
    private const CONTROLLER_FLAGS = [
        self::CTRL_WIZARD,
        self::CTRL_SMART_MEDIA,
        self::CTRL_LAWYERS,
        self::CTRL_EMPLOYEE,
        self::CTRL_COMPANIES,
        self::CTRL_DOCUMENT_HOLDER,
        self::CTRL_JUDICIARY,
        self::CTRL_JUDICIARY_ACTS,
        self::CTRL_MOVEMENT,
        self::CTRL_MEDIA_API,
    ];

    /**
     * Should the given controller use the unified MediaService?
     *
     * Returns true ONLY when:
     *   1. The global kill-switch `media.use_unified` is true; AND
     *   2. The per-controller flag is true.
     *
     * Any failure (missing config, unknown controller in dev) returns
     * false — defensive default — but `assertKnown()` raises in non-prod
     * to surface the bug immediately.
     */
    public static function useUnified(string $controller): bool
    {
        self::assertKnown($controller);

        $cfg = self::cfg();
        if (empty($cfg['use_unified'])) {
            return false;
        }
        return !empty($cfg['controllers'][$controller]);
    }

    /**
     * Should MediaService dispatch the post-store async pipeline?
     * When false, jobs run synchronously inside the request and the
     * row is marked 'ready' before the HTTP response returns.
     */
    public static function asyncJobsEnabled(): bool
    {
        return !empty(self::cfg()['async_jobs']);
    }

    /**
     * SHA-256 dedup window — see MediaService::findRecentDuplicate().
     * Off by default to give us a clean baseline for comparing dedup
     * audit hits against false-positive complaints.
     */
    public static function dedupEnabled(): bool
    {
        return !empty(self::cfg()['dedup_enabled']);
    }

    /**
     * The label rendered in the top-of-page environment banner. NULL
     * means "do not render the banner at all" — the production case.
     * Staging tenants set this in `params-local.php` to a high-contrast
     * string like "STAGING — بيانات اختبارية فقط".
     */
    public static function environmentBanner(): ?string
    {
        try {
            $b = Yii::$app->params['environmentBanner'] ?? null;
        } catch (\Throwable) {
            return null;
        }
        return is_string($b) && $b !== '' ? $b : null;
    }

    // ── Internals ─────────────────────────────────────────────────

    private static function cfg(): array
    {
        try {
            $m = Yii::$app->params['media'] ?? [];
        } catch (\Throwable) {
            $m = [];
        }
        return is_array($m) ? $m : [];
    }

    /**
     * Refuses unknown controller identifiers during dev so a typo does
     * not silently leave a tenant on the legacy path. In production
     * (`YII_ENV=prod`) we degrade to a warning so a hot-fix env never
     * crashes the request — defensive on top of the test-time guard.
     */
    private static function assertKnown(string $controller): void
    {
        if (in_array($controller, self::CONTROLLER_FLAGS, true)) {
            return;
        }
        $msg = "MediaFlags: unknown controller '$controller'. "
             . "Allowed: " . implode(', ', self::CONTROLLER_FLAGS);

        $env = defined('YII_ENV') ? YII_ENV : 'prod';
        if ($env !== 'prod') {
            throw new \InvalidArgumentException($msg);
        }
        try { Yii::warning($msg, __METHOD__); } catch (\Throwable) {}
    }
}
