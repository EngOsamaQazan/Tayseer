<?php

namespace backend\modules\diwan\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * نموذج البحث في معاملات الديوان
 */
class DiwanTransactionSearch extends DiwanTransaction
{
    /** حقل بحث رقم العقد */
    public $contract_search;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'from_employee_id', 'to_employee_id', 'created_by'], 'integer'],
            [['transaction_type', 'receipt_number', 'notes', 'transaction_date', 'contract_search'], 'safe'],
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
     * البحث في المعاملات
     */
    public function search($params)
    {
        $query = DiwanTransaction::find()
            ->alias('t')
            ->with(['fromEmployee', 'toEmployee', 'createdByUser', 'details']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        /* فلترة حسب رقم العقد */
        if (!empty($this->contract_search)) {
            $query->innerJoin('os_diwan_transaction_details d', 'd.transaction_id = t.id')
                  ->andWhere(['like', 'd.contract_number', $this->contract_search]);
        }

        $query->andFilterWhere([
            't.id' => $this->id,
            't.transaction_type' => $this->transaction_type,
            't.from_employee_id' => $this->from_employee_id,
            't.to_employee_id' => $this->to_employee_id,
            't.created_by' => $this->created_by,
        ]);

        $query->andFilterWhere(['like', 't.receipt_number', $this->receipt_number])
              ->andFilterWhere(['like', 't.notes', $this->notes]);

        if (!empty($this->transaction_date)) {
            $query->andFilterWhere(['DATE(t.transaction_date)' => $this->transaction_date]);
        }

        return $dataProvider;
    }
}
