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
        // MUST be overridden in params-local.php per environment.
        'companyName'    => null,

        // Hard timeout per HTTP call (seconds).
        'timeoutSec'     => 8,

        // Cache verdict for the same (id_number, name-hash) for this many
        // seconds to avoid hammering Fahras during wizard navigation.
        'cacheTtlSec'    => 300,

        // Failure policy when Fahras is unreachable / errors out:
        //   'closed' → block customer creation (recommended for production).
        //   'open'   → warn but allow creation (for staging / dev).
        'failurePolicy'  => 'closed',

        // RBAC permission name allowed to override a `cannot_sell` verdict.
        'overridePerm'   => 'customer.fahras.override',

        // RBAC permission name allowed to view the audit log screen.
        'logViewPerm'    => 'customer.fahras.log.view',
    ],
];
