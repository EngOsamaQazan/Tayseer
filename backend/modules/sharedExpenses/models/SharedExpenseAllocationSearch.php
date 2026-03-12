<?php

namespace backend\modules\sharedExpenses\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class SharedExpenseAllocationSearch extends SharedExpenseAllocation
{
    public function rules()
    {
        return [
            [['id', 'created_by', 'approved_by'], 'integer'],
            [['name', 'allocation_method', 'allocation_date', 'status', 'period_from', 'period_to', 'notes'], 'safe'],
            [['total_amount'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = SharedExpenseAllocation::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'allocation_method' => $this->allocation_method,
            'status' => $this->status,
            'allocation_date' => $this->allocation_date,
            'created_by' => $this->created_by,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}
