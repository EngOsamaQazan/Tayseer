<?php

namespace backend\modules\accounting\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use backend\modules\accounting\models\JournalEntry;
use backend\modules\accounting\models\Receivable;
use backend\modules\accounting\models\Payable;
use backend\modules\accounting\models\Account;
use backend\modules\accounting\models\FiscalYear;
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
                        'actions' => ['index', 'dismiss-cash-migration-alert'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_VIEW],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $currentYear = FiscalYear::getCurrentYear();

        $stats = [
            'journal_count' => JournalEntry::find()->count(),
            'posted_count' => JournalEntry::find()->where(['status' => 'posted'])->count(),
            'draft_count' => JournalEntry::find()->where(['status' => 'draft'])->count(),
            'receivable_balance' => Receivable::find()->sum('balance') ?: 0,
            'payable_balance' => Payable::find()->sum('balance') ?: 0,
            'overdue_receivables' => Receivable::find()->where(['status' => 'overdue'])->sum('balance') ?: 0,
            'overdue_payables' => Payable::find()->where(['status' => 'overdue'])->sum('balance') ?: 0,
            'accounts_count' => Account::find()->where(['is_active' => 1])->count(),
            'fiscal_year' => $currentYear ? $currentYear->name : 'غير محددة',
        ];

        $recentEntries = JournalEntry::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10)
            ->all();

        try {
            $cashFundBalances = Account::getCashFundBalances();
            $totalCashPosition = array_sum(array_column($cashFundBalances, 'balance'));
        } catch (\Exception $e) {
            $cashFundBalances = [];
            $totalCashPosition = 0;
            Yii::warning('Cash fund balances error: ' . $e->getMessage(), 'accounting');
        }

        $migrationNeedsReview = $this->checkCashMigrationReview();

        return $this->render('index', [
            'stats' => $stats,
            'recentEntries' => $recentEntries,
            'cashFundBalances' => $cashFundBalances,
            'totalCashPosition' => $totalCashPosition,
            'migrationNeedsReview' => $migrationNeedsReview,
        ]);
    }

    /**
     * AJAX: dismiss the cash migration review alert.
     */
    public function actionDismissCashMigrationAlert()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->cache->set('cash_migration_reviewed', true, 0);
        return ['success' => true];
    }

    private function checkCashMigrationReview()
    {
        try {
            if (Yii::$app->cache->get('cash_migration_reviewed')) {
                return false;
            }
            $schema = Yii::$app->db->getTableSchema('{{%company_banks}}');
            if (!$schema || !$schema->getColumn('account_id')) {
                return false;
            }
            $linked = (new \yii\db\Query())
                ->from('{{%company_banks}}')
                ->where(['is_deleted' => 0])
                ->andWhere(['not', ['account_id' => null]])
                ->count();
            return $linked > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
