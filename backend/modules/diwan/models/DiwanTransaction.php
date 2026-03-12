<?php

namespace backend\modules\diwan\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use common\models\User;

/**
 * معاملات الديوان (استلام / تسليم)
 *
 * @property int $id
 * @property string $transaction_type
 * @property int $from_employee_id
 * @property int $to_employee_id
 * @property string|null $receipt_number
 * @property string|null $notes
 * @property string $transaction_date
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $created_at
 * @property int|null $updated_at
 */
class DiwanTransaction extends \yii\db\ActiveRecord
{
    /** أنواع المعاملات */
    const TYPE_RECEIVE = 'استلام';
    const TYPE_DELIVER = 'تسليم';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_diwan_transactions';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transaction_type', 'from_employee_id', 'to_employee_id', 'transaction_date'], 'required'],
            [['from_employee_id', 'to_employee_id', 'created_by', 'updated_by', 'created_at', 'updated_at'], 'integer'],
            [['notes'], 'string'],
            [['transaction_date'], 'safe'],
            [['transaction_type'], 'string', 'max' => 20],
            [['transaction_type'], 'in', 'range' => [self::TYPE_RECEIVE, self::TYPE_DELIVER]],
            [['receipt_number'], 'string', 'max' => 50],
            [['receipt_number'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'رقم المعاملة',
            'transaction_type' => 'نوع المعاملة',
            'from_employee_id' => 'من موظف',
            'to_employee_id' => 'إلى موظف',
            'receipt_number' => 'رقم الإيصال',
            'notes' => 'ملاحظات',
            'transaction_date' => 'تاريخ المعاملة',
            'created_by' => 'أنشئ بواسطة',
            'updated_by' => 'عُدّل بواسطة',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التعديل',
        ];
    }

    /**
     * توليد رقم إيصال فريد
     */
    public function generateReceiptNumber()
    {
        $this->receipt_number = 'RCP-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * أنواع المعاملات
     */
    public static function getTypeList()
    {
        return [
            self::TYPE_RECEIVE => 'استلام',
            self::TYPE_DELIVER => 'تسليم',
        ];
    }

    /* ═══ العلاقات ═══ */

    public function getFromEmployee()
    {
        return $this->hasOne(User::class, ['id' => 'from_employee_id']);
    }

    public function getToEmployee()
    {
        return $this->hasOne(User::class, ['id' => 'to_employee_id']);
    }

    public function getCreatedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getDetails()
    {
        return $this->hasMany(DiwanTransactionDetail::class, ['transaction_id' => 'id']);
    }

    /**
     * عدد العقود في هذه المعاملة
     */
    public function getContractCount()
    {
        return $this->hasMany(DiwanTransactionDetail::class, ['transaction_id' => 'id'])->count();
    }
}
