<?php

namespace common\helper;

use Yii;
//Yii::$app->user->can('مدير')
class Permissions
{

    const CUSTOMERS = 'العملاء';
    const ROUTE = 'الجذر';
    const ASSIGNMENT = 'اسناد الصلاحيات  للموظفين';
    const ROLE = 'القواعد';
    const PERMISSION = 'الصلاحيات';
    const LOAN_SCHEDULING = 'التسويات الماليه';
    const EXPENSES = 'المصاريف';
    const FINANCIAL_TRANSACTION = 'الحركات المالية';
    const EXPENSE_CATEGORIES = 'فئات المصايف';
    const EMPLOYEE = 'الموظفين';
    const INCOME = 'الدخل';
    const FOLLOW_UP_REPORT = 'تقرير المتابعة';
    const CONTRACTS = 'العقود';
    const INVENTORY_ITEMS = 'عناصر المخزون';
    const INVENTORY_STOCK_LOCATIONS = 'مواقع المخزون';
    const INVENTORY_SUPPLIERS = 'موردي المخزون';
    const BRANCH = 'الفروع';
    const COMPANIES = 'الشركات';
    const INVENTORY_ITEMS_QUANTITY = 'كمية عناصر المخزون';
    const TRANSFER_TO_LEGAL_DEPARTMENT = 'التحويل إلى الدائره القانونية';
    const LAWYERS = 'المفوضين والوكلاء';
    const COURT = 'المحاكم';
    const JUDICIARY_TYPE = 'انواع القضايا';
    const JUDICIARY = 'القضاء';
    const JUDICIARY_ACTION = 'الإجراءات القضائية';
    const JUDICIARY_CUSTOMERS_ACTION = 'إجراءات العملاء القضائية';
    const Notification = 'الاشعارات';
    const HOLIDAYS = 'العطل';
    const LEAVE_POLICY = 'سياسات الاجازات';
    const LEAVE_TYPES = 'أنواع الإجازات';
    const WORKDAYS = 'أيام العمل';
    const  LEAVE_REQUEST = 'طلب إجازة';
    const  DOCUMENT_HOLDER = 'حامل الوثيقة';
    const  MANAGER = 'الاداره';
    const  DIWAN = 'الديوان';
    const  DIWAN_REPORTS = 'تقارير الديوان';
    const  JOBS = 'الوظائف';
    const  COLLECTION = 'الحسميات';
    const COMPAINES = 'المستثمرين';
    const REPORTS = 'التقارير';
    const STATUS = 'الحالات';
    const COUSINS = 'الاقارب';
    const CITIZEN = 'الجنسيه';
    const BANCKS = 'البنوك';
    const HEAR_ABOUT_US = 'كيف سمعت عنا';
    const CITY = 'المدن';
    const PAYMENT_TYPE = 'طرق الدفع';
    const FEELINGS = 'الانفعالات';
    const CONTACT_TYPE = 'طريقة الاتصال';
    const CONNECTION_RESPONSE = 'نتيجة الاتصال';
    const CLIENT_RESPONSE = 'رد العميل';
    const MESSAGES = 'الرسائل';
    const MASSAGING = 'الرسائل';
    const FOLLOW_UP_REPORTS = 'تقرير المتابعة';
    const  DOCYUMENT_TYPE = 'انواع الوثائق';
    const Document_STATUS = 'حالات الوثائق';
    const INVENTORY_INVOICES = 'فواتير المخزون';
    const INVENTORY_IEMS_QUERY = 'استعلام عناصر المخزون';
    const FINANCIAL_TRANSACTION_TO_EXPORT_DATA = 'الحركات المالية لتصدير ونقل البيانات';
    const COLLECTION_MANAGER = 'مدير التحصيل';
    const JUDICIARY_INFORM_ADDRESS = 'الموطن المختار';

    /** لوحة التحكم الرئيسية — ملخص أعمال الشركة (تظهر في قسم الإدارة) */
    const DASHBOARD = 'لوحة التحكم';

    /** أدوات المستخدم (فحص حساب، إصلاح، تعيين كلمة مرور) — إدارة الصلاحيات */
    const USER_TOOLS = 'أدوات المستخدم';

    /** اشعارات الموظفين — للموارد البشرية */
    const EMPLOYEE_NOTIFICATIONS = 'اشعارات الموظفين';

    /** المحاسبة — النظام المحاسبي المتكامل */
    const ACCOUNTING = 'المحاسبة';

    /** إدارة المنشآت — إضافة وتجهيز الشركات الجديدة */
    const COMPANY_MANAGEMENT = 'إدارة المنشآت';

    /* ═══════════════════════════════════════════════════════════════
     *  صلاحيات الإجراءات — المحاسبة (Action-Level)
     * ═══════════════════════════════════════════════════════════════ */
    const ACC_VIEW          = 'المحاسبة: مشاهدة';
    const ACC_CREATE        = 'المحاسبة: إضافة قيود';
    const ACC_EDIT          = 'المحاسبة: تعديل';
    const ACC_DELETE        = 'المحاسبة: حذف';
    const ACC_POST          = 'المحاسبة: ترحيل قيود';
    const ACC_REVERSE       = 'المحاسبة: عكس قيود';
    const ACC_COA_MANAGE    = 'شجرة الحسابات: إدارة';
    const ACC_FISCAL_MANAGE = 'السنة المالية: إدارة';
    const ACC_BUDGET_VIEW   = 'الموازنات: مشاهدة';
    const ACC_BUDGET_MANAGE = 'الموازنات: إدارة';
    const ACC_REPORTS       = 'التقارير المالية: مشاهدة';
    const ACC_AR_MANAGE     = 'الذمم المدينة: إدارة';
    const ACC_AP_MANAGE     = 'الذمم الدائنة: إدارة';

    /* ═══════════════════════════════════════════════════════════════
     *  صلاحيات الإجراءات — الحركات المالية (Action-Level)
     * ═══════════════════════════════════════════════════════════════ */
    const FIN_VIEW     = 'الحركات المالية: مشاهدة';
    const FIN_CREATE   = 'الحركات المالية: إضافة';
    const FIN_EDIT     = 'الحركات المالية: تعديل';
    const FIN_DELETE   = 'الحركات المالية: حذف';
    const FIN_IMPORT   = 'الحركات المالية: استيراد';
    const FIN_TRANSFER = 'الحركات المالية: ترحيل';

