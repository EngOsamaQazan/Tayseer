<?php

namespace backend\modules\sharedExpenses\controllers;

use Yii;
use backend\modules\sharedExpenses\models\SharedExpenseAllocation;
use backend\modules\sharedExpenses\models\SharedExpenseLine;
use backend\modules\sharedExpenses\models\SharedExpenseAllocationSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\helper\Permissions;

class SharedExpenseController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'calculate', 'approve'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::COMPAINES) || Permissions::can(Permissions::COMP_VIEW);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'approve' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new SharedExpenseAllocationSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $totalAllocations = (int) SharedExpenseAllocation::find()->count();
        $totalDistributed = (float) SharedExpenseAllocation::find()->sum('total_amount') ?: 0;
        $draftCount = (int) SharedExpenseAllocation::find()->where(['status' => SharedExpenseAllocation::STATUS_DRAFT])->count();
        $approvedCount = (int) SharedExpenseAllocation::find()->where(['status' => SharedExpenseAllocation::STATUS_APPROVED])->count();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'totalAllocations' => $totalAllocations,
            'totalDistributed' => $totalDistributed,
            'draftCount' => $draftCount,
            'approvedCount' => $approvedCount,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new SharedExpenseAllocation();
        $model->status = SharedExpenseAllocation::STATUS_DRAFT;

        if ($model->load(Yii::$app->request->post())) {
            $model->created_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل حفظ التوزيع');
                }

                $linesData = Yii::$app->request->post('Lines', []);
                foreach ($linesData as $lineData) {
                    if (empty($lineData['company_id'])) continue;
                    $line = new SharedExpenseLine();
                    $line->allocation_id = $model->id;
                    $line->company_id = (int) $lineData['company_id'];
                    $line->metric_value = isset($lineData['metric_value']) ? (float) $lineData['metric_value'] : 0;
                    $line->percentage = isset($lineData['percentage']) ? (float) $lineData['percentage'] : 0;
                    $line->allocated_amount = isset($lineData['allocated_amount']) ? (float) $lineData['allocated_amount'] : 0;
                    if (!$line->save()) {
                        throw new \Exception('فشل حفظ سطر التوزيع');
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء التوزيع بنجاح');
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->status === SharedExpenseAllocation::STATUS_APPROVED) {
            Yii::$app->session->setFlash('error', 'لا يمكن تعديل توزيع معتمد');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل حفظ التوزيع');
                }

                SharedExpenseLine::deleteAll(['allocation_id' => $model->id]);

                $linesData = Yii::$app->request->post('Lines', []);
                foreach ($linesData as $lineData) {
                    if (empty($lineData['company_id'])) continue;
                    $line = new SharedExpenseLine();
                    $line->allocation_id = $model->id;
                    $line->company_id = (int) $lineData['company_id'];
                    $line->metric_value = isset($lineData['metric_value']) ? (float) $lineData['metric_value'] : 0;
                    $line->percentage = isset($lineData['percentage']) ? (float) $lineData['percentage'] : 0;
                    $line->allocated_amount = isset($lineData['allocated_amount']) ? (float) $lineData['allocated_amount'] : 0;
                    if (!$line->save()) {
                        throw new \Exception('فشل حفظ سطر التوزيع');
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم تحديث التوزيع بنجاح');
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if ($model->status === SharedExpenseAllocation::STATUS_APPROVED) {
            Yii::$app->session->setFlash('error', 'لا يمكن حذف توزيع معتمد');
            return $this->redirect(['index']);
        }

        SharedExpenseLine::deleteAll(['allocation_id' => $model->id]);
        $model->delete();
        Yii::$app->session->setFlash('success', 'تم حذف التوزيع بنجاح');

        return $this->redirect(['index']);
    }

    public function actionCalculate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $method = Yii::$app->request->post('method');
        $totalAmount = (float) Yii::$app->request->post('total_amount', 0);

        if (empty($method) || $totalAmount <= 0) {
            return ['success' => false, 'message' => 'يرجى تحديد طريقة التوزيع والمبلغ'];
        }

        $model = new SharedExpenseAllocation();
        $model->allocation_method = $method;
        $model->total_amount = $totalAmount;

        $lines = $model->calculateAllocation();

        return ['success' => true, 'lines' => $lines];
    }

    public function actionApprove($id)
    {
        $model = $this->findModel($id);
        $model->status = SharedExpenseAllocation::STATUS_APPROVED;
        $model->approved_by = Yii::$app->user->id;
        $model->approved_at = time();

        if ($model->save(false)) {
            Yii::$app->session->setFlash('success', 'تم اعتماد التوزيع بنجاح');
        } else {
            Yii::$app->session->setFlash('error', 'فشل اعتماد التوزيع');
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    protected function findModel($id)
    {
        if (($model = SharedExpenseAllocation::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة.');
    }
}
