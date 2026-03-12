<?php

namespace backend\modules\court\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\court\models\Court;

/**
 * CourtSearch represents the model behind the search form about `backend\modules\court\models\Court`.
 */
class CourtSearch extends Court
{
    public $number_row;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'city', 'created_by', 'last_updated_by', 'created_at', 'updated_at', 'is_deleted'], 'integer'],
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
        $query = Court::find();
        if(!empty($params['CourtSearch']['number_row'])){
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['CourtSearch']['number_row'],
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
            'city' => $this->city,
            'created_by' => $this->created_by,
            'last_updated_by' => $this->last_updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
        ]);


        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'adress', $this->adress])
            ->andFilterWhere(['like', 'phone_number', $this->phone_number])->where(['is_deleted' => false]);

        return $dataProvider;
    }
    public function searchCounter($params)
    {
        $query = Court::find();

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
            'city' => $this->city,
            'created_by' => $this->created_by,
            'last_updated_by' => $this->last_updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'adress', $this->adress])
            ->andFilterWhere(['like', 'phone_number', $this->phone_number])->where(['is_deleted' => false]);

        return $query->count();
    }
}
