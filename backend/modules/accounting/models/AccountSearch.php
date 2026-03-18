<?php

namespace backend\modules\accounting\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class AccountSearch extends Account
{
    public function rules()
    {
        return [
            [['id', 'parent_id', 'level', 'is_parent', 'is_active', 'company_id'], 'integer'],
            [['code', 'name_ar', 'name_en', 'type', 'nature', 'description'], 'safe'],
            [['opening_balance'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Account::find()->orderBy(['code' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => [
                'defaultOrder' => ['code' => SORT_ASC],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'type' => $this->type,
            'nature' => $this->nature,
            'level' => $this->level,
            'is_parent' => $this->is_parent,
            'is_active' => $this->is_active,
            'company_id' => $this->company_id,
        ]);

        $query->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['like', 'name_ar', $this->name_ar])
            ->andFilterWhere(['like', 'name_en', $this->name_en]);

        return $dataProvider;
    }
}
