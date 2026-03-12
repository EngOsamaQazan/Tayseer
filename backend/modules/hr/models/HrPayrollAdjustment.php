<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;

/**
 * Model for table "{{%hr_payroll_adjustment}}".
 * التعديلات/العمولات الشهرية على مستوى المسيرة
 *
 * @property int $id
 * @property int $payroll_run_id
 * @property int $user_id
 * @property string $adjustment_type
 * @property float $amount
 * @property string|null $description
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrPayrollAdjustment extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_payroll_adjustment}}';
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
            [['payroll_run_id', 'user_id', 'amount'], 'required'],
            [['payroll_run_id', 'user_id', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['amount'], 'number'],
            [['adjustment_type'], 'in', 'range' => ['commission', 'bonus', 'deduction', 'other']],
            [['description'], 'string', 'max' => 300],
            [['adjustment_type'], 'default', 'value' => 'commission'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'المعرف',
            'payroll_run_id' => 'مسيرة الرواتب',
            'user_id' => 'الموظف',
            'adjustment_type' => 'نوع التعديل',
            'amount' => 'المبلغ',
            'description' => 'الوصف',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPayrollRun()
    {
        return $this->hasOne(HrPayrollRun::class, ['id' => 'payroll_run_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    /**
     * Adjustment type labels (Arabic)
     */
    public static function typeLabels()
    {
        return [
            'commission' => 'عمولة',
            'bonus'      => 'مكافأة',
            'deduction'  => 'خصم',
            'other'      => 'أخرى',
        ];
    }

    /**
     * Get the Arabic label for this adjustment type
     */
    public function getTypeLabel()
    {
        return static::typeLabels()[$this->adjustment_type] ?? $this->adjustment_type;
    }
}
