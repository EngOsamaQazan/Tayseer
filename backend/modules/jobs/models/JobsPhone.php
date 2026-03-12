<?php

namespace backend\modules\jobs\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "os_jobs_phones".
 *
 * @property int $id
 * @property int $job_id
 * @property string $phone_number
 * @property string $phone_type
 * @property string|null $employee_name
 * @property string|null $employee_position
 * @property string|null $department
 * @property int $is_primary
 * @property string|null $notes
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $created_by
 * @property int $is_deleted
 *
 * @property Jobs $job
 */
class JobsPhone extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_jobs_phones';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => false,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['job_id', 'phone_number'], 'required'],
            [['job_id', 'is_primary', 'is_deleted'], 'integer'],
            [['phone_number'], 'string', 'max' => 20],
            [['phone_type'], 'in', 'range' => ['office', 'mobile', 'fax', 'whatsapp']],
            [['phone_type'], 'default', 'value' => 'office'],
            [['employee_name', 'employee_position', 'department'], 'string', 'max' => 255],
            [['notes'], 'string', 'max' => 500],
            [['is_primary'], 'default', 'value' => 0],
            [['is_deleted'], 'default', 'value' => 0],
            [['job_id'], 'exist', 'skipOnError' => true, 'targetClass' => Jobs::class, 'targetAttribute' => ['job_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '#',
            'job_id' => Yii::t('app', 'جهة العمل'),
            'phone_number' => Yii::t('app', 'رقم الهاتف'),
            'phone_type' => Yii::t('app', 'نوع الرقم'),
            'employee_name' => Yii::t('app', 'اسم الموظف'),
            'employee_position' => Yii::t('app', 'منصب الموظف'),
            'department' => Yii::t('app', 'القسم'),
            'is_primary' => Yii::t('app', 'رقم أساسي'),
            'notes' => Yii::t('app', 'ملاحظات'),
            'created_at' => Yii::t('app', 'تاريخ الإنشاء'),
            'updated_at' => Yii::t('app', 'تاريخ التحديث'),
            'created_by' => Yii::t('app', 'أنشئ بواسطة'),
            'is_deleted' => Yii::t('app', 'محذوف'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getJob()
    {
        return $this->hasOne(Jobs::class, ['id' => 'job_id']);
    }

    /**
     * Get phone type label in Arabic
     * @return string
     */
    public function getPhoneTypeLabel()
    {
        $types = self::getPhoneTypes();
        return $types[$this->phone_type] ?? $this->phone_type;
    }

    /**
     * @return array phone type options
     */
    public static function getPhoneTypes()
    {
        return [
            'office' => 'هاتف مكتب',
            'mobile' => 'موبايل',
            'fax' => 'فاكس',
            'whatsapp' => 'واتساب',
        ];
    }

    /**
     * Default scope: exclude soft-deleted
     */
    public static function find()
    {
        return parent::find()->andWhere(['os_jobs_phones.is_deleted' => 0]);
    }

    /**
     * Soft delete
     */
    public function softDelete()
    {
        $this->is_deleted = 1;
        return $this->save(false);
    }
}
