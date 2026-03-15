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
 * @property string $name
 * @property string $template_type
 * @property string|null $template_content
 * @property int $is_combinable
 * @property int $sort_order
 * @property int $is_deleted
 * @property int $created_at
 * @property int $updated_at
 * @property int $created_by
 */
class JudiciaryRequestTemplate extends \yii\db\ActiveRecord
{
    const TYPE_SALARY_DEDUCTION  = 'salary_deduction';
    const TYPE_BANK_FREEZE       = 'bank_freeze';
    const TYPE_VEHICLE_SEIZURE   = 'vehicle_seizure';
    const TYPE_PROPERTY_SEIZURE  = 'property_seizure';
    const TYPE_COMPANIES_SHARES  = 'companies_shares';
    const TYPE_E_PAYMENT         = 'e_payment';
    const TYPE_RE_NOTIFY         = 're_notify';
    const TYPE_PUBLISH_NOTIFY    = 'publish_notify';
    const TYPE_VEHICLE_ARREST    = 'vehicle_arrest';
    const TYPE_VALUATION         = 'valuation';
    const TYPE_AUCTION           = 'auction';
    const TYPE_TRANSFER_FUNDS    = 'transfer_funds';
    const TYPE_RELEASE_SEIZURE   = 'release_seizure';
    const TYPE_COMBINED          = 'combined';
    const TYPE_OTHER             = 'other';

    public static function tableName()
    {
        return 'os_judiciary_request_templates';
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
            [['name', 'template_type'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['template_type'], 'string', 'max' => 50],
            [['template_content'], 'string'],
            [['is_combinable', 'sort_order', 'is_deleted', 'created_at', 'updated_at', 'created_by'], 'integer'],
            [['is_combinable'], 'default', 'value' => 1],
            [['sort_order'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'اسم القالب',
            'template_type' => 'نوع القالب',
            'template_content' => 'محتوى القالب',
            'is_combinable' => 'قابل للدمج',
            'sort_order' => 'الترتيب',
        ];
    }

    public static function getTypeLabels()
    {
        return [
            self::TYPE_SALARY_DEDUCTION => 'حسم راتب',
            self::TYPE_BANK_FREEZE      => 'حجز حسابات بنكية',
            self::TYPE_VEHICLE_SEIZURE  => 'حجز/ضبط مركبة',
            self::TYPE_PROPERTY_SEIZURE => 'حجز عقار',
            self::TYPE_COMPANIES_SHARES => 'حجز حصص شركات',
            self::TYPE_E_PAYMENT        => 'حجز محافظ دفع إلكتروني',
            self::TYPE_RE_NOTIFY        => 'إعادة تبليغ',
            self::TYPE_PUBLISH_NOTIFY   => 'تبليغ بالنشر',
            self::TYPE_VEHICLE_ARREST   => 'ضبط مركبة',
            self::TYPE_VALUATION        => 'تخمين',
            self::TYPE_AUCTION          => 'بيع بالمزاد العلني',
            self::TYPE_TRANSFER_FUNDS   => 'تحويل أرصدة',
            self::TYPE_RELEASE_SEIZURE  => 'رفع حجوزات',
            self::TYPE_COMBINED         => 'طلب مُجمّع',
            self::TYPE_OTHER            => 'أخرى',
        ];
    }

    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
