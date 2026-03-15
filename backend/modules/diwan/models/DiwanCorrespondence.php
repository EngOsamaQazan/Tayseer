<?php

namespace backend\modules\diwan\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\behaviors\BlameableBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use backend\models\JudiciaryAuthority;
use backend\models\JudiciaryDeadline;

/**
 * @property int $id
 * @property string $communication_type  notification|outgoing_letter|incoming_response
 * @property string $related_module
 * @property int|null $related_record_id
 * @property int|null $customer_id
 * @property string $direction
 * @property string|null $recipient_type
 * @property int|null $authority_id
 * @property int|null $bank_id
 * @property int|null $job_id
 * @property string|null $notification_method
 * @property string|null $delivery_date
 * @property string|null $notification_result
 * @property string|null $reference_number
 * @property string|null $purpose
 * @property int|null $parent_id
 * @property string|null $response_result
 * @property float|null $response_amount
 * @property string $correspondence_date
 * @property string|null $content_summary
 * @property string|null $image
 * @property string|null $follow_up_date
 * @property string $status
 * @property string|null $notes
 * @property int|null $company_id
 * @property int $is_deleted
 * @property int $created_at
 * @property int $updated_at
 * @property int $created_by
 * @property int $updated_by
 */
class DiwanCorrespondence extends \yii\db\ActiveRecord
{
    const TYPE_NOTIFICATION     = 'notification';
    const TYPE_OUTGOING_LETTER  = 'outgoing_letter';
    const TYPE_INCOMING_RESPONSE = 'incoming_response';

    const STATUS_DRAFT     = 'draft';
    const STATUS_SENT      = 'sent';
    const STATUS_DELIVERED  = 'delivered';
    const STATUS_RESPONDED = 'responded';
    const STATUS_CLOSED    = 'closed';

    const RECIPIENT_DEFENDANT      = 'defendant';
    const RECIPIENT_BANK           = 'bank';
    const RECIPIENT_EMPLOYER       = 'employer';
    const RECIPIENT_ADMINISTRATIVE = 'administrative';

    public static function tableName()
    {
        return 'os_diwan_correspondence';
    }

