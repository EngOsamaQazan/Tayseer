<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use backend\modules\hr\models\HrWorkZone;
use common\helper\Permissions;

class HrWorkZoneController extends Controller
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
            'query' => HrWorkZone::find()->orderBy(['is_active' => SORT_DESC, 'name' => SORT_ASC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate()
    {
        $model = new HrWorkZone();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'تم إنشاء منطقة العمل بنجاح');
            return $this->redirect(['index']);
        }

        return $this->render('form', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'تم تحديث منطقة العمل بنجاح');
            return $this->redirect(['index']);
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

        Yii::$app->session->setFlash('success', $model->is_active ? 'تم تفعيل المنطقة' : 'تم تعطيل المنطقة');
        return $this->redirect(['index']);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->is_active = 0;
        $model->save(false);

        Yii::$app->session->setFlash('success', 'تم حذف المنطقة');
        return $this->redirect(['index']);
    }

    public function actionGetZoneInfo($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $zone = HrWorkZone::findOne($id);
        if (!$zone) {
            return ['success' => false];
        }
        return [
            'success' => true,
            'zone' => [
                'id' => $zone->id,
                'name' => $zone->name,
                'latitude' => (float)$zone->latitude,
                'longitude' => (float)$zone->longitude,
                'radius_meters' => $zone->radius_meters,
            ],
        ];
    }

    protected function findModel($id)
    {
        $model = HrWorkZone::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('منطقة العمل غير موجودة');
        }
        return $model;
    }
}
