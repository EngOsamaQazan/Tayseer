<?php

namespace backend\modules\inventorySuppliers\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\inventorySuppliers\models\InventorySuppliers;

/**
 * InventorySuppliersSearch represents the model behind the search form about `common\models\InventorySuppliers`.
 */
class InventorySuppliersSearch extends InventorySuppliers
{
    /**
     * @inheritdoc
     */
    public $number_row;
    public function rules()
    {
        return [
            [['id', 'company_id', 'created_by', 'created_at', 'updated_at', 'last_update_by', 'is_deleted','number_row'], 'integer'],
            [['name', 'adress', 'phone_number'], 'safe'],
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
        $query = InventorySuppliers::find();

        if(!empty($params['InventorySuppliersSearch']['number_row'])){

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['InventorySuppliersSearch']['number_row'],
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
            'is_deleted' => $this->is_deleted,
        ]);

        $query->andFilterWhere(['=', 'name', $this->name])
            ->andFilterWhere(['=', 'adress', $this->adress])
            ->andFilterWhere(['=', 'phone_number', $this->phone_number])->andWhere(['is_deleted' => false]);;

        return $dataProvider;
    }
    public function searchCounter($params)
    {
        $query = InventorySuppliers::find();

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
            'is_deleted' => $this->is_deleted,
        ]);

        $query->andFilterWhere(['=', 'name', $this->name])
            ->andFilterWhere(['=', 'adress', $this->adress])
            ->andFilterWhere(['=', 'phone_number', $this->phone_number])->andWhere(['is_deleted' => false]);;

        return $query->count();
    }
}
