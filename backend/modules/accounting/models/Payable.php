<?php

namespace backend\modules\accounting\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $vendor_name
 * @property int|null $vendor_id
 * @property int $account_id
 * @property float $amount
 * @property float $paid_amount
 * @property float $balance
 * @property string|null $due_date
 * @property string $status
 * @property int|null $journal_entry_id
 * @property int|null $company_id
 * @property string|null $description
 * @property string|null $category
 * @property string|null $reference_number
 * @property int|null $created_by
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property Account $account
 * @property JournalEntry $journalEntry
 */
class Payable extends ActiveRecord
{
    const STATUS_OPEN = 'open';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';

    public static function tableName()
    {
        return '{{%payables}}';
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
            [['vendor_name', 'account_id', 'amount'], 'required'],
            [['vendor_id', 'account_id', 'journal_entry_id', 'company_id', 'created_by'], 'integer'],
            [['amount', 'paid_amount', 'balance'], 'number', 'min' => 0],
            [['due_date'], 'date', 'format' => 'php:Y-m-d'],
            [['vendor_name'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 500],
            [['category', 'reference_number'], 'string', 'max' => 100],
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
            'vendor_name' => 'اسم المورد/الجهة',
            'vendor_id' => 'رقم المورد',
            'account_id' => 'الحساب',
            'amount' => 'المبلغ الأصلي',
            'paid_amount' => 'المبلغ المدفوع',
            'balance' => 'الرصيد المتبقي',
            'due_date' => 'تاريخ الاستحقاق',
            'status' => 'الحالة',
            'journal_entry_id' => 'رقم القيد',
            'company_id' => 'الشركة',
            'description' => 'الوصف',
            'category' => 'التصنيف',
            'reference_number' => 'رقم المرجع',
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

    public static function getStatuses()
    {
        return [
            self::STATUS_OPEN => 'مفتوحة',
            self::STATUS_PARTIAL => 'مدفوعة جزئيا',
            self::STATUS_PAID => 'مدفوعة بالكامل',
            self::STATUS_OVERDUE => 'متأخرة',
        ];
    }

    public static function getCategories()
    {
        return [
            'مورد' => 'مورد',
            'إيجار' => 'إيجار',
            'خدمات' => 'خدمات',
            'رواتب' => 'رواتب',
            'حكومي' => 'حكومي',
            'أخرى' => 'أخرى',
        ];
    }

    public function getStatusBadge()
    {
        $colors = [
            self::STATUS_OPEN => 'info',
            self::STATUS_PARTIAL => 'warning',
            self::STATUS_PAID => 'success',
            self::STATUS_OVERDUE => 'danger',
        ];
        $color = $colors[$this->status] ?? 'default';
        $label = self::getStatuses()[$this->status] ?? $this->status;
        return '<span class="label label-' . $color . '">' . $label . '</span>';
    }

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
