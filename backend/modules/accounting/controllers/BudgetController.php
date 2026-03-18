<?php

namespace backend\modules\accounting\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use backend\modules\accounting\models\Budget;
use backend\modules\accounting\models\BudgetLine;
use backend\modules\accounting\models\Account;
use backend\modules\accounting\models\FiscalYear;
use backend\modules\accounting\models\JournalEntryLine;
use common\helper\Permissions;

class BudgetController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'variance'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_VIEW, Permissions::ACC_BUDGET_VIEW],
                    ],
                    [
                        'actions' => ['create', 'update', 'add-line', 'remove-line', 'approve'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_BUDGET_MANAGE],
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
                    'approve' => ['post'],
                    'remove-line' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Budget::find()->with(['fiscalYear'])->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionCreate()
    {
        $model = new Budget();

        if ($model->load(Yii::$app->request->post())) {
            $model->created_by = Yii::$app->user->id;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'تم إنشاء الموازنة بنجاح. أضف بنود الموازنة الآن.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        $lines = BudgetLine::find()
            ->with(['account', 'costCenter'])
            ->where(['budget_id' => $id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        // Actual spending per account for variance
        $actuals = [];
        if ($model->fiscalYear) {
            foreach ($lines as $line) {
                $actual = JournalEntryLine::find()
                    ->joinWith('journalEntry')
                    ->where([
                        '{{%journal_entry_lines}}.account_id' => $line->account_id,
                        '{{%journal_entries}}.status' => 'posted',
                        '{{%journal_entries}}.fiscal_year_id' => $model->fiscal_year_id,
                    ])
                    ->select([
                        'total_debit' => 'SUM({{%journal_entry_lines}}.debit)',
                        'total_credit' => 'SUM({{%journal_entry_lines}}.credit)',
                    ])
                    ->asArray()
                    ->one();

                $account = $line->account;
                if ($account->nature === 'debit') {
                    $actuals[$line->id] = ((float)($actual['total_debit'] ?? 0)) - ((float)($actual['total_credit'] ?? 0));
                } else {
                    $actuals[$line->id] = ((float)($actual['total_credit'] ?? 0)) - ((float)($actual['total_debit'] ?? 0));
                }
            }
        }

        return $this->render('view', [
            'model' => $model,
            'lines' => $lines,
            'actuals' => $actuals,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->status === Budget::STATUS_APPROVED) {
            Yii::$app->session->setFlash('error', 'لا يمكن تعديل موازنة معتمدة');
            return $this->redirect(['view', 'id' => $id]);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'تم تحديث الموازنة');
            return $this->redirect(['view', 'id' => $id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionAddLine($id)
    {
        $budget = $this->findModel($id);
        if ($budget->status === Budget::STATUS_APPROVED) {
            Yii::$app->session->setFlash('error', 'لا يمكن تعديل موازنة معتمدة');
            return $this->redirect(['view', 'id' => $id]);
        }

        $line = new BudgetLine();
        $line->budget_id = $id;

        if ($line->load(Yii::$app->request->post())) {
            if ($line->save()) {
                $budget->recalculateTotal();
                Yii::$app->session->setFlash('success', 'تمت إضافة بند الموازنة');
            } else {
                Yii::$app->session->setFlash('error', 'خطأ في إضافة البند: ' . implode(', ', $line->getFirstErrors()));
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionRemoveLine($id, $line_id)
    {
        $budget = $this->findModel($id);
        if ($budget->status === Budget::STATUS_APPROVED) {
            Yii::$app->session->setFlash('error', 'لا يمكن تعديل موازنة معتمدة');
            return $this->redirect(['view', 'id' => $id]);
        }

        $line = BudgetLine::findOne(['id' => $line_id, 'budget_id' => $id]);
        if ($line) {
            $line->delete();
            $budget->recalculateTotal();
            Yii::$app->session->setFlash('success', 'تم حذف البند');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionApprove($id)
    {
        $model = $this->findModel($id);
        if ($model->approve()) {
            Yii::$app->session->setFlash('success', 'تم اعتماد الموازنة');
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if ($model->status === Budget::STATUS_APPROVED) {
            Yii::$app->session->setFlash('error', 'لا يمكن حذف موازنة معتمدة');
        } else {
            $model->delete();
            Yii::$app->session->setFlash('success', 'تم حذف الموازنة');
        }
        return $this->redirect(['index']);
    }

    public function actionVariance($id)
    {
        $model = $this->findModel($id);
        $lines = BudgetLine::find()
            ->with(['account', 'costCenter'])
            ->where(['budget_id' => $id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        // Monthly actuals
        $monthlyActuals = [];
        foreach ($lines as $line) {
            for ($m = 1; $m <= 12; $m++) {
                $fy = $model->fiscalYear;
                if (!$fy) continue;
                $year = date('Y', strtotime($fy->start_date));
                $monthStart = sprintf('%04d-%02d-01', $year, $m);
                $monthEnd = date('Y-m-t', strtotime($monthStart));

                $actual = JournalEntryLine::find()
                    ->joinWith('journalEntry')
                    ->where([
                        '{{%journal_entry_lines}}.account_id' => $line->account_id,
                        '{{%journal_entries}}.status' => 'posted',
                    ])
                    ->andWhere(['>=', '{{%journal_entries}}.entry_date', $monthStart])
                    ->andWhere(['<=', '{{%journal_entries}}.entry_date', $monthEnd])
                    ->select([
                        'total_debit' => 'SUM({{%journal_entry_lines}}.debit)',
                        'total_credit' => 'SUM({{%journal_entry_lines}}.credit)',
                    ])
                    ->asArray()
                    ->one();

                $account = $line->account;
                if ($account->nature === 'debit') {
                    $monthlyActuals[$line->id][$m] = ((float)($actual['total_debit'] ?? 0)) - ((float)($actual['total_credit'] ?? 0));
                } else {
                    $monthlyActuals[$line->id][$m] = ((float)($actual['total_credit'] ?? 0)) - ((float)($actual['total_debit'] ?? 0));
                }
            }
        }

        return $this->render('variance', [
            'model' => $model,
            'lines' => $lines,
            'monthlyActuals' => $monthlyActuals,
        ]);
    }

    protected function findModel($id)
    {
        if (($model = Budget::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة');
    }
}
