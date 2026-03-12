<?php

namespace backend\modules\shareholders\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use common\models\User;

/**
 * This is the model class for table "{{%shareholders}}"
 *
 * @property int $id
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $national_id
 * @property int $share_count
 * @property string|null $join_date
 * @property string|null $documents
 * @property string|null $notes
 * @property int $is_active
 * @property int $is_deleted
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $created_by
 */
class Shareholders extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%shareholders}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'value' => time(),
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'is_deleted' => true,
                ],
                'replaceRegularDelete' => true,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['share_count'], 'required'],
            [['share_count', 'is_active', 'is_deleted', 'created_at', 'updated_at', 'created_by'], 'integer'],
            [['share_count'], 'default', 'value' => 0],
            [['is_active'], 'default', 'value' => 1],
            [['is_deleted'], 'default', 'value' => 0],
            [['name'], 'string', 'max' => 250],
            [['phone'], 'string', 'max' => 50],
            [['email'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['national_id'], 'string', 'max' => 50],
            [['documents', 'notes'], 'string'],
            [['join_date', 'documents'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'name' => 'اسم المساهم',
            'phone' => 'الهاتف',
            'email' => 'البريد الإلكتروني',
            'national_id' => 'رقم الهوية',
            'share_count' => 'عدد الأسهم',
            'join_date' => 'تاريخ الانضمام',
            'documents' => 'الوثائق',
            'notes' => 'ملاحظات',
            'is_active' => 'فعّال',
            'is_deleted' => 'محذوف',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
            'created_by' => 'أنشئ بواسطة',
        ];
    }

    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query->notDeleted();
    }

    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    public function getShares()
    {
        return $this->hasMany(\backend\modules\profitDistribution\models\ProfitDistributionLines::class, ['shareholder_id' => 'id']);
    }
}