    public static function find()
    {
        return (new DiwanCorrespondenceQuery(get_called_class()))->active();
    }

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
                'softDeleteAttributeValues' => ['is_deleted' => true],
                'replaceRegularDelete' => true,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['communication_type', 'correspondence_date', 'direction'], 'required'],
            [['communication_type'], 'in', 'range' => [self::TYPE_NOTIFICATION, self::TYPE_OUTGOING_LETTER, self::TYPE_INCOMING_RESPONSE]],
            [['direction'], 'in', 'range' => ['outgoing', 'incoming']],
            [['status'], 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_DELIVERED, self::STATUS_RESPONDED, self::STATUS_CLOSED]],
            [['status'], 'default', 'value' => self::STATUS_DRAFT],

            [['related_module'], 'string', 'max' => 50],
            [['related_module'], 'default', 'value' => 'judiciary'],
            [['related_record_id', 'customer_id', 'authority_id', 'bank_id', 'job_id', 'parent_id', 'company_id', 'is_deleted', 'created_at', 'updated_at', 'created_by', 'updated_by'], 'integer'],

            [['recipient_type'], 'in', 'range' => [self::RECIPIENT_DEFENDANT, self::RECIPIENT_BANK, self::RECIPIENT_EMPLOYER, self::RECIPIENT_ADMINISTRATIVE], 'skipOnEmpty' => true],

            // Notification fields
            [['notification_method'], 'in', 'range' => ['in_person', 'posting', 'electronic', 'by_publication'], 'skipOnEmpty' => true],
            [['notification_result'], 'in', 'range' => ['delivered', 'not_delivered', 'refused', 'pending'], 'skipOnEmpty' => true],
            [['delivery_date', 'correspondence_date', 'follow_up_date'], 'date', 'format' => 'php:Y-m-d'],

            // Letter fields
            [['reference_number'], 'string', 'max' => 100],
            [['purpose'], 'string', 'max' => 100],

            // Response fields
            [['response_result'], 'string', 'max' => 50],
            [['response_amount'], 'number'],

            // Common
            [['content_summary', 'notes'], 'string'],
            [['image'], 'string', 'max' => 500],

            // --- Conditional validation by communication_type ---

            // Notification requires customer_id, notification_method, notification_result
            [['customer_id', 'notification_method'], 'required',
                'when' => function ($model) { return $model->communication_type === self::TYPE_NOTIFICATION; },
                'whenClient' => "function(a,v){return $('#communication_type').val()==='notification';}",
            ],

            // Outgoing letter requires recipient_type, reference_number, purpose
            [['recipient_type', 'reference_number', 'purpose'], 'required',
                'when' => function ($model) { return $model->communication_type === self::TYPE_OUTGOING_LETTER; },
                'whenClient' => "function(a,v){return $('#communication_type').val()==='outgoing_letter';}",
            ],

            // Incoming response requires parent_id, response_result
            [['parent_id', 'response_result'], 'required',
                'when' => function ($model) { return $model->communication_type === self::TYPE_INCOMING_RESPONSE; },
                'whenClient' => "function(a,v){return $('#communication_type').val()==='incoming_response';}",
            ],

            // Bank recipient requires bank_id
            [['bank_id'], 'required',
                'when' => function ($model) { return $model->recipient_type === self::RECIPIENT_BANK; },
            ],
            // Employer recipient requires job_id
            [['job_id'], 'required',
                'when' => function ($model) { return $model->recipient_type === self::RECIPIENT_EMPLOYER; },
            ],
            // Administrative recipient requires authority_id
            [['authority_id'], 'required',
                'when' => function ($model) { return $model->recipient_type === self::RECIPIENT_ADMINISTRATIVE; },
            ],
        ];
    }

    /**
     * Null out fields that don't belong to this communication_type.
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        switch ($this->communication_type) {
            case self::TYPE_NOTIFICATION:
                $this->recipient_type = self::RECIPIENT_DEFENDANT;
                $this->direction = 'outgoing';
                $this->reference_number = null;
                $this->purpose = null;
                $this->response_result = null;
                $this->response_amount = null;
                $this->parent_id = null;
                $this->bank_id = null;
                $this->job_id = null;
                $this->authority_id = null;
                break;

            case self::TYPE_OUTGOING_LETTER:
                $this->direction = 'outgoing';
                $this->notification_method = null;
                $this->notification_result = null;
                $this->delivery_date = null;
                $this->response_result = null;
                $this->response_amount = null;
                $this->nullUnusedRecipientFks();
                break;

            case self::TYPE_INCOMING_RESPONSE:
                $this->direction = 'incoming';
                $this->notification_method = null;
                $this->notification_result = null;
                $this->delivery_date = null;
                if ($this->parent_id && !$this->recipient_type) {
                    $parent = self::findOne($this->parent_id);
                    if ($parent) {
                        $this->recipient_type = $parent->recipient_type;
                        $this->bank_id = $parent->bank_id;
                        $this->job_id = $parent->job_id;
                        $this->authority_id = $parent->authority_id;
                    }
                }
                break;
        }

        foreach (['reference_number', 'purpose', 'response_result', 'notification_method', 'notification_result', 'content_summary', 'notes'] as $field) {
            if ($this->$field === '') {
                $this->$field = null;
            }
        }

        return true;
    }

    private function nullUnusedRecipientFks()
    {
        if ($this->recipient_type !== self::RECIPIENT_BANK) $this->bank_id = null;
        if ($this->recipient_type !== self::RECIPIENT_EMPLOYER) $this->job_id = null;
        if ($this->recipient_type !== self::RECIPIENT_ADMINISTRATIVE) $this->authority_id = null;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'communication_type' => 'نوع الاتصال',
            'related_module' => 'الوحدة',
            'related_record_id' => 'رقم السجل',
            'customer_id' => 'المحكوم عليه',
            'direction' => 'الاتجاه',
            'recipient_type' => 'نوع المستلم',
            'authority_id' => 'الجهة الإدارية',
            'bank_id' => 'البنك',
            'job_id' => 'جهة التوظيف',
            'notification_method' => 'طريقة التبليغ',
            'delivery_date' => 'تاريخ التسليم',
            'notification_result' => 'نتيجة التبليغ',
            'reference_number' => 'رقم الكتاب',
            'purpose' => 'الغرض',
            'parent_id' => 'الكتاب الأصلي',
            'response_result' => 'نتيجة الرد',
            'response_amount' => 'المبلغ',
            'correspondence_date' => 'تاريخ المراسلة',
            'content_summary' => 'الملخص',
            'image' => 'مرفق',
            'follow_up_date' => 'تاريخ المتابعة',
            'status' => 'الحالة',
            'notes' => 'ملاحظات',
        ];
    }

    /* ─── Relations ─── */

    public function getAuthority()
    {
        return $this->hasOne(JudiciaryAuthority::class, ['id' => 'authority_id']);
    }

    public function getBank()
    {
        return $this->hasOne(\backend\modules\bancks\models\Bancks::class, ['id' => 'bank_id']);
    }

    public function getJob()
    {
        return $this->hasOne(\backend\modules\jobs\models\Jobs::class, ['id' => 'job_id']);
    }

    public function getParentCorrespondence()
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    public function getResponses()
    {
        return $this->hasMany(self::class, ['parent_id' => 'id'])->andWhere(['is_deleted' => 0]);
    }

    public function getDeadlines()
    {
        return $this->hasMany(JudiciaryDeadline::class, ['related_communication_id' => 'id']);
    }

    public function getCustomer()
    {
        return $this->hasOne(\backend\modules\customers\models\Customers::class, ['id' => 'customer_id']);
    }

    public function getJudiciary()
    {
        return $this->hasOne(\backend\modules\judiciary\models\Judiciary::class, ['id' => 'related_record_id']);
    }

    /* ─── Labels ─── */

    public static function getCommunicationTypeLabels()
    {
        return [
            self::TYPE_NOTIFICATION      => 'تبليغ',
            self::TYPE_OUTGOING_LETTER   => 'كتاب صادر',
            self::TYPE_INCOMING_RESPONSE => 'رد وارد',
        ];
    }

    public static function getStatusLabels()
    {
        return [
            self::STATUS_DRAFT     => 'مسودة',
            self::STATUS_SENT      => 'مُرسل',
            self::STATUS_DELIVERED => 'مُسلّم',
            self::STATUS_RESPONDED => 'تم الرد',
            self::STATUS_CLOSED    => 'مغلق',
        ];
    }

    public static function getNotificationMethodLabels()
    {
        return [
            'in_person'      => 'بالذات',
            'posting'        => 'بالإلصاق',
            'electronic'     => 'إلكترونياً',
            'by_publication' => 'بالنشر',
        ];
    }

    public static function getNotificationResultLabels()
    {
        return [
            'delivered'     => 'تم التبليغ',
            'not_delivered' => 'لم يتم',
            'refused'       => 'رفض الاستلام',
            'pending'       => 'قيد الانتظار',
        ];
    }

    public static function getPurposeLabels()
    {
        return [
            'salary_deduction'  => 'حسم راتب',
            'account_freeze'    => 'تجميد حساب بنكي',
            'vehicle_seizure'   => 'حجز مركبة',
            'property_seizure'  => 'حجز عقار',
            'shares_freeze'     => 'تجميد حصص',
            'e_payment_freeze'  => 'تجميد محفظة إلكترونية',
            'release'           => 'رفع حجز',
            'transfer'          => 'تحويل أرصدة',
            'vehicle_arrest'    => 'ضبط مركبة',
            'valuation'         => 'تخمين',
            'auction'           => 'بيع بالمزاد',
            'other'             => 'أخرى',
        ];
    }

    public static function getResponseResultLabels()
    {
        return [
            'seized'       => 'تم الحجز',
            'not_found'    => 'غير موجود',
            'insufficient' => 'لا يكفي / لا يسمح',
            'unemployed'   => 'فاقد وظيفة',
            'transferred'  => 'تم التحويل',
            'no_response'  => 'لم يرد',
        ];
    }

    /**
     * Get the resolved recipient display name via EntityResolverService.
     */
    public function getRecipientDisplayName(): string
    {
        if ($this->recipient_type === self::RECIPIENT_DEFENDANT) {
            return $this->customer ? $this->customer->name : '';
        }

        $service = new \backend\services\EntityResolverService();
        $fkId = null;
        switch ($this->recipient_type) {
            case self::RECIPIENT_BANK: $fkId = $this->bank_id; break;
            case self::RECIPIENT_EMPLOYER: $fkId = $this->job_id; break;
            case self::RECIPIENT_ADMINISTRATIVE: $fkId = $this->authority_id; break;
        }

        return $fkId ? $service->getDisplayName($this->recipient_type, $fkId) : '';
    }
}
