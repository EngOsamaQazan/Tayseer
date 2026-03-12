<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use common\helper\Permissions;

class HrReportController extends Controller
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
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionAttendance()
    {
        return $this->redirect(['/hr/hr-tracking-report/index']);
    }

    public function actionPayroll()
    {
        return $this->redirect(['/hr/hr-payroll/index']);
    }

    public function actionHeadcount()
    {
        return $this->redirect(['/hr/hr-employee/index']);
    }

    public function actionLeave()
    {
        return $this->redirect(['/hr/hr-leave/index']);
    }

    public function actionField()
    {
        return $this->redirect(['/hr/hr-tracking-api/live-map']);
    }

    public function actionPerformance()
    {
        return $this->redirect(['/hr/hr-tracking-report/punctuality']);
    }
}
