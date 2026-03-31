<?php

namespace backend\modules\contracts\controllers;

use Yii;
use yii\helpers\Html;
use yii\web\Response;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

use common\components\notificationComponent;
use backend\modules\customers\models\Customers;
use backend\modules\contracts\models\Contracts;
use backend\modules\contracts\models\ContractsSearch;
use backend\modules\customers\models\ContractsCustomers;
use backend\modules\inventoryItems\models\ContractInventoryItem;
use backend\modules\inventoryItems\models\InventoryItems;
use backend\modules\inventoryItems\models\InventorySerialNumber;
use backend\modules\inventoryItemQuantities\models\InventoryItemQuantities;
use backend\modules\inventoryStockLocations\models\InventoryStockLocations;
use backend\modules\contractDocumentFile\models\ContractDocumentFile;
use backend\modules\notification\models\Notification;
use backend\modules\followUp\helper\ContractCalculations;
use backend\modules\inventoryItems\models\StockMovement;
use backend\modules\companies\models\Companies;
use backend\modules\contracts\models\PromissoryNote;
use common\helper\Permissions;
use backend\helpers\ExportTrait;

class ContractsController extends Controller
{
    use ExportTrait;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['login', 'error'], 'allow' => true],
                    ['actions' => ['logout'], 'allow' => true, 'roles' => ['@']],
                    [
                        'actions' => ['index', 'view', 'index-legal-department', 'legal-department',
                            'print-preview', 'print-first-page', 'print-second-page',
                            'export-excel', 'export-pdf', 'export-legal-excel', 'export-legal-pdf',
                            'search-suggest'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::CONT_VIEW);
                        },
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::CONT_CREATE);
                        },
                    ],
                    [
                        'actions' => ['update', 'finish', 'finish-contract', 'cancel', 'cancel-contract',
                            'return-to-continue', 'to-legal-department', 'remove-from-legal-department',
                            'convert-to-manager',
                            'is-read', 'chang-follow-up', 'is-connect', 'is-not-connect',
                            'lookup-serial',
                            'add-adjustment', 'delete-adjustment', 'adjustments', 'refresh-status'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::CONT_UPDATE);
                        },
                    ],
                    [
                        'actions' => ['delete', 'bulkdelete'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::CONT_DELETE);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['logout' => ['post'], 'delete' => ['post']],
            ],
        ];
    }

    /* ══════════════════════════════════════════════════════════════
     *  بحث مقترحات — Search Suggest (AJAX)
     * ══════════════════════════════════════════════════════════════ */

    public function actionSearchSuggest($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return ['results' => []];
        }

        $db = Yii::$app->db;
        $qNorm = str_replace(['أ', 'إ', 'آ'], 'ا', str_replace('ة', 'ه', str_replace('ى', 'ي', $q)));
        $words = preg_split('/\s+/u', $qNorm, -1, PREG_SPLIT_NO_EMPTY);

        $nameNorm = "REPLACE(REPLACE(REPLACE(REPLACE(cu.name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'ى', 'ي')";
        $nameNormNoSpace = "REPLACE($nameNorm, ' ', '')";
        $nameWhere = [];
        $nameParams = [];
        foreach ($words as $i => $w) {
            $p1 = ":w{$i}a";
            $p2 = ":w{$i}b";
            $nameWhere[] = "($nameNorm LIKE $p1 OR $nameNormNoSpace LIKE $p2)";
            $likeVal = '%' . $w . '%';
            $nameParams[$p1] = $likeVal;
            $nameParams[$p2] = $likeVal;
        }
        $nameClause = implode(' AND ', $nameWhere);

        $rows = $db->createCommand(
            "SELECT c.id, c.status, c.total_value,
                    GROUP_CONCAT(DISTINCT cu.name SEPARATOR '، ') AS customer_name,
                    MIN(cu.primary_phone_number) AS phone
             FROM {{%contracts}} c
             LEFT JOIN {{%contracts_customers}} cc ON cc.contract_id = c.id
             LEFT JOIN {{%customers}} cu ON cu.id = cc.customer_id
             WHERE (c.is_deleted = 0 OR c.is_deleted IS NULL)
               AND (
                   c.id = :qIntContract
                   OR ($nameClause)
                   OR cu.id_number LIKE :qLikeId
                   OR cu.primary_phone_number LIKE :qLikePhone
                   OR cu.id = :qIntCustomer
               )
             GROUP BY c.id
             ORDER BY c.id DESC
             LIMIT 10"
        , array_merge([
            ':qIntContract'  => (int)$q,
            ':qIntCustomer'  => (int)$q,
            ':qLikeId'       => '%' . $q . '%',
            ':qLikePhone'    => '%' . $q . '%',
        ], $nameParams))->queryAll();

        $results = [];
        foreach ($rows as $r) {
            $results[] = [
                'id'    => $r['id'],
                'title' => $r['customer_name'] ?: ('عقد #' . $r['id']),
                'sub'   => 'عقد #' . $r['id'] . ($r['phone'] ? ' — ' . $r['phone'] : ''),
                'icon'  => 'fa-file-text-o',
            ];
        }
        return ['results' => $results];
    }

    /* ══════════════════════════════════════════════════════════════
     *  قوائم — Index
     * ══════════════════════════════════════════════════════════════ */

    public function actionIndex()
    {
        $searchModel = new ContractsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataCount = $searchModel->searchcounter(Yii::$app->request->queryParams);

        $dataProvider->query->with(['seller', 'followedBy']);

        $models = $dataProvider->getModels();
        $contractIds = ArrayHelper::getColumn($models, 'id');

        $batchData = ContractCalculations::batchPreload($contractIds);

        $db = Yii::$app->db;

        $namesMap = [];
        if (!empty($contractIds)) {
            $idList = implode(',', array_map('intval', $contractIds));
            $namesMap = ArrayHelper::index(
                $db->createCommand("SELECT contract_id, client_names, all_party_names, client_phone FROM {{%vw_contract_customers_names}} WHERE contract_id IN ($idList)")->queryAll(),
                'contract_id'
            );
        }

        $statusCounts = ArrayHelper::map(
            $db->createCommand("SELECT status, COUNT(*) AS cnt FROM os_contracts WHERE is_deleted=0 OR is_deleted IS NULL GROUP BY status")->queryAll(),
            'status', 'cnt'
        );

        $judPaidCount = (int)$db->createCommand(
            "SELECT COUNT(*) FROM {{%vw_contract_balance}} WHERE status = 'judiciary' AND remaining_balance <= 0"
        )->queryScalar();

        $judTotalCount = (int)($statusCounts['judiciary'] ?? 0);
        $statusCounts['judiciary_active'] = $judTotalCount - $judPaidCount;
        $statusCounts['judiciary_paid'] = $judPaidCount;

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'dataCount'    => $dataCount,
            'batchData'    => $batchData,
            'statusCounts' => $statusCounts,
            'namesMap'     => $namesMap,
        ]);
    }

    public function actionIndexLegalDepartment()
    {
        $searchModel = new ContractsSearch();
        $dataProvider = $searchModel->searchLegalDepartment(Yii::$app->request->queryParams);
        $dataCount = $searchModel->searchLegalDepartmentCount(Yii::$app->request->queryParams);

        if (Yii::$app->request->get('show_all')) {
            $dataProvider->setPagination(false);
        }

        if (Yii::$app->request->get('export') === 'csv') {
            return $this->exportLegalCsv($dataProvider);
        }

        $models = $dataProvider->getModels();
        $ids = ArrayHelper::getColumn($models, 'id');
        $balanceMap = [];
        $namesMap = [];
        if (!empty($ids)) {
            $idList = implode(',', array_map('intval', $ids));
            $balanceMap = ArrayHelper::index(
                Yii::$app->db->createCommand(
                    "SELECT contract_id, total_value, total_paid, total_expenses, total_lawyer_cost, remaining_balance
                     FROM {{%vw_contract_balance}} WHERE contract_id IN ($idList)"
                )->queryAll(),
                'contract_id'
            );
            $namesMap = ArrayHelper::index(
                Yii::$app->db->createCommand(
                    "SELECT contract_id, client_names, guarantor_names, all_party_names FROM {{%vw_contract_customers_names}} WHERE contract_id IN ($idList)"
                )->queryAll(),
                'contract_id'
            );
        }

        $referrer = Yii::$app->request->referrer ?: '';
        $isIframe = strpos($referrer, '/judiciary') !== false || Yii::$app->request->get('_iframe');
        $renderMethod = $isIframe ? 'renderAjax' : 'render';

        return $this->$renderMethod('index-legal-department', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'dataCount'    => $dataCount,
            'balanceMap'   => $balanceMap,
            'namesMap'     => $namesMap,
        ]);
    }

    private function exportLegalCsv($dataProvider)
    {
        $dataProvider->setPagination(false);
        $dataProvider->prepare(true);
        $models = $dataProvider->getModels();

        $jobsRows = \backend\modules\jobs\models\Jobs::find()->select(['id', 'name', 'job_type'])->asArray()->all();
        $jobsMap = ArrayHelper::map($jobsRows, 'id', 'name');
        $jobToTypeMap = ArrayHelper::map($jobsRows, 'id', 'job_type');
        $jobTypesMap = ArrayHelper::map(
            \backend\modules\jobs\models\JobsType::find()->select(['id', 'name'])->asArray()->all(), 'id', 'name'
        );

        $ids = ArrayHelper::getColumn($models, 'id');
        $balanceMap = [];
        if (!empty($ids)) {
            $idList = implode(',', array_map('intval', $ids));
            $balanceMap = ArrayHelper::index(
                Yii::$app->db->createCommand(
                    "SELECT contract_id, total_value, total_paid, total_expenses, total_lawyer_cost, remaining_balance
                     FROM {{%vw_contract_balance}} WHERE contract_id IN ($idList)"
                )->queryAll(),
                'contract_id'
            );
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['#', 'الأطراف', 'الإجمالي', 'المتبقي', 'الوظيفة', 'نوع الوظيفة'], ',', '"', '\\');

        foreach ($models as $m) {
            $parties = $m->customersAndGuarantor;
            $partyLines = [];
            $firstCustomer = $parties[0] ?? null;
            foreach ($parties as $p) {
                $line = $p->name;
                if ($p->id_number) $line .= ' (' . $p->id_number . ')';
                $partyLines[] = $line;
            }
            $partiesText = implode(' | ', $partyLines) ?: '—';

            $b = $balanceMap[$m->id] ?? null;
            $total = $b ? (float)$b['total_value'] + (float)$b['total_expenses'] + (float)$b['total_lawyer_cost'] : (float)$m->total_value;
            $remaining = $b ? (float)$b['remaining_balance'] : 0;

            $jobId = ($firstCustomer && $firstCustomer->job_title) ? $firstCustomer->job_title : null;
            $jobName = $jobId ? ($jobsMap[$jobId] ?? '') : '';
            $jobTypeId = $jobId ? ($jobToTypeMap[$jobId] ?? null) : null;
            $jobTypeName = $jobTypeId ? ($jobTypesMap[$jobTypeId] ?? '') : '';

            fputcsv($handle, [
                $m->id, $partiesText, $total, round($remaining), $jobName, $jobTypeName,
            ], ',', '"', '\\');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $filename = 'الدائرة_القانونية_' . date('Y-m-d') . '.csv';
        return Yii::$app->response->sendContentAsFile($content, $filename, [
            'mimeType' => 'text/csv',
        ]);
    }

    public function actionLegalDepartment()
    {
        return $this->actionIndexLegalDepartment();
    }

    /* ══════════════════════════════════════════════════════════════
     *  تصدير — Export
     * ══════════════════════════════════════════════════════════════ */

    public function actionExportExcel()
    {
        return $this->exportContractsIndex('excel');
    }

    public function actionExportPdf()
    {
        return $this->exportContractsIndex('pdf');
    }

    public function actionExportLegalExcel()
    {
        return $this->exportLegalDepartment('excel');
    }

    public function actionExportLegalPdf()
    {
        return $this->exportLegalDepartment('pdf');
    }

    private function exportContractsIndex(string $format)
    {
        $searchModel = new ContractsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $query = $dataProvider->query;
        $query->with = [];
        $dataProvider->pagination = false;
        $rows = $query->select(['os_contracts.id'])->asArray()->all();
        $contractIds = array_column($rows, 'id');

        $statusLabels = [
            'active' => 'نشط', 'pending' => 'معلّق', 'judiciary' => 'قضاء',
            'legal_department' => 'قانوني', 'settlement' => 'تسوية', 'finished' => 'منتهي',
            'canceled' => 'ملغي', 'refused' => 'مرفوض',
        ];

        $exportRows = [];
        if (!empty($contractIds)) {
            set_time_limit(0);
            $db = Yii::$app->db;
            $idList = implode(',', array_map('intval', $contractIds));

            $overviewRows = $db->createCommand(
                "SELECT v.id, v.total_value, v.Date_of_sale, v.status,
                        v.seller_name, v.client_names,
                        v.total_paid, v.total_expenses, v.total_lawyer_cost, v.remaining_balance,
                        v.first_installment_value,
                        fu.username AS follower_name
                 FROM {{%vw_contracts_overview}} v
                 LEFT JOIN {{%user}} fu ON fu.id = v.followed_by
                 WHERE v.id IN ($idList)
                 ORDER BY v.id DESC"
            )->queryAll();

            $adjRows = $db->createCommand(
                "SELECT contract_id, COALESCE(SUM(amount),0) AS total_adj
                 FROM {{%contract_adjustments}}
                 WHERE contract_id IN ($idList) AND is_deleted = 0
                 GROUP BY contract_id"
            )->queryAll();
            $adjMap = ArrayHelper::map($adjRows, 'contract_id', 'total_adj');

            $judIds = $db->createCommand(
                "SELECT DISTINCT contract_id
                 FROM {{%judiciary}}
                 WHERE contract_id IN ($idList) AND (is_deleted = 0 OR is_deleted IS NULL)"
            )->queryColumn();
            $judMap = array_flip($judIds);

            $settRows = $db->createCommand(
                "SELECT ls.*
                 FROM {{%loan_scheduling}} ls
                 INNER JOIN (
                     SELECT contract_id, MAX(id) AS max_id
                     FROM {{%loan_scheduling}}
                     WHERE contract_id IN ($idList)
                     GROUP BY contract_id
                 ) latest ON ls.id = latest.max_id"
            )->queryAll();
            $settMap = ArrayHelper::index($settRows, 'contract_id');

            $cDataRows = $db->createCommand(
                "SELECT id, first_installment_date, monthly_installment_value
                 FROM {{%contracts}}
                 WHERE id IN ($idList)"
            )->queryAll();
            $cDataMap = ArrayHelper::index($cDataRows, 'id');

            $today = date('Y-m-d');

            foreach ($overviewRows as $r) {
                $cid = (int)$r['id'];
                $totalDebt = (float)$r['total_value'] + (float)$r['total_expenses'] + (float)$r['total_lawyer_cost'];
                $totalAdj  = (float)($adjMap[$cid] ?? 0);
                $totalPaid = (float)$r['total_paid'];
                $hasJud    = isset($judMap[$cid]);
                $sett      = $settMap[$cid] ?? null;
                $netTotal  = $totalDebt - $totalAdj;

                $deserved = 0;
                if ($hasJud && !$sett) {
                    $deserved = max(0, round($netTotal - $totalPaid, 2));
                } else {
                    $firstDate = $sett
                        ? ($sett['first_installment_date'] ?? null)
                        : ($cDataMap[$cid]['first_installment_date'] ?? null);

                    if (!empty($firstDate) && $today >= $firstDate) {
                        $d1 = new \DateTime($firstDate);
                        $d2 = new \DateTime($today);
                        $interval = $d2->diff($d1);
                        $months = $interval->y * 12 + $interval->m;

                        if ($sett) {
                            $monthly = (float)($sett['monthly_installment'] ?? 0);
                        } else {
                            $monthly = (float)($cDataMap[$cid]['monthly_installment_value'] ?? 0);
                        }

                        $shouldPaid = min(($months + 1) * $monthly, $netTotal);
                        $deserved = max(0, round($shouldPaid - $totalPaid, 2));
                    }
                }

                $exportRows[] = [
                    'id'            => $r['id'],
                    'seller'        => $r['seller_name'] ?: '—',
                    'customer'      => $r['client_names'] ?: '—',
                    'deserved'      => $deserved,
                    'date'          => $r['Date_of_sale'] ?: '—',
                    'first_payment' => $r['first_installment_value'] ? (float)$r['first_installment_value'] : 0,
                    'total'         => $totalDebt,
                    'status'        => $statusLabels[$r['status']] ?? $r['status'],
                    'remaining'     => (float)$r['remaining_balance'],
                    'follower'      => $r['follower_name'] ?: '—',
                ];
            }
        }

        return $this->exportArrayData($exportRows, [
            'title'    => 'العقود',
            'filename' => 'contracts',
            'headers'  => ['#', 'البائع', 'العميل', 'المستحق', 'التاريخ', 'الدفعة الأولى', 'الإجمالي', 'الحالة', 'المتبقي', 'المتابع'],
            'keys'     => ['id', 'seller', 'customer', 'deserved', 'date', 'first_payment', 'total', 'status', 'remaining', 'follower'],
            'widths'   => [8, 16, 22, 14, 14, 14, 14, 12, 14, 14],
        ], $format);
    }

    private function exportLegalDepartment(string $format)
    {
        $searchModel = new ContractsSearch();
        $dataProvider = $searchModel->searchLegalDepartment(Yii::$app->request->queryParams);
        $dataProvider->pagination = false;
        $models = $dataProvider->getModels();

        $jobsRows = \backend\modules\jobs\models\Jobs::find()->select(['id', 'name', 'job_type'])->asArray()->all();
        $jobsMap = ArrayHelper::map($jobsRows, 'id', 'name');
        $jobToTypeMap = ArrayHelper::map($jobsRows, 'id', 'job_type');
        $jobTypesMap = ArrayHelper::map(
            \backend\modules\jobs\models\JobsType::find()->select(['id', 'name'])->asArray()->all(), 'id', 'name'
        );

        $ids = ArrayHelper::getColumn($models, 'id');
        $balanceMap = [];
        if (!empty($ids)) {
            $idList = implode(',', array_map('intval', $ids));
            $balanceMap = ArrayHelper::index(
                Yii::$app->db->createCommand(
                    "SELECT contract_id, total_value, total_paid, total_expenses, total_lawyer_cost, total_adjustments, remaining_balance
                     FROM {{%vw_contract_balance}} WHERE contract_id IN ($idList)"
                )->queryAll(),
                'contract_id'
            );
        }

        return $this->exportArrayData($models, [
            'title' => 'الدائرة القانونية',
            'filename' => 'legal_department',
            'headers' => ['#', 'الأطراف', 'الإجمالي', 'المتبقي', 'الوظيفة', 'نوع الوظيفة'],
            'keys' => [
                'id',
                function ($m) {
                    $parties = $m->customersAndGuarantor;
                    $lines = [];
                    foreach ($parties as $p) {
                        $line = $p->name;
                        if ($p->id_number) $line .= ' (' . $p->id_number . ')';
                        $lines[] = $line;
                    }
                    return implode(' | ', $lines) ?: '—';
                },
                function ($m) use ($balanceMap) {
                    $b = $balanceMap[$m->id] ?? null;
                    if ($b) return (float)$b['total_value'] + (float)$b['total_expenses'] + (float)$b['total_lawyer_cost'];
                    return (float)$m->total_value;
                },
                function ($m) use ($balanceMap) {
                    $b = $balanceMap[$m->id] ?? null;
                    return $b ? (float)$b['remaining_balance'] : 0;
                },
                function ($m) use ($jobsMap) {
                    $firstCustomer = ($m->customersAndGuarantor)[0] ?? null;
                    $jobId = ($firstCustomer && $firstCustomer->job_title) ? $firstCustomer->job_title : null;
                    return $jobId ? ($jobsMap[$jobId] ?? '—') : '—';
                },
                function ($m) use ($jobsMap, $jobToTypeMap, $jobTypesMap) {
                    $firstCustomer = ($m->customersAndGuarantor)[0] ?? null;
                    $jobId = ($firstCustomer && $firstCustomer->job_title) ? $firstCustomer->job_title : null;
                    $jobTypeId = $jobId ? ($jobToTypeMap[$jobId] ?? null) : null;
                    return $jobTypeId ? ($jobTypesMap[$jobTypeId] ?? '—') : '—';
                },
            ],
            'widths' => [8, 30, 14, 14, 16, 16],
        ], $format);
    }

    /** @deprecated Replaced by vw_contract_balance — kept for reference only */

    /* ══════════════════════════════════════════════════════════════
     *  عرض — View
     * ══════════════════════════════════════════════════════════════ */

    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title'   => "العقد #$id",
                'content' => $this->renderAjax('view', ['model' => $this->findModel($id)]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal'])
                           . Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /* ══════════════════════════════════════════════════════════════
     *  إنشاء عقد — Create
     * ══════════════════════════════════════════════════════════════ */

    public function actionCreate()
    {
        $model = new Contracts();
        $model->status    = Contracts::STATUS_ACTIVE;
        $model->seller_id = Yii::$app->user->id;
        $model->type      = 'normal';
        $model->Date_of_sale = date('Y-m-d');
        $model->is_legal_department = 0;
        $model->first_installment_value = 0;
        $model->commitment_discount = 0;
        $model->loss_commitment = 0;

        if (defined('\backend\modules\contracts\models\Contracts::DEFAUULT_TOTAL_VALUE'))
            $model->total_value = Contracts::DEFAUULT_TOTAL_VALUE;
        if (defined('\backend\modules\contracts\models\Contracts::MONTHLY_INSTALLMENT_VALE'))
            $model->monthly_installment_value = Contracts::MONTHLY_INSTALLMENT_VALE;

        $primaryCompany = Companies::findOne(['is_primary_company' => 1]);
        if ($primaryCompany) {
            $model->company_id = $primaryCompany->id;
        }

        if (!Yii::$app->request->isPost) {
            $params = $this->buildFormParams($model);
            $customerId = Yii::$app->request->get('id');
            if ($customerId) {
                $cust = \backend\modules\customers\models\Customers::findOne($customerId);
                if ($cust) {
                    $params['existingCustomers'] = [[
                        'id' => $cust->id,
                        'name' => $cust->name,
                        'id_number' => $cust->id_number ?? '',
                        'phone' => $cust->primary_phone_number ?? '',
                    ]];
                }
            }
            return $this->render('create', $params);
        }

        if (!$model->load(Yii::$app->request->post())) {
            Yii::$app->session->setFlash('error', 'فشل تحميل بيانات النموذج');
            return $this->render('create', $this->buildFormParams($model));
        }

        if ($model->type !== 'solidarity' && empty($model->customer_id)) {
            Yii::$app->session->setFlash('error', 'يجب اختيار العميل');
            return $this->render('create', $this->buildFormParams($model));
        }
        if ($model->type === 'solidarity' && empty($model->customers_ids)) {
            Yii::$app->session->setFlash('error', 'يجب اختيار العملاء للعقد التضامني');
            return $this->render('create', $this->buildFormParams($model));
        }

        $model->is_legal_department    = $model->is_legal_department ?: 0;
        $model->first_installment_value = $model->first_installment_value !== null && $model->first_installment_value !== '' ? $model->first_installment_value : 0;
        $model->commitment_discount     = $model->commitment_discount !== null && $model->commitment_discount !== '' ? $model->commitment_discount : 0;
        $model->loss_commitment         = $model->loss_commitment !== null && $model->loss_commitment !== '' ? $model->loss_commitment : 0;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save(false)) {
                throw new \Exception('فشل حفظ العقد');
            }

            // ── حفظ الأجهزة (سيريال) ──
            $this->saveSerialItems($model);

            // ── حفظ الأجهزة (يدوي — بدون سيريال) ──
            $this->saveManualItems($model);

            // ── حفظ العملاء والكفلاء ──
            $this->saveContractCustomers($model);

            // ── إنشاء ملف مستندات ──
            $docFile = new ContractDocumentFile();
            $docFile->document_type = 'contract file';
            $docFile->contract_id = $model->id;
            $docFile->save(false);

            // ── إشعار ──
            Yii::$app->notifications->sendByRule(
                ['Manager'],
                'contracts/update?id=' . $model->id,
                Notification::GENERAL,
                Yii::t('app', 'إنشاء عقد رقم'),
                Yii::t('app', 'إنشاء عقد رقم') . $model->id,
                Yii::$app->user->id
            );

            // ── تحديث الكاش ──
            $this->refreshContractCaches();

            $transaction->commit();

            if (Yii::$app->request->post('print') !== null) {
                return $this->redirect(['print-preview', 'id' => $model->id]);
            }
            return $this->redirect(['index']);

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'حدث خطأ: ' . $e->getMessage());
            return $this->render('create', $this->buildFormParams($model));
        }
    }

    /* ══════════════════════════════════════════════════════════════
     *  تعديل عقد — Update
     * ══════════════════════════════════════════════════════════════ */

    public function actionUpdate($id, $notificationID = 0)
    {
        if ($notificationID) {
            Yii::$app->notifications->setReaded($notificationID);
        }

        $model = $this->findModel($id);

        if (!Yii::$app->request->isPost) {
            return $this->render('update', $this->buildFormParams($model));
        }

        if (!$model->load(Yii::$app->request->post())) {
            Yii::$app->session->setFlash('error', 'فشل تحميل بيانات النموذج');
            return $this->render('update', $this->buildFormParams($model));
        }

        if ($model->type !== 'solidarity' && empty($model->customer_id)) {
            Yii::$app->session->setFlash('error', 'يجب اختيار العميل');
            return $this->render('update', $this->buildFormParams($model));
        }
        if ($model->type === 'solidarity' && empty($model->customers_ids)) {
            Yii::$app->session->setFlash('error', 'يجب اختيار العملاء للعقد التضامني');
            return $this->render('update', $this->buildFormParams($model));
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save(false)) {
                throw new \Exception('فشل حفظ العقد');
            }

            // ── تحديث الأجهزة (إزالة القديمة + إضافة الجديدة) ──
            $this->updateSerialItems($model);

            // ── تحديث الأجهزة اليدوية ──
            $this->updateManualItems($model);

            // ── تحديث العملاء والكفلاء ──
            ContractsCustomers::deleteAll(['contract_id' => $id]);
            $this->saveContractCustomers($model);

            // ── إشعار ──
            Yii::$app->notifications->sendByRule(
                ['Manager'],
                'contracts/update?id=' . $model->id,
                Notification::GENERAL,
                Yii::t('app', 'تم تعديل عقد رقم') . $model->id,
                Yii::t('app', 'تعديل عقد رقم') . $model->id . ' من قبل ' . Yii::$app->user->identity['username'],
                Yii::$app->user->id
            );

            $this->refreshContractCaches();
            $transaction->commit();

            if (Yii::$app->request->post('print') !== null) {
                return $this->redirect(['print-preview', 'id' => $model->id]);
            }
            return $this->redirect(['update', 'id' => $model->id]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'حدث خطأ: ' . $e->getMessage());
            return $this->render('update', $this->buildFormParams($model));
        }
    }

    /* ══════════════════════════════════════════════════════════════
     *  البحث بالرقم التسلسلي — AJAX
     * ══════════════════════════════════════════════════════════════ */

    public function actionLookupSerial(string $serial = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $serial = trim($serial);

        if ($serial === '') {
            return ['success' => false, 'message' => 'أدخل الرقم التسلسلي'];
        }

        $model = InventorySerialNumber::find()
            ->andWhere(['serial_number' => $serial])
            ->with('item')
            ->one();

        if (!$model) {
            return ['success' => false, 'message' => 'الرقم التسلسلي غير موجود في النظام'];
        }

        if ($model->status !== InventorySerialNumber::STATUS_AVAILABLE) {
            $labels = InventorySerialNumber::getStatusList();
            return ['success' => false, 'message' => 'الجهاز غير متاح — الحالة: ' . ($labels[$model->status] ?? $model->status)];
        }

        return [
            'success' => true,
            'data'    => [
                'id'            => $model->id,
                'serial_number' => $model->serial_number,
                'item_id'       => $model->item_id,
                'item_name'     => $model->item ? $model->item->item_name : 'غير معروف',
                'status'        => $model->status,
            ],
        ];
    }

    /* ══════════════════════════════════════════════════════════════
     *  حذف — Delete
     * ══════════════════════════════════════════════════════════════ */

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    public function actionBulkdelete()
    {
        $raw = Yii::$app->request->post('pks');
        if ($raw === null || $raw === '') {
            return $this->redirect(['index']);
        }
        $pks = is_array($raw) ? $raw : explode(',', (string)$raw);
        foreach ($pks as $pk) {
            $this->findModel($pk)->delete();
        }
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    /* ══════════════════════════════════════════════════════════════
     *  إجراءات العقد — Status Actions
     * ══════════════════════════════════════════════════════════════ */

    /** @deprecated Status is now computed automatically */
    public function actionFinish()
    {
        $id = Yii::$app->request->post('contract_id');
        $model = $this->findModel($id);
        $model->refreshStatus();
        Yii::$app->session->addFlash('success', 'تم تحديث حالة العقد');
        return $this->redirect(['index']);
    }

    /** @deprecated Status is now computed automatically */
    public function actionFinishContract($contract_id)
    {
        $model = $this->findModel($contract_id);
        $model->refreshStatus();
        Yii::$app->session->addFlash('success', 'تم تحديث حالة العقد');
        return $this->redirect(['index']);
    }

    public function actionCancel()
    {
        $id = (int) Yii::$app->request->post('contract_id');
        Contracts::releaseInventoryOnCancel($id);
        Contracts::updateAll(['status' => 'canceled'], ['id' => $id]);
        Yii::$app->session->addFlash('success', 'تم إلغاء العقد وإرجاع الأصناف إلى المخزون');
        return $this->redirect(['index']);
    }

    public function actionCancelContract($contract_id)
    {
        $contract_id = (int) $contract_id;
        Contracts::releaseInventoryOnCancel($contract_id);
        Contracts::updateAll(['status' => 'canceled'], ['id' => $contract_id]);
        Yii::$app->session->addFlash('success', 'تم إلغاء العقد وإرجاع الأصناف إلى المخزون');
        return $this->redirect(['index']);
    }

    public function actionReturnToContinue($id)
    {
        if ($id > 0) {
            $model = $this->findModel($id);
            if ($model->status === Contracts::CANCEL_STATUS) {
                Contracts::updateAll(['status' => Contracts::STATUS_ACTIVE], ['id' => $id]);
            }
            $model->refresh();
            $model->refreshStatus();
        }
        return $this->redirect(['/follow-up-report/index']);
    }

    public function actionToLegalDepartment($id)
    {
        $model = $this->findModel($id);
        $model->toggleLegalDepartment(true);
        Yii::$app->session->addFlash('success', 'تم تحويل العقد إلى الدائرة القانونية');
        Yii::$app->notifications->sendByRule(
            ['Manager'], '/follow-up?contract_id=' . $id,
            Notification::GENERAL,
            Yii::t('app', 'تحويل عقد الى الدائره القانونيه'),
            Yii::t('app', 'تحويل عقد ' . $id . ' الى الدائره القانونيه'),
            Yii::$app->user->id
        );
        return $this->redirect(['index']);
    }

    public function actionRemoveFromLegalDepartment($id)
    {
        $model = $this->findModel($id);
        $model->toggleLegalDepartment(false);
        Yii::$app->session->addFlash('success', 'تم إزالة العقد من الدائرة القانونية');
        return $this->redirect(['index']);
    }

    /* ══════════════════════════════════════════════════════════════
     *  التسويات والخصومات — Contract Adjustments
     * ══════════════════════════════════════════════════════════════ */

    public function actionAddAdjustment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = new \backend\modules\contracts\models\ContractAdjustment();
        $model->contract_id = Yii::$app->request->post('contract_id');
        $model->type = Yii::$app->request->post('type', 'discount');
        $model->amount = Yii::$app->request->post('amount');
        $model->reason = Yii::$app->request->post('reason', '');
        $model->approved_by = Yii::$app->user->id;

        if ($model->save()) {
            return ['success' => true, 'message' => 'تم إضافة الخصم بنجاح'];
        }
        return ['success' => false, 'errors' => $model->getFirstErrors()];
    }

    public function actionDeleteAdjustment($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = \backend\modules\contracts\models\ContractAdjustment::findOne($id);
        if ($model) {
            $model->is_deleted = 1;
            $model->save(false);
            Contracts::refreshContractStatus($model->contract_id);
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'لم يتم العثور على الخصم'];
    }

    public function actionAdjustments($contract_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $adjustments = \backend\modules\contracts\models\ContractAdjustment::find()
            ->where(['contract_id' => $contract_id, 'is_deleted' => 0])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()
            ->all();
        return $adjustments;
    }

    public function actionRefreshStatus($id)
    {
        $model = $this->findModel($id);
        $newStatus = $model->refreshStatus();
        Yii::$app->session->addFlash('success', 'تم تحديث حالة العقد');
        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    public function actionConvertToManager($id)
    {
        Yii::$app->notifications->sendByRule(
            ['Manager'], '/follow-up?contract_id=' . $id,
            Notification::GENERAL,
            Yii::t('app', 'مراجعة متابعه'),
            Yii::t('app', 'مراجعة متابعه للعقد رقم') . $id,
            Yii::$app->user->id
        );
        return $this->redirect(['index']);
    }

    public function actionChangFollowUp()
    {
        $id = Yii::$app->request->post('id');
        $followedBy = Yii::$app->request->post('followedBy');
        Contracts::updateAll(['followed_by' => (int)$followedBy], ['id' => (int)$id]);
    }

    public function actionIsNotConnect($contract_id)
    {
        Yii::$app->db->createCommand()
            ->update('{{%contracts}}', ['is_can_not_contact' => 1], 'id = ' . (int)$contract_id)
            ->execute();
        return $this->redirect(['/followUp/follow-up/index', 'contract_id' => $contract_id]);
    }

    public function actionIsConnect($contract_id)
    {
        Yii::$app->db->createCommand()
            ->update('{{%contracts}}', ['is_can_not_contact' => 0], 'id = ' . (int)$contract_id)
            ->execute();
        return $this->redirect(['/followUp/follow-up/index', 'contract_id' => $contract_id]);
    }

    /* ══════════════════════════════════════════════════════════════
     *  الطباعة — Print
     * ══════════════════════════════════════════════════════════════ */

    public function actionPrintPreview($id)
    {
        $this->layout = false;
        $model = $this->findModel($id);

        $kambAmount = ($model->total_value ?: 0) * 1.15;
        $notes = PromissoryNote::ensureNotesExist($model->id, $kambAmount, $model->first_installment_date);

        $buyers     = $model->customers;
        $guarantors = $model->guarantor;
        $allPeople  = array_merge($buyers, $guarantors);
        $pCount     = count($allPeople);

        $maxNameLen = 0;
        foreach ($allPeople as $p) {
            $len = mb_strlen($p->name, 'UTF-8');
            if ($len > $maxNameLen) $maxNameLen = $len;
        }
        $density = ($pCount >= 4 || $maxNameLen > 30) ? 'tight' : 'normal';

        $sellerName = '';
        if ($model->seller_id) {
            $profile = \dektrium\user\models\Profile::findOne(['user_id' => $model->seller_id]);
            $sellerName = $profile ? $profile->name : ($model->seller->username ?? '');
        }

        return $this->renderPartial('_print_preview', [
            'model'      => $model,
            'notes'      => $notes,
            'allPeople'  => $allPeople,
            'guarantors' => $guarantors,
            'pCount'     => $pCount,
            'density'    => $density,
            'sellerName' => $sellerName,
        ]);
    }

    public function actionPrintFirstPage($id)
    {
        $this->layout = false;
        $model = $this->findModel($id);
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title'   => "العقد #$id",
                'content' => $this->renderAjax('_contract_print', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal'])
                           . Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }
        return $this->renderPartial('_contract_print', ['model' => $model]);
    }

    public function actionPrintSecondPage($id)
    {
        $this->layout = false;
        $model = $this->findModel($id);
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title'   => "العقد #$id",
                'content' => $this->renderAjax('_draft_print', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal'])
                           . Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }
        return $this->renderPartial('_draft_print', ['model' => $model]);
    }

    /* ══════════════════════════════════════════════════════════════
     *  دوال مساعدة — Helpers
     * ══════════════════════════════════════════════════════════════ */

    /**
     * بناء مصفوفة البيانات اللازمة للفورم
     */
    private function buildFormParams($model)
    {
        $companies = ArrayHelper::map(Companies::find()->asArray()->all(), 'id', 'name');
        $inventoryItems = ArrayHelper::map(InventoryItems::find()->asArray()->all(), 'id', 'item_name');

        $scannedSerials = [];
        $existingCustomers = [];
        $existingGuarantors = [];

        if (!$model->isNewRecord) {
            $items = ContractInventoryItem::find()
                ->where(['contract_id' => $model->id])
                ->andWhere(['IS NOT', 'serial_number_id', null])
                ->all();
            foreach ($items as $item) {
                $serial = InventorySerialNumber::findOne($item->serial_number_id);
                if ($serial) {
                    $invItem = InventoryItems::findOne($serial->item_id);
                    $scannedSerials[] = [
                        'id'            => $serial->id,
                        'serial_number' => $serial->serial_number,
                        'item_name'     => $invItem ? $invItem->item_name : '',
                        'item_id'       => $serial->item_id,
                    ];
                }
            }

            foreach ($model->contractsCustomers as $cc) {
                $cust = $cc->customer ?? null;
                if (!$cust) continue;
                $entry = [
                    'id'   => $cust->id,
                    'name' => $cust->name,
                    'id_number' => $cust->id_number ?? '',
                    'phone' => $cust->primary_phone_number ?? '',
                ];
                if ($cc->customer_type === 'client') {
                    $existingCustomers[] = $entry;
                } else {
                    $existingGuarantors[] = $entry;
                }
            }
        }

        return [
            'model'              => $model,
            'companies'          => $companies,
            'inventoryItems'     => $inventoryItems,
            'scannedSerials'     => $scannedSerials,
            'existingCustomers'  => $existingCustomers,
            'existingGuarantors' => $existingGuarantors,
        ];
    }

    /**
     * حفظ بنود السيريال عند إنشاء عقد
     */
    private function saveSerialItems($model)
    {
        $saleTimestamp = $model->Date_of_sale ? strtotime($model->Date_of_sale) : null;
        $serialIds = Yii::$app->request->post('serial_ids', []);
        foreach ($serialIds as $serialId) {
            $serial = InventorySerialNumber::findOne((int)$serialId);
            if (!$serial || $serial->status !== InventorySerialNumber::STATUS_AVAILABLE) continue;

            $ci = new ContractInventoryItem();
            $ci->contract_id = $model->id;
            $ci->item_id = $serial->item_id;
            $ci->serial_number_id = $serial->id;
            $ci->code = $serial->serial_number;
            $ci->save(false);

            $serial->status = InventorySerialNumber::STATUS_SOLD;
            $serial->contract_id = $model->id;
            $serial->sold_at = $saleTimestamp ?: time();
            $serial->save(false);

            StockMovement::record($serial->item_id, StockMovement::TYPE_OUT, 1, [
                'reference_type' => 'contract_sale',
                'reference_id'   => $model->id,
                'company_id'     => $model->company_id,
                'notes'          => 'بيع عبر عقد #' . $model->id . ' — سيريال: ' . $serial->serial_number,
                'created_at'     => $saleTimestamp,
            ]);

            $this->deductInventoryQuantity($model, $serial->item_id);
        }
    }

    /**
     * حفظ بنود يدوية (بدون سيريال)
     */
    private function saveManualItems($model)
    {
        $manualItemIds = Yii::$app->request->post('manual_item_ids', []);
        foreach ($manualItemIds as $itemId) {
            $ci = new ContractInventoryItem();
            $ci->contract_id = $model->id;
            $ci->item_id = (int)$itemId;
            $ci->save(false);

            $this->deductInventoryQuantity($model, (int)$itemId);
        }
    }

    /**
     * تحديث بنود السيريال عند تعديل عقد
     */
    private function updateSerialItems($model)
    {
        $saleTimestamp = $model->Date_of_sale ? strtotime($model->Date_of_sale) : null;
        $postSerialIds = Yii::$app->request->post('serial_ids');
        $newSerialIds = is_array($postSerialIds) ? array_map('intval', $postSerialIds) : [];

        $oldItems = ContractInventoryItem::find()
            ->where(['contract_id' => $model->id])
            ->andWhere(['IS NOT', 'serial_number_id', null])
            ->all();

        $oldSerialIds = array_map(function($i) { return (int)$i->serial_number_id; }, $oldItems);

        $toRelease = array_diff($oldSerialIds, $newSerialIds);
        foreach ($toRelease as $sid) {
            $releasedSerial = InventorySerialNumber::findOne($sid);
            $this->releaseSerial($sid);
            ContractInventoryItem::deleteAll([
                'contract_id' => $model->id,
                'serial_number_id' => $sid,
            ]);
            if ($releasedSerial) {
                StockMovement::record($releasedSerial->item_id, StockMovement::TYPE_RETURN, 1, [
                    'reference_type' => 'contract_update_release',
                    'reference_id'   => $model->id,
                    'company_id'     => $model->company_id,
                    'notes'          => 'إرجاع من عقد #' . $model->id . ' — سيريال: ' . $releasedSerial->serial_number,
                ]);
            }
        }

        $toAdd = array_diff($newSerialIds, $oldSerialIds);
        foreach ($toAdd as $sid) {
            $serial = InventorySerialNumber::findOne($sid);
            if (!$serial || $serial->status !== InventorySerialNumber::STATUS_AVAILABLE) continue;

            $ci = new ContractInventoryItem();
            $ci->contract_id = $model->id;
            $ci->item_id = $serial->item_id;
            $ci->serial_number_id = $serial->id;
            $ci->code = $serial->serial_number;
            $ci->save(false);

            $serial->status = InventorySerialNumber::STATUS_SOLD;
            $serial->contract_id = $model->id;
            $serial->sold_at = $saleTimestamp ?: time();
            $serial->save(false);

            StockMovement::record($serial->item_id, StockMovement::TYPE_OUT, 1, [
                'reference_type' => 'contract_sale',
                'reference_id'   => $model->id,
                'company_id'     => $model->company_id,
                'notes'          => 'بيع عبر عقد #' . $model->id . ' — سيريال: ' . $serial->serial_number,
                'created_at'     => $saleTimestamp,
            ]);

            $this->deductInventoryQuantity($model, $serial->item_id);
        }

        $this->syncOrphanedSerials();
    }

    /**
     * إرجاع سيريال إلى حالة "متاح" — يعمل حتى لو كان محذوفاً ناعماً
     */
    private function releaseSerial($serialId)
    {
        Yii::$app->db->createCommand()->update(
            'os_inventory_serial_numbers',
            ['status' => 'available', 'contract_id' => null, 'sold_at' => null],
            ['id' => (int) $serialId]
        )->execute();
    }

    /**
     * مزامنة: أي سيريال حالته "مباع" بدون سجل فعلي في بنود العقود يرجع "متاح"
     */
    private function syncOrphanedSerials()
    {
        Yii::$app->db->createCommand(
            "UPDATE os_inventory_serial_numbers
             SET status = 'available', contract_id = NULL, sold_at = NULL
             WHERE status = 'sold'
               AND is_deleted = 0
               AND id NOT IN (
                   SELECT serial_number_id
                   FROM os_contract_inventory_item
                   WHERE serial_number_id IS NOT NULL
               )"
        )->execute();
    }

    /**
     * تحديث بنود يدوية عند التعديل
     */
    private function updateManualItems($model)
    {
        // حذف البنود اليدوية القديمة
        ContractInventoryItem::deleteAll([
            'contract_id' => $model->id,
            'serial_number_id' => null,
        ]);
        // إضافة الجديدة
        $this->saveManualItems($model);
    }

    /**
     * خصم كمية من المخزون
     */
    private function deductInventoryQuantity($model, $itemId)
    {
        $location = InventoryStockLocations::find()
            ->andWhere(['company_id' => $model->company_id])
            ->one();

        $qty = new InventoryItemQuantities();
        $qty->item_id = $itemId;
        $qty->suppliers_id = $model->company_id;
        $qty->locations_id = $location ? $location->id : 0;
        $qty->quantity = 1;
        $qty->save(false);
    }

    /**
     * حفظ العملاء والكفلاء
     */
    private function saveContractCustomers($model)
    {
        if ($model->type === 'solidarity') {
            foreach ((array)$model->customers_ids as $customerId) {
                $cc = new ContractsCustomers();
                $cc->contract_id = $model->id;
                $cc->customer_id = $customerId;
                $cc->customer_type = 'client';
                $cc->save(false);
            }
        } else {
            // العميل الأساسي
            $cc = new ContractsCustomers();
            $cc->contract_id = $model->id;
            $cc->customer_id = $model->customer_id;
            $cc->customer_type = 'client';
            $cc->save(false);

            // الكفلاء
            if (!empty($model->guarantors_ids)) {
                foreach ((array)$model->guarantors_ids as $gid) {
                    $gc = new ContractsCustomers();
                    $gc->contract_id = $model->id;
                    $gc->customer_id = $gid;
                    $gc->customer_type = 'guarantor';
                    $gc->save(false);
                }
            }
        }
    }

    /**
     * تحديث كاش العقود
     */
    private function refreshContractCaches()
    {
        Yii::$app->cache->set(
            Yii::$app->params['key_contract_id'],
            Yii::$app->db->createCommand(Yii::$app->params['contract_id_query'])->queryAll(),
            Yii::$app->params['time_duration']
        );
        Yii::$app->cache->set(
            Yii::$app->params['key_contract_status'],
            Yii::$app->db->createCommand(Yii::$app->params['contract_status_query'])->queryAll(),
            Yii::$app->params['time_duration']
        );
    }

    /**
     * إيجاد موديل العقد
     */
    protected function findModel($id)
    {
        $model = Contracts::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة.');
        }
        return $model;
    }
}
