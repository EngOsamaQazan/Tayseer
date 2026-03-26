<?php

namespace backend\modules\accounting\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use backend\modules\accounting\models\Account;
use backend\modules\accounting\models\JournalEntryLine;
use backend\modules\accounting\models\FiscalYear;
use backend\modules\accounting\helpers\FinancialReportPdf;
use common\helper\Permissions;

class FinancialStatementsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['trial-balance', 'income-statement', 'balance-sheet', 'cash-flow', 'export-pdf', 'export-single-pdf'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_VIEW, Permissions::ACC_REPORTS],
                    ],
                ],
            ],
        ];
    }

    /**
     * Helper: get account balances for a given fiscal year / date range.
     */
    private function getAccountBalances($fiscalYearId = null, $dateFrom = null, $dateTo = null)
    {
        $accounts = Account::find()
            ->where(['is_active' => 1, 'is_parent' => 0])
            ->orderBy(['code' => SORT_ASC])
            ->all();

        $balances = [];
        foreach ($accounts as $account) {
            $query = JournalEntryLine::find()
                ->joinWith('journalEntry')
                ->where(['{{%journal_entry_lines}}.account_id' => $account->id])
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

            $balance = (float)$account->opening_balance;
            if ($account->nature === 'debit') {
                $balance += $totalDebit - $totalCredit;
            } else {
                $balance += $totalCredit - $totalDebit;
            }

            $balances[$account->id] = [
                'account' => $account,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'balance' => $balance,
            ];
        }

        return $balances;
    }

    /**
     * Group balances by account type.
     */
    private function groupByType($balances, $type)
    {
        $result = [];
        foreach ($balances as $data) {
            if ($data['account']->type === $type) {
                $result[] = $data;
            }
        }
        return $result;
    }

    public function actionTrialBalance()
    {
        $request = Yii::$app->request;
        $fiscalYearId = $request->get('fiscal_year_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $currentYear = FiscalYear::getCurrentYear();
        if (!$fiscalYearId && $currentYear) {
            $fiscalYearId = $currentYear->id;
        }

        $balances = $this->getAccountBalances($fiscalYearId, $dateFrom, $dateTo);
        $fiscalYears = FiscalYear::find()->orderBy(['start_date' => SORT_DESC])->all();

        return $this->render('trial-balance', [
            'balances' => $balances,
            'fiscalYears' => $fiscalYears,
            'fiscalYearId' => $fiscalYearId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function actionIncomeStatement()
    {
        $request = Yii::$app->request;
        $fiscalYearId = $request->get('fiscal_year_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $currentYear = FiscalYear::getCurrentYear();
        if (!$fiscalYearId && $currentYear) {
            $fiscalYearId = $currentYear->id;
        }

        $balances = $this->getAccountBalances($fiscalYearId, $dateFrom, $dateTo);

        $revenue = $this->groupByType($balances, Account::TYPE_REVENUE);
        $expenses = $this->groupByType($balances, Account::TYPE_EXPENSES);

        $totalRevenue = array_sum(array_column($revenue, 'balance'));
        $totalExpenses = array_sum(array_column($expenses, 'balance'));
        $netIncome = $totalRevenue - $totalExpenses;

        $fiscalYears = FiscalYear::find()->orderBy(['start_date' => SORT_DESC])->all();

        return $this->render('income-statement', [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netIncome' => $netIncome,
            'fiscalYears' => $fiscalYears,
            'fiscalYearId' => $fiscalYearId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function actionBalanceSheet()
    {
        $request = Yii::$app->request;
        $fiscalYearId = $request->get('fiscal_year_id');
        $dateTo = $request->get('date_to', date('Y-m-d'));

        $currentYear = FiscalYear::getCurrentYear();
        if (!$fiscalYearId && $currentYear) {
            $fiscalYearId = $currentYear->id;
        }

        $balances = $this->getAccountBalances($fiscalYearId, null, $dateTo);

        $assets = $this->groupByType($balances, Account::TYPE_ASSETS);
        $liabilities = $this->groupByType($balances, Account::TYPE_LIABILITIES);
        $equity = $this->groupByType($balances, Account::TYPE_EQUITY);

        $totalAssets = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity = array_sum(array_column($equity, 'balance'));

        // Net income for the period adds to equity
        $revenue = $this->groupByType($balances, Account::TYPE_REVENUE);
        $expensesAccounts = $this->groupByType($balances, Account::TYPE_EXPENSES);
        $netIncome = array_sum(array_column($revenue, 'balance')) - array_sum(array_column($expensesAccounts, 'balance'));

        $fiscalYears = FiscalYear::find()->orderBy(['start_date' => SORT_DESC])->all();

        return $this->render('balance-sheet', [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'netIncome' => $netIncome,
            'fiscalYears' => $fiscalYears,
            'fiscalYearId' => $fiscalYearId,
            'dateTo' => $dateTo,
        ]);
    }

    public function actionCashFlow()
    {
        $request = Yii::$app->request;
        $fiscalYearId = $request->get('fiscal_year_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $currentYear = FiscalYear::getCurrentYear();
        if (!$fiscalYearId && $currentYear) {
            $fiscalYearId = $currentYear->id;
        }

        $balances = $this->getAccountBalances($fiscalYearId, $dateFrom, $dateTo);

        $cashFlowData = $this->classifyCashFlow($balances);

        $fiscalYears = FiscalYear::find()->orderBy(['start_date' => SORT_DESC])->all();

        return $this->render('cash-flow', [
            'operating' => $cashFlowData['operating'],
            'investing' => $cashFlowData['investing'],
            'financing' => $cashFlowData['financing'],
            'fiscalYears' => $fiscalYears,
            'fiscalYearId' => $fiscalYearId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    private function classifyCashFlow($balances)
    {
        $operating = [];
        $investing = [];
        $financing = [];

        foreach ($balances as $data) {
            $account = $data['account'];
            $netMovement = $data['total_debit'] - $data['total_credit'];
            if ($netMovement == 0) continue;

            switch ($account->type) {
                case Account::TYPE_REVENUE:
                case Account::TYPE_EXPENSES:
                    $operating[] = $data;
                    break;
                case Account::TYPE_ASSETS:
                    if (strpos($account->code, '12') === 0) {
                        $investing[] = $data;
                    } else {
                        $operating[] = $data;
                    }
                    break;
                case Account::TYPE_LIABILITIES:
                    $operating[] = $data;
                    break;
                case Account::TYPE_EQUITY:
                    $financing[] = $data;
                    break;
            }
        }

        return compact('operating', 'investing', 'financing');
    }

    /**
     * Build the full data array used by all financial reports.
     */
    private function buildReportData($fiscalYearId, $dateFrom = null, $dateTo = null)
    {
        $dateTo = $dateTo ?: date('Y-m-d');
        $balances = $this->getAccountBalances($fiscalYearId, $dateFrom, $dateTo);

        $assets = $this->groupByType($balances, Account::TYPE_ASSETS);
        $liabilities = $this->groupByType($balances, Account::TYPE_LIABILITIES);
        $equity = $this->groupByType($balances, Account::TYPE_EQUITY);
        $revenue = $this->groupByType($balances, Account::TYPE_REVENUE);
        $expenses = $this->groupByType($balances, Account::TYPE_EXPENSES);

        $totalAssets = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity = array_sum(array_column($equity, 'balance'));
        $totalRevenue = array_sum(array_column($revenue, 'balance'));
        $totalExpenses = array_sum(array_column($expenses, 'balance'));
        $netIncome = $totalRevenue - $totalExpenses;

        $cashFlowData = $this->classifyCashFlow($balances);

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netIncome' => $netIncome,
            'operating' => $cashFlowData['operating'],
            'investing' => $cashFlowData['investing'],
            'financing' => $cashFlowData['financing'],
        ];
    }

    /**
     * Export complete financial statements package as PDF.
     */
    public function actionExportPdf()
    {
        $request = Yii::$app->request;
        $fiscalYearId = $request->get('fiscal_year_id');
        $dateTo = $request->get('date_to', date('Y-m-d'));

        $currentYear = FiscalYear::getCurrentYear();
        if (!$fiscalYearId && $currentYear) {
            $fiscalYearId = $currentYear->id;
        }

        $fiscalYear = $fiscalYearId ? FiscalYear::findOne($fiscalYearId) : null;
        $data = $this->buildReportData($fiscalYearId, null, $dateTo);

        $report = new FinancialReportPdf($fiscalYear, $dateTo);
        return $report->generateFullPackage($data);
    }

    /**
     * Export a single financial statement as PDF.
     */
    public function actionExportSinglePdf($type = 'balance-sheet')
    {
        $request = Yii::$app->request;
        $fiscalYearId = $request->get('fiscal_year_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to', date('Y-m-d'));

        $currentYear = FiscalYear::getCurrentYear();
        if (!$fiscalYearId && $currentYear) {
            $fiscalYearId = $currentYear->id;
        }

        $fiscalYear = $fiscalYearId ? FiscalYear::findOne($fiscalYearId) : null;
        $data = $this->buildReportData($fiscalYearId, $dateFrom, $dateTo);

        $report = new FinancialReportPdf($fiscalYear, $dateTo);
        return $report->generateSingleReport($type, $data);
    }
}
