<?php

namespace backend\modules\accounting\helpers;

use Yii;
use yii\db\Exception;
use backend\modules\accounting\models\Account;
use backend\modules\accounting\models\JournalEntry;
use backend\modules\accounting\models\JournalEntryLine;
use backend\modules\accounting\models\FiscalYear;
use backend\modules\accounting\models\Receivable;
use backend\modules\accounting\models\Payable;

/**
 * AutoPostingService bridges existing financial modules (payments, expenses, loans)
 * with the double-entry accounting system by creating journal entries automatically.
 */
class AutoPostingService
{
    /**
     * Resolve the cash/fund account: use a specific sub-account if provided,
     * otherwise fall back to the first active child of 1101, then 1101 itself.
     * @param int|null $cashAccountId  specific account ID from the chart of accounts
     */
    private static function resolveCashAccount($cashAccountId = null)
    {
        if ($cashAccountId) {
            $account = Account::find()->where(['id' => $cashAccountId, 'is_active' => 1])->one();
            if ($account) {
                return $account;
            }
        }

        $parent = self::findAccountByCode('1101');
        if (!$parent) {
            return null;
        }

        if ($parent->is_parent) {
            $child = Account::find()
                ->where(['parent_id' => $parent->id, 'is_active' => 1, 'is_parent' => 0])
                ->orderBy(['code' => SORT_ASC])
                ->one();
            return $child ?: $parent;
        }

        return $parent;
    }

    /**
     * Create a journal entry when a customer payment is recorded.
     * Debit: Cash/Bank  |  Credit: Revenue/Customer Receivable
     * @param int|null $cashAccountId  specific fund account from chart of accounts
     */
    public static function postCustomerPayment($amount, $customerId = null, $contractId = null, $description = '', $date = null, $cashAccountId = null)
    {
        $date = $date ?: date('Y-m-d');
        $cashAccount = self::resolveCashAccount($cashAccountId);
        $revenueAccount = self::findAccountByCode('4100');

        if (!$cashAccount || !$revenueAccount) {
            Yii::warning('AutoPostingService: Missing cash or revenue (4100) account', 'accounting');
            return null;
        }

        $entry = self::createJournalEntry(
            'payment',
            $date,
            'دفعة عميل' . ($description ? ": {$description}" : ''),
            [
                ['account_id' => $cashAccount->id, 'debit' => $amount, 'credit' => 0, 'description' => 'نقدية واردة — ' . $cashAccount->name_ar],
                ['account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => $amount, 'description' => 'إيراد دفعة عميل'],
            ]
        );

        if ($entry && $customerId) {
            self::updateOrCreateReceivable($customerId, $contractId, $amount, $entry->id);
        }

        return $entry;
    }

    /**
     * Create a journal entry when an expense is recorded.
     * Debit: Expense Account  |  Credit: Cash/Bank
     * @param int|null $cashAccountId  specific fund account from chart of accounts
     */
    public static function postExpense($amount, $expenseCategory = null, $description = '', $date = null, $cashAccountId = null)
    {
        $date = $date ?: date('Y-m-d');
        $cashAccount = self::resolveCashAccount($cashAccountId);
        $expenseAccount = self::resolveExpenseAccount($expenseCategory);

        if (!$cashAccount || !$expenseAccount) {
            Yii::warning('AutoPostingService: Missing cash or expense account', 'accounting');
            return null;
        }

        return self::createJournalEntry(
            'expense',
            $date,
            'مصروف' . ($description ? ": {$description}" : ''),
            [
                ['account_id' => $expenseAccount->id, 'debit' => $amount, 'credit' => 0, 'description' => $description ?: 'مصروف'],
                ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => $amount, 'description' => 'صرف من ' . $cashAccount->name_ar],
            ]
        );
    }