    /* ═══ صلاحيات الإجراءات — الدفعات ═══ */
    const INC_VIEW   = 'الدخل: مشاهدة';
    const INC_CREATE = 'الدخل: إضافة';
    const INC_EDIT   = 'الدخل: تعديل';
    const INC_DELETE  = 'الدخل: حذف';
    const INC_REVERT = 'الدخل: ارجاع';

    /* ═══ صلاحيات الإجراءات — المصاريف ═══ */
    const EXP_VIEW   = 'المصاريف: مشاهدة';
    const EXP_CREATE = 'المصاريف: إضافة';
    const EXP_EDIT   = 'المصاريف: تعديل';
    const EXP_DELETE  = 'المصاريف: حذف';
    const EXP_REVERT = 'المصاريف: ارجاع';

    /* ═══════════════════════════════════════════════════════════════
     *  صلاحيات CRUD تفصيلية — العملاء
     * ═══════════════════════════════════════════════════════════════ */
    const CUST_VIEW   = 'العملاء: مشاهدة';
    const CUST_CREATE = 'العملاء: إضافة';
    const CUST_UPDATE = 'العملاء: تعديل';
    const CUST_DELETE = 'العملاء: حذف';
    const CUST_EXPORT = 'العملاء: تصدير';

    /* ═══ CRUD — المستثمرين ═══ */
    const COMP_VIEW   = 'المستثمرين: مشاهدة';
    const COMP_CREATE = 'المستثمرين: إضافة';
    const COMP_UPDATE = 'المستثمرين: تعديل';
    const COMP_DELETE = 'المستثمرين: حذف';

    /* ═══ CRUD — العقود ═══ */
    const CONT_VIEW   = 'العقود: مشاهدة';
    const CONT_CREATE = 'العقود: إضافة';
    const CONT_UPDATE = 'العقود: تعديل';
    const CONT_DELETE = 'العقود: حذف';

    /* ═══ CRUD — المتابعة ═══ */
    const FOLLOWUP_VIEW   = 'المتابعة: مشاهدة';
    const FOLLOWUP_CREATE = 'المتابعة: إضافة';
    const FOLLOWUP_UPDATE = 'المتابعة: تعديل';
    const FOLLOWUP_DELETE = 'المتابعة: حذف';

    /* ═══ CRUD — الحسميات ═══ */
    const COLL_VIEW   = 'الحسميات: مشاهدة';
    const COLL_CREATE = 'الحسميات: إضافة';
    const COLL_UPDATE = 'الحسميات: تعديل';
    const COLL_DELETE = 'الحسميات: حذف';

    /* ═══ CRUD — القضاء ═══ */
    const JUD_VIEW   = 'القضاء: مشاهدة';
    const JUD_CREATE = 'القضاء: إضافة';
    const JUD_UPDATE = 'القضاء: تعديل';
    const JUD_DELETE = 'القضاء: حذف';

    /* ═══ CRUD — الموظفين ═══ */
    const EMP_VIEW   = 'الموظفين: مشاهدة';
    const EMP_CREATE = 'الموظفين: إضافة';
    const EMP_UPDATE = 'الموظفين: تعديل';
    const EMP_DELETE = 'الموظفين: حذف';

    /* ═══ CRUD — عناصر المخزون ═══ */
    const INVITEM_VIEW   = 'عناصر المخزون: مشاهدة';
    const INVITEM_CREATE = 'عناصر المخزون: إضافة';
    const INVITEM_UPDATE = 'عناصر المخزون: تعديل';
    const INVITEM_DELETE = 'عناصر المخزون: حذف';

    /* ═══ CRUD — فواتير المخزون ═══ */
    const INVINV_VIEW    = 'فواتير المخزون: مشاهدة';
    const INVINV_CREATE  = 'فواتير المخزون: إضافة';
    const INVINV_UPDATE  = 'فواتير المخزون: تعديل';
    const INVINV_DELETE  = 'فواتير المخزون: حذف';
    const INVINV_APPROVE = 'فواتير المخزون: اعتماد';

    /* ═══ CRUD — الديوان ═══ */
    const DIWAN_VIEW   = 'الديوان: مشاهدة';
    const DIWAN_CREATE = 'الديوان: إضافة';
    const DIWAN_UPDATE = 'الديوان: تعديل';
    const DIWAN_DELETE = 'الديوان: حذف';

    /* ═══ CRUD — الوظائف (جهات العمل) ═══ */
    const JOBS_VIEW   = 'الوظائف: مشاهدة';
    const JOBS_CREATE = 'الوظائف: إضافة';
    const JOBS_UPDATE = 'الوظائف: تعديل';
    const JOBS_DELETE = 'الوظائف: حذف';

    /* ═══ CRUD — التقارير ═══ */
    const REP_VIEW   = 'التقارير: مشاهدة';
    const REP_EXPORT = 'التقارير: تصدير';


