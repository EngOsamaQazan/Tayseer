<?php

namespace backend\modules\accounting\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $entry_number
 * @property string $entry_date
 * @property int $fiscal_year_id
 * @property int $fiscal_period_id
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string $description
 * @property float $total_debit
 * @property float $total_credit
 * @property string $status
 * @property int $is_auto
 * @property int|null $company_id
 * @property int|null $reversed_by
 * @property int|null $created_by
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $approved_by
 * @property int|null $approved_at
 *
 * @property JournalEntryLine[] $lines
 * @property FiscalYear $fiscalYear
 * @property FiscalPeriod $fiscalPeriod
 * @property JournalEntry $reversalEntry
 */
class JournalEntry extends ActiveRecord
{
    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_REVERSED = 'reversed';

    const REF_MANUAL = 'manual';
    const REF_INCOME = 'income';
    const REF_EXPENSE = 'expense';
    const REF_CONTRACT = 'contract';
    const REF_PAYROLL = 'payroll';
    const REF_CAPITAL = 'capital';

    public static function tableName()
    {
        return '{{%journal_entries}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['entry_date', 'fiscal_year_id', 'fiscal_period_id', 'description'], 'required'],
            [['fiscal_year_id', 'fiscal_period_id', 'reference_id', 'is_auto', 'company_id', 'reversed_by', 'created_by', 'approved_by'], 'integer'],
            [['total_debit', 'total_credit'], 'number'],
            [['description'], 'string'],
            [['entry_number'], 'string', 'max' => 30],
            [['reference_type'], 'string', 'max' => 50],
            [['entry_date'], 'date', 'format' => 'php:Y-m-d'],
            [['status'], 'in', 'range' => array_keys(self::getStatuses())],
            [['status'], 'default', 'value' => self::STATUS_DRAFT],
            [['is_auto'], 'default', 'value' => 0],
            [['fiscal_year_id'], 'exist', 'skipOnError' => true, 'targetClass' => FiscalYear::class, 'targetAttribute' => ['fiscal_year_id' => 'id']],
            [['fiscal_period_id'], 'exist', 'skipOnError' => true, 'targetClass' => FiscalPeriod::class, 'targetAttribute' => ['fiscal_period_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'entry_number' => 'رقم القيد',
            'entry_date' => 'التاريخ',
            'fiscal_year_id' => 'السنة المالية',
            'fiscal_period_id' => 'الفترة',
            'reference_type' => 'نوع المرجع',
            'reference_id' => 'رقم المرجع',
            'description' => 'البيان',
            'total_debit' => 'إجمالي المدين',
            'total_credit' => 'إجمالي الدائن',
            'status' => 'الحالة',
            'is_auto' => 'قيد تلقائي',
            'company_id' => 'الشركة',
            'reversed_by' => 'عُكس بقيد',
            'created_by' => 'أنشئ بواسطة',
            'created_at' => 'تاريخ الإنشاء',
            'approved_by' => 'اعتُمد بواسطة',
            'approved_at' => 'تاريخ الاعتماد',
        ];
    }

    public function getLines()
    {
        return $this->hasMany(JournalEntryLine::class, ['journal_entry_id' => 'id']);
    }

    public function getFiscalYear()
    {
        return $this->hasOne(FiscalYear::class, ['id' => 'fiscal_year_id']);
    }

    public function getFiscalPeriod()
    {
        return $this->hasOne(FiscalPeriod::class, ['id' => 'fiscal_period_id']);
    }

    public function getReversalEntry()
    {
        return $this->hasOne(self::class, ['id' => 'reversed_by']);
    }

    public function getCreatedByUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'created_by']);
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_POSTED => 'مرحّل',
            self::STATUS_REVERSED => 'معكوس',
        ];
    }

    public static function getReferenceTypes()
    {
        return [
            self::REF_MANUAL => 'يدوي',
            self::REF_INCOME => 'دفعة عميل',
            self::REF_EXPENSE => 'مصروف',
            self::REF_CONTRACT => 'عقد',
            self::REF_PAYROLL => 'رواتب',
            self::REF_CAPITAL => 'رأس مال',
        ];
    }

    public function getStatusBadge()
    {
        $colors = [
            self::STATUS_DRAFT => 'warning',
            self::STATUS_POSTED => 'success',
            self::STATUS_REVERSED => 'danger',
        ];
        $color = $colors[$this->status] ?? 'default';
        $label = self::getStatuses()[$this->status] ?? $this->status;
        $map = ['info' => 'badge bg-info', 'warning' => 'badge bg-warning text-dark', 'success' => 'badge bg-success', 'danger' => 'badge bg-danger', 'default' => 'badge bg-secondary', 'primary' => 'badge bg-primary'];
        $bc = $map[$color] ?? 'badge bg-secondary';
        return '<span class="' . $bc . '">' . $label . '</span>';
    }

    /**
     * Generate the next entry number for a fiscal year.
     */
    public static function generateEntryNumber($fiscalYearId)
    {
        $maxNumber = self::find()
            ->where(['fiscal_year_id' => $fiscalYearId])
            ->max('CAST(SUBSTRING(entry_number, 5) AS UNSIGNED)');

        $next = ($maxNumber ?? 0) + 1;

        $year = FiscalYear::findOne($fiscalYearId);
        $prefix = $year ? substr($year->name, -2) : date('y');

        return $prefix . '-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate totals from lines and validate balance.
     */
    public function recalculateTotals()
    {
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($this->lines as $line) {
            $totalDebit += (float)$line->debit;
            $totalCredit += (float)$line->credit;
        }
        $this->total_debit = $totalDebit;
        $this->total_credit = $totalCredit;
    }

    public function isBalanced()
    {
        return abs($this->total_debit - $this->total_credit) < 0.005;
    }

    /**
     * Post the journal entry (change status to posted).
     */
    public function post()
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }
        if (!$this->isBalanced()) {
            return false;
        }
        $this->status = self::STATUS_POSTED;
        $this->approved_by = Yii::$app->user->id;
        $this->approved_at = time();
        return $this->save(false, ['status', 'approved_by', 'approved_at']);
    }

    /**
     * Reverse a posted entry by creating a mirror entry.
     */
    public function reverse($description = null)
    {
        if ($this->status !== self::STATUS_POSTED) {
            return null;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $reversal = new self();
            $reversal->entry_date = date('Y-m-d');
            $reversal->fiscal_year_id = $this->fiscal_year_id;
            $reversal->fiscal_period_id = $this->fiscal_period_id;
            $reversal->reference_type = $this->reference_type;
            $reversal->reference_id = $this->reference_id;
            $reversal->description = $description ?: 'عكس قيد رقم ' . $this->entry_number;
            $reversal->status = self::STATUS_POSTED;
            $reversal->is_auto = 1;
            $reversal->company_id = $this->company_id;
            $reversal->created_by = Yii::$app->user->id;
            $reversal->approved_by = Yii::$app->user->id;
            $reversal->approved_at = time();
            $reversal->entry_number = self::generateEntryNumber($this->fiscal_year_id);

            if (!$reversal->save()) {
                $transaction->rollBack();
                return null;
            }

            // Mirror lines (swap debit/credit)
            foreach ($this->lines as $line) {
                $reversalLine = new JournalEntryLine();
                $reversalLine->journal_entry_id = $reversal->id;
                $reversalLine->account_id = $line->account_id;
                $reversalLine->cost_center_id = $line->cost_center_id;
                $reversalLine->debit = $line->credit;
                $reversalLine->credit = $line->debit;
                $reversalLine->description = $line->description;
                $reversalLine->contract_id = $line->contract_id;
                $reversalLine->customer_id = $line->customer_id;
                $reversalLine->save();
            }

            $reversal->recalculateTotals();
            $reversal->save(false, ['total_debit', 'total_credit']);

            $this->status = self::STATUS_REVERSED;
            $this->reversed_by = $reversal->id;
            $this->save(false, ['status', 'reversed_by']);

            $transaction->commit();
            return $reversal;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert && empty($this->entry_number)) {
            $this->entry_number = self::generateEntryNumber($this->fiscal_year_id);
        }
        return true;
    }
}
