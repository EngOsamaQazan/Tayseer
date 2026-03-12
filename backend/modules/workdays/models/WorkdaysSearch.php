<?php

namespace  backend\modules\workdays\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use  backend\modules\workdays\models\Workdays;

/**
 * WorkdaysSearch represents the model behind the search form about `common\models\Workdays`.
 */
class WorkdaysSearch extends Workdays
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'created_by', 'created_at', 'updated_at'], 'integer'],
            [['day_name', 'start_at', 'end_at', 'status'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Workdays::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'day_name', $this->day_name])
            ->andFilterWhere(['like', 'status', $this->status]);

        return $dataProvider;
    }
}
