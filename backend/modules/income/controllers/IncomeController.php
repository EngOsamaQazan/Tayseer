<?php

namespace backend\modules\income\controllers;

use common\models\Expenses;
use backend\modules\financialTransaction\models\FinancialTransaction;
use Yii;
use backend\modules\income\models\Income;
use backend\modules\income\models\IncomeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use common\helper\Permissions;
use backend\helpers\ExportTrait;

/**
 * IncomeController implements the CRUD actions for Installment model.
 */
class IncomeController extends Controller
{
    use ExportTrait;
    public $customer_ids;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    ['actions' => ['login', 'error'], 'allow' => true],
                    /* ═══ عرض ═══ */
                    [
                        'actions' => ['income-list', 'index', 'view', 'export-excel', 'export-pdf'],
                        'allow'   => true,
                        'roles'   => [Permissions::INC_VIEW],
                    ],
                    /* ═══ إضافة ═══ */
                    [
                        'actions' => ['create'],
                        'allow'   => true,
                        'roles'   => [Permissions::INC_CREATE],
                    ],
                    /* ═══ تعديل ═══ */
                    [
                        'actions' => ['update', 'update-income'],
                        'allow'   => true,
                        'roles'   => [Permissions::INC_EDIT],
                    ],
                    /* ═══ حذف ═══ */
                    [
                        'actions' => ['delete', 'bulkdelete'],
                        'allow'   => true,
                        'roles'   => [Permissions::INC_DELETE],
                    ],
                    /* ═══ إرجاع للحركات المالية ═══ */
                    [
                        'actions' => ['back-to-financial-transaction'],
                        'allow'   => true,
                        'roles'   => [Permissions::INC_REVERT],
                    ],
                    /* ═══ تسجيل خروج ═══ */
                    ['actions' => ['logout'], 'allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                    'delete' => ['post'],
                    'bulkdelete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Installment models.
     * @return mixed
     */
    public function actionIndex($customer_id)
    {
        $customer_ids = $customer_id;
        $searchModel = new IncomeSearch();
//        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $query = Income::find()->andFilterWhere(['customer_id' => $customer_id]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $incomeSummary = Income::findOne(['customer_id' => $customer_id]);


        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'incomeSummary' => $incomeSummary,
            'customer_id' => $customer_id
        ]);
    }


    /**
     * Displays a single Installment model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "Installment #" . $id,
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
     * Creates a new Installment model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Income();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Create new Installment",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "Create new Installment",
                    'content' => '<span class="text-success">Create Installment success</span>',
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Create More', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])

                ];
            } else {
                return [
                    'title' => "Create new Installment",
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
                Yii::$app->cache->set(Yii::$app->params['key_income_by'],Yii::$app->db->createCommand(Yii::$app->params['income_by_query'])->queryAll(), Yii::$app->params['time_duration']);
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
        }

    }

    /**
     * Updates an existing Installment model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);


        if ($model->load($request->post()) && $model->save()) {
            Yii::$app->cache->set(Yii::$app->params['key_income_by'],Yii::$app->db->createCommand(Yii::$app->params['income_by_query'])->queryAll(), Yii::$app->params['time_duration']);
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }

    }

    /**
     * Delete an existing Installment model.
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
     * Delete multiple existing Installment model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkdelete()
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

    public function actionExportExcel($customer_id)
    {
        return $this->exportIncomeLightweight($customer_id, 'excel');
    }

    public function actionExportPdf($customer_id)
    {
        return $this->exportIncomeLightweight($customer_id, 'pdf');
    }

    private function exportIncomeLightweight($customer_id, $format)
    {
        $query = (new \yii\db\Query())
            ->select([
                'i.id', 'i.date', 'i.cheque_number', 'i.notes', 'i.total',
                'item_name' => 'it.name',
                'customer_name' => 'c.name',
            ])
            ->from('{{%income}} i')
            ->leftJoin('{{%items}} it', 'it.id = i.item_id')
            ->leftJoin('{{%customers}} c', 'c.id = i.customer_id')
            ->andWhere(['or', ['i.is_deleted' => 0], ['i.is_deleted' => null]]);

        if (!empty($customer_id)) {
            $query->andWhere(['i.customer_id' => $customer_id]);
        }

        $rows = $query->all();

        $exportRows = [];
        foreach ($rows as $r) {
            $exportRows[] = [
                'item'     => $r['item_name'] ?: '—',
                'customer' => $r['customer_name'] ?: '—',
                'date'     => $r['date'] ?: '—',
                'cheque'   => $r['cheque_number'] ?: '—',
                'notes'    => $r['notes'] ?: '—',
                'total'    => $r['total'] ?: 0,
            ];
        }

        return $this->exportArrayData($exportRows, [
            'title'    => 'الأقساط',
            'filename' => 'income',
            'headers'  => ['#', 'اسم الصنف', 'اسم العميل', 'التاريخ', 'رقم الشيك', 'ملاحظات', 'الإجمالي'],
            'keys'     => ['#', 'item', 'customer', 'date', 'cheque', 'notes', 'total'],
            'widths'   => [8, 22, 22, 14, 16, 25, 14],
        ], $format);
    }

    /**
     * Finds the Installment model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Income the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Income::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionIncomeList()
    {
        $searchModel  = new IncomeSearch();
        $dataProvider = $searchModel->incomeListSearch(Yii::$app->request->queryParams);

        /* ═══ Eager loading للعلاقات — تجنب N+1 queries ═══ */
        $dataProvider->query->with(['contract', 'created', 'incomeCategory', 'paymentType']);

        /* ═══ حساب الإجماليات من الاستعلام المفلتر ═══ */
        $summary = (clone $dataProvider->query)
            ->select([
                'COALESCE(SUM({{%income}}.amount), 0) AS total_amount',
                'COUNT({{%income}}.id) AS total_count',
            ])
            ->createCommand()
            ->queryOne();

        return $this->render('income-item-list', [
            'searchModel'  => $searchModel,
            'dataProvider'  => $dataProvider,
            'totalAmount'   => (float)($summary['total_amount'] ?? 0),
            'totalCount'    => (int)($summary['total_count'] ?? 0),
        ]);
    }

    public function actionBackToFinancialTransaction($id, $financial)
    {
        if (!empty($id) && !empty($financial)) {
            Income::deleteAll(['id' => $id]);
            FinancialTransaction::updateAll(['is_transfer' => 0], ['id' => $financial]);
        }

        $this->redirect(['income-list']);

    }

    public function actionUpdateIncome($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);


        if ($model->load($request->post()) && $model->save()) {
            $this->redirect(['income-list']);
        } else {
            return $this->render('income_list_form', [
                'model' => $model,
            ]);
        }
        
    }

}
