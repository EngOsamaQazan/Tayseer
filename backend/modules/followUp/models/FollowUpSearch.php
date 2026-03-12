<?php

namespace backend\modules\followUp\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\followUp\models\FollowUp;

/**
 * FollowUpSearch represents the model behind the search form about `common\models\FollowUp`.
 */
class FollowUpSearch extends FollowUp
{
    /**
     * @inheritdoc
     */
    public $date_from;
    public $date_to;
    public $number_row;

    public function rules()
    {
        return [
            [['id', 'contract_id', 'created_by','number_row'], 'integer'],
            [['date_time', 'connection_type', 'clinet_response', 'feeling', 'connection_goal'], 'safe'],
            [['date_from', 'date_to'], 'string']
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
        $query = FollowUp::find();
        if(!empty($params['FollowUpSearch']['number_row'])){

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['FollowUpSearch']['number_row'],
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
            'contract_id' => $this->contract_id,
            'date_time' => $this->date_time,
            'created_by' => $this->created_by,
        ]);
        $query->where(['=', 'contract_id', $params['contract_id']])->orderBy(['date_time' => SORT_DESC]);

        return $dataProvider;
    }

    public function searchReport($params)
    {
        $query = FollowUp::find();

        if(!empty($params['FollowUpSearch']['number_row'])){

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['FollowUpSearch']['number_row'],
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
            'contract_id' => $this->contract_id,
            'date_time' => $this->date_time,
            'created_by' => $this->created_by,
            'reminder' => $this->reminder,
            'promise_to_pay_at' => $this->promise_to_pay_at,
        ]);

        $query->andFilterWhere(['like', 'notes', $this->notes])
            ->andFilterWhere(['like', 'feeling', $this->feeling])
            ->andFilterWhere(['like', 'connection_goal', $this->connection_goal]);
        if ((!empty($this->date_from))) {

            $query->andFilterWhere(['>=', 'date_time', $this->date_from]);
        }
        if ((!empty($this->date_to))) {

            $query->andFilterWhere(['<=', 'date_time', $this->date_to]);
        }

        return $dataProvider;
    }

    public function searchReportCount($params)
    {
        $query = FollowUp::find();

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
            'contract_id' => $this->contract_id,
            'date_time' => $this->date_time,
            'created_by' => $this->created_by,
            'reminder' => $this->reminder,
            'promise_to_pay_at' => $this->promise_to_pay_at,
        ]);

        $query->andFilterWhere(['like', 'notes', $this->notes])
            ->andFilterWhere(['like', 'feeling', $this->feeling])
            ->andFilterWhere(['like', 'connection_goal', $this->connection_goal]);

        if ((!empty($this->date_from))) {

            $query->andFilterWhere(['>=', 'date_time', $this->date_from]);
        }
        if ((!empty($this->date_to))) {

            $query->andFilterWhere(['<=', 'date_time', $this->date_to]);
        }

        return $query->count();
    }
}
