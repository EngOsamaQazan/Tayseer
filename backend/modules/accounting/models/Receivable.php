<?php

namespace backend\modules\accounting\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * @property int $id
 * @property int|null $customer_id
 * @property int|null $contract_id
 * @property int|null $invoice_id
 * @property int $account_id
 * @property float $amount
 * @property float $paid_amount
 * @property float $balance
 * @property string|null $due_date
 * @property string $status
 * @property int|null $journal_entry_id
 * @property int|null $company_id
 * @property string|null $description
 * @property int|null $created_by
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property Account $account
 * @property JournalEntry $journalEntry
 */
class Receivable extends ActiveRecord
{
    const STATUS_OPEN = 'open';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_WRITTEN_OFF = 'written_off';

    public static function tableName()
    {
        return '{{%receivables}}';
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
            [['account_id', 'amount'], 'required'],
            [['customer_id', 'contract_id', 'invoice_id', 'account_id', 'journal_entry_id', 'company_id', 'created_by'], 'integer'],
            [['amount', 'paid_amount', 'balance'], 'number', 'min' => 0],
            [['due_date'], 'date', 'format' => 'php:Y-m-d'],
            [['description'], 'string', 'max' => 500],
            [['status'], 'in', 'range' => array_keys(self::getStatuses())],
            [['status'], 'default', 'value' => self::STATUS_OPEN],
            [['paid_amount'], 'default', 'value' => 0],
            [['account_id'], 'exist', 'skipOnError' => true, 'targetClass' => Account::class, 'targetAttribute' => ['account_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'customer_id' => 'العميل',
            'contract_id' => 'العقد',
            'invoice_id' => 'الفاتورة',
            'account_id' => 'الحساب',
            'amount' => 'المبلغ الأصلي',
            'paid_amount' => 'المبلغ المدفوع',
            'balance' => 'الرصيد المتبقي',
            'due_date' => 'تاريخ الاستحقاق',
            'status' => 'الحالة',
            'journal_entry_id' => 'رقم القيد',
            'company_id' => 'الشركة',
            'description' => 'الوصف',
            'created_by' => 'أنشئ بواسطة',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
        ];
    }

    public function getAccount()
    {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    public function getJournalEntry()
    {
        return $this->hasOne(JournalEntry::class, ['id' => 'journal_entry_id']);
    }

    public function getCustomer()
    {
        return $this->hasOne(\backend\modules\customers\models\Customers::class, ['id' => 'customer_id']);
    }

    public function getContract()
    {
        return $this->hasOne(\backend\modules\contracts\models\Contracts::class, ['id' => 'contract_id']);
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_OPEN => 'مفتوحة',
            self::STATUS_PARTIAL => 'مدفوعة جزئيا',
            self::STATUS_PAID => 'مدفوعة بالكامل',
            self::STATUS_OVERDUE => 'متأخرة',
            self::STATUS_WRITTEN_OFF => 'مشطوبة',
        ];
    }

    public function getStatusBadge()
    {
        $colors = [
            self::STATUS_OPEN => 'info',
            self::STATUS_PARTIAL => 'warning',
            self::STATUS_PAID => 'success',
            self::STATUS_OVERDUE => 'danger',
            self::STATUS_WRITTEN_OFF => 'default',
        ];
        $color = $colors[$this->status] ?? 'default';
        $label = self::getStatuses()[$this->status] ?? $this->status;
        return '<span class="label label-' . $color . '">' . $label . '</span>';
    }

    /**
     * Record a payment against this receivable.
     */
    public function recordPayment($paymentAmount)
    {
        $this->paid_amount += $paymentAmount;
        $this->balance = $this->amount - $this->paid_amount;

        if ($this->balance <= 0.005) {
            $this->balance = 0;
            $this->status = self::STATUS_PAID;
        } else {
            $this->status = self::STATUS_PARTIAL;
        }

        return $this->save(false, ['paid_amount', 'balance', 'status']);
    }

    /**
     * Update overdue status based on due date.
     */
    public function checkOverdue()
    {
        if ($this->due_date && $this->status === self::STATUS_OPEN && $this->due_date < date('Y-m-d')) {
            $this->status = self::STATUS_OVERDUE;
            $this->save(false, ['status']);
        }
    }

    /**
     * Get aging bucket (0-30, 31-60, 61-90, 90+).
     */
    public function getAgingDays()
    {
        if (!$this->due_date || $this->status === self::STATUS_PAID) {
            return 0;
        }
        $dueDate = new \DateTime($this->due_date);
        $today = new \DateTime();
        $diff = $today->diff($dueDate);
        return $diff->invert ? $diff->days : 0;
    }

    public function getAgingBucket()
    {
        $days = $this->getAgingDays();
        if ($days == 0) return 'جاري';
        if ($days <= 30) return '1-30 يوم';
        if ($days <= 60) return '31-60 يوم';
        if ($days <= 90) return '61-90 يوم';
        return '+90 يوم';
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert) {
            $this->balance = $this->amount - $this->paid_amount;
        }
        return true;
    }
}
