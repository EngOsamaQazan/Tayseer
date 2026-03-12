<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\FollowUp;

/**
 * FollowUpSearch represents the model behind the search form of `common\models\FollowUp`.
 */
class FollowUpSearch extends FollowUp {

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            [['id', 'contract_id', 'created_by', 'connection_goal'], 'integer'],
            [['date_time', 'notes', 'feeling'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios() {
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
    public function search($params, $contract_id = null) {
        $query = FollowUp::find()->orderBy(['id' => SORT_DESC]);

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
            'date_time' => $this->date_time,
            'created_by' => $this->created_by,
            'connection_goal' => $this->connection_goal,
        ]);
        if (isset($contract_id)) {
            $query->andFilterWhere([
                'contract_id' => $contract_id]
            );
        }

        $query->andFilterWhere(['like', 'notes', $this->notes])
                ->andFilterWhere(['like', 'feeling', $this->feeling]);

        return $dataProvider;
    }

}
