<?php

namespace backend\modules\customers\controllers;

use common\helper\Permissions;
use common\models\FahrasCheckLog;
use common\models\FahrasCheckLogSearch;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Audit log for every Fahras verdict request issued from this Tayseer
 * install — including manager overrides. Read-only on purpose: nothing
 * in the UI may mutate or delete an audit row (defence in depth against
 * "we'll just edit the log, no one will know").
 *
 * Permission: {@see Permissions::CUST_FAHRAS_LOG_VIEW}.
 */
class FahrasLogController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view'],
                        'allow'   => true,
                        'roles'   => ['@'],
                        'matchCallback' => static function () {
                            return Permissions::can(Permissions::CUST_FAHRAS_LOG_VIEW);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'view' => ['GET'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel  = new FahrasCheckLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id)
    {
        $model = FahrasCheckLog::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('السجل المطلوب غير موجود.');
        }
        return $this->render('view', ['model' => $model]);
    }
}
