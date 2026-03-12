<?php

namespace backend\modules\department\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\department\models\Department;

/**
 * DepartmentSearch represents the model behind the search form about `common\models\Department`.
 */
class DepartmentSearch extends Department {

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'lead_by', 'created_by', 'created_at', 'updated_at'], 'integer'],
            [['title', 'description', 'status'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios() {
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
    public function search($params) {
        $query = Department::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $dataProvider->sort->attributes['username'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['leadBy.username' => SORT_ASC],
            'desc' => ['leadBy.username' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'lead_by' => $this->lead_by,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title])
                ->andFilterWhere(['like', 'description', $this->description])
                ->andFilterWhere(['like', 'status', $this->status]);

        return $dataProvider;
    }

}
