<?php

namespace backend\modules\companyManagement\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use backend\modules\companyManagement\models\Company;
use backend\modules\companyManagement\models\ProvisionService;
use common\helper\Permissions;

class ProvisionController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => fn() => Yii::$app->user->can(Permissions::COMPANY_MANAGEMENT),
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'run-step' => ['POST'],
                ],
            ],
        ];
    }

    public function actionRunStep()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id   = Yii::$app->request->post('company_id');
        $step = Yii::$app->request->post('step');

        $company = Company::findOne($id);
        if (!$company) {
            return ['success' => false, 'message' => 'الشركة غير موجودة'];
        }

        $adminData = Yii::$app->request->post('admin', []);
        $service = new ProvisionService($company, $adminData);

        try {
            $result = $service->runStep($step);
            $company->save(false);
            return $result;
        } catch (\Exception $e) {
            $company->appendLog("خطأ في {$step}: " . $e->getMessage());
            $company->save(false);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionStatus($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $company = Company::findOne($id);
        if (!$company) {
            return ['error' => 'not found'];
        }

        return [
            'status' => $company->status,
            'log'    => $company->provision_log,
        ];
    }
}
