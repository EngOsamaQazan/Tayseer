<?php

namespace backend\modules\inventoryStockLocations\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

class InventoryStockLocations extends \yii\db\ActiveRecord
{
    public $number_row;

    public static function tableName()
    {
        return 'os_inventory_stock_locations';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_update_by',
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
            [['locations_name'], 'required'],
            [['company_id', 'created_by', 'created_at', 'updated_at', 'last_update_by', 'is_deleted', 'number_row'], 'integer'],
            [['locations_name'], 'string', 'max' => 250],
            [['company_id'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'             => 'م',
            'locations_name' => 'اسم الموقع',
            'company_id'     => 'الشركة',
            'created_by'     => 'أنشئ بواسطة',
            'created_at'     => 'تاريخ الإنشاء',
            'updated_at'     => 'آخر تحديث',
            'last_update_by' => 'آخر تعديل بواسطة',
        ];
    }

    /* ── العلاقات (مصلحة) ── */
    public function getCreatedByUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'created_by']);
    }

    public function getCompanies()
    {
        return $this->hasOne(\backend\modules\companies\models\Companies::class, ['id' => 'company_id']);
    }

    /* ── SoftDelete scope (مصلح — كان ناقص) ── */
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
