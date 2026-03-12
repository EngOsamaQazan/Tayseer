<?php

namespace backend\modules\contractInstallment\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\contractInstallment\models\ContractInstallment;

/**
 * ContractInstallmentSearch represents the model behind the search form of `backend\modules\contractInstallment\models\contract-installment`.
 */
class ContractInstallmentSearch extends ContractInstallment
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'contract_id', 'created_by', 'type'], 'integer'],
            [['date', 'payment_type', '_by', 'receipt_bank', 'payment_purpose'], 'safe'],
            [['amount'], 'number'],
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
        $query = ContractInstallment::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (isset($params['contract_id'])) {
            $query->andWhere(['contract_id' => $params['contract_id']]);
        }

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'date' => $this->date,
            'amount' => $this->amount,
            'created_by' => $this->created_by,
            'type' => $this->type,
        ]);

        $query->andFilterWhere(['like', 'payment_type', $this->payment_type])
            ->andFilterWhere(['like', '_by', $this->_by])
            ->andFilterWhere(['like', 'receipt_bank', $this->receipt_bank])
            ->andFilterWhere(['like', 'payment_purpose', $this->payment_purpose])->orderBy(['date' => SORT_ASC]);

        return $dataProvider;
    }
}
