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
 * This is the model class for table "{{%hr_payroll_run}}".
 * دورة الرواتب
 *
 * @property int $id
 * @property string $run_code
 * @property int $period_month
 * @property int $period_year
 * @property string|null $status
 * @property float|null $total_gross
 * @property float|null $total_deductions
 * @property float|null $total_net
 * @property int|null $branch_id
 * @property int|null $total_employees
 * @property int|null $approved_at
 * @property int|null $approved_by
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrPayrollRun extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_payroll_run}}';
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
            [['run_code', 'period_month', 'period_year'], 'required'],
            [['period_month', 'period_year', 'branch_id', 'total_employees', 'approved_by', 'approved_at', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['total_gross', 'total_deductions', 'total_net'], 'number'],
            [['run_code'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => ['draft', 'calculated', 'reviewed', 'approved', 'paid', 'cancelled']],
            [['notes'], 'string'],
            [['run_code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'المعرف'),
            'run_code' => Yii::t('app', 'رمز الدورة'),
            'period_month' => Yii::t('app', 'الشهر'),
            'period_year' => Yii::t('app', 'السنة'),
            'status' => Yii::t('app', 'الحالة'),
            'total_gross' => Yii::t('app', 'إجمالي الرواتب'),
            'total_deductions' => Yii::t('app', 'إجمالي الخصومات'),
            'total_net' => Yii::t('app', 'صافي الرواتب'),
            'branch_id' => Yii::t('app', 'الفرع'),
            'total_employees' => Yii::t('app', 'عدد الموظفين'),
            'approved_at' => Yii::t('app', 'تاريخ الاعتماد'),
            'approved_by' => Yii::t('app', 'اعتمد بواسطة'),
            'notes' => Yii::t('app', 'ملاحظات'),
            'is_deleted' => Yii::t('app', 'محذوف'),
            'created_at' => Yii::t('app', 'تاريخ الإنشاء'),
            'created_by' => Yii::t('app', 'أنشئ بواسطة'),
            'updated_at' => Yii::t('app', 'تاريخ التعديل'),
            'updated_by' => Yii::t('app', 'عُدّل بواسطة'),
        ];
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
