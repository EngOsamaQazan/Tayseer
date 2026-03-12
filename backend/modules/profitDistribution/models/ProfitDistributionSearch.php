<?php

namespace backend\modules\profitDistribution\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class ProfitDistributionSearch extends ProfitDistributionModel
{
    public function rules()
    {
        return [
            [['id', 'company_id', 'created_by', 'approved_by'], 'integer'],
            [['distribution_type', 'status', 'period_from', 'period_to', 'notes'], 'safe'],
            [['total_revenue', 'direct_expenses', 'shared_expenses', 'net_profit',
              'investor_share_pct', 'investor_amount', 'parent_amount', 'distribution_amount'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = ProfitDistributionModel::find();

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
            'company_id' => $this->company_id,
            'distribution_type' => $this->distribution_type,
            'status' => $this->status,
            'created_by' => $this->created_by,
        ]);

        $query->andFilterWhere(['>=', 'period_from', $this->period_from])
              ->andFilterWhere(['<=', 'period_to', $this->period_to]);

        return $dataProvider;
    }

    public function searchCounter($params)
    {
        $query = ProfitDistributionModel::find();

        $this->load($params);

        if (!$this->validate()) {
            return 0;
        }

        $query->andFilterWhere([
            'distribution_type' => $this->distribution_type,
            'status' => $this->status,
        ]);

        return $query->count();
    }
}
