<?php

namespace backend\modules\accounting\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class ReceivableSearch extends Receivable
{
    public $customer_name;
    public $aging;

    public function rules()
    {
        return [
            [['id', 'customer_id', 'contract_id', 'account_id', 'company_id'], 'integer'],
            [['status', 'due_date', 'description', 'customer_name', 'aging'], 'safe'],
            [['amount', 'paid_amount', 'balance'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Receivable::find()
            ->joinWith(['customer', 'account'])
            ->orderBy(['due_date' => SORT_ASC, 'balance' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 25],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            '{{%receivables}}.id' => $this->id,
            '{{%receivables}}.customer_id' => $this->customer_id,
            '{{%receivables}}.contract_id' => $this->contract_id,
            '{{%receivables}}.account_id' => $this->account_id,
            '{{%receivables}}.status' => $this->status,
            '{{%receivables}}.company_id' => $this->company_id,
        ]);

        $query->andFilterWhere(['like', '{{%receivables}}.description', $this->description]);

        return $dataProvider;
    }
}