    /**
     * Create a journal entry when a loan installment is paid.
     * Debit: Loan Receivable / Revenue  |  Credit: Cash/Bank
     * @param int|null $cashAccountId  specific fund account from chart of accounts
     */
    public static function postLoanPayment($amount, $interestAmount = 0, $description = '', $date = null, $cashAccountId = null)
    {
        $date = $date ?: date('Y-m-d');
        $cashAccount = self::resolveCashAccount($cashAccountId);
        $loanReceivable = self::findAccountByCode('1201');
        $interestRevenue = self::findAccountByCode('4200');

        if (!$cashAccount || !$loanReceivable) {
            Yii::warning('AutoPostingService: Missing loan accounts', 'accounting');
            return null;
        }

        $lines = [
            ['account_id' => $cashAccount->id, 'debit' => $amount + $interestAmount, 'credit' => 0, 'description' => 'تحصيل قسط — ' . $cashAccount->name_ar],
            ['account_id' => $loanReceivable->id, 'debit' => 0, 'credit' => $amount, 'description' => 'سداد أصل القسط'],
        ];

        if ($interestAmount > 0 && $interestRevenue) {
            $lines[] = ['account_id' => $interestRevenue->id, 'debit' => 0, 'credit' => $interestAmount, 'description' => 'إيراد فوائد'];
        }

        return self::createJournalEntry(
            'loan',
            $date,
            'سداد قسط' . ($description ? ": {$description}" : ''),
            $lines
        );
    }

    /**
     * Core method: create a balanced journal entry with lines.
     */
    private static function createJournalEntry($referenceType, $date, $description, $lines)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $fiscalYear = FiscalYear::getCurrentYear();
            $fiscalPeriod = $fiscalYear ? $fiscalYear->findPeriodByDate($date) : null;

            $entry = new JournalEntry();
            $entry->entry_number = JournalEntry::generateEntryNumber($fiscalYear ? $fiscalYear->id : null);
            $entry->entry_date = $date;
            $entry->description = $description;
            $entry->reference_type = $referenceType;
            $entry->status = 'draft';
            $entry->fiscal_year_id = $fiscalYear ? $fiscalYear->id : null;
            $entry->fiscal_period_id = $fiscalPeriod ? $fiscalPeriod->id : null;
            $entry->created_by = Yii::$app->user->id ?? null;

            $totalDebit = array_sum(array_column($lines, 'debit'));
            $totalCredit = array_sum(array_column($lines, 'credit'));
            $entry->total_debit = $totalDebit;
            $entry->total_credit = $totalCredit;

            if (abs($totalDebit - $totalCredit) > 0.005) {
                throw new Exception('Journal entry is not balanced: debit=' . $totalDebit . ' credit=' . $totalCredit);
            }

            if (!$entry->save()) {
                throw new Exception('Failed to save journal entry: ' . implode(', ', $entry->getFirstErrors()));
            }

            foreach ($lines as $lineData) {
                $line = new JournalEntryLine();
                $line->journal_entry_id = $entry->id;
                $line->account_id = $lineData['account_id'];
                $line->debit = $lineData['debit'];
                $line->credit = $lineData['credit'];
                $line->description = $lineData['description'] ?? '';
                if (!$line->save()) {
                    throw new Exception('Failed to save line: ' . implode(', ', $line->getFirstErrors()));
                }
            }

            // Auto-post the entry
            $entry->status = 'posted';
            $entry->save(false, ['status']);

            $transaction->commit();
            return $entry;

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('AutoPostingService error: ' . $e->getMessage(), 'accounting');
            return null;
        }
    }

    private static function findAccountByCode($code)
    {
        return Account::find()
            ->where(['code' => $code, 'is_active' => 1])
            ->one();
    }

    private static function resolveExpenseAccount($category)
    {
        $mapping = [
            'رواتب' => '5100',
            'إيجار' => '5200',
            'خدمات' => '5300',
            'صيانة' => '5300',
            'تسويق' => '5400',
            'إدارية' => '5500',
        ];

        $code = $mapping[$category] ?? '5500';
        return self::findAccountByCode($code);
    }

    private static function updateOrCreateReceivable($customerId, $contractId, $paymentAmount, $journalEntryId)
    {
        $receivable = Receivable::find()
            ->where(['customer_id' => $customerId, 'status' => ['open', 'partial', 'overdue']])
            ->orderBy(['due_date' => SORT_ASC])
            ->one();

        if ($receivable) {
            $receivable->recordPayment($paymentAmount);
        }
    }
}
