<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
   'bsVersion' => '5.x',
   'bsDependencyEnabled' => false,
    /*key name for cach*/
    'key_customers' => "key_customers",
    'key_income_by' => "key_income_by",
    'key_users' => "key_users",
    'key_company' => "key_company",
    'key_company_name' => "key_company_name",
    'key_payment_type' => "key_payment_type",
    'key_income_category' => "key_income_category",
    'key_document_number' => "document_number",
    'key_contract_id' => "contract_id",
    'key_contract_status' => "contract_status",
    'key_expenses_contract' => "expenses_contract",
    'key_loan_contract' => "loan_contract",
    'key_status' => "status",
    'key_expenses_category' => "expenses_category",
    'key_court' => "court",
    'key_judiciary_type' => "judiciary_type",
    'key_lawyer' => "lawyer",
    'key_judiciary_contract' => "judiciary_contract",
    'key_judiciary_year' => "judiciary_year",
    'key_customers_name' => 'customers_name',
    'key_city' => 'city',
    'key_jobs' => 'jobs',
    'key_citizen' => 'citizen',
    'key_hear_about_us' => 'hear_about_us',
    'key_banks' => 'banks',
    'key_customers_all' => 'company_bank_',

    /*query  for cach*/
    'customers_all_query' => 'SELECT * FROM {{%customers}}',
    'court_query' => 'SELECT id , name FROM os_court',
    'customers_query' => 'SELECT id , name FROM os_customers',
    'customers_name_query' => 'SELECT  name FROM os_customers',
    'users_query' => 'SELECT id , username FROM `os_user`',
    'payment_type_query' => 'SELECT id , name FROM os_payment_type',
    'income_by_query' => 'SELECT id ,_by FROM os_income',
    'company_query' => 'SELECT id , name FROM os_companies',
    'company_name_query' => 'SELECT name FROM os_companies',
    'status_query' => 'SELECT id,name FROM os_status',
    'city_query' => 'SELECT id,name FROM os_city',
    'jobs_query' => 'SELECT id,name FROM os_jobs',
    'contract_status_query' => 'SELECT status FROM os_contracts',
    'citizen_query' => 'SELECT id,name FROM os_citizen',
    'hear_about_us_query' => 'SELECT id,name FROM os_hear_about_us',
    'banks_query' => 'SELECT id,name FROM os_bancks',
    'income_category_query' => 'SELECT id,name FROM os_income_category',
    'payment_type_query' => 'SELECT id,name FROM os_payment_type',
    'document_number_query' => 'SELECT document_number FROM os_financial_transaction',
    'income_category_query' => 'SELECT id ,name FROM os_income_category',
    'expenses_contract_query' => 'SELECT contract_id FROM os_expenses',
    'contract_id_query' => 'SELECT id FROM os_contracts',
    'expenses_category_query' => 'SELECT id , name FROM os_expense_categories',
    'status_query' => 'SELECT id , name FROM os_status',
    'judiciary_type_query' => 'SELECT id , name FROM os_judiciary_type',
    'lawyer_query' => 'SELECT id , name FROM os_lawyers',
    'judiciary_contract_query' => 'SELECT contract_id FROM os_judiciary',
    'judiciary_year_query' => 'SELECT year FROM os_judiciary',

    /*duration time for cach*/
    'time_duration' => 31536000,
    /**
     * Fahras integration. Token comes from Apache `SetEnv FAHRAS_TOKEN_TAYSEER`.
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
        'cacheTtlSec'    => 0,    // verdict cache disabled — see common/config/params.php
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
     * impossible to miss. Rendered by the `EnvironmentBannerWidget`
     * (added in the same change-set as the unify-media rollout).
     */
    'environmentBanner' => 'STAGING — بيانات اختبارية فقط — لا تُدخل بيانات حقيقية',
];
