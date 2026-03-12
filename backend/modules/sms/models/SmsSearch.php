<?php

namespace backend\modules\sms\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\sms\models\Sms;

/**
 * SmsSearch represents the model behind the search form about `backend\modules\sms\models\Sms`.
 */
class SmsSearch extends Sms
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['is_send','created_at','updated_at','created_by','last_updated_by','is_deleted','type','customers_id','contract_id'], 'integer'],
            [['date', 'is_send','created_at','updated_at','created_by','last_updated_by','is_deleted','type','customers_id','contract_id'], 'safe'],
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
        $query = Sms::find();

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
            'date' => $this->date,
        ]);

        $query->andFilterWhere(['like', 'is_send', $this->is_send])->where(['is_deleted'=>0]);

        return $dataProvider;
    }
}
