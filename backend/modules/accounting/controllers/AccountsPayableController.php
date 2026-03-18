<?php

namespace backend\modules\accounting\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use backend\modules\accounting\models\Payable;
use backend\modules\accounting\models\PayableSearch;
use common\helper\Permissions;

class AccountsPayableController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'aging-report'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_VIEW, Permissions::ACC_AP_MANAGE],
                    ],
                    [
                        'actions' => ['create', 'update', 'record-payment'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_AP_MANAGE],
                    ],
                    [
                        'actions' => ['delete'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_DELETE],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'record-payment' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new PayableSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $stats = [
            'total' => Payable::find()->sum('amount') ?: 0,
            'paid' => Payable::find()->sum('paid_amount') ?: 0,
            'balance' => Payable::find()->sum('balance') ?: 0,
            'overdue' => Payable::find()->where(['status' => 'overdue'])->sum('balance') ?: 0,
            'count_open' => Payable::find()->where(['status' => ['open', 'partial', 'overdue']])->count(),
        ];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'stats' => $stats,
        ]);
    }

    public function actionCreate()
    {
        $model = new Payable();

        if ($model->load(Yii::$app->request->post())) {
            $model->created_by = Yii::$app->user->id;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'تم إنشاء الذمة الدائنة بنجاح');
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->status === Payable::STATUS_PAID) {
            Yii::$app->session->setFlash('error', 'لا يمكن تعديل ذمة مدفوعة بالكامل');
            return $this->redirect(['index']);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'تم تحديث الذمة الدائنة');
            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionRecordPayment($id)
    {
        $model = $this->findModel($id);
        $amount = (float)Yii::$app->request->post('payment_amount', 0);

        if ($amount > 0 && $amount <= $model->balance) {
            if ($model->recordPayment($amount)) {
                Yii::$app->session->setFlash('success', 'تم تسجيل الدفعة: ' . number_format($amount, 2));
            }
        } else {
            Yii::$app->session->setFlash('error', 'مبلغ الدفعة غير صالح');
        }

        return $this->redirect(['index']);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if ($model->paid_amount > 0) {
            Yii::$app->session->setFlash('error', 'لا يمكن حذف ذمة عليها مدفوعات');
        } else {
            $model->delete();
            Yii::$app->session->setFlash('success', 'تم حذف الذمة الدائنة');
        }
        return $this->redirect(['index']);
    }

    public function actionAgingReport()
    {
        $payables = Payable::find()
            ->with(['account'])
            ->where(['!=', 'status', Payable::STATUS_PAID])
            ->orderBy(['due_date' => SORT_ASC])
            ->all();

        $aging = ['جاري' => 0, '1-30 يوم' => 0, '31-60 يوم' => 0, '61-90 يوم' => 0, '+90 يوم' => 0];
        foreach ($payables as $p) {
            $bucket = $p->getAgingBucket();
            $aging[$bucket] = ($aging[$bucket] ?? 0) + (float)$p->balance;
        }

        return $this->render('aging-report', [
            'payables' => $payables,
            'aging' => $aging,
        ]);
    }

    protected function findModel($id)
    {
        if (($model = Payable::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة');
    }
}
