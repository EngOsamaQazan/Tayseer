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
                        'actions' => ['index'],
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

        return $this->render('index', [
            'stats' => $stats,
            'recentEntries' => $recentEntries,
        ]);
    }
}
