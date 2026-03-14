<?php
/**
 * القائمة الجانبية — ترتيب ستاندرد ERP
 * ─────────────────────────────────────
 * لوحة التحكم → العملاء → العقود → المتابعة → المالية
 * → القانوني → التحصيل → التقارير → الموظفين → المخزون
 * → الديوان → المستثمرين → الصلاحيات → الإعدادات
 */

use yii\helpers\Url;
use common\helper\Permissions;

$mainMenuItems = [

    ['label' => 'العمليات', 'header' => true],

    // ─── 1. العملاء ───
    ['label' => 'العملاء', 'icon' => 'users', 'url' => ['/customers/index'], 'privilege' => [Permissions::CUSTOMERS, Permissions::CUST_VIEW]],

    // ─── 2. العقود ───
    ['label' => 'العقود', 'icon' => 'file-text', 'url' => ['/contracts/contracts/index'], 'privilege' => [Permissions::CONTRACTS, Permissions::CONT_VIEW]],

    // ─── 3. المتابعة ───
    ['label' => 'تقرير المتابعة', 'icon' => 'phone', 'url' => ['/followUpReport/follow-up-report/index'], 'privilege' => Permissions::FOLLOW_UP_REPORT],

    // ─── 4. الإدارة المالية ───
    ['label' => 'الإدارة المالية', 'icon' => 'money', 'url' => ['/financialTransaction/financial-transaction/index'], 'privilege' => [Permissions::FINANCIAL_TRANSACTION, Permissions::INCOME, Permissions::EXPENSES, Permissions::LOAN_SCHEDULING]],

    // ─── 5. القسم القانوني (يتضمن تبويب قسم الحسم/التحصيل) ───
    ['label' => 'القسم القانوني', 'icon' => 'gavel', 'url' => ['/judiciary/judiciary/index'], 'privilege' => [Permissions::JUDICIARY, Permissions::JUD_VIEW, Permissions::COLLECTION, Permissions::COLL_VIEW, Permissions::COLLECTION_MANAGER]],

    // ─── 6. التقارير ───
    ['label' => 'التقارير', 'icon' => 'bar-chart', 'url' => ['/reports/reports/index'], 'privilege' => [Permissions::REPORTS, Permissions::REP_VIEW]],

    ['label' => 'الموارد', 'header' => true],

    // ─── 7. الموارد البشرية (HR) ───
    [
        'label' => 'الموارد البشرية',
        'icon'  => 'id-card',
        'privilege' => [Permissions::EMPLOYEE, Permissions::JOBS, Permissions::HOLIDAYS, Permissions::LEAVE_POLICY, Permissions::LEAVE_TYPES, Permissions::WORKDAYS, Permissions::LEAVE_REQUEST, Permissions::EMPLOYEE_NOTIFICATIONS],
        'items' => [
            ['label' => 'لوحة تحكم HR',    'icon' => 'tachometer',        'url' => ['/hr/hr-dashboard/index']],
            ['label' => 'سجل الموظفين',    'icon' => 'users',             'url' => ['/hr/hr-employee/index']],
            ['label' => 'الحضور والتتبع',  'icon' => 'clock-o',           'url' => ['/hr/hr-tracking-api/attendance-board']],
            ['label' => 'الرواتب والمالية', 'icon' => 'money',             'url' => ['/hr/hr-payroll/index']],
            ['label' => 'إدارة الإجازات',   'icon' => 'calendar',          'url' => ['/hr/hr-leave/index']],
            ['label' => 'تقييمات الأداء',   'icon' => 'star-half-o',       'url' => ['/hr/hr-evaluation/index']],
            ['label' => 'التقارير',         'icon' => 'bar-chart',         'url' => ['/hr/hr-tracking-report/index']],
        ],
    ],

    // ─── 8. إدارة المخزون ───
    ['label' => 'إدارة المخزون', 'icon' => 'cubes', 'url' => ['/inventoryItems/inventory-items/index'], 'privilege' => [Permissions::INVENTORY_ITEMS, Permissions::INVENTORY_INVOICES, Permissions::INVENTORY_SUPPLIERS, Permissions::INVENTORY_STOCK_LOCATIONS, Permissions::INVENTORY_ITEMS_QUANTITY, Permissions::INVENTORY_IEMS_QUERY]],

    // ─── 9. الاستثمار ───
    ['label' => 'الاستثمار', 'icon' => 'building', 'url' => ['/companies/companies/index'], 'privilege' => Permissions::COMPAINES],

    // ─── 10. قسم الديوان ───
    ['label' => 'قسم الديوان', 'icon' => 'archive', 'url' => ['/diwan/diwan/index'], 'privilege' => [Permissions::DIWAN, Permissions::DIWAN_REPORTS]],

    ['label' => 'الإدارة والإعدادات', 'header' => true],

    // ─── لوحة التحكم — ملخص أعمال الشركة (صلاحية مستقلة) ───
    ['label' => 'لوحة التحكم', 'icon' => 'tachometer', 'url' => ['/site/index'], 'privilege' => Permissions::DASHBOARD],

    // ─── 12. إدارة الصلاحيات ───
    ['label' => 'إدارة الصلاحيات', 'icon' => 'shield', 'url' => ['/permissions-management'], 'privilege' => [Permissions::PERMISSION, Permissions::ROLE, Permissions::ASSIGNMENT]],

    // ─── أدوات المستخدم (فحص حساب، إصلاح، تعيين كلمة مرور) ───
    ['label' => 'أدوات المستخدم', 'icon' => 'user-circle', 'url' => ['/user-tools/index'], 'privilege' => Permissions::USER_TOOLS],

    // ─── 13. إعدادات النظام (تبويب واحد → صفحة موحّدة) ───
    ['label' => 'إعدادات النظام', 'icon' => 'cogs', 'url' => ['/site/system-settings'], 'privilege' => Permissions::getSettingsPermissions()],

    // ─── تسجيل الدخول (للزوار) ───
    ['label' => 'تسجيل الدخول', 'url' => ['/user/login'], 'visible' => Yii::$app->user->isGuest],
];

return Permissions::checkMainMenuItems($mainMenuItems);