    /* ═══════════════════════════════════════════════════════════════
     *  هرمية الصلاحيات — الأب يمنح كل الأبناء تلقائياً
     *  ─────────────────────────────────────────────────────────────
     *  عندما يملك المستخدم الصلاحية الأب (مثلاً «العملاء»)
     *  فإن Yii2 RBAC تمنحه تلقائياً كل الأبناء المذكورة هنا.
     *  هذا يضمن التوافق العكسي: من لديه الصلاحية القديمة يحتفظ بكل الوصول.
     * ═══════════════════════════════════════════════════════════════ */
    public static function getPermissionHierarchy()
    {
        return [
            self::CUSTOMERS => [self::CUST_VIEW, self::CUST_CREATE, self::CUST_UPDATE, self::CUST_DELETE, self::CUST_EXPORT],
            self::COMPAINES => [self::COMP_VIEW, self::COMP_CREATE, self::COMP_UPDATE, self::COMP_DELETE],
            self::CONTRACTS => [self::CONT_VIEW, self::CONT_CREATE, self::CONT_UPDATE, self::CONT_DELETE],
            'المتابعة'     => [self::FOLLOWUP_VIEW, self::FOLLOWUP_CREATE, self::FOLLOWUP_UPDATE, self::FOLLOWUP_DELETE],
            self::COLLECTION => [self::COLL_VIEW, self::COLL_CREATE, self::COLL_UPDATE, self::COLL_DELETE],
            self::FINANCIAL_TRANSACTION => [self::FIN_VIEW, self::FIN_CREATE, self::FIN_EDIT, self::FIN_DELETE, self::FIN_IMPORT, self::FIN_TRANSFER],
            self::INCOME    => [self::INC_VIEW, self::INC_CREATE, self::INC_EDIT, self::INC_DELETE, self::INC_REVERT],
            self::EXPENSES  => [self::EXP_VIEW, self::EXP_CREATE, self::EXP_EDIT, self::EXP_DELETE, self::EXP_REVERT],
            self::JUDICIARY => [self::JUD_VIEW, self::JUD_CREATE, self::JUD_UPDATE, self::JUD_DELETE],
            self::EMPLOYEE  => [self::EMP_VIEW, self::EMP_CREATE, self::EMP_UPDATE, self::EMP_DELETE],
            self::INVENTORY_ITEMS    => [self::INVITEM_VIEW, self::INVITEM_CREATE, self::INVITEM_UPDATE, self::INVITEM_DELETE],
            self::INVENTORY_INVOICES => [self::INVINV_VIEW, self::INVINV_CREATE, self::INVINV_UPDATE, self::INVINV_DELETE, self::INVINV_APPROVE],
            self::DIWAN     => [self::DIWAN_VIEW, self::DIWAN_CREATE, self::DIWAN_UPDATE, self::DIWAN_DELETE],
            self::JOBS      => [self::JOBS_VIEW, self::JOBS_CREATE, self::JOBS_UPDATE, self::JOBS_DELETE],
            self::REPORTS   => [self::REP_VIEW, self::REP_EXPORT],
            self::ACCOUNTING => [self::ACC_VIEW, self::ACC_CREATE, self::ACC_EDIT, self::ACC_DELETE, self::ACC_POST, self::ACC_REVERSE, self::ACC_COA_MANAGE, self::ACC_FISCAL_MANAGE, self::ACC_BUDGET_VIEW, self::ACC_BUDGET_MANAGE, self::ACC_REPORTS, self::ACC_AR_MANAGE, self::ACC_AP_MANAGE],
        ];
    }

    /**
     * إرجاع كل صلاحيات وحدة معيّنة (الأب + كل الأبناء CRUD)
     */
    public static function getModulePermissions($parentPermission)
    {
        $hierarchy = self::getPermissionHierarchy();
        $result = [$parentPermission];
        if (isset($hierarchy[$parentPermission])) {
            $result = array_merge($result, $hierarchy[$parentPermission]);
        }
        return $result;
    }

