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
 * This is the model class for table "{{%hr_attendance}}".
 * سجل الحضور والانصراف
 *
 * @property int $id
 * @property int $user_id
 * @property string $attendance_date
 * @property string|null $check_in_time
 * @property string|null $check_out_time
 * @property string|null $status
 * @property int|null $shift_id
 * @property float|null $total_hours
 * @property float|null $overtime_hours
 * @property int|null $late_minutes
 * @property int|null $early_leave_minutes
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrAttendance extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_attendance}}';
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
            [['user_id', 'attendance_date'], 'required'],
            [['user_id', 'shift_id', 'late_minutes', 'early_leave_minutes', 'is_adjusted', 'adjusted_by', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['attendance_date', 'check_in_time', 'check_out_time'], 'safe'],
            [['check_in_lat', 'check_in_lng', 'check_out_lat', 'check_out_lng', 'total_hours', 'overtime_hours'], 'number'],
            [['check_in_method'], 'in', 'range' => ['manual', 'gps', 'qr', 'biometric', 'system']],
            [['check_out_method'], 'in', 'range' => ['manual', 'gps', 'qr', 'biometric', 'system']],
            [['status'], 'in', 'range' => ['present', 'absent', 'leave', 'holiday', 'half_day', 'remote']],
            [['check_in_note', 'check_out_note'], 'string', 'max' => 300],
            [['adjustment_reason', 'notes'], 'string'],
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
            'attendance_date' => Yii::t('app', 'تاريخ الحضور'),
            'check_in_time' => Yii::t('app', 'وقت الدخول'),
            'check_out_time' => Yii::t('app', 'وقت الخروج'),
            'check_in_method' => Yii::t('app', 'طريقة الدخول'),
            'check_out_method' => Yii::t('app', 'طريقة الخروج'),
            'status' => Yii::t('app', 'الحالة'),
            'shift_id' => Yii::t('app', 'الوردية'),
            'total_hours' => Yii::t('app', 'إجمالي الساعات'),
            'overtime_hours' => Yii::t('app', 'ساعات العمل الإضافي'),
            'late_minutes' => Yii::t('app', 'دقائق التأخير'),
            'early_leave_minutes' => Yii::t('app', 'دقائق المغادرة المبكرة'),
            'is_adjusted' => Yii::t('app', 'معدّل'),
            'adjusted_by' => Yii::t('app', 'معدّل بواسطة'),
            'adjustment_reason' => Yii::t('app', 'سبب التعديل'),
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
    public function getShift()
    {
        return $this->hasOne(HrWorkShift::class, ['id' => 'shift_id']);
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
