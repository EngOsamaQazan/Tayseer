<?php

namespace backend\modules\accounting\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class PayableSearch extends Payable
{
    public function rules()
    {
        return [
            [['id', 'vendor_id', 'account_id', 'company_id'], 'integer'],
            [['vendor_name', 'status', 'due_date', 'description', 'category', 'reference_number'], 'safe'],
            [['amount', 'paid_amount', 'balance'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Payable::find()
            ->with(['account'])
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
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'account_id' => $this->account_id,
            'status' => $this->status,
            'company_id' => $this->company_id,
            'category' => $this->category,
        ]);

        $query->andFilterWhere(['like', 'vendor_name', $this->vendor_name])
            ->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'reference_number', $this->reference_number]);

        return $dataProvider;
    }
}