    /**
     * خريطة الإجراءات التفصيلية — تحدد أي صلاحية مطلوبة لكل action محدد
     * المفتاح: controllerId (مثل customers/customers)
     * القيمة: مصفوفة [actionId => صلاحية أو مصفوفة صلاحيات]
     * الإجراءات غير المذكورة = لا تحتاج فحص إضافي (يكفي صلاحية الوحدة)
     */
    public static function getActionPermissionMap()
    {
        return [
            /* العملاء */
            'customers/customers' => [
                'index'         => self::CUST_VIEW,
                'view'          => self::CUST_VIEW,
                'create'        => self::CUST_CREATE,
                /* Emergency rollback for Customer Wizard v2.
                 * Same permission as the new wizard so existing CUST_CREATE
                 * holders can fall back to the legacy form if ever needed. */
                'create-legacy' => self::CUST_CREATE,
                'create-summary'=> self::CUST_VIEW,
                'update'        => self::CUST_UPDATE,
                'update-contact'=> self::CUST_UPDATE,
                'delete'        => self::CUST_DELETE,
                'bulkdelete'    => self::CUST_DELETE,
            ],
            /* معالج إنشاء العميل (الإصدار الجديد) */
            'customers/wizard' => [
                'start'    => self::CUST_CREATE,
                'step'     => self::CUST_CREATE,
                'save'     => self::CUST_CREATE,
                'finish'   => self::CUST_CREATE,
                'resume'   => self::CUST_CREATE,
                'drafts'   => self::CUST_CREATE,
                'discard'  => self::CUST_CREATE,
                'scan'     => self::CUST_CREATE,
                'validate' => self::CUST_CREATE,
            ],
            /* المستثمرين */
            'companies/companies' => [
                'index'  => self::COMP_VIEW,
                'view'   => self::COMP_VIEW,
                'create' => self::COMP_CREATE,
                'update' => self::COMP_UPDATE,
                'delete' => self::COMP_DELETE,
                'bulkdelete' => self::COMP_DELETE,
            ],
            /* العقود */
            'contracts/contracts' => [
                'index'              => self::CONT_VIEW,
                'view'               => self::CONT_VIEW,
                'create'             => self::CONT_CREATE,
                'update'             => self::CONT_UPDATE,
                'delete'             => self::CONT_DELETE,
                'bulkdelete'         => self::CONT_DELETE,
                'index-legal-department' => self::CONT_VIEW,
                'legal-department'   => self::CONT_VIEW,
                'print-preview'      => self::CONT_VIEW,
                'print-first-page'   => self::CONT_VIEW,
                'print-second-page'  => self::CONT_VIEW,
            ],
            /* المتابعة */
            'followUp/follow-up' => [
                'index'  => self::FOLLOWUP_VIEW,
                'view'   => self::FOLLOWUP_VIEW,
                'create' => self::FOLLOWUP_CREATE,
                'update' => self::FOLLOWUP_UPDATE,
                'delete' => self::FOLLOWUP_DELETE,
            ],
            /* الحسميات */
            'collection/collection' => [
                'index'  => self::COLL_VIEW,
                'view'   => self::COLL_VIEW,
                'create' => self::COLL_CREATE,
                'update' => self::COLL_UPDATE,
                'delete' => self::COLL_DELETE,
            ],
            /* الحركات المالية */
            'financialTransaction/financial-transaction' => [
                'index'  => self::FIN_VIEW,
                'view'   => self::FIN_VIEW,
                'create' => self::FIN_CREATE,
                'update' => self::FIN_EDIT,
                'delete' => self::FIN_DELETE,
            ],
            /* الدخل */
            'income/income' => [
                'index'  => self::INC_VIEW,
                'view'   => self::INC_VIEW,
                'create' => self::INC_CREATE,
                'update' => self::INC_EDIT,
                'delete' => self::INC_DELETE,
            ],
            /* المصاريف */
            'expenses/expenses' => [
                'index'  => self::EXP_VIEW,
                'view'   => self::EXP_VIEW,
                'create' => self::EXP_CREATE,
                'update' => self::EXP_EDIT,
                'delete' => self::EXP_DELETE,
            ],
            /* القضاء */
            'judiciary/judiciary' => [
                'index'  => self::JUD_VIEW,
                'view'   => self::JUD_VIEW,
                'create' => self::JUD_CREATE,
                'update' => self::JUD_UPDATE,
                'delete' => self::JUD_DELETE,
                'tab-actions'     => self::JUD_VIEW,
                'tab-cases'       => self::JUD_VIEW,
                'tab-persistence' => self::JUD_VIEW,
                'tab-legal'       => self::JUD_VIEW,
                'tab-collection'  => self::JUD_VIEW,
                'tab-counts'      => self::JUD_VIEW,
                'export-cases-report'    => self::JUD_VIEW,
                'export-cases-excel'     => self::JUD_VIEW,
                'export-cases-pdf'       => self::JUD_VIEW,
                'export-actions-excel'   => self::JUD_VIEW,
                'export-actions-pdf'     => self::JUD_VIEW,
                'batch-print'            => self::JUD_VIEW,
                'print-case'             => self::JUD_VIEW,
                'case-timeline'          => self::JUD_VIEW,
                'deadline-dashboard'     => self::JUD_VIEW,
                'deadline-dashboard-view' => self::JUD_VIEW,
                'deadline-refresh'       => self::JUD_VIEW,
                'correspondence-list'    => self::JUD_VIEW,
                'generate-request'       => self::JUD_CREATE,
                'save-generated-request' => self::JUD_CREATE,
                'update-request-status'  => self::JUD_UPDATE,
                'entity-search'          => self::JUD_VIEW,
                'delete-customer-action' => self::JUD_DELETE,
                'report'                 => self::JUD_VIEW,
            ],
            /* الموظفين */
            'employee/employee' => [
                'index'  => self::EMP_VIEW,
                'view'   => self::EMP_VIEW,
                'create' => self::EMP_CREATE,
                'update' => self::EMP_UPDATE,
                'delete' => self::EMP_DELETE,
            ],
            'hr/hr-employee' => [
                'index'  => self::EMP_VIEW,
                'view'   => self::EMP_VIEW,
                'create' => self::EMP_CREATE,
                'update' => self::EMP_UPDATE,
                'delete' => self::EMP_DELETE,
            ],
            /* عناصر المخزون */
            'inventoryItems/inventory-items' => [
                'index'    => self::INVITEM_VIEW,
                'view'     => self::INVITEM_VIEW,
                'create'   => self::INVITEM_CREATE,
                'update'   => self::INVITEM_UPDATE,
                'delete'   => self::INVITEM_DELETE,
                'settings' => self::INVITEM_UPDATE,
            ],
            /* فواتير المخزون */
            'inventoryInvoices/inventory-invoices' => [
                'index'         => self::INVINV_VIEW,
                'view'          => self::INVINV_VIEW,
                'create'        => self::INVINV_CREATE,
                'create-wizard' => self::INVINV_CREATE,
                'update'        => self::INVINV_UPDATE,
                'delete'        => self::INVINV_DELETE,
                'approve'            => self::INVINV_APPROVE,
                'approve-reception'  => self::INVINV_APPROVE,
                'approve-manager'    => self::INVINV_APPROVE,
                'reject-reception'   => self::INVINV_APPROVE,
                'reject-manager'     => self::INVINV_APPROVE,
            ],
            /* الديوان */
            'diwan/diwan' => [
                'index'  => self::DIWAN_VIEW,
                'view'   => self::DIWAN_VIEW,
                'create' => self::DIWAN_CREATE,
                'update' => self::DIWAN_UPDATE,
                'delete' => self::DIWAN_DELETE,
            ],
            /* التقارير */
            'reports/reports' => [
                'index' => self::REP_VIEW,
            ],
            /* جهات العمل — CRUD بصلاحيات تفصيلية، search-list مسموح لمن يملك صلاحية عملاء أو عقود */
            'jobs/jobs' => [
                'index'       => self::JOBS_VIEW,
                'view'        => self::JOBS_VIEW,
                'create'      => self::JOBS_CREATE,
                'update'      => self::JOBS_UPDATE,
                'delete'      => self::JOBS_DELETE,
                'bulk-delete' => self::JOBS_DELETE,
            ],
            /* المحاسبة — شجرة الحسابات */
            'accounting/chart-of-accounts' => [
                'index'         => self::ACC_VIEW,
                'tree'          => self::ACC_VIEW,
                'view'          => self::ACC_VIEW,
                'create'        => self::ACC_COA_MANAGE,
                'update'        => self::ACC_COA_MANAGE,
                'delete'        => self::ACC_COA_MANAGE,
                'toggle-status' => self::ACC_COA_MANAGE,
            ],
            /* المحاسبة — السنوات المالية */
            'accounting/fiscal-year' => [
                'index'        => self::ACC_VIEW,
                'view'         => self::ACC_VIEW,
                'create'       => self::ACC_FISCAL_MANAGE,
                'update'       => self::ACC_FISCAL_MANAGE,
                'close-period' => self::ACC_FISCAL_MANAGE,
                'close-year'   => self::ACC_FISCAL_MANAGE,
            ],
            /* المحاسبة — مراكز التكلفة */
            'accounting/cost-center' => [
                'index'  => self::ACC_VIEW,
                'create' => self::ACC_COA_MANAGE,
                'update' => self::ACC_COA_MANAGE,
                'delete' => self::ACC_COA_MANAGE,
            ],
            /* المحاسبة — القيود اليومية */
            'accounting/journal-entry' => [
                'index'   => self::ACC_VIEW,
                'view'    => self::ACC_VIEW,
                'create'  => self::ACC_CREATE,
                'update'  => self::ACC_EDIT,
                'delete'  => self::ACC_DELETE,
                'post'    => self::ACC_POST,
                'reverse' => self::ACC_REVERSE,
            ],
            /* المحاسبة — الأستاذ العام */
            'accounting/general-ledger' => [
                'index'   => [self::ACC_VIEW, self::ACC_REPORTS],
                'account' => [self::ACC_VIEW, self::ACC_REPORTS],
            ],
            /* المحاسبة — لوحة التحكم */
            'accounting/default' => [
                'index' => self::ACC_VIEW,
            ],
            /* المحاسبة — التحليل الذكي */
            'accounting/ai-insights' => [
                'index' => [self::ACC_VIEW, self::ACC_REPORTS],
            ],
            /* المحاسبة — التقارير المالية */
            'accounting/financial-statements' => [
                'trial-balance'    => [self::ACC_VIEW, self::ACC_REPORTS],
                'income-statement' => [self::ACC_VIEW, self::ACC_REPORTS],
                'balance-sheet'    => [self::ACC_VIEW, self::ACC_REPORTS],
                'cash-flow'        => [self::ACC_VIEW, self::ACC_REPORTS],
            ],
            /* المحاسبة — الموازنات */
            'accounting/budget' => [
                'index'       => [self::ACC_VIEW, self::ACC_BUDGET_VIEW],
                'view'        => [self::ACC_VIEW, self::ACC_BUDGET_VIEW],
                'variance'    => [self::ACC_VIEW, self::ACC_BUDGET_VIEW],
                'create'      => self::ACC_BUDGET_MANAGE,
                'update'      => self::ACC_BUDGET_MANAGE,
                'add-line'    => self::ACC_BUDGET_MANAGE,
                'remove-line' => self::ACC_BUDGET_MANAGE,
                'approve'     => self::ACC_BUDGET_MANAGE,
                'delete'      => self::ACC_DELETE,
            ],
            /* المحاسبة — الذمم المدينة */
            'accounting/accounts-receivable' => [
                'index'          => [self::ACC_VIEW, self::ACC_AR_MANAGE],
                'create'         => self::ACC_AR_MANAGE,
                'update'         => self::ACC_AR_MANAGE,
                'delete'         => self::ACC_DELETE,
                'record-payment' => self::ACC_AR_MANAGE,
                'aging-report'   => [self::ACC_VIEW, self::ACC_AR_MANAGE],
            ],
            /* المحاسبة — الذمم الدائنة */
            'accounting/accounts-payable' => [
                'index'          => [self::ACC_VIEW, self::ACC_AP_MANAGE],
                'create'         => self::ACC_AP_MANAGE,
                'update'         => self::ACC_AP_MANAGE,
                'delete'         => self::ACC_DELETE,
                'record-payment' => self::ACC_AP_MANAGE,
                'aging-report'   => [self::ACC_VIEW, self::ACC_AP_MANAGE],
            ],
        ];
    }

