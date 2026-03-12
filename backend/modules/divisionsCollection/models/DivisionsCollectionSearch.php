<?php

namespace backend\modules\divisionsCollection\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\divisionsCollection\models\DivisionsCollection;

/**
 * DivisionsCollectionSearch represents the model behind the search form about `backend\modules\divisionsCollection\models\DivisionsCollection`.
 */
class DivisionsCollectionSearch extends DivisionsCollection
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'collection_id', 'month', 'created_at', 'year','updated_at', 'created_by', 'last_updated_by', 'is_deleted'], 'integer'],
            [['amount'], 'number'],
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
        $query = DivisionsCollection::find();

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
            'collection_id' => $this->collection_id,
            'month' => $this->month,
            'amount' => $this->amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'last_updated_by' => $this->last_updated_by,
            'is_deleted' => $this->is_deleted,
        ]);

        return $dataProvider;
    }
}
