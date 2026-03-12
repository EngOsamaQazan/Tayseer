<?php

namespace backend\modules\companyBanks\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\companyBanks\models\CompanyBanks;

/**
 * CompanyBanksSearch represents the model behind the search form about `backend\modules\companyBanks\models\CompanyBanks`.
 */
class CompanyBanksSearch extends CompanyBanks
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'company_id', 'created_at', 'updated_at', 'created_by', 'last_updated_by', 'is_deleted'], 'integer'],
            [['bank_id', 'bank_number'], 'safe'],
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
        $query = CompanyBanks::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        $query->andFilterWhere(['like', 'bank_id', $this->bank_id])
            ->andFilterWhere(['like', 'bank_number', $this->bank_number])
            ->andFilterWhere(['=', 'is_deleted', false]);


        return $dataProvider;
    }
}
