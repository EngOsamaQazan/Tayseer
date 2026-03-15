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
 * @property string $authority_type
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $updated_at
 * @property int $created_by
 * @property int $company_id
 */
class JudiciaryAuthority extends \yii\db\ActiveRecord
{
    const TYPE_LAND               = 'land';
    const TYPE_LICENSING          = 'licensing';
    const TYPE_COMPANIES_REGISTRY = 'companies_registry';
    const TYPE_INDUSTRY_TRADE     = 'industry_trade';
    const TYPE_SECURITY           = 'security';
    const TYPE_COURT              = 'court';
    const TYPE_SOCIAL_SECURITY    = 'social_security';
    const TYPE_OTHER              = 'other';

    public static function tableName()
    {
        return 'os_judiciary_authorities';
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
            [['name', 'authority_type'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['authority_type'], 'string', 'max' => 50],
            [['notes'], 'string'],
            [['is_deleted', 'created_at', 'updated_at', 'created_by', 'company_id'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'اسم الجهة',
            'authority_type' => 'نوع الجهة',
            'notes' => 'ملاحظات',
        ];
    }

    public static function getTypeList()
    {
        return [
            self::TYPE_LAND               => 'دائرة الأراضي والمساحة',
            self::TYPE_LICENSING          => 'إدارة ترخيص السواقين والمركبات',
            self::TYPE_COMPANIES_REGISTRY => 'دائرة مراقبة الشركات',
            self::TYPE_INDUSTRY_TRADE     => 'وزارة الصناعة والتجارة',
            self::TYPE_SECURITY           => 'الأمن العام',
            self::TYPE_COURT              => 'المحكمة الشرعية',
            self::TYPE_SOCIAL_SECURITY    => 'الضمان الاجتماعي',
            self::TYPE_OTHER              => 'أخرى',
        ];
    }

    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
