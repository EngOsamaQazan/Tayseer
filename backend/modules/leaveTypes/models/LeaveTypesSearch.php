<?php

namespace  backend\modules\LeaveTypes\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use  backend\modules\LeaveTypes\models\LeaveTypes;

/**
 * LeaveTypesSearch represents the model behind the search form about `common\models\LeaveTypes`.
 */
class LeaveTypesSearch extends LeaveTypes
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'leave_type', 'total_days', 'department', 'designation', 'location', 'created_by', 'created_at', 'updated_at'], 'integer'],
            [['year', 'description', 'gender', 'marital_status', 'status'], 'safe'],
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
        $query = LeaveTypes::find();

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
            'year' => $this->year,
            'leave_type' => $this->leave_type,
            'total_days' => $this->total_days,
            'department' => $this->department,
            'designation' => $this->designation,
            'location' => $this->location,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'gender', $this->gender])
            ->andFilterWhere(['like', 'marital_status', $this->marital_status])
            ->andFilterWhere(['like', 'status', $this->status]);

        return $dataProvider;
    }
}
