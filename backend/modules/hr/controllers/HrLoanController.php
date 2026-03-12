<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use backend\modules\hr\models\HrLoan;
use common\models\User;
use common\helper\Permissions;

/**
 * HrLoanController — Loan and advance management
 * يتطلب أحد صلاحيات الموارد البشرية.
 */
class HrLoanController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
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
                ],
            ],
        ];
    }

    /**
     * List loans.
     *
     * @return string
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $filterStatus = $request->get('status', '');
        $filterType = $request->get('loan_type', '');
        $filterEmployee = $request->get('user_id', '');

        $query = HrLoan::find()
            ->where(['is_deleted' => 0]);

        if (!empty($filterStatus)) {
            $query->andWhere(['status' => $filterStatus]);
        }
        if (!empty($filterType)) {
            $query->andWhere(['loan_type' => $filterType]);
        }
        if (!empty($filterEmployee)) {
            $query->andWhere(['user_id' => $filterEmployee]);
        }

        $query->orderBy(['created_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        // Summary statistics
        $stats = Yii::$app->db->createCommand("
            SELECT
                COUNT(*) as total_loans,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_loans,
                SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END) as total_active_amount,
                SUM(CASE WHEN status = 'active' THEN (amount - repaid) ELSE 0 END) as total_outstanding
            FROM {{%hr_loan}}
            WHERE is_deleted = 0
        ")->queryOne();

        $employees = ArrayHelper::map(
            User::find()->where(['IS', 'blocked_at', null])->orderBy(['username' => SORT_ASC])->asArray()->all(),
            'id',
            'username'
        );

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'filterStatus' => $filterStatus,
            'filterType' => $filterType,
            'filterEmployee' => $filterEmployee,
            'stats' => $stats,
            'employees' => $employees,
        ]);
    }

    /**
     * Create new loan/advance.
     *
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new HrLoan();
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->repaid = 0;
            $model->status = 'active'; // Will require approval in practice
            $model->remaining_installments = $model->installments;

            // Auto-calculate monthly deduction if not provided
            if (empty($model->monthly_deduction) && $model->amount > 0 && $model->installments > 0) {
                $model->monthly_deduction = round($model->amount / $model->installments, 2);
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل إنشاء القرض/السلفة: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء القرض/السلفة بنجاح.');

                if ($request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => true, 'message' => 'تم إنشاء القرض/السلفة بنجاح.', 'id' => $model->id];
                }

                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                if ($request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => false, 'message' => $e->getMessage()];
                }
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        $employees = ArrayHelper::map(
            User::find()->where(['IS', 'blocked_at', null])->orderBy(['username' => SORT_ASC])->asArray()->all(),
            'id',
            'username'
        );

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'إنشاء قرض/سلفة',
                'content' => $this->renderAjax('form', [
                    'model' => $model,
                    'employees' => $employees,
                ]),
            ];
        }

        return $this->render('form', [
            'model' => $model,
            'employees' => $employees,
        ]);
    }

    /**
     * Update loan.
     *
     * @param int $id
     * @return string|Response
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        // Only allow edits if loan is still active
        if ($model->status === 'completed') {
            Yii::$app->session->setFlash('warning', 'لا يمكن تعديل قرض مكتمل.');
            return $this->redirect(['view', 'id' => $id]);
        }

        if ($model->load($request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل تحديث القرض: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم تحديث القرض بنجاح.');

                if ($request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => true, 'message' => 'تم تحديث القرض بنجاح.'];
                }

                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                if ($request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => false, 'message' => $e->getMessage()];
                }
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        $employees = ArrayHelper::map(
            User::find()->where(['IS', 'blocked_at', null])->orderBy(['username' => SORT_ASC])->asArray()->all(),
            'id',
            'username'
        );

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'تعديل القرض/السلفة',
                'content' => $this->renderAjax('form', [
                    'model' => $model,
                    'employees' => $employees,
                ]),
            ];
        }

        return $this->render('form', [
            'model' => $model,
            'employees' => $employees,
        ]);
    }

    /**
     * Approve loan.
     *
     * @param int $id
     * @return array|Response
     */
    public function actionApprove($id)
    {
        $model = $this->findModel($id);

        if ($model->approved_by) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'تم اعتماد هذا القرض مسبقاً.'];
            }
            Yii::$app->session->setFlash('warning', 'تم اعتماد هذا القرض مسبقاً.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model->approved_by = Yii::$app->user->id;
            $model->approved_at = time();
            $model->status = 'active';

            if (!$model->save()) {
                throw new \Exception('فشل اعتماد القرض: ' . implode(', ', $model->getFirstErrors()));
            }

            $transaction->commit();

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => true, 'message' => 'تم اعتماد القرض بنجاح.'];
            }

            Yii::$app->session->setFlash('success', 'تم اعتماد القرض بنجاح.');
            return $this->redirect(['view', 'id' => $id]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => $e->getMessage()];
            }
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(['view', 'id' => $id]);
        }
    }

    /**
     * View loan detail.
     *
     * @param int $id
     * @return string
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $employee = $model->user;
        $approver = $model->approved_by ? User::findOne($model->approved_by) : null;

        // Calculate repayment schedule
        $schedule = [];
        $remainingAmount = (float) $model->amount - (float) $model->repaid;
        $monthlyDeduction = (float) $model->monthly_deduction;
        $startDate = new \DateTime($model->start_date);

        // Determine how many installments have been paid
        $paidInstallments = $model->installments - ($model->remaining_installments ?? $model->installments);

        for ($i = 1; $i <= $model->installments; $i++) {
            $installmentDate = clone $startDate;
            $installmentDate->modify('+' . ($i - 1) . ' months');

            $schedule[] = [
                'number' => $i,
                'date' => $installmentDate->format('Y-m-d'),
                'amount' => ($i < $model->installments) ? $monthlyDeduction : $remainingAmount,
                'status' => ($i <= $paidInstallments) ? 'paid' : 'pending',
            ];

            if ($i < $model->installments) {
                $remainingAmount -= $monthlyDeduction;
            }
        }

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => ($model->loan_type === 'advance' ? 'سلفة' : 'قرض') . ' — ' . ($employee->name ?: $employee->username),
                'content' => $this->renderAjax('view', [
                    'model' => $model,
                    'employee' => $employee,
                    'approver' => $approver,
                    'schedule' => $schedule,
                ]),
            ];
        }

        return $this->render('view', [
            'model' => $model,
            'employee' => $employee,
            'approver' => $approver,
            'schedule' => $schedule,
        ]);
    }

    /**
     * Finds the HrLoan model.
     *
     * @param int $id
     * @return HrLoan
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = HrLoan::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('القرض/السلفة المطلوب غير موجود.');
    }
}
