<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use backend\modules\hr\models\HrWorkShift;
use common\helper\Permissions;

class HrShiftController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::hasAnyPermission(Permissions::getHrPermissions());
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'toggle-active' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => HrWorkShift::find()->orderBy(['is_active' => SORT_DESC, 'title' => SORT_ASC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate()
    {
        $model = new HrWorkShift();

        if ($model->load(Yii::$app->request->post())) {
            $model->working_days = Yii::$app->request->post('HrWorkShift')['working_days'] ?? null;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'تم إنشاء الوردية بنجاح');
                return $this->redirect(['index']);
            }
        }

        return $this->render('form', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $model->working_days = Yii::$app->request->post('HrWorkShift')['working_days'] ?? null;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'تم تحديث الوردية بنجاح');
                return $this->redirect(['index']);
            }
        }

        return $this->render('form', [
            'model' => $model,
        ]);
    }

    public function actionToggleActive($id)
    {
        $model = $this->findModel($id);
        $model->is_active = $model->is_active ? 0 : 1;
        $model->save(false);

        Yii::$app->session->setFlash('success', $model->is_active ? 'تم تفعيل الوردية' : 'تم تعطيل الوردية');
        return $this->redirect(['index']);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->is_active = 0;
        $model->save(false);

        Yii::$app->session->setFlash('success', 'تم حذف الوردية');
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        $model = HrWorkShift::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('الوردية غير موجودة');
        }
        return $model;
    }
}
