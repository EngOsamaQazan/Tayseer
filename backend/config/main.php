<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php', require __DIR__ . '/../../common/config/params-local.php', require __DIR__ . '/params.php', require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'name' => 'easy installment manager',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'aliases' => [
        '@mdm/admin' => '@vendor/mdmsoft/yii2-admin',
    ],
    'modules' => [
        'LawyersImage' => [
            'class' => 'backend\modules\LawyersImage\LawyersImage',
        ],
        'address' => [
            'class' => 'backend\modules\address\Address',
        ],
        'divisionsCollection' => [
            'class' => 'backend\modules\divisionsCollection\DivisionsCollection',
        ],
        'sms' => [
            'class' => 'backend\modules\sms\Sms',
        ],
        'contracts' => [
            'class' => 'backend\modules\contracts\Contracts',
        ],
        'companyBanks' => [
            'class' => 'backend\modules\companyBanks\CompanyBanks',
        ],
        'status' => [
            'class' => 'backend\modules\status\Status',
        ],
        'rejesterFollowUpType' => [
            'class' => 'backend\modules\rejesterFollowUpType\RejesterFollowUpType',
        ],
        'shareholders' => [
            'class' => 'backend\modules\shareholders\Shareholders',
        ],
        'workdays' => [
            'class' => 'backend\modules\workdays\Workdays',
        ],

        'capitalTransactions' => [
            'class' => 'backend\modules\capitalTransactions\CapitalTransactions',
        ],
        'sharedExpenses' => [
            'class' => 'backend\modules\sharedExpenses\SharedExpenses',
        ],
        'profitDistribution' => [
            'class' => 'backend\modules\profitDistribution\ProfitDistribution',
        ],
        'phoneNumbers' => [
            'class' => 'backend\modules\phoneNumbers\PhoneNumbers',
        ],
        'notification' => [
            'class' => 'backend\modules\notification\Notification',
        ],
        'cousins' => [
            'class' => 'backend\modules\cousins\Cousins',
        ],
        'citizen' => [
            'class' => 'backend\modules\citizen\Citizen',
        ],
        'movment' => [
            'class' => 'backend\modules\movment\Movment',
        ],
        'location' => [
            'class' => 'backend\modules\location\Location',
        ],
        'branch' => [
            'class' => 'backend\modules\branch\Branch',
        ],
        'loanScheduling' => [
            'class' => 'backend\modules\loanScheduling\LoanScheduling',
        ],
        'leaveRequest' => [
            'class' => 'backend\modules\leaveRequest\LeaveRequest',
        ],
        'leaveTypes' => [
            'class' => 'backend\modules\leaveTypes\LeaveTypes',
        ],
        'lawyers' => [
            'class' => 'backend\modules\lawyers\Lawyers',
        ],
        'inventoryItemQuantities' => [
            'class' => 'backend\modules\inventoryItemQuantities\InventoryItemQuantities',
        ],
        'inventoryItems' => [
            'class' => 'backend\modules\inventoryItems\InventoryItems',
        ],
        'items' => [
            'class' => 'backend\modules\items\Items',
        ],
        'jobs' => [
            'class' => 'backend\modules\jobs\Jobs',
        ],
        'judiciary' => [
            'class' => 'backend\modules\judiciary\Judiciary',
        ],
        'judiciaryRequestTemplates' => [
            'class' => 'backend\modules\judiciaryRequestTemplates\JudiciaryRequestTemplates',
        ],
        'diwan' => [
            'class' => 'backend\modules\diwan\Diwan',
        ],

        'inventoryStockLocations' => [
            'class' => 'backend\modules\inventoryStockLocations\InventoryStockLocations',
        ],
        'judiciaryType' => [
            'class' => 'backend\modules\judiciaryType\JudiciaryType',
        ],
        'judiciaryAuthorities' => [
            'class' => 'backend\modules\judiciaryAuthorities\JudiciaryAuthorities',
        ],
        'realEstate' => [
            'class' => 'backend\modules\realEstate\RealEstate',
        ],
        'inventorySuppliers' => [
            'class' => 'backend\modules\inventorySuppliers\InventorySuppliers',
        ],
        'invoice' => [
            'class' => 'backend\modules\invoice\Invoice',
        ],
        'holidays' => [
            'class' => 'backend\modules\holidays\Holidays',
        ],
        'officialHolidays' => [
            'class' => 'backend\modules\officialHolidays\OfficialHolidays',
        ],
        'income' => [
            'class' => 'backend\modules\income\Income',
        ],
        'incomeCategory' => [
            'class' => 'backend\modules\incomeCategory\IncomeCategory',
        ],
        'leavePolicy' => [
            'class' => 'backend\modules\leavePolicy\LeavePolicy',
        ],
        'department' => [
            'class' => 'backend\modules\department\Department',
        ],
        'bancks' => [
            'class' => 'backend\modules\bancks\Bancks',
        ],
        'documentStatus' => [
            'class' => 'backend\modules\documentStatus\DocumentStatus',
        ],
        'hearAboutUs' => [
            'class' => 'backend\modules\hearAboutUs\HearAboutUs',
        ],
        'city' => [
            'class' => 'backend\modules\city\City',
        ],
        'paymentType' => [
            'class' => 'backend\modules\paymentType\PaymentType',
        ],
        'feelings' => [
            'class' => 'backend\modules\feelings\Feelings',
        ],
        'contactType' => [
            'class' => 'backend\modules\contactType\ContactType',
        ],
        'connectionResponse' => [
            'class' => 'backend\modules\connectionResponse\ConnectionResponse',
        ],
        'documentType' => [
            'class' => 'backend\modules\documentType\DocumentType',
        ],
        'designation' => [
            'class' => 'backend\modules\designation\Designation',
        ],
        'documentHolder' => [
            'class' => 'backend\modules\documentHolder\DocumentHolder',
        ],
        'employee' => [
            'class' => 'backend\modules\employee\Employee',
        ],
        'judiciaryActions' => [
            'class' => 'backend\modules\judiciaryActions\JudiciaryActions',
        ],
        'judiciaryCustomersActions' => [
            'class' => 'backend\modules\judiciaryCustomersActions\JudiciaryCustomersActions',
        ],
        'expenseCategories' => [
            'class' => 'backend\modules\expenseCategories\ExpenseCategories',
        ],
        'expenses' => [
            'class' => 'backend\modules\expenses\Expenses',
        ],
        'financialTransaction' => [
            'class' => 'backend\modules\financialTransaction\FinancialTransaction',
        ],
        'accounting' => [
            'class' => 'backend\modules\accounting\Accounting',
        ],
        'followUp' => [
            'class' => 'backend\modules\followUp\FollowUp',
        ],
        'followUpReport' => [
            'class' => 'backend\modules\followUpReport\FollowUpReport',
        ],
        'attendance' => [
            'class' => 'backend\modules\attendance\Attendance',
        ],
        'authAssignment' => [
            'class' => 'backend\modules\authAssignment\AuthAssignment',
        ],
        'collection' => [
            'class' => 'backend\modules\collection\Collection',
        ],
        'contractDocumentFile' => [
            'class' => 'backend\modules\contractDocumentFile\ContractDocumentFile',
        ],
        'contractInstallment' => [
            'class' => 'backend\modules\contractInstallment\ContractInstallment',
        ],
        'contracts' => [
            'class' => 'backend\modules\contracts\Contracts',
        ],
        'followUp' => [
            'class' => 'backend\modules\followUp\FollowUp',
        ],
        'companies' => [
            'class' => 'backend\modules\companies\Companies',
        ],
        'itemsInventoryInvoices' => [
            'class' => 'backend\modules\itemsInventoryInvoices\ItemsInventoryInvoices',
        ],
        'inventoryInvoices' => [
            'class' => 'backend\modules\inventoryInvoices\InventoryInvoices',
        ],
        'reports' => [
            'class' => 'backend\modules\reports\Reports',
        ],
        'customers' => [
            'class' => 'backend\modules\customers\Customers',
        ],

        'court' => [
            'class' => 'backend\modules\court\Court',
        ],
        'JudiciaryInformAddress'=>
        [
            'class' => 'backend\modules\JudiciaryInformAddress\JudiciaryInformAddress',
        ],
        'hr' => [
            'class' => 'backend\modules\hr\Module',
        ],
        'companyManagement' => [
            'class' => 'backend\modules\companyManagement\CompanyManagement',
        ],
        'gridview' => [
            'class' => '\kartik\grid\Module',
            'bsVersion' => '5',
        ],
        // 'v1' => [
        //     'basePath' => '@api/modules/v1',
        //     'class' => 'api\modules\v1\Module',
        // ],
        'admin' => [
            'class' => 'mdm\admin\Module',
        ]
    ],
    'controllerMap' => [
    ],
    'components' => [
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@common/messages',
                ],
                'user*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@backend/modules/dektrium/user/messages',
                    'sourceLanguage' => 'en',
                ],
            ],
        ],
        'customersInformation' => [
            'class' => 'common\components\customersInformation'
        ],
        'City' => [
            'class' => 'common\components\City'
        ],
        'companyChecked' => [
            'class' => 'common\components\CompanyChecked'
        ],
        'PhpSpreadsheet_DefaultValueBinder' => [
            'class' => 'common/overridden/DefaultValueBinder.php',
        ],
        'notifications' => [
            'class' => 'common\services\NotificationService',
        ],
        'notificationService' => [
            'class' => 'common\services\NotificationService',
        ],
        // Fahras integration — verdict-gated customer creation.
        // All knobs (baseUrl/token/timeout/...) live in params.fahras.
        'fahras' => [
            'class' => 'common\services\FahrasService',
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'cache' => 'cache',
        ],
        'request' => [
            'csrfParam' => '_csrf-backend',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['/user/login'],
            'identityCookie' => ['name' => '_identity-website', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'normalizer' => [
                'class' => 'yii\web\UrlNormalizer',
                'action' => \yii\web\UrlNormalizer::ACTION_REDIRECT_TEMPORARY,
            ],
            'rules' => call_user_func(function () {
                /*
                 * URL Shortening Rules
                 * =====================
                 * Converts: /module/module/action → /module/action
                 * Example:  /customers/customers/create → /customers/create
                 *
                 * Modules with extra controllers are handled separately
                 * to avoid conflicts (e.g. /customers/smart-media/upload)
                 */

                /*
                 * Layer 1: Old-format backward compatibility (PARSING_ONLY)
                 * ─────────────────────────────────────────────────────────
                 * These rules handle the old 3-segment URLs like:
                 *   /customers/customers/create
                 *   /financialTransaction/financial-transaction/import-file
                 * They only parse incoming URLs, never used for URL creation.
                 * This prevents the shortened rules from incorrectly matching
                 * the controller name as an action.
                 */

                $rules = [];

                // --- Multi-controller modules: old-format backward compat ---
                $multiControllerCompat = [
                    'customers'  => 'customers',
                    'contracts'  => 'contracts',
                    'judiciary'  => 'judiciary',
                    'reports'    => 'reports',
                    'court'      => 'court',
                ];
                foreach ($multiControllerCompat as $moduleId => $controllerId) {
                    $rules[] = [
                        'pattern' => "{$moduleId}/{$controllerId}/<action:[\\w-]+>/<id:\\d+>",
                        'route'   => "{$moduleId}/{$controllerId}/<action>",
                        'mode'    => \yii\web\UrlRule::PARSING_ONLY,
                    ];
                    $rules[] = [
                        'pattern' => "{$moduleId}/{$controllerId}/<action:[\\w-]+>",
                        'route'   => "{$moduleId}/{$controllerId}/<action>",
                        'mode'    => \yii\web\UrlRule::PARSING_ONLY,
                    ];
                    $rules[] = [
                        'pattern' => "{$moduleId}/{$controllerId}",
                        'route'   => "{$moduleId}/{$controllerId}/index",
                        'mode'    => \yii\web\UrlRule::PARSING_ONLY,
                    ];
                }

                // --- Single-controller modules definition ---
                $singleControllerModules = [
                    // Core business
                    'collection'            => 'collection',
                    'financialTransaction'  => 'financial-transaction',
                    'followUp'              => 'follow-up',
                    'followUpReport'        => 'follow-up-report',
                    'loanScheduling'        => 'loan-scheduling',
                    'diwan'                 => 'diwan',

                    // Customer & contact
                    'address'               => 'address',
                    'citizen'               => 'citizen',
                    'cousins'               => 'cousins',
                    'phoneNumbers'          => 'phone-numbers',
                    'contactType'           => 'contact-type',
                    'connectionResponse'    => 'connection-response',
                    'feelings'              => 'feelings',
                    'hearAboutUs'           => 'hear-about-us',

                    // Inventory
                    'inventoryItems'            => 'inventory-items',
                    'inventoryItemQuantities'   => 'inventory-item-quantities',
                    'inventoryInvoices'         => 'inventory-invoices',
                    'inventoryStockLocations'   => 'inventory-stock-locations',
                    'inventorySuppliers'        => 'inventory-suppliers',
                    'itemsInventoryInvoices'    => 'items-inventory-invoices',
                    'items'                     => 'items',
                    'invoice'                   => 'invoice',

                    // Legal
                    'judiciaryType'         => 'judiciary-type',
                    'judiciaryAuthorities'        => 'judiciary-authorities',
                    'judiciaryRequestTemplates'   => 'judiciary-request-templates',
                    'JudiciaryInformAddress' => 'judiciary-inform-address',
                    'lawyers'               => 'lawyers',
                    'contractDocumentFile'  => 'contract-document-file',
                    'contractInstallment'   => 'contract-installment',
                    'documentHolder'        => 'document-holder',
                    'documentStatus'        => 'document-status',
                    'documentType'          => 'document-type',

                    // HR
                    'employee'              => 'employee',
                    'department'            => 'department',
                    'designation'           => 'designation',
                    'attendance'            => 'attendance',
                    'holidays'              => 'holidays',
                    'officialHolidays'      => 'official-holidays',
                    'leavePolicy'           => 'leave-policy',
                    'leaveRequest'          => 'leave-request',
                    'leaveTypes'            => 'leave-types',
                    'workdays'              => 'workdays',
                    'jobs'                  => 'jobs',

                    // Finance
                    'bancks'                => 'bancks',
                    'companies'             => 'companies',
                    'income'                => 'income',
                    'incomeCategory'        => 'income-category',
                    'expenses'              => 'expenses',
                    'expenseCategories'     => 'expense-categories',
                    'paymentType'           => 'payment-type',

                    // Settings & other
                    'city'                  => 'city',
                    'location'              => 'location',
                    'branch'                => 'branch',
                    'status'                => 'status',
                    'movment'               => 'movment',
                    'notification'          => 'notification',
                    'sms'                   => 'sms',
                    'shareholders'          => 'shareholders',
                    'capitalTransactions'   => 'capital-transactions',
                    'sharedExpenses'        => 'shared-expenses',
                    'profitDistribution'    => 'profit-distribution',
                    'authAssignment'        => 'auth-assignment',
                    'rejesterFollowUpType'  => 'rejester-follow-up-type',
                    'realEstate'            => 'real-estate',
                ];

                /*
                 * Legacy URL aliases (PARSING_ONLY)
                 * --------------------------------
                 * Map old/deprecated URLs to the current canonical action.
                 * Used when a URL was previously generated incorrectly by a
                 * menu item or external link and may still live in browser
                 * history, bookmarks, or old documents.
                 */
                $rules[] = [
                    'pattern' => 'income/income/income-item-list',
                    'route'   => 'income/income/income-list',
                    'mode'    => \yii\web\UrlRule::PARSING_ONLY,
                ];
                $rules[] = [
                    'pattern' => 'income/income-item-list',
                    'route'   => 'income/income/income-list',
                    'mode'    => \yii\web\UrlRule::PARSING_ONLY,
                ];

                // --- Old-format backward compat for single-controller modules ---
                foreach ($singleControllerModules as $moduleId => $controllerId) {
                    $rules[] = [
                        'pattern' => "{$moduleId}/{$controllerId}/<action:[\\w-]+>/<id:\\d+>",
                        'route'   => "{$moduleId}/{$controllerId}/<action>",
                        'mode'    => \yii\web\UrlRule::PARSING_ONLY,
                    ];
                    $rules[] = [
                        'pattern' => "{$moduleId}/{$controllerId}/<action:[\\w-]+>",
                        'route'   => "{$moduleId}/{$controllerId}/<action>",
                        'mode'    => \yii\web\UrlRule::PARSING_ONLY,
                    ];
                    $rules[] = [
                        'pattern' => "{$moduleId}/{$controllerId}",
                        'route'   => "{$moduleId}/{$controllerId}/index",
                        'mode'    => \yii\web\UrlRule::PARSING_ONLY,
                    ];
                }

                // Also handle kebab-case URL backward compat for judiciaryActions / judiciaryCustomersActions
                $legacyKebabCompat = [
                    'judiciaryActions'            => 'judiciary-actions',
                    'judiciaryCustomersActions'   => 'judiciary-customers-actions',
                ];
                foreach ($legacyKebabCompat as $moduleId => $controllerId) {
                    $rules[] = [
                        'pattern' => "{$moduleId}/{$controllerId}/<action:[\\w-]+>/<id:\\d+>",
                        'route'   => "{$moduleId}/{$controllerId}/<action>",
                        'mode'    => \yii\web\UrlRule::PARSING_ONLY,
                    ];
                    $rules[] = [
                        'pattern' => "{$moduleId}/{$controllerId}/<action:[\\w-]+>",
                        'route'   => "{$moduleId}/{$controllerId}/<action>",
                        'mode'    => \yii\web\UrlRule::PARSING_ONLY,
                    ];
                    $rules[] = [
                        'pattern' => "{$moduleId}/{$controllerId}",
                        'route'   => "{$moduleId}/{$controllerId}/index",
                        'mode'    => \yii\web\UrlRule::PARSING_ONLY,
                    ];
                }

                /*
                 * Layer 2: Shortened URL rules (CREATION + PARSING)
                 * ──────────────────────────────────────────────────
                 * These generate clean short URLs and also parse them.
                 * Old-format URLs are already handled above (PARSING_ONLY),
                 * so they won't conflict with these rules.
                 */

                // --- Multi-controller modules: shortened rules ---
                // customers: has smart-media, wizard, test controllers
                $rules['customers/smart-media/<action:[\w-]+>'] = 'customers/smart-media/<action>';
                $rules['customers/wizard/<action:[\w-]+>/<id:\d+>'] = 'customers/wizard/<action>';
                $rules['customers/wizard/<action:[\w-]+>']      = 'customers/wizard/<action>';
                $rules['customers/wizard']                      = 'customers/wizard/start';
                $rules['customers/test/<action:[\w-]+>']        = 'customers/test/<action>';
                $rules['customers/<action:[\w-]+>/<id:\d+>']    = 'customers/customers/<action>';
                $rules['customers/<action:[\w-]+>']             = 'customers/customers/<action>';
                $rules['customers']                             = 'customers/customers/index';

                // contracts
                $rules['contracts/<action:[\w-]+>/<id:\d+>']    = 'contracts/contracts/<action>';
                $rules['contracts/<action:[\w-]+>']             = 'contracts/contracts/<action>';
                $rules['contracts']                             = 'contracts/contracts/index';

                // judiciary
                $rules['judiciary/<action:[\w-]+>/<id:\d+>']    = 'judiciary/judiciary/<action>';
                $rules['judiciary/<action:[\w-]+>']             = 'judiciary/judiciary/<action>';
                $rules['judiciary']                             = 'judiciary/judiciary/index';

                // reports
                $rules['reports/<action:[\w-]+>/<id:\d+>']      = 'reports/reports/<action>';
                $rules['reports/<action:[\w-]+>']               = 'reports/reports/<action>';
                $rules['reports']                               = 'reports/reports/index';

                // court
                $rules['court/<action:[\w-]+>/<id:\d+>']        = 'court/court/<action>';
                $rules['court/<action:[\w-]+>']                 = 'court/court/<action>';
                $rules['court']                                 = 'court/court/index';

                // judiciaryActions (kebab short URL)
                $rules['judiciary-actions/<action:[\w-]+>/<id:\d+>'] = 'judiciaryActions/judiciary-actions/<action>';
                $rules['judiciary-actions/<action:[\w-]+>']          = 'judiciaryActions/judiciary-actions/<action>';
                $rules['judiciary-actions']                          = 'judiciaryActions/judiciary-actions/index';

                // judiciaryCustomersActions (kebab short URL)
                $rules['judiciary-customers-actions/<action:[\w-]+>/<id:\d+>'] = 'judiciaryCustomersActions/judiciary-customers-actions/<action>';
                $rules['judiciary-customers-actions/<action:[\w-]+>']          = 'judiciaryCustomersActions/judiciary-customers-actions/<action>';
                $rules['judiciary-customers-actions']                          = 'judiciaryCustomersActions/judiciary-customers-actions/index';

                // --- Single-controller modules: shortened rules ---
                foreach ($singleControllerModules as $moduleId => $controllerId) {
                    $rules["{$moduleId}/<action:[\\w-]+>/<id:\\d+>"] = "{$moduleId}/{$controllerId}/<action>";
                    $rules["{$moduleId}/<action:[\\w-]+>"]           = "{$moduleId}/{$controllerId}/<action>";
                    $rules["{$moduleId}"]                            = "{$moduleId}/{$controllerId}/index";
                }

                // --- HR Module: multi-controller shortened rules ---
                $hrControllers = [
                    'hr/dashboard'   => 'hr-dashboard',
                    'hr/employees'   => 'hr-employee',
                    'hr/attendance'  => 'hr-attendance',
                    'hr/payroll'     => 'hr-payroll',
                    'hr/field'       => 'hr-field',
                    'hr/evaluations' => 'hr-evaluation',
                    'hr/loans'       => 'hr-loan',
                    'hr/documents'   => 'hr-document',
                    'hr/reports'     => 'hr-report',
                    'hr/leaves'      => 'hr-leave',
                    'hr/shifts'      => 'hr-shift',
                    'hr/work-zones'  => 'hr-work-zone',
                    'hr/tracking'    => 'hr-tracking-api',
                    'hr/tracking-reports' => 'hr-tracking-report',
                ];
                foreach ($hrControllers as $shortUrl => $controllerId) {
                    $rules["{$shortUrl}/<action:[\\w-]+>/<id:\\d+>"] = "hr/{$controllerId}/<action>";
                    $rules["{$shortUrl}/<action:[\\w-]+>"]           = "hr/{$controllerId}/<action>";
                    $rules["{$shortUrl}"]                            = "hr/{$controllerId}/index";
                }

                return $rules;
            }),
        ],
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@dektrium/user/views' => '@backend/views/user'
                ],
            ],
        ],
        'assetManager' => [
            // 'forceCopy' => true,
            'appendTimestamp' => true,
            'hashCallback' => function ($path) {
                return hash('md4', $path);
            },
            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'sourcePath' => null,
                    'js' => [
                        '//ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js',
                    ]
                ],
                // Suppress BS5 CSS (already loaded via Vuexy core.css; JS bundle is NOT suppressed)
                // Suppress BS5 CSS only (already loaded via Vuexy core.css)
                // BS5 JS (bootstrap.bundle.js) is NOT suppressed — it must load as a regular script
                // so window.bootstrap is available for inline Kartik/Yii2 widget scripts
                'yii\bootstrap5\BootstrapAsset' => ['css' => []],
                // Suppress AdminLTE (replaced by Vuexy)
                'potime\adminlte3\assets\AdminLteAsset' => ['css' => [], 'js' => [], 'depends' => []],
                'potime\adminlte3\assets\PluginAsset' => ['css' => [], 'depends' => []],
                'potime\adminlte3\assets\FontAwesomeAsset' => ['css' => [], 'depends' => []],
                // Suppress legacy CrudAsset JS (replaced by tayseer-gridview-modal.js)
                'johnitvn\ajaxcrud\CrudAsset' => ['js' => [], 'css' => [], 'depends' => ['yii\web\JqueryAsset']],
                // Suppress Kartik Dialog assets (BS3 modal wrapper conflicts with BS5;
                // yii.confirm is already overridden by Vite bundle with SweetAlert2)
                'kartik\dialog\DialogBootstrapAsset' => ['js' => [], 'depends' => []],
                'kartik\dialog\DialogYiiAsset' => ['js' => [], 'depends' => []],
            ],
        ],
    ],
    /* 'as access' => [
     'class' => 'mdm\admin\components\AccessControl',
     'allowActions' => [
     'site/*',
     ]
     ],*/
    'as routeAccess' => [
        'class' => 'backend\components\RouteAccessBehavior',
    ],
    'params' => $params,
];
