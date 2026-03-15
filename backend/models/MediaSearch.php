<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Search model for Media (os_ImageManager).
 * Replaces all duplicate ImageManagerSearch classes.
 */
class MediaSearch extends Media
{
    public $globalSearch;

    public function rules()
    {
        return [
            [['globalSearch', 'groupName', 'contractId'], 'safe'],
            [['customer_id', 'createdBy'], 'integer'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Media::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pagesize' => 20],
            'sort' => ['defaultOrder' => ['created' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['customer_id' => $this->customer_id])
              ->andFilterWhere(['createdBy' => $this->createdBy])
              ->andFilterWhere(['groupName' => $this->groupName])
              ->andFilterWhere(['contractId' => $this->contractId]);

        if (!empty($this->globalSearch)) {
            $query->andWhere(['or',
                ['like', 'fileName', $this->globalSearch],
                ['like', 'created', $this->globalSearch],
            ]);
        }

        if (isset($params['group-name'])) {
            $query->andFilterWhere(['groupName' => $params['group-name']]);
        }
        if (isset($params['contract-id'])) {
            $query->andFilterWhere(['contractId' => $params['contract-id']]);
        }

        return $dataProvider;
    }
}
