<?php

namespace backend\modules\followUpReport\controllers;

use Yii;
use backend\modules\followUpReport\models\FollowUpReport;
use backend\modules\followUpReport\models\FollowUpReportSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\filters\AccessControl;
use backend\helpers\ExportTrait;

/**
 * FollowUpReportController implements the CRUD actions for FollowUpReport model.
 */
class FollowUpReportController extends Controller
{
    use ExportTrait;
    /**
     * @inheritdoc
     */
    public function behaviors()
    {

        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'no-contact',
                        'export-excel', 'export-pdf', 'export-no-contact-excel', 'export-no-contact-pdf',
                        'search-suggest'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
//             [
//            'class' => 'yii\filters\PageCache',
//            'only' => ['index'],
//            'duration' => 60,
//            'variations' => [
//                \Yii::$app->language,
//            ],
//            'dependency' => [
//                'class' => 'yii\caching\DbDependency',
//                'sql' => 'SELECT COUNT(*) FROM os_follow_up_report',
//            ],
//        ],
        ];
    }

    /**
     * AJAX autocomplete for unified search.
     */
    public function actionSearchSuggest($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return ['results' => []];
        }

        $db = Yii::$app->db;
        $nameNorm = "REPLACE(REPLACE(REPLACE(REPLACE(cu.name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'ى', 'ي')";
        $nameNormNoSpace = "REPLACE($nameNorm, ' ', '')";

        $words = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);
        $nameParams = [];
        $nameClauses = [];
        foreach ($words as $i => $w) {
            $wNorm = str_replace(['أ', 'إ', 'آ'], 'ا', $w);
            $wNorm = str_replace('ة', 'ه', $wNorm);
            $wNorm = str_replace('ى', 'ي', $wNorm);
            $p1 = ':nw' . $i . 'a';
            $p2 = ':nw' . $i . 'b';
            $likeVal = '%' . $wNorm . '%';
            $nameClauses[] = "($nameNorm LIKE $p1 OR $nameNormNoSpace LIKE $p2)";
            $nameParams[$p1] = $likeVal;
            $nameParams[$p2] = $likeVal;
        }
        $nameClause = implode(' AND ', $nameClauses);
        $qLike = '%' . $q . '%';

        $rows = $db->createCommand(
            "SELECT c.id, c.status,
                    GROUP_CONCAT(DISTINCT cu.name SEPARATOR '، ') AS customer_name,
                    MIN(cu.primary_phone_number) AS phone,
                    MIN(cu.id_number) AS id_number
             FROM {{%contracts}} c
             LEFT JOIN {{%contracts_customers}} cc ON cc.contract_id = c.id
             LEFT JOIN {{%customers}} cu ON cu.id = cc.customer_id
             WHERE (c.is_deleted = 0 OR c.is_deleted IS NULL)
               AND c.status NOT IN ('finished','canceled')
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
            ':qIntContract' => (int)$q,
            ':qIntCustomer' => (int)$q,
            ':qLikeId'      => $qLike,
            ':qLikePhone'   => $qLike,
        ], $nameParams))->queryAll();

        $statusLabels = [
            'active' => 'نشط', 'settlement' => 'تسوية',
            'judiciary' => 'قضائي', 'legal_department' => 'قانوني',
        ];
        $results = [];
        foreach ($rows as $r) {
            $stLabel = $statusLabels[$r['status']] ?? $r['status'];
            $results[] = [
                'id'    => $r['id'],
                'title' => $r['customer_name'] ?: ('عقد #' . $r['id']),
                'sub'   => 'عقد #' . $r['id'] . ' — ' . $stLabel . ($r['phone'] ? ' — ' . $r['phone'] : ''),
                'icon'  => 'fa-gavel',
            ];
        }
        return ['results' => $results];
    }

    /**
     * Lists all FollowUpReport models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new FollowUpReportSearch();
        $params = Yii::$app->request->queryParams;
        if (!isset($params['FollowUpReportSearch']['is_can_not_contact'])) {
            $params['FollowUpReportSearch']['is_can_not_contact'] = '0';
        }
        if (!isset($params['FollowUpReportSearch']['reminder'])) {
            $params['FollowUpReportSearch']['reminder'] = date('Y-m-d');
        }
        $dataProvider = $searchModel->search($params);
        $dataCount = $dataProvider->getTotalCount();

        $db = Yii::$app->db;

        $models = $dataProvider->getModels();
        $contractIds = \yii\helpers\ArrayHelper::getColumn($models, 'id');
        $namesMap = [];
        if (!empty($contractIds)) {
            $idList = implode(',', array_map('intval', $contractIds));
            $namesMap = \yii\helpers\ArrayHelper::index(
                $db->createCommand("SELECT contract_id, client_names, client_phone FROM {{%vw_contract_customers_names}} WHERE contract_id IN ($idList)")->queryAll(),
                'contract_id'
            );
        }

        $cacheKey = 'followup_card_stats_' . date('Y-m-d-H');
        $cardStats = Yii::$app->cache->getOrSet($cacheKey, function () use ($db) {
            return $db->createCommand("
                SELECT
                    SUM(CASE WHEN is_can_not_contact = 0 AND (reminder IS NULL OR reminder <= CURDATE() OR never_followed = 1) THEN 1 ELSE 0 END) AS active_count,
                    SUM(CASE WHEN is_can_not_contact = 0 AND never_followed = 1 THEN 1 ELSE 0 END) AS never_followed_count,
                    SUM(CASE WHEN is_can_not_contact = 0 AND promise_to_pay_at IS NOT NULL AND promise_to_pay_at <= CURDATE() AND due_amount > 0 THEN 1 ELSE 0 END) AS overdue_promise_count,
                    SUM(CASE WHEN is_can_not_contact = 1 THEN 1 ELSE 0 END) AS no_contact_count
                FROM {{%follow_up_report}}
            ")->queryOne();
        }, 300);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'dataCount' => $dataCount,
            'namesMap' => $namesMap,
            'activeCount' => (int)($cardStats['active_count'] ?? 0),
            'neverFollowedCount' => (int)($cardStats['never_followed_count'] ?? 0),
            'overduePromiseCount' => (int)($cardStats['overdue_promise_count'] ?? 0),
            'noContactCount' => (int)($cardStats['no_contact_count'] ?? 0),
        ]);
    }


    /**
     * تقرير العقود التي لا يوجد بها أرقام تواصل
     * is_can_not_contact = 1
     */
    public function actionNoContact()
    {
        $searchModel = new FollowUpReportSearch();
        $dataProvider = $searchModel->searchNoContact(Yii::$app->request->queryParams);
        $dataCount = $searchModel->searchNoContactCount(Yii::$app->request->queryParams);

        $models = $dataProvider->getModels();
        $contractIds = \yii\helpers\ArrayHelper::getColumn($models, 'id');
        $namesMap = [];
        if (!empty($contractIds)) {
            $idList = implode(',', array_map('intval', $contractIds));
            $namesMap = \yii\helpers\ArrayHelper::index(
                Yii::$app->db->createCommand("SELECT contract_id, client_names, client_phone FROM {{%vw_contract_customers_names}} WHERE contract_id IN ($idList)")->queryAll(),
                'contract_id'
            );
        }

        return $this->render('no-contact', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'dataCount' => $dataCount,
            'namesMap' => $namesMap,
        ]);
    }

    /**
     * Displays a single FollowUpReport model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "FollowUpReport #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new FollowUpReport model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new FollowUpReport();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Create new FollowUpReport",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "Create new FollowUpReport",
                    'content' => '<span class="text-success">Create FollowUpReport success</span>',
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Create More', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])

                ];
            } else {
                return [
                    'title' => "Create new FollowUpReport",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
        } else {
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
        }

    }

    /**
     * Updates an existing FollowUpReport model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Update FollowUpReport #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "FollowUpReport #" . $id,
                    'content' => $this->renderAjax('view', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                ];
            } else {
                return [
                    'title' => "Update FollowUpReport #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            }
        } else {
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Delete an existing FollowUpReport model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }


    }

    /**
     * Delete multiple existing FollowUpReport model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks')); // Array or selected records primary keys
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }

    }

    /**
     * Export follow-up report to Excel.
     */
    public function actionExportExcel()
    {
        return $this->exportFollowUpLightweight('excel');
    }

    public function actionExportPdf()
    {
        return $this->exportFollowUpLightweight('pdf');
    }

    private function exportFollowUpLightweight($format)
    {
        $searchModel = new FollowUpReportSearch();
        $params = Yii::$app->request->queryParams;
        if (!isset($params['FollowUpReportSearch']['is_can_not_contact'])) {
            $params['FollowUpReportSearch']['is_can_not_contact'] = '0';
        }
        $dataProvider = $searchModel->search($params);
        $query = $dataProvider->query;
        $query->with = [];

        $query->leftJoin('{{%user}} _fu', '_fu.id = os_follow_up_report.followed_by');
        $query->select([
            'os_follow_up_report.id', 'os_follow_up_report.status',
            'os_follow_up_report.effective_installment', 'os_follow_up_report.monthly_installment_value',
            'os_follow_up_report.due_installments', 'os_follow_up_report.due_amount',
            'os_follow_up_report.last_follow_up', 'os_follow_up_report.never_followed',
            'os_follow_up_report.reminder', 'os_follow_up_report.promise_to_pay_at',
            'follower_name' => '_fu.username',
        ]);

        $dataProvider->pagination = false;
        $rows = $query->asArray()->all();

        $contractIds = array_column($rows, 'id');
        $customersByContract = [];
        if (!empty($contractIds)) {
            $idList = implode(',', array_map('intval', $contractIds));
            $customersByContract = \yii\helpers\ArrayHelper::map(
                Yii::$app->db->createCommand("SELECT contract_id, client_names FROM {{%vw_contract_customers_names}} WHERE contract_id IN ($idList)")->queryAll(),
                'contract_id', 'client_names'
            );
        }

        $statusMap = [
            'active' => 'نشط', 'settlement' => 'تسوية',
            'judiciary' => 'قضائي', 'legal_department' => 'دائرة قانونية',
        ];

        $exportRows = [];
        foreach ($rows as $r) {
            $exportRows[] = [
                'id'        => $r['id'],
                'customer'  => $customersByContract[$r['id']] ?? '—',
                'installment' => $r['effective_installment'] ?? $r['monthly_installment_value'] ?? 0,
                'due_count' => $r['due_installments'] ?? 0,
                'due_amount' => $r['due_amount'] ?? 0,
                'last_fu'   => ((int)($r['never_followed'] ?? 0) === 1) ? 'لم يُتابع أبداً' : ($r['last_follow_up'] ? date('Y-m-d', strtotime($r['last_follow_up'])) : '—'),
                'reminder'  => $r['reminder'] ?: '—',
                'promise'   => $r['promise_to_pay_at'] ?: '—',
                'status'    => $statusMap[$r['status']] ?? $r['status'],
                'follower'  => $r['follower_name'] ?: '—',
            ];
        }

        return $this->exportArrayData($exportRows, [
            'title'       => 'تقرير المتابعة',
            'filename'    => 'follow_up_report',
            'headers'     => ['#', 'العميل', 'القسط', 'أقساط مستحقة', 'المبلغ المستحق', 'آخر متابعة', 'التذكير', 'وعد بالدفع', 'الحالة', 'المتابع'],
            'keys'        => ['id', 'customer', 'installment', 'due_count', 'due_amount', 'last_fu', 'reminder', 'promise', 'status', 'follower'],
            'widths'      => [10, 22, 14, 14, 16, 16, 14, 14, 14, 16],
            'orientation' => 'L',
        ], $format);
    }

    /**
     * Export no-contact report to Excel.
     */
    private function noContactExportKeys()
    {
        $statusLabels = [
            'active' => 'نشط', 'judiciary' => 'قضاء',
            'legal_department' => 'قانوني', 'settlement' => 'تسوية',
            'finished' => 'منتهي', 'canceled' => 'ملغي',
        ];
        $namesCache = [];
        return [
            'id',
            function ($model) use (&$namesCache) {
                $cid = $model->id;
                if (!isset($namesCache[$cid])) {
                    $namesCache[$cid] = Yii::$app->db->createCommand(
                        "SELECT client_names FROM {{%vw_contract_customers_names}} WHERE contract_id = :id", [':id' => $cid]
                    )->queryScalar() ?: '—';
                }
                return $namesCache[$cid];
            },
            function ($model) { return $model->seller ? $model->seller->name : '—'; },
            'Date_of_sale',
            'total_value',
            'total_paid',
            function ($model) { return max(0, ($model->total_value ?? 0) - ($model->total_paid ?? 0)); },
            function ($model) use ($statusLabels) { return $statusLabels[$model->status] ?? $model->status; },
            function ($model) { return $model->date_time ? date('Y-m-d', strtotime($model->date_time)) : 'لا يوجد'; },
            function ($model) { return $model->followedBy ? $model->followedBy->username : '—'; },
        ];
    }

    public function actionExportNoContactExcel()
    {
        $searchModel = new FollowUpReportSearch();
        $dataProvider = $searchModel->searchNoContact(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'عقود بدون أرقام تواصل',
            'filename' => 'no_contact_contracts',
            'headers' => ['#', 'العميل', 'البائع', 'تاريخ البيع', 'الإجمالي', 'المدفوع', 'المتبقي', 'الحالة', 'آخر متابعة', 'المتابع'],
            'keys' => $this->noContactExportKeys(),
            'widths' => [10, 22, 16, 14, 14, 14, 14, 14, 14, 16],
            'orientation' => 'L',
        ], 'excel');
    }

    public function actionExportNoContactPdf()
    {
        $searchModel = new FollowUpReportSearch();
        $dataProvider = $searchModel->searchNoContact(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'عقود بدون أرقام تواصل',
            'filename' => 'no_contact_contracts',
            'headers' => ['#', 'العميل', 'البائع', 'تاريخ البيع', 'الإجمالي', 'المدفوع', 'المتبقي', 'الحالة', 'آخر متابعة', 'المتابع'],
            'keys' => $this->noContactExportKeys(),
            'orientation' => 'L',
        ], 'pdf');
    }

    /**
     * Finds the FollowUpReport model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return FollowUpReport the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = FollowUpReport::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
