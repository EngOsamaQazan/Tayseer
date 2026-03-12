<?php

namespace backend\modules\InventoryItemQuantities\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\InventoryItemquantities\models\InventoryItemQuantities;

/**
 * InventoryItemQuantitiesSearch represents the model behind the search form about `common\models\InventoryItemQuantities`.
 */
class InventoryItemQuantitiesSearch extends InventoryItemQuantities
{
    /**
     * @inheritdoc
     */
    public $number_row;
    public function rules()
    {
        return [
            [['id', 'item_id', 'locations_id', 'suppliers_id', 'quantity', 'created_at', 'created_by','company_id','number_row'], 'integer'],
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
        $query = InventoryItemQuantities::find();

        if(!empty($params['InventoryItemQuantitiesSearch']['number_row'])){

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['InventoryItemQuantitiesSearch']['number_row'],
                ],
            ]);
        }else{
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

        $query->andFilterWhere(['=', 'item_id', $this->item_id]);
        $query->andFilterWhere(['=', 'locations_id', $this->locations_id]);
        $query->andFilterWhere(['=', 'suppliers_id', $this->suppliers_id]);
        $query->andFilterWhere(['=', 'quantity', $this->quantity])->andWhere(['is_deleted' => false]);
        return $dataProvider;
    }
    public function searchCounter($params)
    {
        $query = InventoryItemQuantities::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere(['=', 'item_id', $this->item_id]);
        $query->andFilterWhere(['=', 'locations_id', $this->locations_id]);
        $query->andFilterWhere(['=', 'suppliers_id', $this->suppliers_id]);
        $query->andFilterWhere(['=', 'quantity', $this->quantity])->andWhere(['is_deleted' => false]);
        return $query->count();
    }
}
