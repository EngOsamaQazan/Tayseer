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
 * This is the model class for table "{{%hr_employee_extended}}".
 * بيانات الموظف الموسعة
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $employee_code
 * @property string|null $national_id
 * @property string|null $date_of_birth
 * @property string|null $blood_type
 * @property float|null $basic_salary
 * @property string|null $address_text
 * @property int|null $city_id
 * @property int|null $bank_id
 * @property string|null $bank_account_no
 * @property string|null $social_security_no
 * @property string|null $tax_number
 * @property string|null $iban
 * @property int|null $grade_id
 * @property int|null $branch_id
 * @property int|null $unified_branch_id
 * @property int|null $shift_id
 * @property string $employee_type  office|field|sales|hybrid
 * @property int|null $work_zone_id
 * @property string $tracking_mode  geofence_only|continuous|on_task|disabled
 * @property int|null $is_field_staff
 * @property string|null $field_role
 * @property string|null $termination_reason
 * @property string|null $contract_type
 * @property string|null $contract_start
 * @property string|null $contract_end
 * @property string|null $probation_end
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrEmployeeExtended extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_employee_extended}}';
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
            [['user_id'], 'required'],
            [['user_id', 'city_id', 'bank_id', 'grade_id', 'branch_id', 'unified_branch_id', 'shift_id', 'work_zone_id', 'is_field_staff', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['employee_type'], 'in', 'range' => ['office', 'field', 'sales', 'hybrid']],
            [['employee_type'], 'default', 'value' => 'office'],
            [['tracking_mode'], 'in', 'range' => ['geofence_only', 'continuous', 'on_task', 'disabled']],
            [['tracking_mode'], 'default', 'value' => 'geofence_only'],
            [['basic_salary'], 'number'],
            [['date_of_birth', 'contract_start', 'contract_end', 'probation_end'], 'safe'],
            [['employee_code', 'national_id', 'social_security_no', 'tax_number'], 'string', 'max' => 20],
            // تحويل القيم الفارغة إلى null قبل التحقق
            [['contract_type', 'blood_type', 'field_role', 'contract_end', 'probation_end', 'iban', 'tax_number', 'social_security_no'], 'default', 'value' => null],
            [['blood_type'], 'in', 'range' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']],
            [['address_text'], 'string', 'max' => 500],
            [['bank_account_no'], 'string', 'max' => 30],
            [['iban'], 'string', 'max' => 34],
            [['contract_type'], 'in', 'range' => ['permanent', 'contract', 'probation', 'freelance']],
            [['field_role'], 'in', 'range' => ['collector', 'inspector', 'driver', 'messenger', 'lawyer', 'other']],
            [['termination_reason', 'notes'], 'string'],
            [['user_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'المعرف'),
            'user_id' => Yii::t('app', 'الموظف'),
            'employee_code' => Yii::t('app', 'الرقم الوظيفي'),
            'national_id' => Yii::t('app', 'رقم الهوية'),
            'date_of_birth' => Yii::t('app', 'تاريخ الميلاد'),
            'blood_type' => Yii::t('app', 'فصيلة الدم'),
            'basic_salary' => Yii::t('app', 'الراتب الأساسي'),
            'address_text' => Yii::t('app', 'العنوان'),
            'city_id' => Yii::t('app', 'المدينة'),
            'bank_id' => Yii::t('app', 'البنك'),
            'bank_account_no' => Yii::t('app', 'رقم الحساب البنكي'),
            'social_security_no' => Yii::t('app', 'رقم الضمان الاجتماعي'),
            'tax_number' => Yii::t('app', 'الرقم الضريبي'),
            'iban' => Yii::t('app', 'رقم الآيبان'),
            'grade_id' => Yii::t('app', 'الدرجة الوظيفية'),
            'branch_id' => Yii::t('app', 'الفرع (قديم)'),
            'unified_branch_id' => Yii::t('app', 'الفرع'),
            'shift_id' => Yii::t('app', 'الوردية'),
            'employee_type' => Yii::t('app', 'نوع الموظف'),
            'work_zone_id' => Yii::t('app', 'منطقة العمل'),
            'tracking_mode' => Yii::t('app', 'وضع التتبع'),
            'termination_reason' => Yii::t('app', 'سبب إنهاء الخدمة'),
            'is_field_staff' => Yii::t('app', 'موظف ميداني'),
            'field_role' => Yii::t('app', 'الدور الميداني'),
            'contract_type' => Yii::t('app', 'نوع العقد'),
            'contract_start' => Yii::t('app', 'بداية العقد'),
            'contract_end' => Yii::t('app', 'نهاية العقد'),
            'probation_end' => Yii::t('app', 'نهاية فترة التجربة'),
            'notes' => Yii::t('app', 'ملاحظات'),
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
    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGrade()
    {
        return $this->hasOne(HrGrade::class, ['id' => 'grade_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShift()
    {
        return $this->hasOne(HrWorkShift::class, ['id' => 'shift_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    /** @deprecated Use getUnifiedBranch() instead */
    public function getBranch()
    {
        return $this->hasOne(\backend\modules\location\models\Location::class, ['id' => 'branch_id']);
    }

    public function getUnifiedBranch()
    {
        return $this->hasOne(\backend\modules\branch\models\Branch::class, ['id' => 'unified_branch_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkZone()
    {
        return $this->hasOne(HrWorkZone::class, ['id' => 'work_zone_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttendanceLogs()
    {
        return $this->hasMany(HrAttendanceLog::class, ['user_id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmergencyContacts()
    {
        return $this->hasMany(HrEmergencyContact::class, ['user_id' => 'user_id']);
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
