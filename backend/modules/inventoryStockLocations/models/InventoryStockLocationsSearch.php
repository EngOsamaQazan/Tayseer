<?php

namespace  backend\modules\inventoryStockLocations\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use  backend\modules\inventoryStockLocations\models\InventoryStockLocations;

/**
 * InventoryStockLocationsSearch represents the model behind the search form about `common\models\InventoryStockLocations`.
 */
class InventoryStockLocationsSearch extends InventoryStockLocations
{
    /**
     * @inheritdoc
     */
    public $number_row;
    public function rules()
    {
        return [
            [['id', 'company_id', 'created_by', 'created_at', 'updated_at', 'last_update_by','number_row'], 'integer'],
            [['locations_name', 'is_deleted'], 'safe'],
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
        $query = InventoryStockLocations::find();

        if(!empty($params['InventoryStockLocationsSearch']['number_row'])){

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['InventoryStockLocationsSearch']['number_row'],
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

        $query->andFilterWhere([
            'id' => $this->id,
            'company_id' => $this->company_id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'last_update_by' => $this->last_update_by,
        ]);

        $query->andFilterWhere(['=', 'locations_name', $this->locations_name])
            ->andWhere(['is_deleted' => false]);

        return $dataProvider;
    }
    public function searchCounter($params)
    {
        $query = InventoryStockLocations::find();

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
            'company_id' => $this->company_id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'last_update_by' => $this->last_update_by,
        ]);

        $query->andFilterWhere(['=', 'locations_name', $this->locations_name])
            ->andWhere(['is_deleted' => false]);

        return $query->count();
    }
}
