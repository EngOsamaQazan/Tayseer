<?php

namespace backend\modules\inventoryItems\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\inventoryItems\models\InventoryItems;

/**
 * InventoryItemsSearch represents the model behind the search form about `common\models\InventoryItems`.
 */
class InventoryItemsSearch extends InventoryItems
{
    /**
     * @inheritdoc
     */
    public $number_row;
    public $remaining_amount;

    public function rules()
    {
        return [
            [['id', 'created_at', 'updated_at', 'created_by', 'last_update_by', 'number_row', 'supplier_id', 'company_id'], 'integer'],
            [['item_name', 'item_barcode', 'is_deleted', 'status', 'serial_number', 'category'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = InventoryItems::find();

        if (!empty($params['InventoryItemsSearch']['number_row'])) {

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['InventoryItemsSearch']['number_row'],
                ],
            ]);
        } else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'last_update_by' => $this->last_update_by,
        ]);
        $query->andFilterWhere(['like', 'item_name', $this->item_name])
            ->andFilterWhere(['like', 'item_barcode', $this->item_barcode])
            ->andFilterWhere(['like', 'serial_number', $this->serial_number])
            ->andFilterWhere(['like', 'category', $this->category])
            ->andFilterWhere(['status' => $this->status])
            ->andFilterWhere(['supplier_id' => $this->supplier_id])
            ->andwhere(['is_deleted' => false]);

        return $dataProvider;
    }

    public function itemQuery($params)
    {
        $query = InventoryItems::find()->select(" {{%inventory_items}}.*,((SELECT
        COUNT(`item_id`)
    FROM
        {{%inventory_item_quantities}}
    WHERE
        item_id = {{%inventory_items}}.id
) - (
    SELECT
        COUNT(`item_id`)
    FROM
        {{%contract_inventory_item}}
    WHERE
        item_id = {{%inventory_items}}.id
)) AS remaining_amount");

        $provider = new ActiveDataProvider([
            'query' =>$query,
            'pagination' => [
                'pageSize' => 10,
            ],

        ]);

        return $provider;

    }

    public function searchCounter($params)
    {
        $query = InventoryItems::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'last_update_by' => $this->last_update_by,
        ]);
        $query->andFilterWhere(['like', 'item_name', $this->item_name])
            ->andFilterWhere(['like', 'item_barcode', $this->item_barcode])
            ->andFilterWhere(['status' => $this->status])
            ->andwhere(['is_deleted' => false]);

        return $query->count();
    }
}
