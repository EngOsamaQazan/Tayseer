<?php

namespace backend\modules\accounting\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use backend\modules\accounting\models\FiscalYear;
use backend\modules\accounting\models\FiscalPeriod;
use common\helper\Permissions;

class FiscalYearController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_VIEW],
                    ],
                    [
                        'actions' => ['create', 'update', 'close-period', 'close-year'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_FISCAL_MANAGE],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'close-period' => ['post'],
                    'close-year' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => FiscalYear::find()->orderBy(['start_date' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $this->render('view', ['model' => $model]);
    }

    public function actionCreate()
    {
        $model = new FiscalYear();

        if ($model->load(Yii::$app->request->post())) {
            $model->created_by = Yii::$app->user->id;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'تم إنشاء السنة المالية وتوليد الفترات بنجاح');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->status !== FiscalYear::STATUS_OPEN) {
            Yii::$app->session->setFlash('error', 'لا يمكن تعديل سنة مالية مغلقة');
            return $this->redirect(['view', 'id' => $id]);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'تم تحديث السنة المالية بنجاح');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionClosePeriod($id)
    {
        $period = FiscalPeriod::findOne($id);
        if (!$period) {
            throw new NotFoundHttpException('الفترة غير موجودة');
        }
        $period->status = FiscalPeriod::STATUS_CLOSED;
        $period->save(false, ['status']);
        Yii::$app->session->setFlash('success', 'تم إغلاق الفترة: ' . $period->name);
        return $this->redirect(['view', 'id' => $period->fiscal_year_id]);
    }

    public function actionCloseYear($id)
    {
        $model = $this->findModel($id);
        $model->status = FiscalYear::STATUS_CLOSED;
        $model->save(false, ['status']);

        FiscalPeriod::updateAll(
            ['status' => FiscalPeriod::STATUS_CLOSED],
            ['fiscal_year_id' => $id]
        );

        Yii::$app->session->setFlash('success', 'تم إغلاق السنة المالية وجميع فتراتها');
        return $this->redirect(['view', 'id' => $id]);
    }

    protected function findModel($id)
    {
        if (($model = FiscalYear::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة');
    }
}
