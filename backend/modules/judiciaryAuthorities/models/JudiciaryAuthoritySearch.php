<?php

namespace backend\modules\judiciaryAuthorities\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\JudiciaryAuthority;

/**
 * JudiciaryAuthoritySearch represents the model behind the search form of `backend\models\JudiciaryAuthority`.
 */
class JudiciaryAuthoritySearch extends JudiciaryAuthority
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'is_deleted', 'created_at', 'updated_at', 'created_by', 'company_id'], 'integer'],
            [['name', 'authority_type', 'notes'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
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
        $query = JudiciaryAuthority::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $companyId = Yii::$app->user->identity->company_id ?? null;
        if ($companyId !== null) {
            $query->andWhere(['company_id' => $companyId]);
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'authority_type' => $this->authority_type,
            'is_deleted' => $this->is_deleted,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'company_id' => $this->company_id,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'notes', $this->notes]);

        return $dataProvider;
    }
}
