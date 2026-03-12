<?php

namespace api\modules\v1\controllers;

use common\models\User;
use common\models\UserSearch;
use Yii;
use yii\rest\Controller;
use yii\filters\AccessControl;
use api\helpers\ApiResponse;
use api\helpers\Errors;
use api\helpers\Messages;

class UserController extends Controller
{

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [
                    'class' => 'api\helpers\ApiAccessRule'
                ],
                'rules' => [
                    [
                        'actions' => [
                            'index', 'view', 'create', 'update', 'delete'
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return ApiResponse::get(200, $dataProvider->getModels());
    }

    public function actionCreate()
    {
        $model = new User();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                $model->save();
                return ApiResponse::get(200);
            }
            return ApiResponse::get(301, null, Errors::prepar($model->getErrors()));
        }
        return ApiResponse::get(301, null, Messages::t('No data provided'));
    }

}
