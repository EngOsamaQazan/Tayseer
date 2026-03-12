<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;

/**
 * Model for table "{{%hr_annual_increment}}".
 * العلاوات السنوية
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $service_year  سنة الخدمة (1، 2، 3...)
 * @property int|null $increment_year سنة تقويمية (قديم، للتوافق فقط)
 * @property string $increment_type  fixed|percentage
 * @property float $amount
 * @property float|null $calculated_amount
 * @property float|null $previous_salary
 * @property float|null $new_salary
 * @property string $effective_date
 * @property string $status
 * @property int|null $approved_by
 * @property int|null $approved_at
 * @property int|null $applied_at
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrAnnualIncrement extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_annual_increment}}';
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
            [['user_id', 'increment_type', 'amount', 'effective_date'], 'required'],
            [['user_id', 'service_year', 'increment_year', 'approved_by', 'approved_at', 'applied_at', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['service_year'], 'integer', 'min' => 1, 'max' => 50],
            [['amount', 'calculated_amount', 'previous_salary', 'new_salary'], 'number'],
            [['increment_type'], 'in', 'range' => ['fixed', 'percentage']],
            [['status'], 'in', 'range' => ['pending', 'approved', 'applied', 'cancelled']],
            [['effective_date'], 'date', 'format' => 'php:Y-m-d'],
            [['notes'], 'string'],
            [['status'], 'default', 'value' => 'pending'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'المعرف',
            'user_id' => 'الموظف',
            'service_year' => 'سنة الخدمة',
            'increment_year' => 'سنة تقويمية (قديم)',
            'increment_type' => 'نوع العلاوة',
            'amount' => 'المبلغ / النسبة',
            'calculated_amount' => 'المبلغ المحسوب',
            'previous_salary' => 'الراتب السابق',
            'new_salary' => 'الراتب الجديد',
            'effective_date' => 'تاريخ السريان',
            'status' => 'الحالة',
            'notes' => 'ملاحظات',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    /**
     * Status labels
     */
    public static function statusLabels()
    {
        return [
            'pending'   => 'بانتظار الاعتماد',
            'approved'  => 'معتمدة',
            'applied'   => 'مطبّقة',
            'cancelled' => 'ملغية',
        ];
    }

    /**
     * Type labels
     */
    public static function typeLabels()
    {
        return [
            'fixed'      => 'مبلغ ثابت',
            'percentage' => 'نسبة من الراتب الأساسي',
        ];
    }

    public function getStatusLabel()
    {
        return static::statusLabels()[$this->status] ?? $this->status;
    }

    public function getTypeLabel()
    {
        return static::typeLabels()[$this->increment_type] ?? $this->increment_type;
    }

    /**
     * وصف سنة العلاوة للعرض: سنة الخدمة X أو سنة تقويمية X (قديم)
     */
    public function getYearLabel()
    {
        if ($this->service_year !== null && (int) $this->service_year > 0) {
            $n = (int) $this->service_year;
            $ordinals = [1 => 'الأولى', 2 => 'الثانية', 3 => 'الثالثة', 4 => 'الرابعة', 5 => 'الخامسة', 6 => 'السادسة', 7 => 'السابعة', 8 => 'الثامنة', 9 => 'التاسعة', 10 => 'العاشرة'];
            $label = $ordinals[$n] ?? '#' . $n;
            return 'سنة الخدمة ' . $label . ' (' . $n . ')';
        }
        if ($this->increment_year !== null && $this->increment_year !== '') {
            return 'سنة تقويمية ' . $this->increment_year;
        }
        return '—';
    }
}
