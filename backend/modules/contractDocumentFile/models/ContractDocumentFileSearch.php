<?php

namespace backend\modules\contractDocumentFile\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\contractDocumentFile\models\ContractDocumentFile;

/**
 * ContractDocumentFileSearch represents the model behind the search form about `common\models\ContractDocumentFile`.
 */
class ContractDocumentFileSearch extends ContractDocumentFile
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'contract_id'], 'integer'],
            [['document_type'], 'safe'],
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
        $query = ContractDocumentFile::find();

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
        ]);

        $query->andFilterWhere(['like', 'document_type', $this->document_type]);

        return $dataProvider;
    }
}
