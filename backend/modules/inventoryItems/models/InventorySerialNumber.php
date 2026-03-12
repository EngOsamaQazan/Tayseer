<?php
/**
 * نموذج الأرقام التسلسلية — يتتبع كل قطعة فردية برقم IMEI/Serial فريد
 */

namespace backend\modules\inventoryItems\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;
use common\models\User;

class InventorySerialNumber extends ActiveRecord
{
    /* ── حالات القطعة ── */
    const STATUS_AVAILABLE = 'available';
    const STATUS_RESERVED  = 'reserved';
    const STATUS_SOLD      = 'sold';
    const STATUS_RETURNED  = 'returned';
    const STATUS_DEFECTIVE = 'defective';

    public static function tableName()
    {
        return 'os_inventory_serial_numbers';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_updated_by',
            ],
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }

    public function rules()
    {
        return [
            [['company_id', 'item_id', 'serial_number'], 'required'],
            [['company_id', 'item_id', 'supplier_id', 'location_id', 'contract_id', 'received_at', 'sold_at', 'created_at', 'created_by', 'updated_at', 'last_updated_by'], 'integer'],
            [['serial_number'], 'string', 'max' => 50],
            [['note'], 'string', 'max' => 255],
            [['serial_number'], 'unique'],
            [['status'], 'in', 'range' => [self::STATUS_AVAILABLE, self::STATUS_RESERVED, self::STATUS_SOLD, self::STATUS_RETURNED, self::STATUS_DEFECTIVE]],
            [['status'], 'default', 'value' => self::STATUS_AVAILABLE],
            [['is_deleted'], 'default', 'value' => 0],
            [['note', 'supplier_id', 'location_id', 'contract_id', 'received_at', 'sold_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'              => 'م',
            'company_id'      => 'الشركة',
            'item_id'         => 'الصنف',
            'serial_number'   => 'الرقم التسلسلي',
            'status'          => 'الحالة',
            'supplier_id'     => 'المورد',
            'location_id'     => 'موقع التخزين',
            'contract_id'     => 'رقم العقد',
            'received_at'     => 'تاريخ الاستلام',
            'sold_at'         => 'تاريخ البيع',
            'note'            => 'ملاحظات',
            'created_at'      => 'تاريخ الإنشاء',
            'created_by'      => 'أنشئ بواسطة',
            'updated_at'      => 'آخر تحديث',
            'last_updated_by' => 'آخر تعديل بواسطة',
        ];
    }

    /* ── العلاقات ── */
    public function getItem()
    {
        return $this->hasOne(InventoryItems::class, ['id' => 'item_id']);
    }

    public function getSupplier()
    {
        return $this->hasOne(\backend\modules\inventorySuppliers\models\InventorySuppliers::class, ['id' => 'supplier_id']);
    }

    public function getLocation()
    {
        return $this->hasOne(\backend\modules\inventoryStockLocations\models\InventoryStockLocations::class, ['id' => 'location_id']);
    }

    public function getCreatedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getLastUpdatedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'last_updated_by']);
    }

    /* ── حالات القطعة ── */
    public static function getStatusList()
    {
        return [
            self::STATUS_AVAILABLE => 'متاح',
            self::STATUS_RESERVED  => 'محجوز',
            self::STATUS_SOLD      => 'مباع',
            self::STATUS_RETURNED  => 'مرتجع',
            self::STATUS_DEFECTIVE => 'معطل',
        ];
    }

    public function getStatusLabel()
    {
        $list = self::getStatusList();
        return $list[$this->status] ?? $this->status;
    }

    public function getStatusCssClass()
    {
        $map = [
            self::STATUS_AVAILABLE => 'sn-badge--available',
            self::STATUS_RESERVED  => 'sn-badge--reserved',
            self::STATUS_SOLD      => 'sn-badge--sold',
            self::STATUS_RETURNED  => 'sn-badge--returned',
            self::STATUS_DEFECTIVE => 'sn-badge--defective',
        ];
        return $map[$this->status] ?? '';
    }

    public function getStatusIcon()
    {
        $map = [
            self::STATUS_AVAILABLE => 'fa-check-circle',
            self::STATUS_RESERVED  => 'fa-clock-o',
            self::STATUS_SOLD      => 'fa-shopping-cart',
            self::STATUS_RETURNED  => 'fa-undo',
            self::STATUS_DEFECTIVE => 'fa-exclamation-triangle',
        ];
        return $map[$this->status] ?? 'fa-question';
    }

    /* ── Soft Delete ── */
    public static function find()
    {
        return parent::find()->andWhere(['os_inventory_serial_numbers.is_deleted' => 0]);
    }

    public function softDelete()
    {
        $this->is_deleted = 1;
        return $this->save(false);
    }
}
