<?php

namespace backend\modules\address\models;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
/**
 * addressSearch represents the model behind the search form about `app\models\address`.
 */
class AddressSearch extends address
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'customers_id', 'is_deleted'], 'integer'],
            [['address', 'created_at', 'updated_at'], 'safe'],
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
        $query = address::find();

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

        $query->andFilterWhere(['like', 'address', $this->address]);

        return $dataProvider;
    }
}
