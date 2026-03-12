<?php
/**
 * نموذج حركات المخزون — يتتبع كل تغيير في الكميات
 */

namespace backend\modules\inventoryItems\models;

use Yii;
use yii\db\ActiveRecord;
use common\models\User;

class StockMovement extends ActiveRecord
{
    /* ── أنواع الحركات ── */
    const TYPE_IN         = 'IN';
    const TYPE_OUT        = 'OUT';
    const TYPE_TRANSFER   = 'TRANSFER';
    const TYPE_ADJUSTMENT = 'ADJUSTMENT';
    const TYPE_RETURN     = 'RETURN';

    public static function tableName()
    {
        return 'os_stock_movements';
    }

    public function rules()
    {
        return [
            [['item_id', 'movement_type', 'quantity'], 'required'],
            [['item_id', 'quantity', 'from_location_id', 'to_location_id', 'reference_id', 'supplier_id', 'company_id', 'created_by', 'created_at'], 'integer'],
            [['unit_cost'], 'number'],
            [['movement_type'], 'in', 'range' => [self::TYPE_IN, self::TYPE_OUT, self::TYPE_TRANSFER, self::TYPE_ADJUSTMENT, self::TYPE_RETURN]],
            [['reference_type'], 'string', 'max' => 50],
            [['notes'], 'string', 'max' => 500],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'               => 'م',
            'item_id'          => 'الصنف',
            'movement_type'    => 'نوع الحركة',
            'quantity'         => 'الكمية',
            'from_location_id' => 'من موقع',
            'to_location_id'   => 'إلى موقع',
            'reference_type'   => 'نوع المرجع',
            'reference_id'     => 'رقم المرجع',
            'unit_cost'        => 'تكلفة الوحدة',
            'notes'            => 'ملاحظات',
            'supplier_id'      => 'المورد',
            'company_id'       => 'الشركة',
            'created_by'       => 'بواسطة',
            'created_at'       => 'التاريخ',
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_by = Yii::$app->user->id;
            $this->created_at = time();
        }
        return parent::beforeSave($insert);
    }

    /* ── العلاقات ── */
    public function getItem()
    {
        return $this->hasOne(InventoryItems::class, ['id' => 'item_id']);
    }

    public function getCreatedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getFromLocation()
    {
        return $this->hasOne(\backend\modules\inventoryStockLocations\models\InventoryStockLocations::class, ['id' => 'from_location_id']);
    }

    public function getToLocation()
    {
        return $this->hasOne(\backend\modules\inventoryStockLocations\models\InventoryStockLocations::class, ['id' => 'to_location_id']);
    }

    public function getSupplier()
    {
        return $this->hasOne(\backend\modules\inventorySuppliers\models\InventorySuppliers::class, ['id' => 'supplier_id']);
    }

    /* ── مساعدات ── */
    public static function getTypeList()
    {
        return [
            self::TYPE_IN         => 'إدخال (شراء)',
            self::TYPE_OUT        => 'إخراج (بيع)',
            self::TYPE_TRANSFER   => 'تحويل بين مواقع',
            self::TYPE_ADJUSTMENT => 'تعديل يدوي',
            self::TYPE_RETURN     => 'إرجاع',
        ];
    }

    public function getTypeLabel()
    {
        $list = self::getTypeList();
        return $list[$this->movement_type] ?? $this->movement_type;
    }

    public function getTypeCssClass()
    {
        $map = [
            self::TYPE_IN         => 'sm-badge--in',
            self::TYPE_OUT        => 'sm-badge--out',
            self::TYPE_TRANSFER   => 'sm-badge--transfer',
            self::TYPE_ADJUSTMENT => 'sm-badge--adjust',
            self::TYPE_RETURN     => 'sm-badge--return',
        ];
        return $map[$this->movement_type] ?? '';
    }

    /**
     * تسجيل حركة مخزون جديدة — يُستدعى من أي مكان يغيّر الكميات
     */
    public static function record($itemId, $type, $qty, $opts = [])
    {
        $m = new self();
        $m->item_id          = $itemId;
        $m->movement_type    = $type;
        $m->quantity         = abs($qty);
        $m->from_location_id = $opts['from_location_id'] ?? null;
        $m->to_location_id   = $opts['to_location_id'] ?? null;
        $m->reference_type   = $opts['reference_type'] ?? null;
        $m->reference_id     = $opts['reference_id'] ?? null;
        $m->unit_cost        = $opts['unit_cost'] ?? null;
        $m->notes            = $opts['notes'] ?? null;
        $m->supplier_id      = $opts['supplier_id'] ?? null;
        $m->company_id       = $opts['company_id'] ?? null;
        return $m->save(false);
    }
}
