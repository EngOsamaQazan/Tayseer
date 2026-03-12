<?php

namespace backend\modules\itemsInventoryInvoices\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\itemsInventoryInvoices\models\ItemsInventoryInvoices;

/**
 * ItemsInventoryInvoicesSearch represents the model behind the search form of `backend\modules\itemsInventoryInvoices\models\ItemsInventoryInvoices`.
 */
class ItemsInventoryInvoicesSearch extends ItemsInventoryInvoices
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'number', 'created_at', 'updated_at', 'created_by', 'inventory_items_id', 'inventory_invoices_id', 'last_updated_by', 'is_deleted'], 'integer'],
            [['single_price','total_amount'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
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
        $query = ItemsInventoryInvoices::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'number' => $this->number,
            'single_price' => $this->single_price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'inventory_items_id' => $this->inventory_items_id,
            'inventory_invoices_id' => $this->inventory_invoices_id,
            'last_updated_by' => $this->last_updated_by,
            'is_deleted' => $this->is_deleted,
        ])->where(['is_deleted'=>0]);

        return $dataProvider;
    }
}
