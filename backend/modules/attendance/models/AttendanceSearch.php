<?php

namespace backend\modules\attendance\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\attendance\models\Attendance;

/**
 * AttendanceSearch represents the model behind the search form about `common\models\Attendance`.
 */
class AttendanceSearch extends Attendance
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'location_id', 'manual_checked_in_by', 'manual_checked_out_by'], 'integer'],
            [['check_in_time', 'check_out_time', 'is_manual_actions'], 'safe'],
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
        $query = Attendance::find();

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
            'user_id' => $this->user_id,
            'location_id' => $this->location_id,
            'check_in_time' => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'manual_checked_in_by' => $this->manual_checked_in_by,
            'manual_checked_out_by' => $this->manual_checked_out_by,
        ]);

        $query->andFilterWhere(['like', 'is_manual_actions', $this->is_manual_actions]);

        return $dataProvider;
    }
}
