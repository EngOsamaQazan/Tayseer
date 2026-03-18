<?php

namespace backend\modules\accounting\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class CostCenterSearch extends CostCenter
{
    public function rules()
    {
        return [
            [['id', 'parent_id', 'company_id', 'is_active'], 'integer'],
            [['code', 'name'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = CostCenter::find()->orderBy(['code' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'company_id' => $this->company_id,
            'is_active' => $this->is_active,
        ]);

        $query->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}
