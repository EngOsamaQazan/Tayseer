<?php

namespace backend\modules\documentHolder\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\documentHolder\models\DocumentHolder;

/**
 * DocumentHolderSearch represents the model behind the search form about `common\models\DocumentHolder`.
 */
class DocumentHolderSearch extends DocumentHolder
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'approved_by_manager', 'approved_by_employee','contract_id', 'manager_approved'], 'integer'],
            [['approved_at', 'reason', 'status', 'type', 'manager_approved'], 'safe'],
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
        $query = DocumentHolder::find();

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
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'approved_by_manager' => $this->approved_by_manager,
            'approved_by_employee' => $this->approved_by_employee,
            'approved_at' => $this->approved_at,
            'contract_id' => $this->contract_id,
        ]);

        $query->andFilterWhere(['like', 'reason', $this->reason]);
        $query->andFilterWhere(['like', 'manager_approved', $this->manager_approved])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'type', $this->type]);
        $query->where(['created_by' => Yii::$app->user->id]);
        return $dataProvider;
    }
    public function managerSearch($params)
    {
        $query = DocumentHolder::find();

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
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'approved_by_manager' => $this->approved_by_manager,
            'approved_by_employee' => $this->approved_by_employee,
            'approved_at' => $this->approved_at,
            'contract_id' => $this->contract_id,
        ]);

        $query->andFilterWhere(['like', 'reason', $this->reason]);
        $query->andFilterWhere(['like', 'manager_approved', $this->manager_approved])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'type', $this->type]);
        return $dataProvider;
    }
}
