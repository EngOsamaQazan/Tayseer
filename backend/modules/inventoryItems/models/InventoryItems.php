<?php
/**
 * نموذج أصناف المخزون — مع سير عمل الموافقات ودعم التنبيهات
 */

namespace backend\modules\inventoryItems\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use common\models\User;

class InventoryItems extends ActiveRecord
{
    const STATUS_DRAFT    = 'draft';
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public $remaining_amount;

    public static function tableName()
    {
        return 'os_inventory_items';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_update_by',
            ],
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
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
            [['item_name', 'item_barcode'], 'required'],
            [['created_at', 'updated_at', 'created_by', 'last_update_by', 'is_deleted', 'approved_by', 'approved_at', 'supplier_id', 'company_id', 'min_stock_level'], 'integer'],
            [['unit_price'], 'number'],
            [['item_name'], 'string', 'max' => 50],
            [['item_barcode'], 'string', 'max' => 30],
            [['serial_number'], 'string', 'max' => 100],
            [['description', 'rejection_reason'], 'string', 'max' => 500],
            [['category'], 'string', 'max' => 100],
            [['unit'], 'string', 'max' => 30],
            [['item_barcode'], 'unique'],
            [['status'], 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED]],
            [['status'], 'default', 'value' => self::STATUS_APPROVED],
            [['min_stock_level'], 'default', 'value' => 0],
            [['unit'], 'default', 'value' => 'قطعة'],
            [['serial_number', 'description', 'category', 'unit_price', 'supplier_id', 'company_id', 'rejection_reason', 'min_stock_level', 'unit'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'               => 'م',
            'item_name'        => 'اسم الصنف',
            'item_barcode'     => 'الباركود',
            'serial_number'    => 'الرقم التسلسلي',
            'description'      => 'الوصف',
            'category'         => 'التصنيف',
            'unit_price'       => 'سعر الوحدة',
            'min_stock_level'  => 'الحد الأدنى',
            'unit'             => 'وحدة القياس',
            'supplier_id'      => 'المورد',
            'company_id'       => 'الشركة',
            'status'           => 'الحالة',
            'approved_by'      => 'تمت الموافقة بواسطة',
            'approved_at'      => 'تاريخ الموافقة',
            'rejection_reason' => 'سبب الرفض',
            'created_at'       => 'تاريخ الإنشاء',
            'updated_at'       => 'آخر تحديث',
            'created_by'       => 'أنشئ بواسطة',
            'last_update_by'   => 'آخر تعديل بواسطة',
        ];
    }

    /* ── العلاقات ── */
    public function getCreatedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getLastUpdateBy()
    {
        return $this->hasOne(User::class, ['id' => 'last_update_by']);
    }

    public function getApprovedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'approved_by']);
    }

    public function getSupplier()
    {
        return $this->hasOne(\backend\modules\inventorySuppliers\models\InventorySuppliers::class, ['id' => 'supplier_id']);
    }

    public function getQuantities()
    {
        return $this->hasMany(\backend\modules\inventoryItemQuantities\models\InventoryItemQuantities::class, ['item_id' => 'id']);
    }

    public function getMovements()
    {
        return $this->hasMany(StockMovement::class, ['item_id' => 'id']);
    }

    public function getSerialNumbers()
    {
        return $this->hasMany(InventorySerialNumber::class, ['item_id' => 'id']);
    }

    /**
     * إجمالي الكمية الحالية لهذا الصنف عبر كل المواقع
     */
    public function getTotalStock()
    {
        return (int) \backend\modules\inventoryItemQuantities\models\InventoryItemQuantities::find()
            ->where(['item_id' => $this->id, 'is_deleted' => 0])
            ->sum('quantity');
    }

    /**
     * هل المخزون تحت الحد الأدنى؟
     */
    public function isLowStock()
    {
        return $this->min_stock_level > 0 && $this->getTotalStock() < $this->min_stock_level;
    }

    /* ── حالات العنصر ── */
    public static function getStatusList()
    {
        return [
            self::STATUS_DRAFT    => 'مسودة',
            self::STATUS_PENDING  => 'بانتظار الموافقة',
            self::STATUS_APPROVED => 'معتمد',
            self::STATUS_REJECTED => 'مرفوض',
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
            self::STATUS_DRAFT    => 'inv-badge--draft',
            self::STATUS_PENDING  => 'inv-badge--pending',
            self::STATUS_APPROVED => 'inv-badge--approved',
            self::STATUS_REJECTED => 'inv-badge--rejected',
        ];
        return $map[$this->status] ?? '';
    }

    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