    /**
     * فحص صلاحية إجراء محدد لمسار معيّن
     * يُرجع الصلاحية المطلوبة أو null إذا لا قيد action-level
     */
    public static function getActionPermission(string $controllerId, string $actionId)
    {
        $map = self::getActionPermissionMap();

        // محاولة مطابقة المسار الكامل
        if (isset($map[$controllerId][$actionId])) {
            return $map[$controllerId][$actionId];
        }

        // محاولة المسار المختصر (segment واحد)
        if (strpos($controllerId, '/') === false) {
            $expanded = $controllerId . '/' . $controllerId;
            if (isset($map[$expanded][$actionId])) {
                return $map[$expanded][$actionId];
            }
            foreach (array_keys($map) as $key) {
                if (strpos($key, $controllerId . '/') === 0 && isset($map[$key][$actionId])) {
                    return $map[$key][$actionId];
                }
            }
        }

        return null;
    }


    /** صلاحيات الموارد البشرية (أي واحدة تكفي للوصول لشاشات HR) */
    public static function getHrPermissions()
    {
        return [
            self::EMPLOYEE,
            self::HOLIDAYS,
            self::LEAVE_POLICY,
            self::LEAVE_TYPES,
            self::WORKDAYS,
            self::LEAVE_REQUEST,
            self::EMPLOYEE_NOTIFICATIONS,
            self::EMP_VIEW, self::EMP_CREATE, self::EMP_UPDATE, self::EMP_DELETE,
        ];
    }

    /** صلاحيات إعدادات النظام (أي واحدة تكفي للوصول لصفحة الإعدادات) */
    public static function getSettingsPermissions()
    {
        return [
            self::STATUS,
            self::Document_STATUS,
            self::COUSINS,
            self::CITIZEN,
            self::BANCKS,
            self::HEAR_ABOUT_US,
            self::CITY,
            self::PAYMENT_TYPE,
            self::FEELINGS,
            self::CONTACT_TYPE,
            self::CLIENT_RESPONSE,
            self::DOCYUMENT_TYPE,
            self::MESSAGES,
            self::JOBS,
            self::JOBS_VIEW,
            self::JOBS_CREATE,
            self::JOBS_UPDATE,
            self::JOBS_DELETE,
        ];
    }

    /**
     * فحص AND — المستخدم يملك **كل** الصلاحيات المُمرّرة
     */
    public static function hasPermissionOn($permission)
    {
        $permission = is_array($permission) ? $permission : [$permission];
        $hasPermission = true;
        foreach ($permission as $key => $permissionName) {
            if (!Yii::$app->user->can($permissionName)) {
                $hasPermission = false;
                break;
            }
        }
        return $hasPermission;
    }

