<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use common\helper\Permissions;

/**
 * SearchController — البحث الفائق (Super Search)
 *
 * نقطة وصول موحّدة للبحث الذكي عبر كل وحدات النظام:
 *   /search/global?q=...
 *
 * تُعيد JSON بصيغة:
 *   { q, total, groups: [ { key, title, icon, items: [ { id, title, sub, url, icon } ] } ] }
 *
 * يحترم الصلاحيات (RBAC) — لا تُعاد إلا الفئات المسموح للمستخدم بمشاهدتها.
 */
class SearchController extends Controller
{
    /** الحد الأقصى لعدد النتائج لكل فئة */
    const PER_GROUP_LIMIT = 6;

    /** أقل طول للنص قبل البحث في قاعدة البيانات */
    const MIN_QUERY_LEN = 2;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['global'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'global' => ['get', 'post'],
                ],
            ],
        ];
    }

    /**
     * نقطة البحث العامة — تستقبل q وتُعيد كل النتائج مجموعة حسب الفئة
     */
    public function actionGlobal()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $q = trim((string)Yii::$app->request->get('q', ''));
        $q = preg_replace('/\s+/u', ' ', $q);

        if ($q === '' || mb_strlen($q) < self::MIN_QUERY_LEN) {
            return [
                'q'      => $q,
                'total'  => 0,
                'groups' => $this->buildPagesGroup($q, true),
            ];
        }

        $groups = [];

        $pages = $this->buildPagesGroup($q, false);
        if (!empty($pages)) {
            $groups = array_merge($groups, $pages);
        }

        $searchers = [
            'customers'   => 'searchCustomers',
            'contracts'   => 'searchContracts',
            'employees'   => 'searchEmployees',
            'inventory'   => 'searchInventoryItems',
            'suppliers'   => 'searchSuppliers',
            'lawyers'     => 'searchLawyers',
            'judiciary'   => 'searchJudiciary',
            'companies'   => 'searchCompanies',
            'invoices'    => 'searchInvoices',
        ];

        $total = 0;
        foreach ($searchers as $key => $method) {
            try {
                $g = $this->{$method}($q);
            } catch (\Throwable $e) {
                Yii::error('SuperSearch ' . $key . ': ' . $e->getMessage(), 'search');
                continue;
            }
            if ($g && !empty($g['items'])) {
                $total  += count($g['items']);
                $groups[] = $g;
            }
        }

        return [
            'q'      => $q,
            'total'  => $total + (isset($pages[0]['items']) ? count($pages[0]['items']) : 0),
            'groups' => $groups,
        ];
    }

    /* ──────────────────────────────────────────────────
     *  المجموعات (Groups)
     * ────────────────────────────────────────────────── */

    /**
     * بحث في الصفحات/القوائم الجانبية — ملاحة سريعة
     *
     * @param string $q         نص البحث
     * @param bool   $emptyMode إن كان true يُعيد قائمة الصفحات الرئيسية (للحالة الفارغة)
     * @return array
     */
    private function buildPagesGroup(string $q, bool $emptyMode = false): array
    {
        $menuFile = Yii::getAlias('@backend/views/layouts/_menu_items.php');
        if (!is_file($menuFile)) {
            return [];
        }

        $items = require $menuFile;
        $flat = [];
        $this->flattenMenu($items, $flat);

        if ($emptyMode) {
            $flat = array_slice($flat, 0, 8);
        } else {
            $needle = mb_strtolower($q, 'UTF-8');
            $flat = array_values(array_filter($flat, function ($it) use ($needle) {
                $hay = mb_strtolower($it['title'], 'UTF-8');
                return mb_strpos($hay, $needle) !== false;
            }));
            $flat = array_slice($flat, 0, self::PER_GROUP_LIMIT);
        }

        if (empty($flat)) {
            return [];
        }

        return [[
            'key'   => 'pages',
            'title' => 'الصفحات والقوائم',
            'icon'  => 'fa-compass',
            'items' => $flat,
        ]];
    }

    private function flattenMenu(array $items, array &$out, ?string $parent = null): void
    {
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            if (!empty($item['header']) || isset($item['options']['class']) && is_string($item['options']['class']) && strpos($item['options']['class'], 'header') !== false) {
                continue;
            }
            $label = $item['label'] ?? '';
            $url   = $item['url']   ?? null;
            $icon  = isset($item['icon']) ? 'fa-' . $item['icon'] : 'fa-folder';

            if (!empty($item['items']) && is_array($item['items'])) {
                $this->flattenMenu($item['items'], $out, $label);
                continue;
            }

            if ($label === '' || $url === null) continue;

            $out[] = [
                'id'    => null,
                'title' => $label,
                'sub'   => $parent ? $parent : 'انتقال سريع',
                'url'   => is_array($url) ? Url::to($url) : (string)$url,
                'icon'  => $icon,
            ];
        }
    }

    /* ──────────────────────────────────────────────────
     *  Searchers (لكل فئة)
     * ────────────────────────────────────────────────── */

    private function searchCustomers(string $q): ?array
    {
        if (!Permissions::hasAnyPermission([Permissions::CUSTOMERS, Permissions::CUST_VIEW])) {
            return null;
        }

        $like  = '%' . $q . '%';
        $isNum = ctype_digit($q);

        /*
         * نستعمل placeholders مميّزة (:l0, :l1, ...) لأن PDO مع MariaDB
         * في وضع ATTR_EMULATE_PREPARES=false يرفض إعادة استخدام نفس
         * الـ named placeholder ويرمي SQLSTATE[HY093].
         */
        $sql = "SELECT cu.id, cu.name, cu.primary_phone_number, cu.id_number,
                       GROUP_CONCAT(DISTINCT ct.id ORDER BY ct.id DESC SEPARATOR ',') AS contract_ids,
                       GROUP_CONCAT(DISTINCT ct.status ORDER BY ct.id DESC SEPARATOR '|') AS contract_statuses
                FROM os_customers cu
                LEFT JOIN os_contracts_customers cc ON cc.customer_id = cu.id
                LEFT JOIN os_contracts ct ON ct.id = cc.contract_id AND (ct.is_deleted IS NULL OR ct.is_deleted = 0)
                WHERE (cu.is_deleted IS NULL OR cu.is_deleted = 0)
                  AND (cu.name LIKE :l0
                       OR cu.primary_phone_number LIKE :l1
                       OR cu.id_number LIKE :l2
                       OR cu.email LIKE :l3
                       " . ($isNum ? "OR cu.id = :idnum" : "") . ")
                GROUP BY cu.id
                ORDER BY cu.id DESC
                LIMIT :lim";

        $cmd = Yii::$app->db->createCommand($sql)
            ->bindValue(':l0', $like)
            ->bindValue(':l1', $like)
            ->bindValue(':l2', $like)
            ->bindValue(':l3', $like)
            ->bindValue(':lim', self::PER_GROUP_LIMIT, \PDO::PARAM_INT);
        if ($isNum) {
            $cmd->bindValue(':idnum', (int)$q, \PDO::PARAM_INT);
        }

        $rows = $cmd->queryAll();
        if (empty($rows)) return null;

        $canViewContracts = Permissions::hasAnyPermission([Permissions::CONTRACTS, Permissions::CONT_VIEW]);

        $items = [];
        foreach ($rows as $r) {
            $contracts = [];
            if ($canViewContracts && !empty($r['contract_ids'])) {
                $ids      = array_filter(array_map('intval', explode(',', $r['contract_ids'])));
                $statuses = $r['contract_statuses'] ? explode('|', $r['contract_statuses']) : [];
                /* اعرض فقط آخر 4 عقود لتفادي الازدحام البصري */
                $ids = array_slice($ids, 0, 4);
                foreach ($ids as $i => $cid) {
                    $contracts[] = [
                        'id'     => $cid,
                        'url'    => Url::to(['/contracts/contracts/view', 'id' => $cid]),
                        'status' => $this->mapContractStatus($statuses[$i] ?? null),
                    ];
                }
            }

            $items[] = [
                'id'        => (int)$r['id'],
                'title'     => $r['name'] ?: ('عميل #' . $r['id']),
                'sub'       => trim(($r['primary_phone_number'] ?? '') . ($r['id_number'] ? ' • ' . $r['id_number'] : '')) ?: 'عميل',
                'url'       => Url::to(['/customers/customers/view', 'id' => (int)$r['id']]),
                'icon'      => 'fa-user',
                'contracts' => $contracts,
            ];
        }

        return [
            'key'   => 'customers',
            'title' => 'العملاء',
            'icon'  => 'fa-users',
            'items' => $items,
        ];
    }

    /**
     * يُعيد لوناً مناسباً لحالة العقد لعرضه في الشريحة
     */
    private function mapContractStatus(?string $status): string
    {
        $map = [
            'active'           => 'success',
            'finished'         => 'secondary',
            'canceled'         => 'danger',
            'legal_department' => 'warning',
            'judiciary'        => 'danger',
            'settlement'       => 'info',
            'pending'          => 'warning',
            'refused'          => 'danger',
        ];
        return $map[(string)$status] ?? 'secondary';
    }

    private function searchContracts(string $q): ?array
    {
        if (!Permissions::hasAnyPermission([Permissions::CONTRACTS, Permissions::CONT_VIEW])) {
            return null;
        }

        $like  = '%' . $q . '%';
        $isNum = ctype_digit($q);

        /*
         * MariaDB لا يدعم ANY_VALUE() ولا يحتاجها — sql_mode الافتراضي
         * عنده لا يفعّل ONLY_FULL_GROUP_BY. لذا نختار الأعمدة مباشرةً.
         * كذلك نستعمل placeholders مميّزة (:l0..:l2) لتجنّب خطأ HY093.
         */
        $sql = "SELECT c.id,
                       c.status,
                       c.total_value,
                       c.Date_of_sale,
                       MAX(cu.name) AS customer_name
                FROM os_contracts c
                LEFT JOIN os_contracts_customers cc ON cc.contract_id = c.id
                LEFT JOIN os_customers cu ON cu.id = cc.customer_id
                WHERE (c.is_deleted IS NULL OR c.is_deleted = 0)
                  AND (cu.name LIKE :l0
                       OR cu.primary_phone_number LIKE :l1
                       OR cu.id_number LIKE :l2
                       " . ($isNum ? "OR c.id = :idnum" : "") . ")
                GROUP BY c.id
                ORDER BY c.id DESC
                LIMIT :lim";

        $cmd = Yii::$app->db->createCommand($sql)
            ->bindValue(':l0', $like)
            ->bindValue(':l1', $like)
            ->bindValue(':l2', $like)
            ->bindValue(':lim', self::PER_GROUP_LIMIT, \PDO::PARAM_INT);
        if ($isNum) {
            $cmd->bindValue(':idnum', (int)$q, \PDO::PARAM_INT);
        }

        $rows = $cmd->queryAll();
        if (empty($rows)) return null;

        $items = [];
        foreach ($rows as $r) {
            $val = $r['total_value'] !== null ? number_format((float)$r['total_value'], 2) . ' د.أ' : '';
            $sub = trim(($r['customer_name'] ?? 'بدون اسم') . ' • ' . ($r['Date_of_sale'] ?? '') . ($val ? ' • ' . $val : ''), ' •');
            $items[] = [
                'id'    => (int)$r['id'],
                'title' => 'عقد #' . $r['id'],
                'sub'   => $sub,
                'url'   => Url::to(['/contracts/contracts/view', 'id' => (int)$r['id']]),
                'icon'  => 'fa-file-contract',
            ];
        }

        return [
            'key'   => 'contracts',
            'title' => 'العقود',
            'icon'  => 'fa-file-contract',
            'items' => $items,
        ];
    }

    private function searchEmployees(string $q): ?array
    {
        if (!Permissions::hasAnyPermission([Permissions::EMPLOYEE])) {
            return null;
        }

        $like = '%' . $q . '%';
        $sql = "SELECT id, name, username, email, mobile
                FROM {{%user}}
                WHERE name LIKE :l0 OR username LIKE :l1 OR email LIKE :l2 OR mobile LIKE :l3
                ORDER BY id DESC
                LIMIT :lim";

        $rows = Yii::$app->db->createCommand($sql)
            ->bindValue(':l0', $like)
            ->bindValue(':l1', $like)
            ->bindValue(':l2', $like)
            ->bindValue(':l3', $like)
            ->bindValue(':lim', self::PER_GROUP_LIMIT, \PDO::PARAM_INT)
            ->queryAll();

        if (empty($rows)) return null;

        $items = [];
        foreach ($rows as $r) {
            $title = $r['name'] ?: $r['username'] ?: ('موظف #' . $r['id']);
            $sub   = trim(($r['username'] ?? '') . ($r['mobile'] ? ' • ' . $r['mobile'] : '') . ($r['email'] ? ' • ' . $r['email'] : ''), ' •');
            $items[] = [
                'id'    => (int)$r['id'],
                'title' => $title,
                'sub'   => $sub ?: 'موظف',
                'url'   => Url::to(['/employee/update', 'id' => (int)$r['id']]),
                'icon'  => 'fa-id-badge',
            ];
        }

        return [
            'key'   => 'employees',
            'title' => 'الموظفون',
            'icon'  => 'fa-id-badge',
            'items' => $items,
        ];
    }

    private function searchInventoryItems(string $q): ?array
    {
        if (!Permissions::hasAnyPermission([Permissions::INVENTORY_ITEMS])) {
            return null;
        }

        $like = '%' . $q . '%';
        $sql = "SELECT id, item_name, item_barcode, category
                FROM os_inventory_items
                WHERE (is_deleted IS NULL OR is_deleted = 0)
                  AND (item_name LIKE :l0 OR item_barcode LIKE :l1)
                ORDER BY id DESC
                LIMIT :lim";

        $rows = Yii::$app->db->createCommand($sql)
            ->bindValue(':l0', $like)
            ->bindValue(':l1', $like)
            ->bindValue(':lim', self::PER_GROUP_LIMIT, \PDO::PARAM_INT)
            ->queryAll();

        if (empty($rows)) return null;

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id'    => (int)$r['id'],
                'title' => $r['item_name'] ?: ('صنف #' . $r['id']),
                'sub'   => trim(($r['item_barcode'] ? 'باركود: ' . $r['item_barcode'] : '') . ($r['category'] ? ' • ' . $r['category'] : ''), ' •') ?: 'صنف مخزني',
                'url'   => Url::to(['/inventoryItems/inventory-items/view', 'id' => (int)$r['id']]),
                'icon'  => 'fa-box',
            ];
        }

        return [
            'key'   => 'inventory',
            'title' => 'المخزون',
            'icon'  => 'fa-cubes',
            'items' => $items,
        ];
    }

    private function searchSuppliers(string $q): ?array
    {
        if (!Permissions::hasAnyPermission([Permissions::INVENTORY_SUPPLIERS])) {
            return null;
        }

        $like = '%' . $q . '%';
        $sql = "SELECT id, name, phone_number, adress
                FROM os_inventory_suppliers
                WHERE (is_deleted IS NULL OR is_deleted = 0)
                  AND (name LIKE :l0 OR phone_number LIKE :l1 OR adress LIKE :l2)
                ORDER BY id DESC
                LIMIT :lim";

        $rows = Yii::$app->db->createCommand($sql)
            ->bindValue(':l0', $like)
            ->bindValue(':l1', $like)
            ->bindValue(':l2', $like)
            ->bindValue(':lim', self::PER_GROUP_LIMIT, \PDO::PARAM_INT)
            ->queryAll();

        if (empty($rows)) return null;

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id'    => (int)$r['id'],
                'title' => $r['name'] ?: ('مورد #' . $r['id']),
                'sub'   => trim(($r['phone_number'] ?? '') . ($r['adress'] ? ' • ' . $r['adress'] : ''), ' •') ?: 'مورد',
                'url'   => Url::to(['/inventorySuppliers/inventory-suppliers/view', 'id' => (int)$r['id']]),
                'icon'  => 'fa-truck',
            ];
        }

        return [
            'key'   => 'suppliers',
            'title' => 'الموردون',
            'icon'  => 'fa-truck',
            'items' => $items,
        ];
    }

    private function searchLawyers(string $q): ?array
    {
        if (!Permissions::hasAnyPermission([Permissions::LAWYERS, Permissions::JUDICIARY, Permissions::JUD_VIEW])) {
            return null;
        }

        $like = '%' . $q . '%';
        $sql = "SELECT id, name
                FROM os_lawyers
                WHERE (is_deleted IS NULL OR is_deleted = 0)
                  AND name LIKE :like
                ORDER BY id DESC
                LIMIT :lim";

        $rows = Yii::$app->db->createCommand($sql)
            ->bindValue(':like', $like)
            ->bindValue(':lim', self::PER_GROUP_LIMIT, \PDO::PARAM_INT)
            ->queryAll();

        if (empty($rows)) return null;

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id'    => (int)$r['id'],
                'title' => $r['name'] ?: ('وكيل #' . $r['id']),
                'sub'   => 'محامي / وكيل',
                'url'   => Url::to(['/lawyers/lawyers/view', 'id' => (int)$r['id']]),
                'icon'  => 'fa-user-tie',
            ];
        }

        return [
            'key'   => 'lawyers',
            'title' => 'المحامون والوكلاء',
            'icon'  => 'fa-user-tie',
            'items' => $items,
        ];
    }

    private function searchJudiciary(string $q): ?array
    {
        if (!Permissions::hasAnyPermission([Permissions::JUDICIARY, Permissions::JUD_VIEW])) {
            return null;
        }

        $like  = '%' . $q . '%';
        $isNum = ctype_digit($q);

        /*
         * دعم صيغ متعددة لرقم القضية + السنة:
         *   500-2023   500\2023   500/2023   500 2023   500_2023   500.2023   500،2023
         * النمط: رقم → فاصل → سنة (4 خانات)
         */
        $caseNum  = null;
        $caseYear = null;
        if (preg_match('/^\s*(\d{1,10})\s*[-\\\\\/_.\s،,]+\s*(\d{2,4})\s*$/u', $q, $m)) {
            $caseNum  = $m[1];
            $caseYear = $m[2];
        }

        /*
         * ملاحظات تنفيذية:
         *   1) MariaDB لا يملك ANY_VALUE() ولا يحتاجها — sql_mode الافتراضي
         *      عنده لا يفعّل ONLY_FULL_GROUP_BY. لذا نعتمد على MAX/MIN
         *      لاستخراج قيم تمثيلية بسيطة بدون أعمدة aggregator.
         *   2) PDO مع MariaDB في وضع ATTR_EMULATE_PREPARES=false يرفض
         *      تكرار نفس الـ named placeholder، لذلك نستعمل أسماء مميّزة
         *      (:l0, :l1, :cnumA, :cnumB ...).
         */
        $params = [
            ':l0'  => $like,
            ':l1'  => $like,
            ':lim' => self::PER_GROUP_LIMIT,
        ];

        $conditions = [
            'cu.name LIKE :l0',
            'j.judiciary_number LIKE :l1',
        ];

        if ($caseNum !== null && $caseYear !== null) {
            /* تطابق دقيق على رقم + سنة (الأولوية الأعلى) */
            $conditions[] = '(j.judiciary_number = :cnumA AND j.year LIKE :cyearA)';
            $params[':cnumA']  = $caseNum;
            $params[':cyearA'] = '%' . $caseYear;
        }
        if ($isNum) {
            $conditions[] = 'j.id = :idnum';
            $params[':idnum'] = (int)$q;
        }

        $whereOr = implode(' OR ', $conditions);

        /*
         * في الـ priority CASE نحتاج placeholders إضافية لأن نفس القيم
         * تُستخدم في SELECT (لا يجوز إعادة استخدام :cnumA/:cyearA/:l1 هنا).
         */
        $priorityCase = "CASE ";
        if ($caseNum !== null && $caseYear !== null) {
            $priorityCase .= "WHEN j.judiciary_number = :cnumB AND j.year LIKE :cyearB THEN 0 ";
            $params[':cnumB']  = $caseNum;
            $params[':cyearB'] = '%' . $caseYear;
        }
        $priorityCase .= "WHEN j.judiciary_number LIKE :l2 THEN 1 ELSE 2 END";
        $params[':l2'] = $like;

        $sql = "SELECT j.id,
                       MAX(j.judiciary_number) AS judiciary_number,
                       MAX(j.year)             AS year,
                       MAX(j.case_status)      AS case_status,
                       MAX(cu.name)            AS customer_name,
                       MIN({$priorityCase})    AS sort_priority
                FROM os_judiciary j
                LEFT JOIN os_contracts c ON c.id = j.contract_id
                LEFT JOIN os_contracts_customers cc ON cc.contract_id = c.id
                LEFT JOIN os_customers cu ON cu.id = cc.customer_id
                WHERE (j.is_deleted IS NULL OR j.is_deleted = 0)
                  AND ({$whereOr})
                GROUP BY j.id
                ORDER BY sort_priority ASC, j.id DESC
                LIMIT :lim";

        $cmd = Yii::$app->db->createCommand($sql);
        foreach ($params as $name => $val) {
            if ($name === ':lim' || $name === ':idnum') {
                $cmd->bindValue($name, $val, \PDO::PARAM_INT);
            } else {
                $cmd->bindValue($name, $val);
            }
        }

        $rows = $cmd->queryAll();
        if (empty($rows)) return null;

        $items = [];
        foreach ($rows as $r) {
            $title = 'قضية #' . ($r['judiciary_number'] ?: $r['id']) . ($r['year'] ? '/' . $r['year'] : '');
            $sub   = trim(($r['customer_name'] ?? '') . ($r['case_status'] ? ' • ' . $r['case_status'] : ''), ' •') ?: 'قضية';
            $items[] = [
                'id'    => (int)$r['id'],
                'title' => $title,
                'sub'   => $sub,
                'url'   => Url::to(['/judiciary/judiciary/view', 'id' => (int)$r['id']]),
                'icon'  => 'fa-gavel',
            ];
        }

        return [
            'key'   => 'judiciary',
            'title' => 'القضايا القضائية',
            'icon'  => 'fa-gavel',
            'items' => $items,
        ];
    }

    private function searchCompanies(string $q): ?array
    {
        if (!Permissions::hasAnyPermission([Permissions::COMPAINES])) {
            return null;
        }

        $like = '%' . $q . '%';
        $sql = "SELECT id, name, phone_number
                FROM os_companies
                WHERE (is_deleted IS NULL OR is_deleted = 0)
                  AND (name LIKE :l0 OR phone_number LIKE :l1)
                ORDER BY id DESC
                LIMIT :lim";

        $rows = Yii::$app->db->createCommand($sql)
            ->bindValue(':l0', $like)
            ->bindValue(':l1', $like)
            ->bindValue(':lim', self::PER_GROUP_LIMIT, \PDO::PARAM_INT)
            ->queryAll();

        if (empty($rows)) return null;

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id'    => (int)$r['id'],
                'title' => $r['name'] ?: ('شركة #' . $r['id']),
                'sub'   => $r['phone_number'] ?: 'مستثمر/شركة',
                'url'   => Url::to(['/companies/companies/view', 'id' => (int)$r['id']]),
                'icon'  => 'fa-building',
            ];
        }

        return [
            'key'   => 'companies',
            'title' => 'الشركات والمستثمرون',
            'icon'  => 'fa-building',
            'items' => $items,
        ];
    }

    private function searchInvoices(string $q): ?array
    {
        if (!Permissions::hasAnyPermission([Permissions::INVENTORY_INVOICES])) {
            return null;
        }

        $like  = '%' . $q . '%';
        $isNum = ctype_digit($q);

        $sql = "SELECT i.id, i.invoice_number, i.total_amount, i.created_at, s.name AS supplier_name
                FROM os_inventory_invoices i
                LEFT JOIN os_inventory_suppliers s ON s.id = i.suppliers_id
                WHERE (i.is_deleted IS NULL OR i.is_deleted = 0)
                  AND (i.invoice_number LIKE :l0
                       OR s.name LIKE :l1
                       " . ($isNum ? "OR i.id = :idnum" : "") . ")
                ORDER BY i.id DESC
                LIMIT :lim";

        try {
            $cmd = Yii::$app->db->createCommand($sql)
                ->bindValue(':l0', $like)
                ->bindValue(':l1', $like)
                ->bindValue(':lim', self::PER_GROUP_LIMIT, \PDO::PARAM_INT);
            if ($isNum) {
                $cmd->bindValue(':idnum', (int)$q, \PDO::PARAM_INT);
            }
            $rows = $cmd->queryAll();
        } catch (\Throwable $e) {
            return null;
        }

        if (empty($rows)) return null;

        $items = [];
        foreach ($rows as $r) {
            $val = $r['total_amount'] !== null ? number_format((float)$r['total_amount'], 2) . ' د.أ' : '';
            $invDate = !empty($r['created_at']) ? date('Y-m-d', (int)$r['created_at']) : '';
            $sub = trim(($r['supplier_name'] ?? '') . ($invDate ? ' • ' . $invDate : '') . ($val ? ' • ' . $val : ''), ' •');
            $items[] = [
                'id'    => (int)$r['id'],
                'title' => 'فاتورة #' . ($r['invoice_number'] ?: $r['id']),
                'sub'   => $sub ?: 'فاتورة مخزن',
                'url'   => Url::to(['/inventoryInvoices/inventory-invoices/view', 'id' => (int)$r['id']]),
                'icon'  => 'fa-file-invoice',
            ];
        }

        return [
            'key'   => 'invoices',
            'title' => 'فواتير المخزن',
            'icon'  => 'fa-file-invoice',
            'items' => $items,
        ];
    }
}
