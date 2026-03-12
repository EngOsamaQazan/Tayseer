<?php

namespace backend\modules\expenses\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\expenses\models\Expenses;

/**
 * ExpensesSearch represents the model behind the search form about `common\models\Expenses`.
 */
class ExpensesSearch extends Expenses
{
    public $date_from;
    public $date_to;
    public $amount_from;
    public $amount_to;
    public $number_row;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'category_id', 'created_at', 'created_by', 'updated_at', 'last_updated_by', 'is_deleted', 'receiver_number', 'financial_transaction_id','contract_id','number_row'], 'integer'],
            [['description', 'expenses_date', 'amount_from', 'amount_to', 'date_from', 'date_to', 'contract_id', 'document_number'], 'safe'],
            [['amount', 'amount_from', 'amount_to'], 'number'],
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
        $query = Expenses::find();


        if(!empty($params['ExpensesSearch']['number_row'])){
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['ExpensesSearch']['number_row'],
                ],

            ]);
        }else{
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        /* ═══ إذا لم يُحدد تاريخ "من" → لا نعرض أي نتائج ═══ */
        if (empty($this->date_from)) {
            $query->where('0=1');
            return $dataProvider;
        }

        /* ═══ إذا "إلى" فارغ → نعتبره تاريخ اليوم ═══ */
        if (empty($this->date_to)) {
            $this->date_to = date('Y-m-d');
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'category_id' => $this->category_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'last_updated_by' => $this->last_updated_by,
            'financial_transaction_id' => $this->financial_transaction_id,
        ]);

        $query->andWhere(['>=', 'expenses_date', $this->date_from]);
        $query->andWhere(['<=', 'expenses_date', $this->date_to]);
        if ((!empty($this->amount_from))) {
            $query->andWhere(['>=', 'amount', $this->amount_from]);
        }
        if ((!empty($this->amount_to))) {
            $query->andWhere(['<=', 'amount', $this->amount_to]);
        }
        $query->andFilterWhere(['created_by' => $this->created_by]);
        $query->andFilterWhere(['receiver_number' => $this->receiver_number]);
        $query->andFilterWhere(['category_id' => $this->category_id]);
        $query->andFilterWhere(['contract_id' => $this->contract_id]);
        $query->andFilterWhere(['document_number' => $this->document_number]);
        $query->andFilterWhere(['like', 'description', $this->description])->andwhere(['os_expenses.is_deleted' => 0]);

        return $dataProvider;
    }
}
