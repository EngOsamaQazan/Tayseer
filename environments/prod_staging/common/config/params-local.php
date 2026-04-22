<?php
/**
 * STAGING params. Designed to be a full superset of any production
 * tenant's params-local.php so views that read $params[...] never
 * crash with "Undefined array key" under YII_DEBUG=true (the symptom
 * we hit on _search.php when 12+ jobs/judiciary/banks lookup keys
 * were missing from this file relative to prod_jadal).
 *
 * The deliberate divergence from production is limited to:
 *   - SMS credentials are blanked so staging cannot send real SMS.
 *   - Mailer is forced to file transport (in main-local.php).
 *   - Fahras companyName falls back to NULL (no per-brand binding).
 *   - environmentBanner renders the red "STAGING" strip.
 *   - media.* feature flags are all ON so QA exercises the unified
 *     stack one full cycle ahead of any production tenant.
 */
return [
    /* SMS credentials — INTENTIONALLY EMPTY on staging.
       The SMS module reads these; with empty values it should refuse
       to send. NEVER paste real SMS creds here.                     */
    'sender' => '',
    'user'   => '',
    'pass'   => '',

    'adminEmail'                    => 'admin@example.com',
    'supportEmail'                  => 'support@example.com',
    'senderEmail'                   => 'noreply@example.com',
    'senderName'                    => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'lawyer_type'                   => [Yii::t('app','agent'), Yii::t('app','authorized')],

    'bsVersion'           => '5.x',
    'bsDependencyEnabled' => false,

    /* ── cache key names — must mirror prod 1:1 ────────────────── */
    'key_customers'           => 'key_customers',
    'key_income_by'           => 'key_income_by',
    'key_users'               => 'key_users',
    'key_company'             => 'key_company',
    'key_company_name'        => 'key_company_name',
    'key_payment_type'        => 'key_payment_type',
    'key_income_category'     => 'key_income_category',
    'key_document_number'     => 'document_number',
    'key_contract_id'         => 'contract_id',
    'key_contract_status'     => 'contract_status',
    'key_expenses_contract'   => 'expenses_contract',
    'key_loan_contract'       => 'loan_contract',
    'key_status'              => 'status',
    'key_expenses_category'   => 'expenses_category',
    'key_court'               => 'court',
    'key_judiciary_type'      => 'judiciary_type',
    'key_lawyer'              => 'lawyer',
    'key_judiciary_contract'  => 'judiciary_contract',
    'key_judiciary_year'      => 'judiciary_year',
    'key_customers_name'      => 'customers_name',
    'key_city'                => 'city',
    'key_jobs'                => 'jobs',
    'key_citizen'             => 'citizen',
    'key_hear_about_us'       => 'hear_about_us',
    'key_banks'               => 'banks',
    'key_job_title'           => 'job_title',
    'key_jobs_type'           => 'jobs_type',
    'key_judiciary_actions'   => 'judiciary_actions',
    'key_contract_customers'  => 'contract_customer',
    'key_company_bank_id'     => 'company_bank_',
    'key_job_type'            => 'job_type',
    'key_customers_all'       => 'customers_all',

    /* ── lookup queries — use {{%table}} so the os_ prefix from the
         db config is honoured automatically; behaves identically on
         every tenant including staging.                            */
    'job_type_query'           => 'SELECT id , name FROM {{%jobs_type}}',
    'court_query'              => 'SELECT id , name FROM {{%court}}',
    'customers_query'          => 'SELECT id , name FROM {{%customers}}',
    'customers_all_query'      => 'SELECT * FROM {{%customers}}',
    'customers_name_query'     => 'SELECT  name FROM {{%customers}}',
    'users_query'              => 'SELECT id , username FROM {{%user}}',
    'payment_type_query'       => 'SELECT id,name FROM {{%payment_type}}',
    'income_by_query'          => 'SELECT id ,_by FROM {{%income}}',
    'company_query'            => 'SELECT id , name FROM {{%companies}}',
    'company_name_query'       => 'SELECT name FROM {{%companies}}',
    'status_query'             => 'SELECT id , name FROM {{%status}}',
    'city_query'               => 'SELECT id,name FROM {{%city}}',
    'jobs_query'               => 'SELECT id,name FROM {{%jobs}}',
    'contract_status_query'    => 'SELECT status FROM {{%contracts}}',
    'citizen_query'            => 'SELECT id,name FROM {{%citizen}}',
    'hear_about_us_query'      => 'SELECT id,name FROM {{%hear_about_us}}',
    'banks_query'              => 'SELECT id,name FROM {{%bancks}}',
    'income_category_query'    => 'SELECT id ,name FROM {{%income_category}}',
    'document_number_query'    => 'SELECT document_number FROM {{%financial_transaction}}',
    'expenses_contract_query'  => 'SELECT contract_id FROM {{%expenses}}',
    'contract_id_query'        => 'SELECT id FROM {{%contracts}}',
    'expenses_category_query'  => 'SELECT id , name FROM {{%expense_categories}}',
    'judiciary_type_query'     => 'SELECT id , name FROM {{%judiciary_type}}',
    'lawyer_query'             => 'SELECT id , name FROM {{%lawyers}}',
    'judiciary_contract_query' => 'SELECT contract_id FROM {{%judiciary}}',
    'judiciary_year_query'     => 'SELECT year FROM {{%judiciary}}',
    'job_title_query'          => 'SELECT id, name FROM {{%jobs}}',
    'jobs_type_query'          => 'SELECT id, name FROM {{%jobs_type}}',
    'judiciary_actions_query'  => 'SELECT id , name FROM {{%judiciary_actions}}',
    'contract_customers_query' => 'SELECT * FROM {{%contracts_customers}}',
    'company_bank_id_query'    => 'SELECT bank_id FROM  {{%company_banks}}',

    'time_duration' => 31536000,

    'socialSecuritySources' => [
        'social_security'         => 'الضمان الاجتماعي',
        'retirement_directorate'  => 'مديرية التقاعد المدني والعسكري',
        'both'                    => 'كلاهما',
    ],

    /**
     * Fahras integration. Token comes from Apache `SetEnv FAHRAS_TOKEN_TAYSEER`.
     * Empty token disables outbound calls automatically.
     */
    'fahras' => [
        'enabled'        => true,
        'baseUrl'        => 'https://fahras.aqssat.co',
        'token'          => getenv('FAHRAS_TOKEN_TAYSEER') ?: '',
        'clientId'       => 'tayseer',
        // Optional canonical Fahras account name. Staging is generic and
        // not bound to a single brand, so leave NULL by default unless
        // FAHRAS_COMPANY_NAME is explicitly provided in the vhost env.
        'companyName'    => getenv('FAHRAS_COMPANY_NAME') ?: null,
        'timeoutSec'     => 8,
        'cacheTtlSec'    => 0,
        'failurePolicy'  => 'closed',
        'overridePerm'   => 'customer.fahras.override',
        'logViewPerm'    => 'customer.fahras.log.view',
    ],

    /**
     * On staging we deliberately enable the unified media stack
     * EARLY — that is the whole point of having a staging tier.
     * Every Phase-2 controller adopter should land here BEFORE it
     * lands on any production tenant.
     *
     * If a flag here causes problems, flip it back to `false` and
     * `php yii cache/flush-all` on staging only — no redeploy
     * needed, no production impact.
     */
    'media' => [
        'use_unified'   => true,
        'controllers'   => [
            'wizard'          => true,
            'smart_media'     => true,
            'lawyers'         => true,
            'employee'        => true,
            'companies'       => true,
            'document_holder' => true,
            'judiciary'       => true,
            'judiciary_acts'  => true,
            'movement'        => true,
            'media_api'       => true,
        ],
        'async_jobs'    => false,   // turn on once `php yii queue/listen` is supervised
        'dedup_enabled' => true,
    ],

    /**
     * Big visible banner so a tab-switch from prod to staging is
     * impossible to miss. Rendered by the layout when set.
     */
    'environmentBanner' => 'STAGING — بيانات اختبارية فقط — لا تُدخل بيانات حقيقية',
];
