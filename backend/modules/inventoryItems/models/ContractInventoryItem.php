<?php

namespace backend\modules\inventoryItems\models;

use Yii;

/**
 * نموذج بند المخزون المرتبط بالعقد
 *
 * @property int $id
 * @property int $contract_id
 * @property int $item_id
 * @property int|null $serial_number_id
 * @property string|null $code
 * @property string|null $notes
 *
 * @property \backend\modules\contracts\models\Contracts $contract
 * @property InventoryItems $item
 * @property InventorySerialNumber $serialNumber
 */
class ContractInventoryItem extends \yii\db\ActiveRecord
{
    public $number_row;

    public static function tableName()
    {
        return 'os_contract_inventory_item';
    }

    public function rules()
    {
        return [
            [['item_id'], 'required'],
            [['contract_id', 'item_id', 'serial_number_id'], 'integer'],
            [['code'], 'string', 'max' => 250],
            [['notes'], 'string', 'max' => 500],
            [['contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => \backend\modules\contracts\models\Contracts::class, 'targetAttribute' => ['contract_id' => 'id']],
            [['item_id'], 'exist', 'skipOnError' => true, 'targetClass' => InventoryItems::class, 'targetAttribute' => ['item_id' => 'id']],
            [['serial_number_id'], 'exist', 'skipOnError' => true, 'targetClass' => InventorySerialNumber::class, 'targetAttribute' => ['serial_number_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'               => 'م',
            'contract_id'      => 'العقد',
            'item_id'          => 'الصنف',
            'serial_number_id' => 'الرقم التسلسلي',
            'code'             => 'الكود',
            'notes'            => 'ملاحظات',
        ];
    }

    public function getContract()
    {
        return $this->hasOne(\backend\modules\contracts\models\Contracts::class, ['id' => 'contract_id']);
    }

    public function getItem()
    {
        return $this->hasOne(InventoryItems::class, ['id' => 'item_id']);
    }

    public function getSerialNumber()
    {
        return $this->hasOne(InventorySerialNumber::class, ['id' => 'serial_number_id']);
    }
}
