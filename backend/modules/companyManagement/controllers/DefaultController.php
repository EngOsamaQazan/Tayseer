<?php

namespace backend\modules\companyManagement\controllers;

use Yii;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use backend\modules\companyManagement\models\Company;
use backend\modules\companyManagement\models\CompanyForm;
use common\helper\Permissions;

class DefaultController extends Controller
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
                            return Yii::$app->user->can(Permissions::COMPANY_MANAGEMENT);
                        },
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Company::find()->orderBy(['id' => SORT_ASC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate()
    {
        $form = new CompanyForm();

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            $company = new Company();
            $company->slug           = $form->slug;
            $company->name_ar        = $form->name_ar;
            $company->name_en        = $form->name_en;
            $company->domain         = $form->getDomain();
            $company->db_name        = $form->getDbName();
            $company->sms_sender     = $form->sms_sender ?: strtoupper($form->slug);
            $company->sms_user       = $form->sms_user ?: ($form->slug . 'SMS');
            $company->sms_pass       = $form->sms_pass;
            $company->og_title       = 'نظام تيسير — ' . $form->name_ar;
            $company->og_description = 'نظام إدارة التقسيط والأعمال المتكامل — ' . $form->name_ar;
            $company->og_image       = '/img/og-' . $form->slug . '.png';
            $company->status         = 'pending';
            $company->appendLog('تم إنشاء سجل الشركة');

            if ($company->save()) {
                Yii::$app->session->setFlash('success', 'تم إنشاء الشركة بنجاح. يمكنك الآن بدء التجهيز.');
                return $this->redirect(['view', 'id' => $company->id]);
            }

            Yii::$app->session->setFlash('error', 'فشل حفظ الشركة: ' . implode(', ', $company->getFirstErrors()));
        }

        return $this->render('create', [
            'model' => $form,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'تم تحديث بيانات الشركة');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    protected function findModel($id): Company
    {
        if (($model = Company::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الشركة غير موجودة');
    }
}
