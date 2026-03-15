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
 * @property int $customer_id
 * @property string $asset_type
 * @property string $status
 * @property int|null $authority_id
 * @property int|null $correspondence_id
 * @property string|null $description
 * @property float|null $amount
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $updated_at
 * @property int $created_by
 */
class JudiciarySeizedAsset extends \yii\db\ActiveRecord
{
    const ASSET_VEHICLE      = 'vehicle';
    const ASSET_REAL_ESTATE  = 'real_estate';
    const ASSET_BANK_ACCOUNT = 'bank_account';
    const ASSET_SALARY       = 'salary';
    const ASSET_SHARES       = 'shares';
    const ASSET_E_PAYMENT    = 'e_payment';

    const STATUS_SEIZURE_REQUESTED = 'seizure_requested';
    const STATUS_SEIZED            = 'seized';
    const STATUS_VALUED            = 'valued';
    const STATUS_AUCTION_REQUESTED = 'auction_requested';
    const STATUS_AUCTIONED         = 'auctioned';
    const STATUS_RELEASED          = 'released';

    public static function tableName()
    {
        return 'os_judiciary_seized_assets';
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
            [['judiciary_id', 'customer_id', 'asset_type'], 'required'],
            [['judiciary_id', 'customer_id', 'authority_id', 'correspondence_id', 'is_deleted', 'created_at', 'updated_at', 'created_by'], 'integer'],
            [['asset_type'], 'in', 'range' => [self::ASSET_VEHICLE, self::ASSET_REAL_ESTATE, self::ASSET_BANK_ACCOUNT, self::ASSET_SALARY, self::ASSET_SHARES, self::ASSET_E_PAYMENT]],
            [['status'], 'in', 'range' => [self::STATUS_SEIZURE_REQUESTED, self::STATUS_SEIZED, self::STATUS_VALUED, self::STATUS_AUCTION_REQUESTED, self::STATUS_AUCTIONED, self::STATUS_RELEASED]],
            [['status'], 'default', 'value' => self::STATUS_SEIZURE_REQUESTED],
            [['description'], 'string', 'max' => 500],
            [['amount'], 'number'],
            [['notes'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'judiciary_id' => 'القضية',
            'customer_id' => 'المحكوم عليه',
            'asset_type' => 'نوع الأصل',
            'status' => 'الحالة',
            'authority_id' => 'الجهة الحاجزة',
            'correspondence_id' => 'المراسلة',
            'description' => 'الوصف',
            'amount' => 'القيمة',
            'notes' => 'ملاحظات',
        ];
    }

    public static function getAssetTypeLabels()
    {
        return [
            self::ASSET_VEHICLE      => 'مركبة',
            self::ASSET_REAL_ESTATE  => 'عقار',
            self::ASSET_BANK_ACCOUNT => 'حساب بنكي',
            self::ASSET_SALARY       => 'راتب',
            self::ASSET_SHARES       => 'حصص/أسهم',
            self::ASSET_E_PAYMENT    => 'محفظة دفع إلكتروني',
        ];
    }

    public static function getStatusLabels()
    {
        return [
            self::STATUS_SEIZURE_REQUESTED => 'طلب حجز',
            self::STATUS_SEIZED            => 'محجوز',
            self::STATUS_VALUED            => 'مُخمّن',
            self::STATUS_AUCTION_REQUESTED => 'طلب مزاد',
            self::STATUS_AUCTIONED         => 'بيع بالمزاد',
            self::STATUS_RELEASED          => 'تم رفع الحجز',
        ];
    }

    public function getJudiciary()
    {
        return $this->hasOne(\backend\modules\judiciary\models\Judiciary::class, ['id' => 'judiciary_id']);
    }

    public function getAuthority()
    {
        return $this->hasOne(JudiciaryAuthority::class, ['id' => 'authority_id']);
    }

    public function getCorrespondence()
    {
        return $this->hasOne(\backend\modules\diwan\models\DiwanCorrespondence::class, ['id' => 'correspondence_id']);
    }

    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
