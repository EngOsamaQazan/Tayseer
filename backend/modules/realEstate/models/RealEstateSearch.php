<?php

namespace backend\modules\realEstate\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\realEstate\models\RealEstate;

/**
 * RealEstateSearch represents the model behind the search form about `backend\modules\realEstate\models\RealEstate`.
 */
class RealEstateSearch extends RealEstate
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'customer_id', 'created_at', 'updated_at', 'created_by', 'updated_by', 'is_deleted'], 'integer'],
            [['property_type', 'property_number'], 'safe'],
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
        $query = RealEstate::find();

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
            'customer_id' => $this->customer_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
        ]);

        $query->andFilterWhere(['like', 'property_type', $this->property_type])
            ->andFilterWhere(['like', 'property_number', $this->property_number])->where(['is_deleted' => 0]);


        return $dataProvider;
    }
}
