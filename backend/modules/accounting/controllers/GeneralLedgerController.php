<?php

namespace backend\modules\accounting\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\data\ArrayDataProvider;
use backend\modules\accounting\models\Account;
use backend\modules\accounting\models\JournalEntryLine;
use backend\modules\accounting\models\FiscalYear;
use common\helper\Permissions;

class GeneralLedgerController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'account'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_VIEW, Permissions::ACC_REPORTS],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $request = Yii::$app->request;
        $fiscalYearId = $request->get('fiscal_year_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $currentYear = FiscalYear::getCurrentYear();
        if (!$fiscalYearId && $currentYear) {
            $fiscalYearId = $currentYear->id;
        }

        $accounts = Account::find()
            ->where(['is_active' => 1, 'is_parent' => 0])
            ->orderBy(['code' => SORT_ASC])
            ->all();

        $ledgerData = [];
        foreach ($accounts as $account) {
            $query = JournalEntryLine::find()
                ->joinWith('journalEntry')
                ->where(['account_id' => $account->id])
                ->andWhere(['{{%journal_entries}}.status' => 'posted']);

            if ($fiscalYearId) {
                $query->andWhere(['{{%journal_entries}}.fiscal_year_id' => $fiscalYearId]);
            }
            if ($dateFrom) {
                $query->andWhere(['>=', '{{%journal_entries}}.entry_date', $dateFrom]);
            }
            if ($dateTo) {
                $query->andWhere(['<=', '{{%journal_entries}}.entry_date', $dateTo]);
            }

            $totalDebit = (float)$query->sum('{{%journal_entry_lines}}.debit') ?: 0;
            $totalCredit = (float)$query->sum('{{%journal_entry_lines}}.credit') ?: 0;
            $balance = $account->opening_balance;

            if ($account->nature === 'debit') {
                $balance += $totalDebit - $totalCredit;
            } else {
                $balance += $totalCredit - $totalDebit;
            }

            if ($totalDebit == 0 && $totalCredit == 0 && $account->opening_balance == 0) {
                continue;
            }

            $ledgerData[] = [
                'account' => $account,
                'opening_balance' => $account->opening_balance,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'closing_balance' => $balance,
            ];
        }

        $dataProvider = new ArrayDataProvider([
            'allModels' => $ledgerData,
            'pagination' => ['pageSize' => 50],
        ]);

        $fiscalYears = FiscalYear::find()->orderBy(['start_date' => SORT_DESC])->all();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'fiscalYears' => $fiscalYears,
            'fiscalYearId' => $fiscalYearId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function actionAccount($id)
    {
        $account = Account::findOne($id);
        if (!$account) {
            throw new \yii\web\NotFoundHttpException('الحساب غير موجود');
        }

        $request = Yii::$app->request;
        $fiscalYearId = $request->get('fiscal_year_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = JournalEntryLine::find()
            ->joinWith(['journalEntry', 'account'])
            ->where(['{{%journal_entry_lines}}.account_id' => $id])
            ->andWhere(['{{%journal_entries}}.status' => 'posted'])
            ->orderBy(['{{%journal_entries}}.entry_date' => SORT_ASC, '{{%journal_entries}}.id' => SORT_ASC]);

        if ($fiscalYearId) {
            $query->andWhere(['{{%journal_entries}}.fiscal_year_id' => $fiscalYearId]);
        }
        if ($dateFrom) {
            $query->andWhere(['>=', '{{%journal_entries}}.entry_date', $dateFrom]);
        }
        if ($dateTo) {
            $query->andWhere(['<=', '{{%journal_entries}}.entry_date', $dateTo]);
        }

        $lines = $query->all();

        $fiscalYears = FiscalYear::find()->orderBy(['start_date' => SORT_DESC])->all();

        return $this->render('account', [
            'account' => $account,
            'lines' => $lines,
            'fiscalYears' => $fiscalYears,
            'fiscalYearId' => $fiscalYearId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}
