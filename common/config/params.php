<?php
return [
    /**
     * مفتاح Google Maps API — لخريطة تتبع الموظفين (نظام الحضور والانصراف).
     * احصل على المفتاح من: https://console.cloud.google.com/ → APIs & Services → Credentials
     * فعّل "Maps JavaScript API" ثم أنشئ مفتاح (API key).
     * يمكن تعيينه هنا أو في params-local.php: 'googleMapsApiKey' => 'AIza...',
     */
    'googleMapsApiKey' => null,

    /**
     * قاعدة روابط صور العملاء.
     * إذا مُعرّف: تُحمّل كل الصور من هذا العنوان (مثلاً من سيرفر جادل).
     * على نماء يمكن تعيين: 'customerImagesBaseUrl' => 'https://jadal.aqssat.co' في params-local
     * يُستخدم عبر MediaHelper::absoluteUrl()
     */
    'customerImagesBaseUrl' => null,

    /** Asset version — bump to force browser cache refresh
     *  (uses this file's mtime; resave to bust caches site-wide).
     *  Last bump: 2026-04-18 — Customer Wizard v2 edit mode: relax
     *  required-field gating (server validators + HTML5 stripping)
     *  so reps can save partial edits without re-justifying every
     *  required column. Fahras stays bypassed for edits. */
    'assetVersion' => @filemtime(__FILE__) ?: 1,

    /**
     * Fahras integration — central client-violation index used to
     * gate new-customer creation. See docs/fahras-integration.md.
     *
     * The `token` and `baseUrl` overrides MUST live in
     * `common/config/params-local.php` (per environment), NOT here.
     */
    'fahras' => [
        // Master kill-switch. Set to false to bypass the check entirely
        // (UI hides the verdict card and the wizard accepts any customer).
        'enabled'        => true,

        // Base URL of the Fahras deployment (HTTPS only in production).
        // Endpoints: {baseUrl}/admin/api/check.php and /admin/api/search.php
        'baseUrl'        => 'https://fahras.aqssat.co',

        // Per-tenant API token issued by Fahras for this Tayseer install.
        // Override in params-local.php; never commit a real token to git.
        'token'          => null,

        // Tenant slug sent as `client=` to Fahras (must exist in $TOKENS map).
        'clientId'       => 'tayseer',

        // Canonical name of THIS Tayseer instance's installments company as
        // it appears in Fahras `accounts.name` (e.g. "جدل", "نماء", "وتر",
        // "بسيل", "زجل", "عالم المجد"). Required for the "same-company"
        // optimisation: when Fahras returns a cannot_sell verdict whose
        // matches are EXCLUSIVELY from this company, we treat the customer
        // as "already ours" and offer a quick link to add a new contract
        // instead of forcing the rep to re-create the customer.
        //
        // Resolution order:
        //   1. Apache vhost env: `SetEnv FAHRAS_COMPANY_NAME <name>` per
        //      tenant (recommended — mirrors how FAHRAS_TOKEN_TAYSEER is
        //      provisioned, no per-environment params-local edits needed).
        //   2. Override in `common/config/params-local.php` for local dev.
        //   3. null  → optimisation disabled (verdict still blocks
        //      duplicate creation, but no «إضافة عقد جديد» CTA shown).
        'companyName'    => (static function () {
            $env = getenv('FAHRAS_COMPANY_NAME');
            if ($env === false) return null;
            $env = trim($env);
            return $env === '' ? null : $env;
        })(),

        // Hard timeout per HTTP call (seconds).
        'timeoutSec'     => 8,

        // Verdict cache is intentionally OFF — every check hits Fahras live
        // so the rep sees ground truth at the moment they finish typing.
        // (The previous 300s TTL caused stale "no_record" verdicts to mask
        // newly-recorded contracts at sister companies.) Kept here at 0 only
        // for schema continuity; the service ignores the value anyway.
        'cacheTtlSec'    => 0,

        // Failure policy when Fahras is unreachable / errors out:
        //   'closed' → block customer creation (recommended for production).
        //   'open'   → warn but allow creation (for staging / dev).
        'failurePolicy'  => 'closed',

        // RBAC permission name allowed to override a `cannot_sell` verdict.
        'overridePerm'   => 'customer.fahras.override',

        // RBAC permission name allowed to view the audit log screen.
        'logViewPerm'    => 'customer.fahras.log.view',
    ],

    /**
     * Unify-Media feature flags.
     *
     * The whole point of these flags is that a controller migrated to
     * the new MediaService can be flipped back to the legacy upload
     * path WITHOUT a redeploy — operator just edits params-local.php
     * on the affected tenant and clears OPcache. This is the fast
     * rollback the rollout plan relies on (see scripts/backup/README.md).
     *
     * Defaults are intentionally CONSERVATIVE: every flag here is
     * `false` so a fresh deploy never silently switches behaviour.
     * Each environment opts in by overriding inside its own
     * params-local.php — typically:
     *
     *   1. Enable on `prod_staging` first (whole module = true).
     *   2. Soak 1+ week, watch the media_audit_log table.
     *   3. Enable on one production tenant (e.g. prod_majd) for a week.
     *   4. Roll out to remaining tenants.
     *
     * The flags are read through {@see common\helper\MediaFlags} so a
     * typo in a controller raises an exception in dev instead of
     * silently behaving the wrong way.
     */
    'media' => [
        // Global kill-switch. When false, no controller may use the
        // unified service regardless of per-controller flags below.
        'use_unified'           => false,

        // Per-controller flags. Each one corresponds to a Phase-2+
        // adopter in the unify-media plan. Listed in deliberate
        // rollout order — do NOT enable a later one without enabling
        // its predecessors and confirming the audit log stays clean.
        'controllers' => [
            'wizard'          => false, // backend\modules\customers\controllers\WizardController
            'smart_media'     => false, // backend\modules\customers\controllers\SmartMediaController
            'lawyers'         => false, // backend\modules\lawyers\controllers\LawyersController
            'employee'        => false, // backend\modules\employee\controllers\EmployeeController
            'companies'       => false, // backend\modules\companies\controllers\CompaniesController
            'document_holder' => false, // backend\modules\documentHolder\controllers\DocumentHolderController
            'judiciary'       => false, // backend\modules\judiciary\controllers\JudiciaryController
            'judiciary_acts'  => false, // backend\modules\judiciaryCustomersActions\controllers\...
            'movement'        => false, // backend\modules\movment\controllers\MovmentController
            'media_api'       => false, // api\modules\v1\controllers\CustomerImagesController
        ],

        // Async pipeline switch. When true, MediaService dispatches the
        // 4 post-store jobs (scan, exif, optimize, thumbnail) onto the
        // queue. When false (default), the service marks rows ready
        // synchronously — safe baseline for environments without a
        // running `php yii queue/listen` worker.
        'async_jobs'            => false,

        // Soft-fail dedup. When true, an upload whose SHA-256 matches
        // a row uploaded by the same user in the last 24h returns the
        // existing row instead of inserting a duplicate. Off by
        // default until we have a week of audit data confirming no
        // false positives in the wild.
        'dedup_enabled'         => false,
    ],

    /**
     * Environment label shown in a top-of-page banner. NULL hides
     * the banner. Production tenants leave it null; staging sets it
     * to "STAGING — لا تدخل بيانات حقيقية" (or similar) so a careless
     * tab switch from prod to staging is impossible to miss.
     */
    'environmentBanner' => null,
];
