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
use backend\modules\hr\models\HrPayrollRun;
use backend\modules\hr\models\HrPayslip;
use backend\modules\hr\models\HrPayslipLine;
use backend\modules\hr\models\HrSalaryComponent;
use backend\modules\hr\models\HrEmployeeSalary;
use backend\modules\hr\models\HrAttendance;
use backend\modules\hr\models\HrLoan;
use backend\modules\hr\models\HrPayrollAdjustment;
use backend\modules\hr\models\HrAnnualIncrement;
use common\models\User;
use common\helper\Permissions;

/**
 * HrPayrollController — Payroll management
 * يتطلب أحد صلاحيات الموارد البشرية.
 */
class HrPayrollController extends Controller
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
                    'calculate' => ['POST'],
                    'approve' => ['POST'],
                    'save-adjustments' => ['POST'],
                    'apply-increment' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * List payroll runs.
     *
     * @return string
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => HrPayrollRun::find()
                ->orderBy(['period_year' => SORT_DESC, 'period_month' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Create new payroll run (select month/year).
     *
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new HrPayrollRun();
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            // Generate run code
            $model->run_code = 'PAY-' . $model->period_year . '-' . str_pad($model->period_month, 2, '0', STR_PAD_LEFT);
            $model->status = 'draft';

            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Check for duplicate payroll run
                $exists = HrPayrollRun::find()
                    ->where([
                        'period_month' => $model->period_month,
                        'period_year' => $model->period_year,
                        'is_deleted' => 0,
                    ])
                    ->andFilterWhere(['branch_id' => $model->branch_id])
                    ->exists();

                if ($exists) {
                    throw new \Exception('توجد مسيرة رواتب مسبقة لهذه الفترة.');
                }

                if (!$model->save()) {
                    throw new \Exception('فشل إنشاء مسيرة الرواتب: ' . implode(', ', $model->getFirstErrors()));
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء مسيرة الرواتب بنجاح — ' . $model->run_code);
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'إنشاء مسيرة رواتب جديدة',
                'content' => $this->renderAjax('create', [
                    'model' => $model,
                ]),
            ];
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * View payroll run with all payslips.
     *
     * @param int $id
     * @return string
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        $payslips = HrPayslip::find()
            ->where(['payroll_run_id' => $id, 'is_deleted' => 0])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'مسيرة الرواتب — ' . $model->run_code,
                'content' => $this->renderAjax('view', [
                    'model' => $model,
                    'payslips' => $payslips,
                ]),
            ];
        }

        return $this->render('view', [
            'model' => $model,
            'payslips' => $payslips,
        ]);
    }

    /**
     * Calculate payroll — create payslips for all active employees based on
     * salary structure, attendance, and loans.
     *
     * @param int $id Payroll run ID
     * @return Response
     */
    public function actionCalculate($id)
    {
        $run = $this->findModel($id);
        $isAjax = Yii::$app->request->isAjax;

        if (!in_array($run->status, ['draft', 'calculated'])) {
            if ($isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'لا يمكن إعادة الحساب — حالة المسيرة: ' . $run->status];
            }
            Yii::$app->session->setFlash('error', 'لا يمكن إعادة الحساب — حالة المسيرة: ' . $run->status);
            return $this->redirect(['view', 'id' => $id]);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Delete existing payslips for this run (recalculation)
            HrPayslipLine::deleteAll(['payslip_id' => (new Query())
                ->select('id')
                ->from('{{%hr_payslip}}')
                ->where(['payroll_run_id' => $id])
            ]);
            HrPayslip::deleteAll(['payroll_run_id' => $id]);

            // Get active employees (employee_type = 'Active' and not blocked)
            // Note: employee_type stores activation state ('Active'/'Suspended')
            //       employee_status stores employment type ('Full_time'/'Part_time')
            $employees = (new Query())
                ->select(['u.id as user_id'])
                ->from('{{%user}} u')
                ->where(['IS', 'u.blocked_at', null])
                ->andWhere(['u.employee_type' => 'Active'])
                ->andWhere(['IS NOT', 'u.confirmed_at', null])
                ->all();

            $periodStart = $run->period_year . '-' . str_pad($run->period_month, 2, '0', STR_PAD_LEFT) . '-01';
            $periodEnd = date('Y-m-t', strtotime($periodStart));
            $workingDaysInMonth = $this->getWorkingDays($periodStart, $periodEnd);

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;
            $employeeCount = 0;

            foreach ($employees as $emp) {
                $userId = $emp['user_id'];

                // Fetch salary components
                $salaryItems = (new Query())
                    ->select(['es.amount', 'sc.component_type', 'sc.code', 'sc.name', 'sc.id as component_id', 'sc.sort_order', 'sc.calculation'])
                    ->from('{{%hr_employee_salary}} es')
                    ->innerJoin('{{%hr_salary_component}} sc', 'sc.id = es.component_id AND sc.is_deleted = 0')
                    ->where([
                        'es.user_id' => $userId,
                        'es.is_deleted' => 0,
                    ])
                    ->andWhere(['<=', 'es.effective_from', $periodEnd])
                    ->andWhere(['or',
                        ['es.effective_to' => null],
                        ['>=', 'es.effective_to', $periodStart],
                    ])
                    ->orderBy(['sc.sort_order' => SORT_ASC])
                    ->all();

                if (empty($salaryItems)) {
                    continue; // Skip employees without salary structure
                }

                // Calculate attendance
                $attendance = Yii::$app->db->createCommand("
                    SELECT
                        COUNT(*) as total_records,
                        SUM(CASE WHEN status IN ('present','late','field_duty') THEN 1 ELSE 0 END) as present_days,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                        SUM(CASE WHEN status IN ('on_leave','leave') THEN 1 ELSE 0 END) as leave_days,
                        SUM(COALESCE(overtime_hours, 0)) as overtime_hours,
                        SUM(COALESCE(late_minutes, 0)) as total_late_minutes
                    FROM {{%hr_attendance}}
                    WHERE user_id = :userId
                      AND attendance_date BETWEEN :start AND :end
                      AND is_deleted = 0
                ", [
                    ':userId' => $userId,
                    ':start' => $periodStart,
                    ':end' => $periodEnd,
                ])->queryOne();

                $presentDays = (float) ($attendance['present_days'] ?? $workingDaysInMonth);
                $absentDays = (float) ($attendance['absent_days'] ?? 0);
                $leaveDays = (float) ($attendance['leave_days'] ?? 0);
                $overtimeHours = (float) ($attendance['overtime_hours'] ?? 0);

                // ── First pass: collect basic salary for percentage calculations ──
                $basicSalary = 0;
                foreach ($salaryItems as $item) {
                    if ($item['code'] === 'BASIC') {
                        $basicSalary = (float) $item['amount'];
                        break;
                    }
                }

                // ── Calculate earnings and deductions ──
                $earnings = 0;
                $deductions = 0;
                $payslipLines = [];

                foreach ($salaryItems as $item) {
                    $amount = (float) $item['amount'];
                    $calculation = $item['calculation'] ?? 'fixed';

                    // Handle percentage-based components (e.g. Social Security)
                    if ($calculation === 'percentage' && $basicSalary > 0) {
                        // amount stores the percentage value (e.g. 7.5 for 7.5%)
                        $amount = round($basicSalary * ($amount / 100), 2);
                    }

                    if ($item['code'] === 'BASIC') {
                        // basicSalary already set above
                    }

                    if ($item['component_type'] === 'earning') {
                        $earnings += $amount;
                    } else {
                        $deductions += $amount;
                    }

                    $desc = $item['name'];
                    if ($calculation === 'percentage') {
                        $desc .= ' (' . (float)$item['amount'] . '%)';
                    }

                    $payslipLines[] = [
                        'component_id' => $item['component_id'],
                        'component_type' => $item['component_type'],
                        'description' => $desc,
                        'amount' => $amount,
                        'sort_order' => $item['sort_order'],
                    ];
                }

                // ── Payroll adjustments (commissions, bonuses, manual deductions) ──
                $adjustments = (new Query())
                    ->select(['adjustment_type', 'amount', 'description'])
                    ->from('{{%hr_payroll_adjustment}}')
                    ->where([
                        'payroll_run_id' => $id,
                        'user_id' => $userId,
                        'is_deleted' => 0,
                    ])
                    ->all();

                foreach ($adjustments as $adj) {
                    $adjAmount = (float) $adj['amount'];
                    $adjType = $adj['adjustment_type'];
                    $adjDesc = $adj['description'] ?: HrPayrollAdjustment::typeLabels()[$adjType] ?? $adjType;

                    if ($adjType === 'deduction') {
                        $deductions += $adjAmount;
                        $compType = 'deduction';
                    } else {
                        // commission, bonus, other → earning
                        $earnings += $adjAmount;
                        $compType = 'earning';
                    }

                    $payslipLines[] = [
                        'component_id' => 0,
                        'component_type' => $compType,
                        'description' => $adjDesc,
                        'amount' => $adjAmount,
                        'sort_order' => $compType === 'earning' ? 800 : 910,
                    ];
                }

                // Absence deduction (pro-rata)
                if ($absentDays > 0 && $workingDaysInMonth > 0 && $basicSalary > 0) {
                    $dailyRate = $basicSalary / $workingDaysInMonth;
                    $absenceDeduction = round($dailyRate * $absentDays, 2);
                    $deductions += $absenceDeduction;
                    $payslipLines[] = [
                        'component_id' => 0,
                        'component_type' => 'deduction',
                        'description' => 'خصم غياب (' . (int)$absentDays . ' يوم)',
                        'amount' => $absenceDeduction,
                        'sort_order' => 900,
                    ];
                }

                // Active loan deductions
                $activeLoans = (new Query())
                    ->select(['id', 'monthly_deduction', 'loan_type'])
                    ->from('{{%hr_loan}}')
                    ->where([
                        'user_id' => $userId,
                        'status' => 'active',
                        'is_deleted' => 0,
                    ])
                    ->all();

                foreach ($activeLoans as $loan) {
                    $loanDeduction = (float) $loan['monthly_deduction'];
                    $deductions += $loanDeduction;
                    $loanLabel = $loan['loan_type'] === 'advance' ? 'سلفة' : 'قرض';
                    $payslipLines[] = [
                        'component_id' => 0,
                        'component_type' => 'deduction',
                        'description' => 'قسط ' . $loanLabel . ' #' . $loan['id'],
                        'amount' => $loanDeduction,
                        'sort_order' => 950,
                    ];
                }

                $netSalary = round($earnings - $deductions, 2);

                // Create payslip
                $payslip = new HrPayslip();
                $payslip->payroll_run_id = $id;
                $payslip->user_id = $userId;
                $payslip->basic_salary = $basicSalary;
                $payslip->total_earnings = $earnings;
                $payslip->total_deductions = $deductions;
                $payslip->net_salary = $netSalary;
                $payslip->working_days = $workingDaysInMonth;
                $payslip->present_days = $presentDays;
                $payslip->absent_days = $absentDays;
                $payslip->leave_days = $leaveDays;
                $payslip->overtime_hours = $overtimeHours;
                $payslip->status = 'draft';

                if (!$payslip->save()) {
                    throw new \Exception("فشل إنشاء كشف راتب للموظف #{$userId}: " . implode(', ', $payslip->getFirstErrors()));
                }

                // Create payslip lines
                foreach ($payslipLines as $lineData) {
                    $line = new HrPayslipLine();
                    $line->payslip_id = $payslip->id;
                    $line->component_id = $lineData['component_id'];
                    $line->component_type = $lineData['component_type'];
                    $line->description = $lineData['description'];
                    $line->amount = $lineData['amount'];
                    $line->sort_order = $lineData['sort_order'];
                    if (!$line->save()) {
                        throw new \Exception("فشل حفظ بند الراتب: " . implode(', ', $line->getFirstErrors()));
                    }
                }

                $totalGross += $earnings;
                $totalDeductions += $deductions;
                $totalNet += $netSalary;
                $employeeCount++;
            }

            // Update run totals
            $run->total_employees = $employeeCount;
            $run->total_gross = $totalGross;
            $run->total_deductions = $totalDeductions;
            $run->total_net = $totalNet;
            $run->status = 'calculated';

            if (!$run->save()) {
                throw new \Exception('فشل تحديث مسيرة الرواتب: ' . implode(', ', $run->getFirstErrors()));
            }

            $transaction->commit();

            if ($isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [
                    'success' => true,
                    'message' => "تم حساب الرواتب بنجاح — {$employeeCount} موظف.",
                    'total_employees' => $employeeCount,
                    'total_gross' => $totalGross,
                    'total_net' => $totalNet,
                ];
            }

            Yii::$app->session->setFlash('success', "تم حساب الرواتب بنجاح — {$employeeCount} موظف.");
            return $this->redirect(['view', 'id' => $id]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            if ($isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => $e->getMessage()];
            }
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(['view', 'id' => $id]);
        }
    }

    /**
     * Approve payroll run.
     *
     * @param int $id
     * @return array|Response
     */
    public function actionApprove($id)
    {
        $run = $this->findModel($id);

        if ($run->status !== 'calculated') {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'لا يمكن اعتماد المسيرة — الحالة الحالية: ' . $run->status];
            }
            Yii::$app->session->setFlash('error', 'لا يمكن اعتماد المسيرة — الحالة الحالية: ' . $run->status);
            return $this->redirect(['view', 'id' => $id]);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $run->status = 'approved';
            $run->approved_by = Yii::$app->user->id;
            $run->approved_at = time();

            // Confirm all payslips
            HrPayslip::updateAll(
                ['status' => 'confirmed'],
                ['payroll_run_id' => $id, 'is_deleted' => 0]
            );

            // Update loan repayments
            $payslipLines = (new Query())
                ->select(['pl.description', 'pl.amount'])
                ->from('{{%hr_payslip_line}} pl')
                ->innerJoin('{{%hr_payslip}} ps', 'ps.id = pl.payslip_id')
                ->where([
                    'ps.payroll_run_id' => $id,
                    'pl.component_type' => 'deduction',
                ])
                ->andWhere(['like', 'pl.description', 'قسط'])
                ->all();

            // Extract loan IDs from description and update repaid amounts
            foreach ($payslipLines as $line) {
                if (preg_match('/#(\d+)/', $line['description'], $matches)) {
                    $loanId = (int) $matches[1];
                    Yii::$app->db->createCommand()->update(
                        '{{%hr_loan}}',
                        [
                            'repaid' => new \yii\db\Expression('repaid + :amount', [':amount' => $line['amount']]),
                            'remaining_installments' => new \yii\db\Expression('GREATEST(remaining_installments - 1, 0)'),
                            'updated_at' => time(),
                            'updated_by' => Yii::$app->user->id,
                        ],
                        ['id' => $loanId]
                    )->execute();

                    // Check if loan is fully repaid
                    $loan = (new Query())
                        ->select(['amount', 'repaid'])
                        ->from('{{%hr_loan}}')
                        ->where(['id' => $loanId])
                        ->one();

                    if ($loan && (float) $loan['repaid'] >= (float) $loan['amount']) {
                        Yii::$app->db->createCommand()->update(
                            '{{%hr_loan}}',
                            ['status' => 'completed', 'updated_at' => time()],
                            ['id' => $loanId]
                        )->execute();
                    }
                }
            }

            if (!$run->save()) {
                throw new \Exception('فشل اعتماد المسيرة: ' . implode(', ', $run->getFirstErrors()));
            }

            $transaction->commit();

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => true, 'message' => 'تم اعتماد مسيرة الرواتب بنجاح.'];
            }

            Yii::$app->session->setFlash('success', 'تم اعتماد مسيرة الرواتب بنجاح.');
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
     * View individual payslip.
     *
     * @param int $id Payslip ID
     * @return string
     */
    public function actionPayslip($id)
    {
        $payslip = HrPayslip::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($payslip === null) {
            throw new NotFoundHttpException('كشف الراتب المطلوب غير موجود.');
        }

        $lines = HrPayslipLine::find()
            ->where(['payslip_id' => $id])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();

        $user = User::findOne($payslip->user_id);
        $run = HrPayrollRun::findOne($payslip->payroll_run_id);

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'كشف راتب — ' . ($user->name ?: $user->username),
                'content' => $this->renderAjax('payslip', [
                    'payslip' => $payslip,
                    'lines' => $lines,
                    'user' => $user,
                    'run' => $run,
                ]),
            ];
        }

        return $this->render('payslip', [
            'payslip' => $payslip,
            'lines' => $lines,
            'user' => $user,
            'run' => $run,
        ]);
    }

    /**
     * Generate payslip PDF (simple HTML-to-PDF).
     *
     * @param int $id Payslip ID
     * @return string
     */
    public function actionPayslipPdf($id)
    {
        $payslip = HrPayslip::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($payslip === null) {
            throw new NotFoundHttpException('كشف الراتب المطلوب غير موجود.');
        }

        $lines = HrPayslipLine::find()
            ->where(['payslip_id' => $id])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();

        $user = User::findOne($payslip->user_id);
        $run = HrPayrollRun::findOne($payslip->payroll_run_id);

        // Render HTML content for PDF
        $htmlContent = $this->renderPartial('_payslip_pdf', [
            'payslip' => $payslip,
            'lines' => $lines,
            'user' => $user,
            'run' => $run,
        ]);

        // Simple HTML-to-PDF using browser print or mpdf if available
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->add('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->add('Content-Disposition', 'inline; filename="payslip_' . $payslip->id . '.html"');

        return $htmlContent;
    }

    /**
     * Manage salary components (list).
     *
     * @return string
     */
    public function actionComponents()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => HrSalaryComponent::find()
                ->where(['is_deleted' => 0])
                ->orderBy(['sort_order' => SORT_ASC, 'component_type' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('components', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Create salary component.
     *
     * @return string|Response
     */
    public function actionComponentCreate()
    {
        $model = new HrSalaryComponent();
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->created_at = time();
            $model->updated_at = time();
            $model->created_by = Yii::$app->user->id;
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل إنشاء مكون الراتب: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء مكون الراتب بنجاح.');
                return $this->redirect(['components']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'إنشاء مكون راتب',
                'content' => $this->renderAjax('component-form', [
                    'model' => $model,
                ]),
            ];
        }

        return $this->render('component-form', [
            'model' => $model,
        ]);
    }

    /**
     * Update salary component.
     *
     * @param int $id
     * @return string|Response
     */
    public function actionComponentUpdate($id)
    {
        $model = HrSalaryComponent::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($model === null) {
            throw new NotFoundHttpException('مكون الراتب المطلوب غير موجود.');
        }

        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل تحديث مكون الراتب: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم تحديث مكون الراتب بنجاح.');
                return $this->redirect(['components']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'تعديل مكون الراتب',
                'content' => $this->renderAjax('component-form', [
                    'model' => $model,
                ]),
            ];
        }

        return $this->render('component-form', [
            'model' => $model,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  التعديلات / العمولات الشهرية على مستوى المسيرة
     * ═══════════════════════════════════════════════════════════ */

    /**
     * Show adjustments screen for a payroll run.
     */
    public function actionAdjustments($id)
    {
        $run = $this->findModel($id);

        // Get active employees
        $employees = (new Query())
            ->select(['u.id', 'u.username', 'u.name'])
            ->from('{{%user}} u')
            ->where(['IS', 'u.blocked_at', null])
            ->andWhere(['u.employee_type' => 'Active'])
            ->andWhere(['IS NOT', 'u.confirmed_at', null])
            ->orderBy(['u.id' => SORT_ASC])
            ->all();

        // Get existing adjustments for this run
        $adjustments = (new Query())
            ->select(['*'])
            ->from('{{%hr_payroll_adjustment}}')
            ->where(['payroll_run_id' => $id, 'is_deleted' => 0])
            ->indexBy('user_id')
            ->all();

        // Also index by composite key for multiple adjustments per user
        $allAdjustments = (new Query())
            ->select(['*'])
            ->from('{{%hr_payroll_adjustment}}')
            ->where(['payroll_run_id' => $id, 'is_deleted' => 0])
            ->orderBy(['user_id' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        // Group by user
        $adjustmentsByUser = [];
        foreach ($allAdjustments as $adj) {
            $adjustmentsByUser[$adj['user_id']][] = $adj;
        }

        return $this->render('adjustments', [
            'run' => $run,
            'employees' => $employees,
            'adjustmentsByUser' => $adjustmentsByUser,
        ]);
    }

    /**
     * Save adjustments (POST).
     */
    public function actionSaveAdjustments($id)
    {
        $run = $this->findModel($id);
        $request = Yii::$app->request;

        if (!in_array($run->status, ['draft', 'calculated'])) {
            Yii::$app->session->setFlash('error', 'لا يمكن تعديل العمولات — المسيرة بحالة: ' . $run->status);
            return $this->redirect(['view', 'id' => $id]);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Clear existing adjustments for this run
            Yii::$app->db->createCommand()->update(
                '{{%hr_payroll_adjustment}}',
                ['is_deleted' => 1],
                ['payroll_run_id' => $id]
            )->execute();

            $postData = $request->post('Adjustment', []);
            $savedCount = 0;

            foreach ($postData as $userId => $data) {
                $amount = (float) ($data['amount'] ?? 0);
                if ($amount <= 0) continue;

                $adj = new HrPayrollAdjustment();
                $adj->payroll_run_id = $id;
                $adj->user_id = (int) $userId;
                $adj->adjustment_type = $data['type'] ?? 'commission';
                $adj->amount = $amount;
                $adj->description = $data['description'] ?? '';

                if (!$adj->save()) {
                    throw new \Exception("فشل حفظ تعديل الموظف #{$userId}: " . implode(', ', $adj->getFirstErrors()));
                }
                $savedCount++;
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', "تم حفظ {$savedCount} تعديل/عمولة بنجاح.");
            return $this->redirect(['view', 'id' => $id]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(['adjustments', 'id' => $id]);
        }
    }

    /* ═══════════════════════════════════════════════════════════
     *  العلاوات السنوية
     * ═══════════════════════════════════════════════════════════ */

    /**
     * List annual increments.
     */
    public function actionIncrements()
    {
        $query = HrAnnualIncrement::find()
            ->where(['is_deleted' => 0])
            ->orderBy(['increment_year' => SORT_DESC, 'user_id' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 30],
        ]);

        return $this->render('increments', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Create new annual increment.
     */
    public function actionIncrementCreate()
    {
        $model = new HrAnnualIncrement();
        $request = Yii::$app->request;

        // Get active employees for dropdown
        $employees = (new Query())
            ->select(['u.id', "CONCAT(COALESCE(u.name, u.username), ' #', u.id) as label"])
            ->from('{{%user}} u')
            ->where(['IS', 'u.blocked_at', null])
            ->andWhere(['u.employee_type' => 'Active'])
            ->andWhere(['IS NOT', 'u.confirmed_at', null])
            ->orderBy(['u.id' => SORT_ASC])
            ->all();

        $employeeList = ArrayHelper::map($employees, 'id', 'label');

        // إذا فُتحت الصفحة من ملف موظف (user_id في الرابط)، تحديد الموظف مسبقاً
        $presetUserId = $request->get('user_id');
        if ($presetUserId && isset($employeeList[$presetUserId])) {
            $model->user_id = (int) $presetUserId;
        }

        if ($model->load($request->post())) {
            // استحقاق: الموظف يجب أن يكون قد أكمل (service_year) سنوات على الأقل قبل تاريخ السريان
            $dateOfHire = (new Query())
                ->select('date_of_hire')
                ->from('{{%user}}')
                ->where(['id' => $model->user_id])
                ->scalar();
            $serviceYear = (int) ($model->service_year ?? 0);
            $effectiveTs = strtotime($model->effective_date);
            $hireTs = $dateOfHire ? strtotime($dateOfHire) : 0;
            $yearsServed = $hireTs && $effectiveTs ? (int) floor(( $effectiveTs - $hireTs ) / (365.25 * 24 * 3600)) : 0;
            if (empty($dateOfHire) || $yearsServed < $serviceYear) {
                Yii::$app->session->setFlash('error', 'الموظف يجب أن يكون قد أكمل ' . $serviceYear . ' سنة خدمة على الأقل قبل تاريخ السريان (تحقق من تاريخ التعيين وتاريخ السريان).');
            } else {
            // مرجع تقويمي اختياري (سنة السريان)
            if ($model->increment_year === null || $model->increment_year === '') {
                $model->increment_year = (int) date('Y', $effectiveTs);
            }
            // Look up current basic salary
            $currentBasic = (new Query())
                ->select(['es.amount'])
                ->from('{{%hr_employee_salary}} es')
                ->innerJoin('{{%hr_salary_component}} sc', 'sc.id = es.component_id')
                ->where([
                    'es.user_id' => $model->user_id,
                    'es.is_deleted' => 0,
                    'sc.code' => 'BASIC',
                ])
                ->andWhere(['es.effective_to' => null])
                ->orderBy(['es.effective_from' => SORT_DESC])
                ->scalar();

            $currentBasic = (float) ($currentBasic ?: 0);
            $model->previous_salary = $currentBasic;

            if ($model->increment_type === 'percentage') {
                $model->calculated_amount = round($currentBasic * ($model->amount / 100), 2);
            } else {
                $model->calculated_amount = $model->amount;
            }

            $model->new_salary = $currentBasic + $model->calculated_amount;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل إنشاء العلاوة: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء العلاوة بنجاح.');
                // إذا أُضيفت العلاوة من ملف موظف، العودة لملف الموظف
                if ($request->get('user_id')) {
                    return $this->redirect(['/hr/hr-employee/view', 'id' => $model->user_id]);
                }
                return $this->redirect(['increments']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
            }
        }

        // نطاق سنوات الخدمة للقائمة (1 إلى 50)
        $serviceYearRange = array_combine(range(1, 50), range(1, 50));

        return $this->render('increment-form', [
            'model' => $model,
            'employeeList' => $employeeList,
            'serviceYearRange' => $serviceYearRange,
        ]);
    }

    /**
     * علاوة تلقائية: معاينة (قائمة الموظفين + سنوات الخدمة + إجمالي العلاوة) ثم اعتماد أو رفض.
     */
    public function actionIncrementBulk()
    {
        $request = Yii::$app->request;
        $confirm = $request->post('confirm');
        $incrementType = $request->post('increment_type');
        $amount = $request->post('amount');
        $effectiveDate = $request->post('effective_date', date('Y-01-01'));

        // ── اعتماد: إنشاء السجلات فعلياً ──
        if ($request->isPost && $confirm === '1' && $incrementType !== null && $incrementType !== '' && $amount !== '' && $effectiveDate !== '') {
            $amount = (float) str_replace(',', '.', $amount);
            if ($amount <= 0 || !in_array($incrementType, ['fixed', 'percentage'], true)) {
                Yii::$app->session->setFlash('error', 'قيم غير صالحة.');
                return $this->redirect(['increment-bulk']);
            }
            $basicComponentId = (new Query())
                ->select('id')
                ->from('{{%hr_salary_component}}')
                ->where(['code' => 'BASIC', 'is_deleted' => 0])
                ->scalar();
            if (!$basicComponentId) {
                Yii::$app->session->setFlash('error', 'لم يتم العثور على مكوّن الراتب الأساسي (BASIC).');
                return $this->redirect(['increment-bulk']);
            }
            $effectiveTs = strtotime($effectiveDate);
            $employees = (new Query())
                ->select(['u.id', 'u.name', 'u.username', 'u.date_of_hire'])
                ->from('{{%user}} u')
                ->where(['IS', 'u.blocked_at', null])
                ->andWhere(['u.employee_type' => 'Active'])
                ->andWhere(['IS NOT', 'u.confirmed_at', null])
                ->andWhere(['IS NOT', 'u.date_of_hire', null])
                ->andWhere(['<', 'u.date_of_hire', $effectiveDate])
                ->orderBy(['u.id' => SORT_ASC])
                ->all();
            $created = 0;
            $skipped = 0;
            $errors = [];
            $transaction = Yii::$app->db->beginTransaction();
            try {
                foreach ($employees as $emp) {
                    $userId = (int) $emp['id'];
                    $dateOfHire = $emp['date_of_hire'];
                    $hireTs = strtotime($dateOfHire);
                    $yearsServed = $effectiveTs && $hireTs ? (int) floor(($effectiveTs - $hireTs) / (365.25 * 24 * 3600)) : 0;
                    if ($yearsServed < 1) {
                        $skipped++;
                        continue;
                    }
                    $currentBasic = (float) (new Query())
                        ->select('es.amount')
                        ->from('{{%hr_employee_salary}} es')
                        ->where([
                            'es.user_id' => $userId,
                            'es.component_id' => $basicComponentId,
                            'es.is_deleted' => 0,
                        ])
                        ->andWhere(['es.effective_to' => null])
                        ->orderBy(['es.effective_from' => SORT_DESC])
                        ->scalar();
                    if ($currentBasic <= 0) {
                        $skipped++;
                        continue;
                    }
                    $runningSalary = $currentBasic;
                    for ($sv = 1; $sv <= $yearsServed; $sv++) {
                        $existing = (new Query())
                            ->from('{{%hr_annual_increment}}')
                            ->where(['user_id' => $userId, 'service_year' => $sv, 'is_deleted' => 0])
                            ->count();
                        if ($existing > 0) {
                            $runningSalary = (float) (new Query())
                                ->select('new_salary')
                                ->from('{{%hr_annual_increment}}')
                                ->where(['user_id' => $userId, 'service_year' => $sv, 'is_deleted' => 0])
                                ->orderBy(['id' => SORT_DESC])
                                ->scalar();
                            if ($runningSalary <= 0) {
                                $runningSalary = $currentBasic;
                            }
                            continue;
                        }
                        if ($incrementType === 'percentage') {
                            $calculated = round($runningSalary * ($amount / 100), 2);
                        } else {
                            $calculated = $amount;
                        }
                        $newSalary = round($runningSalary + $calculated, 2);
                        $rec = new HrAnnualIncrement();
                        $rec->user_id = $userId;
                        $rec->service_year = $sv;
                        $rec->increment_year = (int) date('Y', $effectiveTs);
                        $rec->increment_type = $incrementType;
                        $rec->amount = $amount;
                        $rec->calculated_amount = $calculated;
                        $rec->previous_salary = $runningSalary;
                        $rec->new_salary = $newSalary;
                        $rec->effective_date = $effectiveDate;
                        $rec->notes = 'علاوة تلقائية — سنة خدمة ' . $sv . ' / ' . $yearsServed;
                        if (!$rec->save()) {
                            $errors[] = 'موظف #' . $userId . ' سنة ' . $sv . ': ' . implode(', ', $rec->getFirstErrors());
                            break;
                        }
                        $created++;
                        $runningSalary = $newSalary;
                    }
                }
                $transaction->commit();
                $msg = 'تم إنشاء ' . $created . ' سجل علاوة تلقائية (حسب سنوات الخدمة).';
                if ($skipped > 0) {
                    $msg .= ' (تخطي: ' . $skipped . ')';
                }
                if (!empty($errors)) {
                    $msg .= ' أخطاء: ' . implode('; ', $errors);
                }
                Yii::$app->session->setFlash('success', $msg);
                return $this->redirect(['increments']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->redirect(['increment-bulk']);
            }
        }

        // ── معاينة: عرض القائمة دون إنشاء ──
        if ($request->isPost && $request->post('preview') === '1' && $incrementType !== null && $incrementType !== '' && $amount !== '' && $effectiveDate !== '') {
            $amount = (float) str_replace(',', '.', $amount);
            if ($amount <= 0 || !in_array($incrementType, ['fixed', 'percentage'], true)) {
                Yii::$app->session->setFlash('error', 'قيم غير صالحة. المبلغ/النسبة أكبر من صفر.');
                return $this->render('increment-bulk', ['effectiveDate' => $effectiveDate]);
            }
            $preview = $this->getBulkIncrementPreviewData($effectiveDate, $incrementType, $amount);
            return $this->render('increment-bulk-preview', [
                'preview' => $preview,
                'incrementType' => $incrementType,
                'amount' => $amount,
                'effectiveDate' => $effectiveDate,
            ]);
        }

        // ── نموذج الإدخال ──
        return $this->render('increment-bulk', [
            'effectiveDate' => $effectiveDate ?: date('Y-01-01'),
        ]);
    }

    /**
     * حساب بيانات المعاينة للعلاوة التلقائية (بدون حفظ).
     * @return array ['rows' => [...], 'grand_total' => float, 'skipped' => int]
     */
    protected function getBulkIncrementPreviewData($effectiveDate, $incrementType, $amount)
    {
        $basicComponentId = (new Query())
            ->select('id')
            ->from('{{%hr_salary_component}}')
            ->where(['code' => 'BASIC', 'is_deleted' => 0])
            ->scalar();
        if (!$basicComponentId) {
            return ['rows' => [], 'grand_total' => 0, 'skipped' => 0];
        }
        $effectiveTs = strtotime($effectiveDate);
        $employees = (new Query())
            ->select(['u.id', 'u.name', 'u.username', 'u.date_of_hire'])
            ->from('{{%user}} u')
            ->where(['IS', 'u.blocked_at', null])
            ->andWhere(['u.employee_type' => 'Active'])
            ->andWhere(['IS NOT', 'u.confirmed_at', null])
            ->andWhere(['IS NOT', 'u.date_of_hire', null])
            ->andWhere(['<', 'u.date_of_hire', $effectiveDate])
            ->orderBy(['u.id' => SORT_ASC])
            ->all();
        $rows = [];
        $grandTotal = 0.0;
        $skipped = 0;
        foreach ($employees as $emp) {
            $userId = (int) $emp['id'];
            $dateOfHire = $emp['date_of_hire'];
            $hireTs = strtotime($dateOfHire);
            $yearsServed = $effectiveTs && $hireTs ? (int) floor(($effectiveTs - $hireTs) / (365.25 * 24 * 3600)) : 0;
            if ($yearsServed < 1) {
                $skipped++;
                continue;
            }
            $currentBasic = (float) (new Query())
                ->select('es.amount')
                ->from('{{%hr_employee_salary}} es')
                ->where([
                    'es.user_id' => $userId,
                    'es.component_id' => $basicComponentId,
                    'es.is_deleted' => 0,
                ])
                ->andWhere(['es.effective_to' => null])
                ->orderBy(['es.effective_from' => SORT_DESC])
                ->scalar();
            if ($currentBasic <= 0) {
                $skipped++;
                continue;
            }
            $runningSalary = $currentBasic;
            $breakdown = [];
            $totalIncrement = 0.0;
            $toCreate = 0;
            for ($sv = 1; $sv <= $yearsServed; $sv++) {
                $existing = (new Query())
                    ->from('{{%hr_annual_increment}}')
                    ->where(['user_id' => $userId, 'service_year' => $sv, 'is_deleted' => 0])
                    ->count();
                if ($existing > 0) {
                    $runningSalary = (float) (new Query())
                        ->select('new_salary')
                        ->from('{{%hr_annual_increment}}')
                        ->where(['user_id' => $userId, 'service_year' => $sv, 'is_deleted' => 0])
                        ->orderBy(['id' => SORT_DESC])
                        ->scalar();
                    if ($runningSalary <= 0) {
                        $runningSalary = $currentBasic;
                    }
                    continue;
                }
                if ($incrementType === 'percentage') {
                    $calculated = round($runningSalary * ($amount / 100), 2);
                } else {
                    $calculated = $amount;
                }
                $totalIncrement += $calculated;
                $breakdown[$sv] = $calculated;
                $runningSalary = round($runningSalary + $calculated, 2);
                $toCreate++;
            }
            if ($toCreate > 0) {
                $name = $emp['name'] ?: $emp['username'];
                $rows[] = [
                    'user_id' => $userId,
                    'name' => $name,
                    'years_served' => $yearsServed,
                    'current_basic' => $currentBasic,
                    'breakdown' => $breakdown,
                    'total_increment' => $totalIncrement,
                    'increments_to_create' => $toCreate,
                ];
                $grandTotal += $totalIncrement;
            }
        }
        return ['rows' => $rows, 'grand_total' => $grandTotal, 'skipped' => $skipped];
    }

    /**
     * Apply (execute) an approved increment — updates salary structure.
     */
    public function actionApplyIncrement($id)
    {
        $increment = HrAnnualIncrement::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if (!$increment) {
            throw new NotFoundHttpException('العلاوة غير موجودة.');
        }

        if ($increment->status === 'applied') {
            Yii::$app->session->setFlash('warning', 'العلاوة مطبقة مسبقاً.');
            return $this->redirect(['increments']);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Find the current BASIC salary record for the employee
            $basicComponentId = (new Query())
                ->select('id')
                ->from('{{%hr_salary_component}}')
                ->where(['code' => 'BASIC', 'is_deleted' => 0])
                ->scalar();

            // End current basic salary (set effective_to)
            Yii::$app->db->createCommand()->update(
                '{{%hr_employee_salary}}',
                [
                    'effective_to' => date('Y-m-d', strtotime($increment->effective_date . ' -1 day')),
                    'updated_at' => time(),
                    'updated_by' => Yii::$app->user->id,
                ],
                [
                    'user_id' => $increment->user_id,
                    'component_id' => $basicComponentId,
                    'is_deleted' => 0,
                    'effective_to' => null,
                ]
            )->execute();

            // Create new basic salary record with new amount
            $newSalary = new HrEmployeeSalary();
            $newSalary->user_id = $increment->user_id;
            $newSalary->component_id = $basicComponentId;
            $newSalary->amount = $increment->new_salary;
            $newSalary->effective_from = $increment->effective_date;
            $newSalary->effective_to = null;
            $yearNote = $increment->service_year
                ? ('علاوة سنة خدمة ' . $increment->service_year)
                : ('علاوة سنوية ' . ($increment->increment_year ?? date('Y')));
            $newSalary->notes = $yearNote
                . ' — ' . ($increment->increment_type === 'percentage'
                    ? $increment->amount . '% = ' . $increment->calculated_amount
                    : $increment->calculated_amount . ' د.أ');

            if (!$newSalary->save()) {
                throw new \Exception('فشل تحديث الراتب: ' . implode(', ', $newSalary->getFirstErrors()));
            }

            // Mark increment as applied
            $increment->status = 'applied';
            $increment->applied_at = time();
            if (!$increment->save(false)) {
                throw new \Exception('فشل تحديث حالة العلاوة.');
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', 'تم تطبيق العلاوة بنجاح — الراتب الجديد: ' . number_format($increment->new_salary, 2));
            return $this->redirect(['increments']);
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(['increments']);
        }
    }

    /**
     * Calculate working days in a date range (excludes Fridays and Saturdays).
     *
     * @param string $start
     * @param string $end
     * @return int
     */
    protected function getWorkingDays($start, $end)
    {
        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);
        $endDate->modify('+1 day');
        $workingDays = 0;

        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($startDate, $interval, $endDate);

        foreach ($period as $day) {
            $dayOfWeek = (int) $day->format('N'); // 1=Mon, 7=Sun
            // Skip Friday (5) and Saturday (6) — Jordan weekend
            if ($dayOfWeek !== 5 && $dayOfWeek !== 6) {
                $workingDays++;
            }
        }

        return $workingDays;
    }

    /**
     * Finds the HrPayrollRun model.
     *
     * @param int $id
     * @return HrPayrollRun
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = HrPayrollRun::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('مسيرة الرواتب المطلوبة غير موجودة.');
    }
}
