<?php

namespace backend\modules\collection\controllers;

use backend\modules\divisionsCollection\models\DivisionsCollection;
use Yii;
use backend\modules\collection\models\Collection;
use backend\modules\collection\models\CollectionSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use common\helper\LoanContract;
use \backend\modules\loanScheduling\models\LoanScheduling;
use \backend\modules\contracts\model\Contracts;
use common\helper\Permissions;
use backend\helpers\ExportTrait;

/**
 * CollectionController implements the CRUD actions for Collection model.
 */
class CollectionController extends Controller
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
                    ['actions' => ['logout'], 'allow' => true, 'roles' => ['@']],
                    [
                        'actions' => ['index', 'view', 'export-excel', 'export-pdf', 'export-view-excel', 'export-view-pdf'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::COLL_VIEW);
                        },
                    ],
                    [
                        'actions' => ['create', 'find-custamers'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::COLL_CREATE);
                        },
                    ],
                    [
                        'actions' => ['update', 'update-amount'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::COLL_UPDATE);
                        },
                    ],
                    [
                        'actions' => ['delete', 'bulk-delete'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::COLL_DELETE);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Collection models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CollectionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $model = new Collection();
        $amount = $model->availableToCatch();
        $count_contract = $model->numberResolvingIssues();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'amount' => $amount,
            'count_contract' => $count_contract
        ]);
    }


    /**
     * Displays a single Collection model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $custamerName = $model->findCustamer($model->custamers_id);
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "Collection #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                'model' => $model,
                'custamer_name' => $custamerName
            ]);
        }
    }

    /**
     * Creates a new Collection model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($contract_id)
    {
        $request = Yii::$app->request;
        $model = new Collection();
        $totle_value_price = 0;
        $totle_value = new LoanContract();
        $totle_value = $totle_value->findContract($contract_id);
        if ($totle_value->status == 'judiciary') {
            if ($totle_value->is_loan == 1) {
                $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $totle_value->id])->where(['>=', 'created_at', $totle_value->created_at])->orderBy(['contract_id' => SORT_DESC])->one();
            } else {
                $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $totle_value->id])->orderBy(['contract_id' => SORT_DESC])->one();
            }
            if (!empty($cost)) {
                $totle_value_price = $totle_value->total_value + $cost->case_cost + $cost->lawyer_cost;
            } else {
                $totle_value_price = $totle_value->total_value;
            }

        } else {
            $totle_value_price = $totle_value->total_value;
        }

        if ($model->load($request->post())) {
            $model->contract_id = $contract_id;
            $model->total_amount = $totle_value_price;
            $model->save();
            $total_month = ceil($model->total_amount / $model->amount);

            $month = date('m', strtotime($model->date));
            $year = date('Y', strtotime($model->date));
            $all_amount = 0;
            for ($count = 1; $count <= $total_month; $count++) {
                $all_amount = $all_amount + $model->amount;
                if ($all_amount > $totle_value_price) {
                    $amount = ($all_amount - $totle_value_price);
                    $amount = $model->amount - $amount;
                } else {
                    $amount = $model->amount;
                }
                $divisionsCollection = new DivisionsCollection();
                $divisionsCollection->amount = $amount;
                $divisionsCollection->collection_id = $model->id;
                $divisionsCollection->month = $month;
                $divisionsCollection->year = $year;
                if ($month <= 11) {
                    $month = $month + 1;
                } else {
                    $month = 1;
                    $year = $year + 1;
                }

                $divisionsCollection->save();
            }

            return $this->redirect(['index']);
        } else {
            return $this->render('create', [
                'model' => $model,
                'contract_id' => $contract_id,
                'totle_value_price' => $totle_value_price
            ]);
        }

    }

    /**
     * Updates an existing Collection model.
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
            $dleted_models = DivisionsCollection::find()->Where(['collection_id' => $id])->all();

            foreach ($dleted_models as $dleted_model) {
                $dleted_model->delete();
            }
            $total_month = ceil($model->total_amount / $model->amount);
            $month = date('m', strtotime($model->date));
            $year = date('Y', strtotime($model->date));
            $all_amount = 0;
            for ($count = 1; $count <= $total_month; $count++) {
                $all_amount = $all_amount + $model->amount;
                if ($all_amount > $model->total_amount) {
                    $amount = ($all_amount - $model->total_amount);
                    $amount = $model->amount - $amount;
                } else {
                    $amount = $model->amount;
                }
                $divisionsCollection = new DivisionsCollection();
                $divisionsCollection->amount = $amount;
                $divisionsCollection->collection_id = $model->id;
                $divisionsCollection->month = $month;
                $divisionsCollection->year = $year;
                if ($month <= 11) {
                    $month = $month + 1;
                } else {
                    $month = 1;
                    $year = $year + 1;
                }
                $divisionsCollection->save();
            }
            return $this->redirect(['index']);
        } else {
            return $this->render('update', [
                'model' => $model,
                'contract_id' => $model->contract_id
            ]);
        }

    }

    /**
     * Delete an existing Collection model.
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
     * Delete multiple existing Collection model.
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
     * Export index (Collection list) to Excel.
     */
    public function actionExportExcel()
    {
        $searchModel = new CollectionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'التحصيلات',
            'filename' => 'collections',
            'headers' => ['رقم العقد', 'التاريخ', 'المبلغ', 'ملاحظات', 'أنشئ بواسطة', 'المتاح للقبض'],
            'keys' => [
                'contract_id',
                'date',
                'amount',
                'notes',
                'createdBy.username',
                function ($model) {
                    $d1 = new \DateTime($model->date);
                    $d2 = new \DateTime(date('Y-m-d'));
                    $interval = $d1->diff($d2);
                    $diffInMonths = $interval->m + 1;
                    $revares_courts = \backend\modules\financialTransaction\models\FinancialTransaction::find()
                        ->where(['contract_id' => $model->contract_id])
                        ->andWhere(['income_type' => 11])->all();
                    $revares = 0;
                    foreach ($revares_courts as $revares_court) {
                        $revares += $revares_court->amount;
                    }
                    return ($diffInMonths * $model->amount) - $revares;
                },
            ],
            'widths' => [14, 14, 14, 25, 16, 16],
        ], 'excel');
    }

    /**
     * Export index (Collection list) to PDF.
     */
    public function actionExportPdf()
    {
        $searchModel = new CollectionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'التحصيلات',
            'filename' => 'collections',
            'headers' => ['رقم العقد', 'التاريخ', 'المبلغ', 'ملاحظات', 'أنشئ بواسطة', 'المتاح للقبض'],
            'keys' => [
                'contract_id',
                'date',
                'amount',
                'notes',
                'createdBy.username',
                function ($model) {
                    $d1 = new \DateTime($model->date);
                    $d2 = new \DateTime(date('Y-m-d'));
                    $interval = $d1->diff($d2);
                    $diffInMonths = $interval->m + 1;
                    $revares_courts = \backend\modules\financialTransaction\models\FinancialTransaction::find()
                        ->where(['contract_id' => $model->contract_id])
                        ->andWhere(['income_type' => 11])->all();
                    $revares = 0;
                    foreach ($revares_courts as $revares_court) {
                        $revares += $revares_court->amount;
                    }
                    return ($diffInMonths * $model->amount) - $revares;
                },
            ],
        ], 'pdf');
    }

    /**
     * Export DivisionsCollection (view/form grid) to Excel.
     */
    public function actionExportViewExcel($id)
    {
        $model = $this->findModel($id);
        $query = \backend\modules\divisionsCollection\models\DivisionsCollection::find()
            ->where(['collection_id' => $model->id]);
        $dataProvider = new \yii\data\ActiveDataProvider(['query' => $query]);

        return $this->exportData($dataProvider, [
            'title' => 'أقساط التحصيل #' . $model->id,
            'filename' => 'collection_divisions_' . $model->id,
            'headers' => ['رقم التحصيل', 'الشهر', 'السنة', 'المبلغ'],
            'keys' => ['collection_id', 'month', 'year', 'amount'],
            'widths' => [16, 12, 12, 14],
        ], 'excel');
    }

    /**
     * Export DivisionsCollection (view/form grid) to PDF.
     */
    public function actionExportViewPdf($id)
    {
        $model = $this->findModel($id);
        $query = \backend\modules\divisionsCollection\models\DivisionsCollection::find()
            ->where(['collection_id' => $model->id]);
        $dataProvider = new \yii\data\ActiveDataProvider(['query' => $query]);

        return $this->exportData($dataProvider, [
            'title' => 'أقساط التحصيل #' . $model->id,
            'filename' => 'collection_divisions_' . $model->id,
            'headers' => ['رقم التحصيل', 'الشهر', 'السنة', 'المبلغ'],
            'keys' => ['collection_id', 'month', 'year', 'amount'],
        ], 'pdf');
    }

    /**
     * Finds the Collection model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Collection the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Collection::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionFindCustamers()
    {
        $id = (int)Yii::$app->request->post('id');
        $names = \backend\modules\customers\models\Customers::find()
            ->innerJoin('os_contracts_customers', 'os_customers.id = os_contracts_customers.customer_id')
            ->where(['os_contracts_customers.contract_id' => $id])
            ->select(['os_customers.name'])
            ->column();
        return json_encode($names);
    }

    public function actionUpdateAmount()
    {
        $new_amount = (int)(Yii::$app->request->post('amount'));
        $id = (int)(Yii::$app->request->post('amount_id'));
        $collection_id = (int)(Yii::$app->request->post('collection_id'));
        $contract_id = (int)(Yii::$app->request->post('contract_id'));
        $month = (int)(Yii::$app->request->post('month'));
        $year = (int)(Yii::$app->request->post('year'));
        $total_amount = (int)(Yii::$app->request->post('total_amount'));
        Collection::updateAll(['amount' => $new_amount], ['id' => $collection_id]);

        $amount_after_change_models = DivisionsCollection::find()->where(['<', 'id', $id])->andWhere(['collection_id' => $collection_id])->all();
        $amount_after_change = 0;
        foreach ($amount_after_change_models as $amount_after_change_model) {

            $amount_after_change = $amount_after_change + $amount_after_change_model->amount;
        }
        if (!empty($new_amount)) {
            $totlal_manth = ceil(($total_amount - $amount_after_change) / $new_amount);
        }


        $dleted_models = DivisionsCollection::find()->where(['>=', 'id', $id])->andWhere(['collection_id' => $collection_id])->all();

        foreach ($dleted_models as $dleted_model) {
            $dleted_model->delete();
        }
        $a = $totlal_manth;
        $all_amount = 0;
        for ($count = 1; $count <= $a; $count++) {
            $all_amount = $all_amount + $new_amount;
            $aa = $all_amount + $amount_after_change;
            if ($aa > $total_amount) {
                $aa = $aa - $new_amount;
                $deferent = 1;
                for ($c = 0; $c < $total_amount; $c++) {
                    $aa = $aa + 1;
                    $deferent = $deferent + 1;
                    if ($aa == $total_amount) {
                        break;
                    }
                    $amount = $deferent;
                }
            } elseif ($all_amount > $total_amount) {
                $amount = ($all_amount - $total_amount);
                $amount = $new_amount - $amount;
            } else {
                $amount = $new_amount;
            }
            $divisionsCollection = new DivisionsCollection();
            $divisionsCollection->amount = $amount;
            $divisionsCollection->collection_id = $collection_id;
            $divisionsCollection->month = $month;
            $divisionsCollection->year = $year;
            if ($month <= 11) {
                $month = $month + 1;
            } else {
                $month = 1;
                $year = $year + 1;

            }
            $divisionsCollection->save();
        }

    }

}
