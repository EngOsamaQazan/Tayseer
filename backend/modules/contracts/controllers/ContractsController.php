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
            $paramKey = ":w{$i}";
            $nameWhere[] = "($nameNorm LIKE $paramKey OR $nameNormNoSpace LIKE $paramKey)";
            $nameParams[$paramKey] = '%' . $w . '%';
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
                   c.id = :qInt
                   OR ($nameClause)
                   OR cu.id_number LIKE :qLike
                   OR cu.primary_phone_number LIKE :qLike
                   OR cu.id = :qInt
               )
             GROUP BY c.id
             ORDER BY c.id DESC
             LIMIT 10"
        , array_merge([
            ':qInt'  => (int)$q,
            ':qLike' => '%' . $q . '%',
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

        $dataProvider->query->with(['customers', 'seller', 'followedBy']);

        $models = $dataProvider->getModels();
        $contractIds = ArrayHelper::getColumn($models, 'id');

        $batchData = ContractCalculations::batchPreload($contractIds);

        $db = Yii::$app->db;
        $statusCounts = ArrayHelper::map(
            $db->createCommand("SELECT status, COUNT(*) AS cnt FROM os_contracts WHERE is_deleted=0 OR is_deleted IS NULL GROUP BY status")->queryAll(),
            'status', 'cnt'
        );

        $judPaidCount = (int)$db->createCommand("
            SELECT COUNT(*) FROM os_contracts c
            WHERE c.status = 'judiciary' AND (c.is_deleted=0 OR c.is_deleted IS NULL)
            AND ROUND(
                c.total_value
                + COALESCE((SELECT SUM(e.amount) FROM os_expenses e WHERE e.contract_id = c.id), 0)
                + COALESCE((SELECT SUM(j.lawyer_cost) FROM os_judiciary j WHERE j.contract_id = c.id AND (j.is_deleted=0 OR j.is_deleted IS NULL)), 0)
                - COALESCE((SELECT SUM(ca.amount) FROM os_contract_adjustments ca WHERE ca.contract_id = c.id AND ca.is_deleted=0), 0)
                - COALESCE((SELECT SUM(i.amount) FROM os_income i WHERE i.contract_id = c.id), 0)
            , 2) <= 0
        ")->queryScalar();

        $judTotalCount = (int)($statusCounts['judiciary'] ?? 0);
        $statusCounts['judiciary_active'] = $judTotalCount - $judPaidCount;
        $statusCounts['judiciary_paid'] = $judPaidCount;

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'dataCount'    => $dataCount,
            'batchData'    => $batchData,
            'statusCounts' => $statusCounts,
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

        $referrer = Yii::$app->request->referrer ?: '';
        $isIframe = strpos($referrer, '/judiciary') !== false || Yii::$app->request->get('_iframe');
        $renderMethod = $isIframe ? 'renderAjax' : 'render';

        return $this->$renderMethod('index-legal-department', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'dataCount'    => $dataCount,
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

        $judiciaryMap = [];
        $ids = ArrayHelper::getColumn($models, 'id');
        if (!empty($ids)) {
            $judRecords = \backend\modules\judiciary\models\Judiciary::find()
                ->where(['contract_id' => $ids, 'is_deleted' => 0])
                ->orderBy(['id' => SORT_DESC])
                ->all();
            foreach ($judRecords as $jud) {
                if (!isset($judiciaryMap[$jud->contract_id])) {
                    $judiciaryMap[$jud->contract_id] = $jud;
                }
            }
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

            $jud = $judiciaryMap[$m->id] ?? null;
            $total = $m->total_value;
            if ($jud) $total += ($jud->case_cost ?? 0) + ($jud->lawyer_cost ?? 0);
            $paid = \backend\modules\contractInstallment\models\ContractInstallment::find()
                ->where(['contract_id' => $m->id])->sum('amount') ?? 0;
            $remaining = $total - $paid;
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

        $query->leftJoin('{{%user}} _sl', '_sl.id = os_contracts.seller_id');
        $query->leftJoin('{{%user}} _fu', '_fu.id = os_contracts.followed_by');
        $query->select([
            'os_contracts.id', 'os_contracts.total_value', 'os_contracts.Date_of_sale',
            'os_contracts.status', 'os_contracts.seller_id', 'os_contracts.followed_by',
            'seller_name' => '_sl.username', 'follower_name' => '_fu.username',
        ]);
        $dataProvider->pagination = false;
        $rows = $query->asArray()->all();

        $contractIds = array_column($rows, 'id');
        $pre = $this->preloadContractExportData($contractIds);

        $customersByContract = [];
        $deservedByContract = [];
        if (!empty($contractIds)) {
            $custData = (new \yii\db\Query())
                ->select(["cc.contract_id", "GROUP_CONCAT(c.name SEPARATOR '، ') as names"])
                ->from('{{%contracts_customers}} cc')
                ->innerJoin('{{%customers}} c', 'c.id = cc.customer_id')
                ->where(['cc.contract_id' => $contractIds])
                ->andWhere(['cc.customer_type' => 'client'])
                ->groupBy('cc.contract_id')
                ->all();
            $customersByContract = ArrayHelper::map($custData, 'contract_id', 'names');

            $deservedData = (new \yii\db\Query())
                ->select(['contract_id', 'COALESCE(SUM(amount),0) as total'])
                ->from('{{%income}}')
                ->where(['contract_id' => $contractIds])
                ->andWhere(['<=', 'date', date('Y-m-d')])
                ->groupBy('contract_id')
                ->all();
            $deservedByContract = ArrayHelper::map($deservedData, 'contract_id', 'total');
        }

        $statusLabels = [
            'active' => 'نشط', 'pending' => 'معلّق', 'judiciary' => 'قضاء',
            'legal_department' => 'قانوني', 'settlement' => 'تسوية', 'finished' => 'منتهي',
            'canceled' => 'ملغي', 'refused' => 'مرفوض',
        ];

        $exportRows = [];
        foreach ($rows as $r) {
            $id = $r['id'];
            $judRows = $pre['judByContract'][$id] ?? [];
            $total = (float)$r['total_value'];
            if ($r['status'] === 'judiciary' && !empty($judRows)) {
                $total += (float)$judRows[0]['case_cost'] + (float)$judRows[0]['lawyer_cost'];
            }
            $caseCosts = (float)($pre['expByContract'][$id] ?? 0);
            $paid = (float)($pre['paidByContract'][$id] ?? 0);
            $totalForRemain = (float)$r['total_value'];
            if (!empty($judRows)) {
                $lawyerSum = 0;
                foreach ($judRows as $j) $lawyerSum += (float)$j['lawyer_cost'];
                $totalForRemain += $caseCosts + $lawyerSum;
            }

            $exportRows[] = [
                'id'        => $id,
                'seller'    => $r['seller_name'] ?: '—',
                'customer'  => $customersByContract[$id] ?? '—',
                'deserved'  => (float)($deservedByContract[$id] ?? 0),
                'date'      => $r['Date_of_sale'] ?: '—',
                'total'     => $total,
                'status'    => $statusLabels[$r['status']] ?? $r['status'],
                'remaining' => $totalForRemain - $paid,
                'follower'  => $r['follower_name'] ?: '—',
            ];
        }

        return $this->exportArrayData($exportRows, [
            'title'    => 'العقود',
            'filename' => 'contracts',
            'headers'  => ['#', 'البائع', 'العميل', 'المستحق', 'التاريخ', 'الإجمالي', 'الحالة', 'المتبقي', 'المتابع'],
            'keys'     => ['id', 'seller', 'customer', 'deserved', 'date', 'total', 'status', 'remaining', 'follower'],
            'widths'   => [8, 16, 22, 14, 14, 14, 12, 14, 14],
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
        $judiciaryMap = [];
        if (!empty($ids)) {
            $judRecords = \backend\modules\judiciary\models\Judiciary::find()
                ->where(['contract_id' => $ids, 'is_deleted' => 0])
                ->orderBy(['id' => SORT_DESC])
                ->all();
            foreach ($judRecords as $jud) {
                if (!isset($judiciaryMap[$jud->contract_id])) {
                    $judiciaryMap[$jud->contract_id] = $jud;
                }
            }
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
                function ($m) use ($judiciaryMap) {
                    $jud = $judiciaryMap[$m->id] ?? null;
                    $total = (float)$m->total_value;
                    if ($jud) $total += ($jud->case_cost ?? 0) + ($jud->lawyer_cost ?? 0);
                    return $total;
                },
                function ($m) use ($judiciaryMap) {
                    $jud = $judiciaryMap[$m->id] ?? null;
                    $totalForRemain = (float)$m->total_value;
                    if ($jud) {
                        $caseCosts = \backend\modules\expenses\models\Expenses::find()
                            ->where(['contract_id' => $m->id, 'category_id' => 4])->sum('amount') ?? 0;
                        $totalForRemain += $caseCosts + ($jud->lawyer_cost ?? 0);
                    }
                    $paid = \backend\modules\contractInstallment\models\ContractInstallment::find()
                        ->where(['contract_id' => $m->id])->sum('amount') ?? 0;
                    return $totalForRemain - $paid;
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

    private function preloadContractExportData(array $contractIds): array
    {
        $pre = ['judByContract' => [], 'expByContract' => [], 'paidByContract' => []];
        if (empty($contractIds)) return $pre;

        $db = Yii::$app->db;
        $idList = implode(',', array_map('intval', $contractIds));

        $judRows = $db->createCommand(
            "SELECT contract_id, id, case_cost, lawyer_cost
             FROM os_judiciary WHERE contract_id IN ($idList) AND is_deleted=0
             ORDER BY id DESC"
        )->queryAll();
        foreach ($judRows as $j) {
            $pre['judByContract'][$j['contract_id']][] = $j;
        }

        $pre['expByContract'] = ArrayHelper::map(
            $db->createCommand(
                "SELECT contract_id, COALESCE(SUM(amount),0) as total
                 FROM os_expenses WHERE contract_id IN ($idList) AND category_id=4
                 GROUP BY contract_id"
            )->queryAll(),
            'contract_id', 'total'
        );

        $pre['paidByContract'] = ArrayHelper::map(
            $db->createCommand(
                "SELECT contract_id, COALESCE(SUM(amount),0) as total
                 FROM os_income WHERE contract_id IN ($idList)
                 GROUP BY contract_id"
            )->queryAll(),
            'contract_id', 'total'
        );

        return $pre;
    }

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

        if (defined('\backend\modules\contracts\models\Contracts::DEFAUULT_TOTAL_VALUE'))
            $model->total_value = Contracts::DEFAUULT_TOTAL_VALUE;
        if (defined('\backend\modules\contracts\models\Contracts::MONTHLY_INSTALLMENT_VALE'))
            $model->monthly_installment_value = Contracts::MONTHLY_INSTALLMENT_VALE;

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

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->load(Yii::$app->request->post()) || !$model->save(false)) {
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

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->load(Yii::$app->request->post()) || !$model->save(false)) {
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
            ->where(['serial_number' => $serial])
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
        $id = Yii::$app->request->post('contract_id');
        Contracts::updateAll(['status' => 'canceled'], ['id' => $id]);
        Yii::$app->session->addFlash('success', 'تم إلغاء العقد');
        return $this->redirect(['index']);
    }

    public function actionCancelContract($contract_id)
    {
        Contracts::updateAll(['status' => 'canceled'], ['id' => $contract_id]);
        Yii::$app->session->addFlash('success', 'تم إلغاء العقد');
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

        /* إنشاء 3 كمبيالات للعقد تلقائياً إذا لم تكن موجودة */
        $kambAmount = ($model->total_value ?: 0) * 1.15;
        $notes = PromissoryNote::ensureNotesExist($model->id, $kambAmount, $model->due_date);

        return $this->renderPartial('_print_preview', [
            'model' => $model,
            'notes' => $notes,
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
            $serial->sold_at = time();
            $serial->save(false);

            StockMovement::record($serial->item_id, StockMovement::TYPE_OUT, 1, [
                'reference_type' => 'contract_sale',
                'reference_id'   => $model->id,
                'company_id'     => $model->company_id,
                'notes'          => 'بيع عبر عقد #' . $model->id . ' — سيريال: ' . $serial->serial_number,
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
            $serial->sold_at = time();
            $serial->save(false);

            StockMovement::record($serial->item_id, StockMovement::TYPE_OUT, 1, [
                'reference_type' => 'contract_sale',
                'reference_id'   => $model->id,
                'company_id'     => $model->company_id,
                'notes'          => 'بيع عبر عقد #' . $model->id . ' — سيريال: ' . $serial->serial_number,
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
