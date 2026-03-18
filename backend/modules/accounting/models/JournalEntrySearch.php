<?php

namespace backend\modules\accounting\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class JournalEntrySearch extends JournalEntry
{
    public $date_from;
    public $date_to;

    public function rules()
    {
        return [
            [['id', 'fiscal_year_id', 'fiscal_period_id', 'reference_id', 'is_auto', 'company_id', 'created_by'], 'integer'],
            [['entry_number', 'entry_date', 'reference_type', 'description', 'status', 'date_from', 'date_to'], 'safe'],
            [['total_debit', 'total_credit'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = JournalEntry::find()
            ->with(['fiscalYear', 'fiscalPeriod', 'lines.account', 'createdByUser'])
            ->orderBy(['entry_date' => SORT_DESC, 'id' => SORT_DESC]);

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
            'fiscal_year_id' => $this->fiscal_year_id,
            'fiscal_period_id' => $this->fiscal_period_id,
            'status' => $this->status,
            'is_auto' => $this->is_auto,
            'company_id' => $this->company_id,
            'reference_type' => $this->reference_type,
        ]);

        $query->andFilterWhere(['like', 'entry_number', $this->entry_number])
            ->andFilterWhere(['like', 'description', $this->description]);

        if ($this->date_from) {
            $query->andFilterWhere(['>=', 'entry_date', $this->date_from]);
        }
        if ($this->date_to) {
            $query->andFilterWhere(['<=', 'entry_date', $this->date_to]);
        }

        return $dataProvider;
    }
}
