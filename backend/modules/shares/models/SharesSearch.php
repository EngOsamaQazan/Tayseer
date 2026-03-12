<?php

namespace backend\modules\shares\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\shares\models\Shares;

/**
 * SharesSearch represents the model behind the search form of `app\models\Shares`.
 */
class SharesSearch extends Shares
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'shareholder_id', 'amount'], 'integer'],
            [['notes', 'receive_number', 'date'], 'safe'],
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
        $query = Shares::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'shareholder_id' => $this->shareholder_id,
            'amount' => $this->amount,
            'date' => $this->date,
        ]);

        $query->andFilterWhere(['like', 'notes', $this->notes])
            ->andFilterWhere(['like', 'receive_number', $this->receive_number]);

        return $dataProvider;
    }
}
