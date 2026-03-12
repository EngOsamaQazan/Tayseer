<?php

namespace backend\modules\collection\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\collection\models\Collection;

/**
 * CollectionSearch represents the model behind the search form about `common\models\Collection`.
 */
class CollectionSearch extends Collection
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'contract_id', 'created_at', 'updated_at', 'created_by', 'last_updated_by', 'is_deleted'], 'integer'],
            [['date', 'notes'], 'safe'],
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
        $query = Collection::find();

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
            'contract_id' => $this->contract_id,
            'date' => $this->date,
            'amount' => $this->amount,
        ]);

        $query->andFilterWhere(['like', 'notes', $this->notes])->where(['is_deleted'=>0]);

        return $dataProvider;
    }
}
