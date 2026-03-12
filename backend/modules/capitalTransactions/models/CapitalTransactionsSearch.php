<?php

namespace backend\modules\capitalTransactions\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class CapitalTransactionsSearch extends CapitalTransactions
{
    public $date_from;
    public $date_to;

    public function rules()
    {
        return [
            [['id', 'company_id', 'created_by', 'created_at'], 'integer'],
            [['transaction_type', 'transaction_date', 'payment_method', 'reference_number', 'notes', 'date_from', 'date_to'], 'safe'],
            [['amount', 'balance_after'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = CapitalTransactions::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'company_id' => $this->company_id,
            'transaction_type' => $this->transaction_type,
            'created_by' => $this->created_by,
        ]);

        $query->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'reference_number', $this->reference_number])
            ->andFilterWhere(['like', 'notes', $this->notes]);

        if (!empty($this->date_from)) {
            $query->andWhere(['>=', 'transaction_date', $this->date_from]);
        }
        if (!empty($this->date_to)) {
            $query->andWhere(['<=', 'transaction_date', $this->date_to]);
        }

        return $dataProvider;
    }
}
