<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\behaviors\BlameableBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

/**
 * @property int $id
 * @property int $judiciary_id
 * @property int|null $customer_id
 * @property string $deadline_type
 * @property string $day_type
 * @property string $label
 * @property string $start_date
 * @property string $deadline_date
 * @property string $status
 * @property int|null $related_communication_id
 * @property int|null $related_customer_action_id
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $updated_at
 * @property int $created_by
 */
class JudiciaryDeadline extends \yii\db\ActiveRecord
{
    const TYPE_REGISTRATION_3WD     = 'registration_3wd';
    const TYPE_NOTIFICATION_CHECK   = 'notification_check_3wd';
    const TYPE_NOTIFICATION_16CD    = 'notification_16cd';
    const TYPE_REQUEST_DECISION     = 'request_decision_3wd';
    const TYPE_CORRESPONDENCE_10WD  = 'correspondence_10wd';
    const TYPE_PROPERTY_7CD         = 'property_7cd';
    const TYPE_SALARY_3M            = 'salary_3m';
    const TYPE_LETTERS_ISSUANCE_3WD = 'letters_issuance_3wd';
    const TYPE_COMPREHENSIVE_16CD   = 'comprehensive_request_16cd';
    const TYPE_CUSTOM               = 'custom';

    const DAY_WORKING  = 'working';
    const DAY_CALENDAR = 'calendar';

    const STATUS_PENDING     = 'pending';
    const STATUS_APPROACHING = 'approaching';
    const STATUS_EXPIRED     = 'expired';
    const STATUS_COMPLETED   = 'completed';

    public static function tableName()
    {
        return 'os_judiciary_deadlines';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => false,
            ],
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::class,
                'softDeleteAttributeValues' => ['is_deleted' => true],
                'replaceRegularDelete' => true,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['judiciary_id', 'deadline_type', 'day_type', 'label', 'start_date', 'deadline_date'], 'required'],
            [['judiciary_id', 'customer_id', 'related_communication_id', 'related_customer_action_id', 'is_deleted', 'created_at', 'updated_at', 'created_by'], 'integer'],
            [['deadline_type'], 'string', 'max' => 30],
            [['day_type'], 'string', 'max' => 10],
            [['label'], 'string', 'max' => 255],
            [['start_date', 'deadline_date'], 'date', 'format' => 'php:Y-m-d'],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_APPROACHING, self::STATUS_EXPIRED, self::STATUS_COMPLETED]],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['notes'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'judiciary_id' => 'القضية',
            'customer_id' => 'المحكوم عليه',
            'deadline_type' => 'نوع المهلة',
            'day_type' => 'نوع الأيام',
            'label' => 'الوصف',
            'start_date' => 'تاريخ البدء',
            'deadline_date' => 'تاريخ الاستحقاق',
            'status' => 'الحالة',
            'notes' => 'ملاحظات',
        ];
    }

    public static function getTypeLabels()
    {
        return [
            self::TYPE_REGISTRATION_3WD    => 'فحص بعد التسجيل (3 أيام عمل)',
            self::TYPE_NOTIFICATION_CHECK  => 'فحص التبليغ (3 أيام عمل)',
            self::TYPE_NOTIFICATION_16CD   => 'مدة التبليغ (16 يوم)',
            self::TYPE_REQUEST_DECISION    => 'قرار القاضي (3 أيام عمل)',
            self::TYPE_CORRESPONDENCE_10WD => 'رد جهة (10 أيام عمل)',
            self::TYPE_PROPERTY_7CD        => 'إخطار عقار (7 أيام)',
            self::TYPE_SALARY_3M           => 'إعادة كتاب راتب (3 أشهر)',
            self::TYPE_LETTERS_ISSUANCE_3WD => 'إصدار الكتب بعد الطلب (3 أيام عمل)',
            self::TYPE_COMPREHENSIVE_16CD   => 'موعد تقديم الطلب الشامل (16 يوم)',
            self::TYPE_CUSTOM              => 'مخصص',
            // Legacy keys without suffix (data inserted by judiciary-v3 or older code)
            'registration'         => 'فحص بعد التسجيل',
            'notification_check'   => 'فحص التبليغ',
            'notification'         => 'مدة التبليغ',
            'request_decision'     => 'قرار القاضي',
            'correspondence'       => 'رد جهة',
            'property'             => 'إخطار عقار',
            'salary'               => 'إعادة كتاب راتب',
        ];
    }

    public static function getStatusLabels()
    {
        return [
            self::STATUS_PENDING     => 'قائم',
            self::STATUS_APPROACHING => 'يقترب',
            self::STATUS_EXPIRED     => 'منتهي',
            self::STATUS_COMPLETED   => 'مكتمل',
        ];
    }

    public function getStatusColor()
    {
        $map = [
            self::STATUS_PENDING     => '#9CA3AF',
            self::STATUS_APPROACHING => '#F59E0B',
            self::STATUS_EXPIRED     => '#EF4444',
            self::STATUS_COMPLETED   => '#10B981',
        ];
        return $map[$this->status] ?? '#6B7280';
    }

    public function getJudiciary()
    {
        return $this->hasOne(\backend\modules\judiciary\models\Judiciary::class, ['id' => 'judiciary_id']);
    }

    public function getCommunication()
    {
        return $this->hasOne(\backend\modules\diwan\models\DiwanCorrespondence::class, ['id' => 'related_communication_id']);
    }

    public function getCustomerAction()
    {
        return $this->hasOne(\backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::class, ['id' => 'related_customer_action_id']);
    }

    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
