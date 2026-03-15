<?php

namespace backend\modules\officialHolidays\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Holiday;

/**
 * HolidaySearch represents the model behind the search form about `backend\models\Holiday`.
 */
class HolidaySearch extends Holiday
{
    public function rules()
    {
        return [
            [['id', 'year', 'created_at'], 'integer'],
            [['holiday_date', 'name', 'source'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Holiday::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['holiday_date' => SORT_DESC],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'year' => $this->year,
            'created_at' => $this->created_at,
        ]);

        $query->andFilterWhere(['like', 'holiday_date', $this->holiday_date])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['source' => $this->source]);

        return $dataProvider;
    }
}
