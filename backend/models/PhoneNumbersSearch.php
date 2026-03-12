<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\PhoneNumbers;

/**
 * PhoneNumbersSearch represents the model behind the search form about `common\models\PhoneNumbers`.
 */
class PhoneNumbersSearch extends PhoneNumbers
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'customers_id', 'is_deleted'], 'integer'],
            [['phone_number', 'created_at', 'updated_at', 'phone_number_owner', 'owner_name'], 'safe'],
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
        $query = PhoneNumbers::find();

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
            'customers_id' => $this->customers_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
        ]);

        $query->andFilterWhere(['like', 'phone_number', $this->phone_number])
            ->andFilterWhere(['like', 'phone_number_owner', $this->phone_number_owner])
            ->andFilterWhere(['like', 'owner_name', $this->owner_name]);

        return $dataProvider;
    }
}
