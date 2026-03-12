<?php

namespace backend\modules\judiciaryActions\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * JudiciaryActionsSearch — supports action_nature filtering
 */
class JudiciaryActionsSearch extends JudiciaryActions
{
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['name', 'action_type', 'action_nature'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     */
    public function search($params)
    {
        $query = JudiciaryActions::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_ASC],
            ],
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Filter by soft delete — show active by default
        $query->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]]);

        $query->andFilterWhere([
            'id' => $this->id,
            'action_type' => $this->action_type,
            'action_nature' => $this->action_nature,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }

    public function searchCounter($params)
    {
        $query = JudiciaryActions::find();

        $this->load($params);

        // Active records only
        $query->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]]);

        if ($this->validate()) {
            $query->andFilterWhere([
                'id' => $this->id,
                'action_type' => $this->action_type,
                'action_nature' => $this->action_nature,
            ]);
            $query->andFilterWhere(['like', 'name', $this->name]);
        }

        return $query->count();
    }
}
