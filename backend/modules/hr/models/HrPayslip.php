<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

/**
 * This is the model class for table "{{%hr_payslip}}".
 * كشف الراتب
 *
 * @property int $id
 * @property int $payroll_run_id
 * @property int $user_id
 * @property float|null $basic_salary
 * @property float|null $total_earnings
 * @property float|null $total_deductions
 * @property float|null $net_salary
 * @property int|null $working_days
 * @property int|null $present_days
 * @property int|null $absent_days
 * @property int|null $leave_days
 * @property float|null $overtime_hours
 * @property float|null $late_deduction
 * @property string|null $status
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrPayslip extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_payslip}}';
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
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::class,
                'softDeleteAttributeValues' => [
                    'is_deleted' => true,
                ],
                'replaceRegularDelete' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['payroll_run_id', 'user_id'], 'required'],
            [['payroll_run_id', 'user_id', 'working_days', 'present_days', 'absent_days', 'leave_days', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['basic_salary', 'total_earnings', 'total_deductions', 'net_salary', 'overtime_hours', 'late_deduction'], 'number'],
            [['status'], 'in', 'range' => ['draft', 'confirmed', 'paid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'المعرف'),
            'payroll_run_id' => Yii::t('app', 'دورة الرواتب'),
            'user_id' => Yii::t('app', 'الموظف'),
            'basic_salary' => Yii::t('app', 'الراتب الأساسي'),
            'total_earnings' => Yii::t('app', 'إجمالي المستحقات'),
            'total_deductions' => Yii::t('app', 'إجمالي الخصومات'),
            'net_salary' => Yii::t('app', 'صافي الراتب'),
            'working_days' => Yii::t('app', 'أيام العمل'),
            'present_days' => Yii::t('app', 'أيام الحضور'),
            'absent_days' => Yii::t('app', 'أيام الغياب'),
            'leave_days' => Yii::t('app', 'أيام الإجازة'),
            'overtime_hours' => Yii::t('app', 'ساعات العمل الإضافي'),
            'late_deduction' => Yii::t('app', 'خصم التأخير'),
            'status' => Yii::t('app', 'الحالة'),
            'is_deleted' => Yii::t('app', 'محذوف'),
            'created_at' => Yii::t('app', 'تاريخ الإنشاء'),
            'created_by' => Yii::t('app', 'أنشئ بواسطة'),
            'updated_at' => Yii::t('app', 'تاريخ التعديل'),
            'updated_by' => Yii::t('app', 'عُدّل بواسطة'),
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
     * @return \yii\db\ActiveQuery
     */
    public function getLines()
    {
        return $this->hasMany(HrPayslipLine::class, ['payslip_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     */
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
