<?php

namespace backend\modules\loanScheduling\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\loanScheduling\models\LoanScheduling;

/**
 * LoanSchedulingSearch represents the model behind the search form about `backend\modules\loanScheduling\models\LoanScheduling`.
 */
class LoanSchedulingSearch extends LoanScheduling
{
    /**
     * @inheritdoc
     */
    public $number_row;
    public function rules()
    {
        return [
            [['id', 'contract_id', 'status_action_by', 'created_by', 'last_update_by', 'is_deleted'], 'integer'],
            [['new_installment_date', 'first_installment_date', 'created_at', 'updated_at', 'status', 'number_row'], 'safe'],
            [['monthly_installment'], 'number'],
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
        $query = LoanScheduling::find();


        if(!empty($params['LoanSchedulingSearch']['number_row'])){
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['LoanSchedulingSearch']['number_row'],
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
        $query->andFilterWhere(['=','first_installment_date' , $this->first_installment_date]);
        $query->andFilterWhere(['=', 'contract_id', $this->contract_id]);
        $query->andFilterWhere(['=','monthly_installment' , $this->monthly_installment]);
        $query->andFilterWhere(['=','status' , $this->status]);
        $query->andFilterWhere(['=','status_action_by' , $this->status_action_by]);
        $query->andFilterWhere(['=','created_by' , $this->created_by]);
        $query->andFilterWhere(['=','last_update_by' , $this->last_update_by]);
        $query->andWhere(['is_deleted' => false]);
        return $dataProvider;
    }
}
