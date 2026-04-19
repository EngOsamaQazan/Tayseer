<?php

namespace backend\modules\notification\models;

use Yii;

/**
 * ActiveRecord for os_notification.
 *
 * @property int $id
 * @property int|null $sender_id
 * @property int|null $recipient_id
 * @property int|null $type_of_notification
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property string|null $title_html
 * @property string|null $body_html
 * @property string|null $href
 * @property int $is_unread
 * @property int|null $read_at
 * @property int $is_hidden
 * @property int|null $priority
 * @property string|null $group_key
 * @property string $channel
 * @property int|null $created_time
 */
class Notification extends \yii\db\ActiveRecord
{
    const SYSTEM_SENDER = 0;

    const GENERAL = 1;
    const TYPE_CONTRACT_CREATED = 100;
    const TYPE_CONTRACT_UPDATED = 101;
    const TYPE_CONTRACT_LEGAL = 102;
    const TYPE_CONTRACT_FOLLOWUP = 103;
    const TYPE_CUSTOMER_CREATED = 200;
    const TYPE_FAHRAS_OVERRIDE  = 201; // Manager bypassed a Fahras "cannot_sell" verdict
    const TYPE_LEAVE_REQUEST = 300;
    const TYPE_ARCHIVE_REQUEST = 400;
    const INVOICE_PENDING_RECEPTION = 1001;
    const INVOICE_PENDING_MANAGER = 1003;
    const TYPE_INVOICE_APPROVED = 1004;
    const TYPE_INVOICE_REJECTED = 1005;

    const PRIORITY_NORMAL = 0;
    const PRIORITY_HIGH = 1;
    const PRIORITY_URGENT = 2;

    public static function tableName()
    {
        return 'os_notification';
    }

    public function rules()
    {
        return [
            [['sender_id', 'recipient_id', 'type_of_notification', 'created_time', 'is_unread', 'is_hidden', 'read_at', 'entity_id', 'priority'], 'integer'],
            [['body_html'], 'string'],
            [['title_html', 'href'], 'string', 'max' => 255],
            [['entity_type'], 'string', 'max' => 50],
            [['group_key'], 'string', 'max' => 100],
            [['channel'], 'string', 'max' => 20],
            [['is_unread'], 'default', 'value' => 1],
            [['is_hidden'], 'default', 'value' => 0],
            [['priority'], 'default', 'value' => self::PRIORITY_NORMAL],
            [['channel'], 'default', 'value' => 'in_app'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sender_id' => Yii::t('app', 'المرسل'),
            'recipient_id' => Yii::t('app', 'المستلم'),
            'type_of_notification' => Yii::t('app', 'النوع'),
            'entity_type' => Yii::t('app', 'نوع المصدر'),
            'entity_id' => Yii::t('app', 'رقم المصدر'),
            'title_html' => Yii::t('app', 'العنوان'),
            'body_html' => Yii::t('app', 'المحتوى'),
            'href' => Yii::t('app', 'الرابط'),
            'is_unread' => Yii::t('app', 'غير مقروء'),
            'read_at' => Yii::t('app', 'وقت القراءة'),
            'is_hidden' => Yii::t('app', 'مخفي'),
            'priority' => Yii::t('app', 'الأولوية'),
            'group_key' => Yii::t('app', 'مفتاح التجميع'),
            'channel' => Yii::t('app', 'القناة'),
            'created_time' => Yii::t('app', 'تاريخ الإنشاء'),
        ];
    }

    /* ═══ Relations ═══ */

    public function getSender()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'sender_id']);
    }

    public function getRecipient()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'recipient_id']);
    }

    /* ═══ Type Registry ═══ */

    public static function getTypeLabels()
    {
        return [
            self::GENERAL                   => 'عام',
            self::TYPE_CONTRACT_CREATED     => 'عقد جديد',
            self::TYPE_CONTRACT_UPDATED     => 'تعديل عقد',
            self::TYPE_CONTRACT_LEGAL       => 'تحويل للقانونية',
            self::TYPE_CONTRACT_FOLLOWUP    => 'مراجعة متابعة',
            self::TYPE_CUSTOMER_CREATED     => 'عميل جديد',
            self::TYPE_LEAVE_REQUEST        => 'طلب إجازة',
            self::TYPE_ARCHIVE_REQUEST      => 'طلب أرشيف',
            self::INVOICE_PENDING_RECEPTION => 'فاتورة بانتظار الاستلام',
            self::INVOICE_PENDING_MANAGER   => 'فاتورة بانتظار المدير',
            self::TYPE_INVOICE_APPROVED     => 'فاتورة معتمدة',
            self::TYPE_INVOICE_REJECTED     => 'فاتورة مرفوضة',
        ];
    }

    public static function getTypeLabel($type)
    {
        return static::getTypeLabels()[$type] ?? 'عام';
    }

    private static $typeIcons = [
        self::GENERAL                   => 'fa-bell',
        self::TYPE_CONTRACT_CREATED     => 'fa-file-signature',
        self::TYPE_CONTRACT_UPDATED     => 'fa-pen-to-square',
        self::TYPE_CONTRACT_LEGAL       => 'fa-scale-balanced',
        self::TYPE_CONTRACT_FOLLOWUP    => 'fa-clipboard-check',
        self::TYPE_CUSTOMER_CREATED     => 'fa-user-plus',
        self::TYPE_LEAVE_REQUEST        => 'fa-calendar-xmark',
        self::TYPE_ARCHIVE_REQUEST      => 'fa-box-archive',
        self::INVOICE_PENDING_RECEPTION => 'fa-truck-ramp-box',
        self::INVOICE_PENDING_MANAGER   => 'fa-file-invoice',
        self::TYPE_INVOICE_APPROVED     => 'fa-circle-check',
        self::TYPE_INVOICE_REJECTED     => 'fa-circle-xmark',
    ];

    public static function getTypeIcon($type)
    {
        return self::$typeIcons[$type] ?? 'fa-bell';
    }

    private static $typeColors = [
        self::GENERAL                   => '#6c757d',
        self::TYPE_CONTRACT_CREATED     => '#0d6efd',
        self::TYPE_CONTRACT_UPDATED     => '#6610f2',
        self::TYPE_CONTRACT_LEGAL       => '#800020',
        self::TYPE_CONTRACT_FOLLOWUP    => '#0dcaf0',
        self::TYPE_CUSTOMER_CREATED     => '#198754',
        self::TYPE_LEAVE_REQUEST        => '#fd7e14',
        self::TYPE_ARCHIVE_REQUEST      => '#6f42c1',
        self::INVOICE_PENDING_RECEPTION => '#ffc107',
        self::INVOICE_PENDING_MANAGER   => '#dc3545',
        self::TYPE_INVOICE_APPROVED     => '#198754',
        self::TYPE_INVOICE_REJECTED     => '#dc3545',
    ];

    public static function getTypeColor($type)
    {
        return self::$typeColors[$type] ?? '#6c757d';
    }

    /* ═══ Query Scopes ═══ */

    public static function unread()
    {
        return static::find()->where(['is_unread' => 1]);
    }

    public static function forUser($userId)
    {
        return static::find()->where(['recipient_id' => $userId]);
    }

    public static function recent($limit = 10)
    {
        return static::find()->orderBy(['created_time' => SORT_DESC])->limit($limit);
    }

    public static function unreadCountForUser($userId)
    {
        return (int) static::find()
            ->where(['recipient_id' => $userId, 'is_unread' => 1])
            ->count();
    }
}
