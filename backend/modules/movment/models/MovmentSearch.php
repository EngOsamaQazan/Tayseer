<?php

namespace  backend\modules\movment\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use  backend\modules\movment\models\Movment;

/**
 * MovmentSearch represents the model behind the search form about `common\models\Movment`.
 */
class MovmentSearch extends Movment
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'movement_number', 'bank_receipt_number', 'financial_value'], 'integer'],
            [['receipt_image'], 'safe'],
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
        $query = Movment::find();

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
            'movement_number' => $this->movement_number,
            'bank_receipt_number' => $this->bank_receipt_number,
            'financial_value' => $this->financial_value,
        ]);

        $query->andFilterWhere(['like', 'receipt_image', $this->receipt_image]);

        return $dataProvider;
    }
}
