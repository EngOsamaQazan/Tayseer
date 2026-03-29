<?php

namespace backend\modules\branch\models;

use yii\data\ActiveDataProvider;

class BranchSearch extends Branch
{
    public function rules()
    {
        return [
            [['id', 'company_id', 'manager_id', 'is_active', 'created_by'], 'integer'],
            [['name', 'code', 'branch_type', 'address', 'phone'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = Branch::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['sort_order' => SORT_ASC, 'id' => SORT_ASC],
            ],
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id'          => $this->id,
            'company_id'  => $this->company_id,
            'branch_type' => $this->branch_type,
            'is_active'   => $this->is_active,
            'manager_id'  => $this->manager_id,
            'created_by'  => $this->created_by,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
              ->andFilterWhere(['like', 'code', $this->code])
              ->andFilterWhere(['like', 'address', $this->address])
              ->andFilterWhere(['like', 'phone', $this->phone]);

        return $dataProvider;
    }
}
