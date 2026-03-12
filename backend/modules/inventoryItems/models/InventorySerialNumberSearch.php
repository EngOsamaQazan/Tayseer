<?php
/**
 * نموذج بحث الأرقام التسلسلية
 */

namespace backend\modules\inventoryItems\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class InventorySerialNumberSearch extends InventorySerialNumber
{
    public $item_name;

    public function rules()
    {
        return [
            [['id', 'company_id', 'item_id', 'supplier_id', 'location_id', 'contract_id', 'created_by'], 'integer'],
            [['serial_number', 'status', 'note', 'item_name'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = InventorySerialNumber::find()
            ->joinWith(['item']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
            ],
            'pagination' => ['pageSize' => 20],
        ]);

        /* السماح بالترتيب حسب اسم الصنف */
        $dataProvider->sort->attributes['item_name'] = [
            'asc'  => ['os_inventory_items.item_name' => SORT_ASC],
            'desc' => ['os_inventory_items.item_name' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'os_inventory_serial_numbers.id' => $this->id,
            'os_inventory_serial_numbers.company_id' => $this->company_id,
            'os_inventory_serial_numbers.item_id' => $this->item_id,
            'os_inventory_serial_numbers.supplier_id' => $this->supplier_id,
            'os_inventory_serial_numbers.location_id' => $this->location_id,
            'os_inventory_serial_numbers.contract_id' => $this->contract_id,
            'os_inventory_serial_numbers.status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'os_inventory_serial_numbers.serial_number', $this->serial_number])
              ->andFilterWhere(['like', 'os_inventory_serial_numbers.note', $this->note])
              ->andFilterWhere(['like', 'os_inventory_items.item_name', $this->item_name]);

        return $dataProvider;
    }
}
