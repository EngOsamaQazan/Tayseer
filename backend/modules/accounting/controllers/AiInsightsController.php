<?php

namespace backend\modules\accounting\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use backend\modules\accounting\helpers\AiInsightsService;
use common\helper\Permissions;

class AiInsightsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_VIEW, Permissions::ACC_REPORTS],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $insights = AiInsightsService::generateInsights();

        $summary = [
            'danger' => 0,
            'warning' => 0,
            'info' => 0,
            'success' => 0,
        ];
        foreach ($insights as $insight) {
            $summary[$insight['severity']] = ($summary[$insight['severity']] ?? 0) + 1;
        }

        return $this->render('index', [
            'insights' => $insights,
            'summary' => $summary,
        ]);
    }
}