    /**
     * فحص OR — المستخدم يملك **أي** صلاحية من المُمرّرة
     */
    public static function hasAnyPermission($permissions)
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        foreach ($permissions as $permissionName) {
            if (Yii::$app->user->can($permissionName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * فحص صلاحية CRUD — يتحقق من الصلاحية التفصيلية أو الأب (التوافق العكسي)
     * مثال: can('العملاء: إضافة') يقبل إذا المستخدم لديه:
     *   - «العملاء: إضافة» مباشرة، أو
     *   - «العملاء» (الأب — من خلال الهرمية RBAC)
     * ملاحظة: Yii2 RBAC يعالج الهرمية تلقائياً عبر auth_item_child
     */
    public static function can($permission)
    {
        return Yii::$app->user->can($permission);
    }

    /**
     * خريطة المسارات والصلاحيات — للتحقق من الوصول المباشر عبر الرابط
     * المفتاح: بادئة المسار (مثلاً hr/) أو المسار الكامل (مثلاً customers/customers)
     * القيمة: مصفوفة صلاحيات (يكفي امتلاك أي واحدة)
     * إذا المسار غير موجود في الخريطة يُسمح بالوصول (للتوافق مع لوحة التحكم والروابط العامة).
     */
    public static function getRoutePermissionMap()
    {
        return [
            /* لوحة التحكم — الصفحة الرئيسية (افتراضي لكل site/*) */
            'site' => [self::DASHBOARD],
            /* إعدادات النظام وإدارة الصور — تحتاج صلاحيات الإعدادات لا لوحة التحكم */
            'site/system-settings'        => self::getSettingsPermissions(),
            'site/image-manager'          => self::getSettingsPermissions(),
            'site/image-manager-data'     => self::getSettingsPermissions(),
            'site/image-manager-stats'    => self::getSettingsPermissions(),
            'site/image-reassign'         => self::getSettingsPermissions(),
            'site/image-search-customers' => self::getSettingsPermissions(),
            'site/image-update-doc-type'  => self::getSettingsPermissions(),
            'site/image-delete'           => self::getSettingsPermissions(),
            'site/server-backup'          => self::getSettingsPermissions(),
            'site/test-google-connection'   => self::getSettingsPermissions(),
            'site/test-maps-connection'     => self::getSettingsPermissions(),
            'site/test-sms-connection'      => self::getSettingsPermissions(),
            'site/test-whatsapp-connection' => self::getSettingsPermissions(),
            'site/test-whatsapp-message'    => self::getSettingsPermissions(),
            /* العملاء */
            'customers/customers' => self::getModulePermissions(self::CUSTOMERS),
            'customers/smart-media' => self::getModulePermissions(self::CUSTOMERS),
            'customers/wizard' => self::getModulePermissions(self::CUSTOMERS),
            /* العقود والمتابعة */
            'contracts/contracts' => array_merge(
                self::getModulePermissions(self::CONTRACTS),
                self::getModulePermissions('المتابعة')
            ),
            'followUp/follow-up' => array_merge(
                self::getModulePermissions(self::CONTRACTS),
                self::getModulePermissions('المتابعة')
            ),
            'followUpReport/follow-up-report' => [self::FOLLOW_UP_REPORT],
            /* المالية */
            'financialTransaction/financial-transaction' => array_merge(
                self::getModulePermissions(self::FINANCIAL_TRANSACTION),
                self::getModulePermissions(self::INCOME),
                self::getModulePermissions(self::EXPENSES),
                [self::LOAN_SCHEDULING]
            ),
            'income/income' => self::getModulePermissions(self::INCOME),
            'expenses/expenses' => self::getModulePermissions(self::EXPENSES),
            'expenseCategories/expense-categories' => [self::EXPENSE_CATEGORIES],
            'loanScheduling/loan-scheduling' => [self::LOAN_SCHEDULING],
            'movment/movment' => self::getModulePermissions(self::FINANCIAL_TRANSACTION),
            /* القضاء والقانون */
            'judiciary/judiciary' => self::getModulePermissions(self::JUDICIARY),
            'judiciaryActions/judiciary-actions' => [self::JUDICIARY_ACTION],
            'judiciaryCustomersActions/judiciary-customers-actions' => [self::JUDICIARY_CUSTOMERS_ACTION],
            'judiciaryType/judiciary-type' => [self::JUDICIARY_TYPE],
            'court/court' => [self::COURT],
            'lawyers/lawyers' => [self::LAWYERS],
            'JudiciaryInformAddress/judiciary-inform-address' => [self::JUDICIARY_INFORM_ADDRESS],
            /* التحصيل */
            'collection/collection' => self::getModulePermissions(self::COLLECTION),
            /* التقارير */
            'reports/reports' => self::getModulePermissions(self::REPORTS),
            /* الموارد البشرية (متحكمات خارج موديول hr) */
            'employee/employee' => self::getHrPermissions(),
            'designation/designation' => self::getHrPermissions(),
            'holidays/holidays' => [self::HOLIDAYS],
            'leaveTypes/leave-types' => [self::LEAVE_TYPES],
            'leaveRequest/leave-request' => [self::LEAVE_REQUEST],
            'leavePolicy/leave-policy' => [self::LEAVE_POLICY],
            'workdays/workdays' => [self::WORKDAYS],
            'attendance/attendance' => self::getHrPermissions(),
            'jobs/jobs' => array_merge(
                self::getModulePermissions(self::JOBS),
                self::getModulePermissions(self::CUSTOMERS),
                self::getModulePermissions(self::CONTRACTS)
            ),
            /* المخزون */
            'inventoryItems/inventory-items' => array_merge(
                self::getModulePermissions(self::INVENTORY_ITEMS),
                self::getModulePermissions(self::INVENTORY_INVOICES),
                [self::INVENTORY_SUPPLIERS, self::INVENTORY_STOCK_LOCATIONS, self::INVENTORY_ITEMS_QUANTITY, self::INVENTORY_IEMS_QUERY]
            ),
            'inventoryInvoices/inventory-invoices' => self::getModulePermissions(self::INVENTORY_INVOICES),
            'inventorySuppliers/inventory-suppliers' => [self::INVENTORY_SUPPLIERS],
            'inventoryStockLocations/inventory-stock-locations' => [self::INVENTORY_STOCK_LOCATIONS],
            'inventoryItemQuantities/inventory-item-quantities' => [self::INVENTORY_ITEMS_QUANTITY],
            'itemsInventoryInvoices/items-inventory-invoices' => self::getModulePermissions(self::INVENTORY_INVOICES),
            /* المستثمرين والاستثمار */
            'companies/companies' => self::getModulePermissions(self::COMPAINES),
            'shareholders/shareholders' => self::getModulePermissions(self::COMPAINES),
            'capitalTransactions/capital-transactions' => self::getModulePermissions(self::COMPAINES),
            'sharedExpenses/shared-expense' => self::getModulePermissions(self::COMPAINES),
            'profitDistribution/profit-distribution' => self::getModulePermissions(self::COMPAINES),
            /* الديوان */
            'diwan/diwan' => array_merge(
                self::getModulePermissions(self::DIWAN),
                self::getModulePermissions(self::JUDICIARY),
                [self::DIWAN_REPORTS]
            ),
            /* الجهات الرسمية */
            'judiciaryAuthorities/judiciary-authorities' => self::getModulePermissions(self::JUDICIARY),
            /* العطل الرسمية */
            'officialHolidays/official-holidays' => self::getModulePermissions(self::JUDICIARY),
            /* قوالب الطلبات */
            'judiciaryRequestTemplates/judiciary-request-templates' => self::getModulePermissions(self::JUDICIARY),
            /* إدارة الصلاحيات وأدوات المستخدم */
            'permissions-management' => [self::PERMISSION, self::ROLE, self::ASSIGNMENT],
            'user-tools' => [self::USER_TOOLS],
            /* الإعدادات والمراجع */
            'status/status' => self::getSettingsPermissions(),
            'documentStatus/document-status' => self::getSettingsPermissions(),
            'cousins/cousins' => self::getSettingsPermissions(),
            'citizen/citizen' => self::getSettingsPermissions(),
            'bancks/bancks' => self::getSettingsPermissions(),
            'hearAboutUs/hear-about-us' => self::getSettingsPermissions(),
            'city/city' => self::getSettingsPermissions(),
            'paymentType/payment-type' => self::getSettingsPermissions(),
            'feelings/feelings' => self::getSettingsPermissions(),
            'contactType/contact-type' => self::getSettingsPermissions(),
            'connectionResponse/connection-response' => self::getSettingsPermissions(),
            'documentType/document-type' => self::getSettingsPermissions(),
            'notification/notification' => [self::Notification],
            'notification/notification/poll'        => [],
            'notification/notification/is-read'     => [],
            'notification/notification/mark-read'   => [],
            'notification/notification/see-all-msg' => [],
            'notification/notification/center'      => [],
            'phoneNumbers/phone-numbers' => self::getSettingsPermissions(),
            'location/location' => self::getSettingsPermissions(),
            'branch/branch' => [self::BRANCH],
            'address/address' => self::getSettingsPermissions(),
            /* أخرى */
            'documentHolder/document-holder' => [self::DOCUMENT_HOLDER],
            'department/department' => [self::MANAGER],
            'authAssignment/auth-assignment' => [self::ASSIGNMENT],
            'rejesterFollowUpType/rejester-follow-up-type' => self::getModulePermissions(self::CONTRACTS),
            'contractInstallment/contract-installment' => self::getModulePermissions(self::CONTRACTS),
            'contractDocumentFile/contract-document-file' => self::getModulePermissions(self::CONTRACTS),
            'invoice/invoice' => self::getModulePermissions(self::INCOME),
            'incomeCategory/income-category' => self::getModulePermissions(self::INCOME),
            'items/items' => self::getModulePermissions(self::INVENTORY_ITEMS),
            'divisionsCollection/divisions-collection' => self::getModulePermissions(self::COLLECTION),
            /* المحاسبة */
            'accounting/default' => [self::ACC_VIEW],
            'accounting/ai-insights' => [self::ACC_VIEW, self::ACC_REPORTS],
            'accounting/chart-of-accounts' => [self::ACC_VIEW, self::ACC_COA_MANAGE],
            'accounting/fiscal-year' => [self::ACC_VIEW, self::ACC_FISCAL_MANAGE],
            'accounting/cost-center' => [self::ACC_VIEW, self::ACC_COA_MANAGE],
            'accounting/journal-entry' => [self::ACC_VIEW, self::ACC_CREATE, self::ACC_POST],
            'accounting/general-ledger' => [self::ACC_VIEW, self::ACC_REPORTS],
            'accounting/accounts-receivable' => [self::ACC_VIEW, self::ACC_AR_MANAGE],
            'accounting/accounts-payable' => [self::ACC_VIEW, self::ACC_AP_MANAGE],
            'accounting/financial-statements' => [self::ACC_VIEW, self::ACC_REPORTS],
            'accounting/budget' => [self::ACC_VIEW, self::ACC_BUDGET_VIEW, self::ACC_BUDGET_MANAGE],
            /* تثبيت العناصر — متاح لجميع المستخدمين المسجّلين */
            'pin' => [],
        ];
    }

    /**
     * إرجاع مصفوفة مسطّحة بكل الصلاحيات المذكورة في خريطة المسارات (لا تكرار)
     * — للاستخدام في «إظهار لوحة التحكم فقط لمن لديه أي صلاحية»
     */
    public static function getAllMappedPermissions()
    {
        $out = [];
        foreach (self::getRoutePermissionMap() as $perms) {
            $arr = is_array($perms) ? $perms : [$perms];
            foreach ($arr as $p) {
                if (is_string($p)) $out[$p] = true;
            }
        }
        foreach (self::getHrPermissions() as $p) {
            $out[$p] = true;
        }
        return array_keys($out);
    }

    /**
     * إرجاع صلاحيات مطلوبة لمسار معيّن.
     * يدعم المسارات المختصرة (مثل customers بدل customers/customers) لأن urlManager ينتج روابط قصيرة.
     */
    public static function getRequiredPermissionsForRoute(string $controllerUniqueId)
    {
        $map = self::getRoutePermissionMap();
        if (isset($map[$controllerUniqueId])) {
            return $map[$controllerUniqueId];
        }
        // مسار مختصر من segment واحد (مثلاً customers، contracts) — نطابق مع module/controller
        if (strpos($controllerUniqueId, '/') === false) {
            $expanded = $controllerUniqueId . '/' . $controllerUniqueId;
            if (isset($map[$expanded])) {
                return $map[$expanded];
            }
            // محاولة كيكس: financialTransaction → financialTransaction/financial-transaction
            foreach (array_keys($map) as $key) {
                if (strpos($key, $controllerUniqueId . '/') === 0) {
                    return $map[$key];
                }
            }
        }
        // نظام الحضور والانصراف — متاح لجميع الموظفين المسجّلين
        if ($controllerUniqueId === 'hr/hr-field') {
            return [];
        }
        if (strpos($controllerUniqueId, 'hr/') === 0) {
            return self::getHrPermissions();
        }
        return null;
    }

    /**
     * فلترة عناصر القائمة الجانبية
     * ─────────────────────────────
     * يدعم privilege كـ:
     *   - string → فحص صلاحية واحدة (AND كالسابق)
     *   - array  → فحص OR (يكفي امتلاك أي صلاحية)
     */
    /**
     * فحص صلاحية مجموعة قائمة جانبية — يُظهر القسم إذا المستخدم يملك أي صلاحية من المجموعة
     */
    public static function groupsPermissions($group)
    {
        $groups = [
            'inventory' => [self::INVENTORY_ITEMS, self::INVENTORY_STOCK_LOCATIONS, self::INVENTORY_SUPPLIERS, self::INVENTORY_ITEMS_QUANTITY, self::INVENTORY_INVOICES],
            'employees manage' => [self::EMPLOYEE, self::HOLIDAYS, self::LEAVE_POLICY, self::LEAVE_TYPES, self::WORKDAYS, self::LEAVE_REQUEST],
            'legal department' => [self::TRANSFER_TO_LEGAL_DEPARTMENT, self::JUDICIARY, self::JUDICIARY_CUSTOMERS_ACTION, self::COLLECTION],
            'reports' => [self::REPORTS, self::JUDICIARY, self::JUDICIARY_CUSTOMERS_ACTION, self::COLLECTION],
            'permissions' => [self::PERMISSION, self::ROLE, self::ROUTE, self::ASSIGNMENT],
            'changing' => [self::STATUS, self::Document_STATUS, self::COUSINS, self::CITIZEN, self::BANCKS, self::HEAR_ABOUT_US, self::CITY, self::PAYMENT_TYPE, self::FEELINGS, self::CONTACT_TYPE, self::CONNECTION_RESPONSE, self::DOCYUMENT_TYPE, self::JUDICIARY_ACTION, self::JUDICIARY_TYPE, self::LAWYERS, self::COURT, self::MASSAGING, self::JOBS, self::EXPENSE_CATEGORIES, self::BRANCH],
            'accounting' => [self::ACC_VIEW, self::ACC_CREATE, self::ACC_COA_MANAGE, self::ACC_FISCAL_MANAGE, self::ACC_REPORTS, self::ACC_BUDGET_VIEW, self::ACC_AR_MANAGE, self::ACC_AP_MANAGE],
        ];

        if (!isset($groups[$group])) {
            return false;
        }

        return self::hasAnyPermission($groups[$group]);
    }

    /**
     * عرض عنصر قائمة جانبية مع فحص الصلاحية
     */
    public static function showItems($permission, $label, $url)
    {
        if (!Yii::$app->user->can($permission)) {
            return '';
        }
        $fullUrl = \yii\helpers\Url::to([$url]);
        return '<li><a href="' . $fullUrl . '"><div class="menu-title">' . $label . '</div></a></li>';
    }

    /**
     * الصفحة الافتراضية بعد تسجيل الدخول حسب صلاحيات المستخدم
     * ─────────────────────────────────────────────────────────
     * تُرتّب الأولويات من الأعلى (المدير) إلى الأدنى.
     * أول صلاحية يملكها المستخدم تحدد صفحته الرئيسية.
     */
    public static function getDefaultLandingUrl()
    {
        $map = [
            self::DASHBOARD            => ['/site/index'],
            self::MANAGER              => ['/site/index'],
            self::ACCOUNTING           => ['/accounting/default/index'],
            self::ACC_VIEW             => ['/accounting/default/index'],
            self::CUSTOMERS            => ['/customers/customers/create'],
            self::CUST_CREATE          => ['/customers/customers/create'],
            self::CUST_VIEW            => ['/customers/customers/index'],
            self::CONTRACTS            => ['/contracts/contracts/index'],
            self::CONT_VIEW            => ['/contracts/contracts/index'],
            self::JUDICIARY            => ['/judiciary/judiciary/index'],
            self::JUD_VIEW             => ['/judiciary/judiciary/index'],
            self::COLLECTION           => ['/collection/collection/index'],
            self::COLL_VIEW            => ['/collection/collection/index'],
            self::COLLECTION_MANAGER   => ['/collection/collection/index'],
            self::FINANCIAL_TRANSACTION => ['/financialTransaction/financial-transaction/index'],
            self::FIN_VIEW             => ['/financialTransaction/financial-transaction/index'],
            self::INCOME               => ['/income/income/index'],
            self::EXPENSES             => ['/expenses/expenses/index'],
            self::DIWAN                => ['/diwan/diwan/index'],
            self::DIWAN_VIEW           => ['/diwan/diwan/index'],
            self::INVENTORY_ITEMS      => ['/inventoryItems/inventory-items/index'],
            self::INVENTORY_INVOICES   => ['/inventoryInvoices/inventory-invoices/index'],
            self::EMPLOYEE             => ['/hr/hr-employee/index'],
            self::EMP_VIEW             => ['/hr/hr-employee/index'],
            self::REPORTS              => ['/reports/reports/index'],
            self::REP_VIEW             => ['/reports/reports/index'],
            self::JOBS                 => ['/jobs/jobs/index'],
            self::JOBS_VIEW            => ['/jobs/jobs/index'],
        ];

        foreach ($map as $permission => $url) {
            if (Yii::$app->user->can($permission)) {
                return $url;
            }
        }

        return ['/site/index'];
    }

    public static function checkMainMenuItems($items)
    {
        foreach ($items as $key => $menuItem) {
            // ── تجاوز العناوين (headers) — لا تحتاج صلاحيات ──
            if (isset($menuItem['options']['class']) && is_string($menuItem['options']['class']) && strpos($menuItem['options']['class'], 'header') !== false) {
                continue;
            }

            // ── تجاوز العناصر بدون privilege ولكن لها url (متاحة للجميع مثل لوحة التحكم) ──
            if (!isset($menuItem['privilege']) && !isset($menuItem['items']) && isset($menuItem['url'])) {
                continue;
            }

            if (isset($menuItem['privilege'])) {
                $priv = $menuItem['privilege'];
                /* إذا privilege مصفوفة → فحص OR */
                if (is_array($priv)) {
                    if (!self::hasAnyPermission($priv)) {
                        unset($items[$key]);
                        continue;
                    }
                } else {
                    if (!Yii::$app->user->can($priv)) {
                        unset($items[$key]);
                        continue;
                    }
                }
            }

            if (isset($menuItem['items'])) {
                $items[$key]['items'] = self::checkMainMenuItems($menuItem['items']);
            }

            // حذف القوائم الفرعية الفارغة فقط
            if (isset($menuItem['items']) && count($items[$key]['items'] ?? []) == 0) {
                unset($items[$key]);
            }
        }
        return $items;
    }
}
