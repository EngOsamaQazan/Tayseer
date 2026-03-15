<?php

namespace backend\modules\judiciaryRequestTemplates\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\JudiciaryRequestTemplate;

/**
 * JudiciaryRequestTemplateSearch represents the model behind the search form of JudiciaryRequestTemplate.
 */
class JudiciaryRequestTemplateSearch extends JudiciaryRequestTemplate
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'is_combinable', 'sort_order'], 'integer'],
            [['name', 'template_type'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = JudiciaryRequestTemplate::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['sort_order' => SORT_ASC],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'is_combinable' => $this->is_combinable,
            'sort_order' => $this->sort_order,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
              ->andFilterWhere(['like', 'template_type', $this->template_type]);

        return $dataProvider;
    }
}
