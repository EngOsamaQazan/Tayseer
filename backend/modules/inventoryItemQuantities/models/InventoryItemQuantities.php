<?php

namespace backend\modules\inventoryItemQuantities\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

use backend\modules\inventorySuppliers\models\InventorySuppliers;
use backend\modules\inventoryStockLocations\models\InventoryStockLocations;
use backend\modules\inventoryItems\models\InventoryItems;
use common\models\User;

class InventoryItemQuantities extends ActiveRecord
{
    public $number_row;

    public static function tableName()
    {
        return '{{%inventory_item_quantities}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_updated_by',
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
            [['item_id', 'quantity'], 'required'],
            [['item_id', 'locations_id', 'suppliers_id', 'quantity', 'created_at', 'created_by', 'company_id', 'number_row'], 'integer'],
            [['locations_id'], 'exist', 'skipOnError' => true, 'targetClass' => InventoryStockLocations::class, 'targetAttribute' => ['locations_id' => 'id']],
            [['suppliers_id'], 'exist', 'skipOnError' => true, 'targetClass' => InventorySuppliers::class, 'targetAttribute' => ['suppliers_id' => 'id']],
            [['item_id'], 'exist', 'skipOnError' => true, 'targetClass' => InventoryItems::class, 'targetAttribute' => ['item_id' => 'id']],
            [['locations_id', 'suppliers_id', 'company_id'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'           => 'م',
            'item_id'      => 'الصنف',
            'locations_id' => 'الموقع',
            'suppliers_id' => 'المورد',
            'quantity'     => 'الكمية',
            'company_id'   => 'الشركة',
            'created_at'   => 'تاريخ الإنشاء',
            'created_by'   => 'أنشئ بواسطة',
        ];
    }

    /* ── العلاقات (كلها مصلحة) ── */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getInventoryItems()
    {
        return $this->hasOne(InventoryItems::class, ['id' => 'item_id']);
    }

    public function getInventoryStockLocations()
    {
        return $this->hasOne(InventoryStockLocations::class, ['id' => 'locations_id']);
    }

    public function getInventorySuppliers()
    {
        return $this->hasOne(InventorySuppliers::class, ['id' => 'suppliers_id']);
    }

    /* مصلح: كان يشير لـ suppliers_id بدلاً من locations_id */
    public function getLocations()
    {
        return $this->hasOne(InventoryStockLocations::class, ['id' => 'locations_id']);
    }

    public function getCompany()
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
