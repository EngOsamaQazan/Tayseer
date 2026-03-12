<?php

namespace backend\modules\jobs\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * JobsSearch represents the model behind the search form of `backend\modules\jobs\models\Jobs`.
 */
class JobsSearch extends Jobs
{
    public $customersCount;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'job_type', 'status'], 'integer'],
            [['name', 'address_city', 'address_area', 'email'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Jobs::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'job_type' => $this->job_type,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'address_city', $this->address_city])
            ->andFilterWhere(['like', 'address_area', $this->address_area])
            ->andFilterWhere(['like', 'email', $this->email]);

        return $dataProvider;
    }
}
