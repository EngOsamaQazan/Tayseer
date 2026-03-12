<?php

namespace backend\modules\income\models;

use backend\modules\judiciary\models\Judiciary;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\contracts\models\Contracts;
use yii\helpers\ArrayHelper;

/**
 * IncomeSearch represents the model behind the search form about `app\models\Income`.
 */
class IncomeSearch extends Income
{
    public $amount_sum = 0;
    public $date_from;
    public $date_to;
    public $from_date;
    public $to_date;

    public $followed_by;
    public $company_id;
    public $number_row;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //[['id', 'item_id', 'customer_id', 'is_made_payment', 'total'], 'integer'], because this search model not used
            [['date', 'from_date', 'to_date', 'type', 'cheque_number', 'notes', 'date_from', 'date_to', 'financial_transaction_id', 'followed_by', 'company_id', 'income_status', 'payment_type', '_by', 'number_row', 'created_by'], 'safe'],
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
        $query = Income::find();
        if (!empty($params['IncomeSearch']['number_row'])) {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['IncomeSearch']['number_row'],
                ],
            ]);
        }
        else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'item_id' => $this->item_id,
            'customer_id' => $this->customer_id,
            'date' => $this->date,
            'is_made_payment' => $this->is_made_payment,
            'total' => $this->total,
            'type' => $this->type,
            'financial_transaction_id' => $this->financial_transaction_id
        ]);

        $query->andFilterWhere(['like', 'cheque_number', $this->cheque_number])
            ->andFilterWhere(['like', 'notes', $this->notes]);

        return $dataProvider;
    }

    public function incomeListSearch($params)
    {
        $query = Income::find()->leftJoin('{{%contracts}}', '{{%income}}.contract_id = {{%contracts}}.id');

        if (!empty($params['IncomeSearch']['number_row'])) {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['IncomeSearch']['number_row'],
                ],
            ]);
        }
        else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            //'date' => $this->date,
            'amount' => $this->amount,
            'created_by' => $this->created_by,

            'financial_transaction_id' => $this->financial_transaction_id
        ]);
        if (!empty($params['IncomeSearch']['payment_type'])) {
            $query->andFilterWhere(['=', 'payment_type', $params['IncomeSearch']['payment_type']]);
        }
        $query
            ->andFilterWhere(['like', 'receipt_bank', $this->receipt_bank])
            ->andFilterWhere(['like', 'payment_purpose', $this->payment_purpose])
            ->andFilterWhere(['=', '{{%income}}.type', $this->type]);

        /* ═══ إذا لم يُحدد تاريخ "من" ولا "الدافع" → لا نعرض أي نتائج ═══ */
        if (empty($this->date_from) && empty($params['IncomeSearch']['_by'])) {
            $query->andWhere('0=1');
            return $dataProvider;
        }

        /* ═══ إذا "إلى" فارغ → نعتبره تاريخ اليوم ═══ */
        if (!empty($this->date_from) && empty($this->date_to)) {
            $this->date_to = date('Y-m-d');
        }

        if (!empty($this->date_from)) {
            $query->andWhere(['>=', 'date', $this->date_from]);
        }
        if (!empty($this->date_to)) {
            $query->andWhere(['<=', 'date', $this->date_to]);
        }
        if (!empty($params['IncomeSearch']['_by'])) {
            $query->andFilterWhere(['_by' => $params['IncomeSearch']['_by']]);
        }
        if (!empty($params['IncomeSearch']['company_id'])) {
            $query->andFilterWhere(['{{%contracts}}.company_id' => $params['IncomeSearch']['company_id']]);
        }


        return $dataProvider;
    }


    public function getTotalAmount($params)
    {

        $query = Income::find()->leftJoin('{{%contracts}}', '{{%income}}.contract_id = {{%contracts}}.id');


        $this->load($params);

        if (!$this->validate()) {
            return 0;
        }
        $query->andFilterWhere(['=', '{{%income}}.type', $this->type]);
        if (!empty($params['IncomeSearch']['company_id'])) {
            $query->andFilterWhere(['{{%contracts}}.company_id' => $params['IncomeSearch']['company_id']]);
        }

        if ((!empty($this->date_from))) {
            $query->andWhere(['>=', 'date', $this->date_from]);
        }

        if ((!empty($this->date_to))) {
            $query->andWhere(['<=', 'date', $this->date_to]);
        }
        if ((empty($this->date_to) and empty($this->date_from) and empty($params['IncomeSearch']['_by']))) {
            $query->andFilterWhere(['=', 'date', '1000-01-01']);
        }
        if ((!empty($params['IncomeSearch']['_by']))) {
            $query->andFilterWhere(['_by' => $params['IncomeSearch']['_by']]);
        }
        if (!empty($params['IncomeSearch']['payment_type'])) {
            $query->andFilterWhere(['=', 'payment_type', $params['IncomeSearch']['payment_type']]);
        }
        return $query->sum('amount');

    }

    public function totalCustomerPayments($params)
    {
        $query = Income::find()->innerJoin('{{%contracts}}', '{{%contracts}}.id = {{%income}}.contract_id');


        if ( !empty($params['IncomeSearch']['income_status']) and  $params['IncomeSearch']['income_status'] == 1) {
            $query ->andWhere(['not in','os_income.contract_id',ArrayHelper::map(Judiciary::find()->all(),'contract_id','contract_id')]) ;
        }
        if (!empty($params['IncomeSearch']['income_status']) and  $params['IncomeSearch']['income_status'] == 2) {
            $query ->andWhere(['in','os_income.contract_id',ArrayHelper::map(Judiciary::find()->all(),'contract_id','contract_id')]) ;

        }



        $this->load($params);

        // if ($this->validate()) {
        //     // uncomment the following line if you do not want to return any records when validation fails
        //     // $query->where('0=1');
        //     return $dataProvider;
        // }
        if ((!empty($params['IncomeSearch']['from_date']))) {
            $query->andFilterWhere(['>=', '{{%contracts}}.Date_of_sale', $params['IncomeSearch']['from_date']]);

        }
        if ((!empty($params['IncomeSearch']['to_date']))) {
            $query->andFilterWhere(['<=', '{{%contracts}}.Date_of_sale', $params['IncomeSearch']['to_date']]);
        }



        $query->andFilterWhere([
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'date' => $this->date,
            'amount' => $this->amount,
            '{{%income}}.created_by' => $this->created_by,
            'financial_transaction_id' => $this->financial_transaction_id,
            'document_number' => $this->document_number,
        ]);

        $query->andFilterWhere(['like', 'payment_type', $this->payment_type])
            ->andFilterWhere(['like', '_by', $this->_by])
            ->andFilterWhere(['like', 'receipt_bank', $this->receipt_bank])
            ->andFilterWhere(['like', 'payment_purpose', $this->payment_purpose])
            ->andFilterWhere(['like', 'notes', $this->notes]);
        if (!empty($params['IncomeSearch']['company_id'])) {
            $query->andFilterWhere(['{{%contracts}}.company_id' => $params['IncomeSearch']['company_id']]);
        }
        if (!empty($params['IncomeSearch']['payment_type'])) {
            $query->andFilterWhere(['=', 'payment_type', $params['IncomeSearch']['payment_type']]);
        }


        if ((empty($this->date_to) and empty($this->date_from) and empty($params['IncomeSearch']['_by']))) {
            $query->andFilterWhere(['=', 'date', '1000-01-01']);
        }

        if (!empty($params['IncomeSearch']['followed_by'])) {
            if (!empty($params['IncomeSearch']['created_by'])) {
                $query->andWhere(['=', '{{%income}}.created_by', $params['IncomeSearch']['created_by']]);
            }
            $query->where(['followed_by' => $params['IncomeSearch']['followed_by']]);
        }
        else {

            if (!empty($params['IncomeSearch']['created_by'])) {
                $query->andWhere(['=', '{{%income}}.created_by', $params['IncomeSearch']['created_by']]);
            }
        }

        if (!empty('_by') && $this->_by != null) {
            $query->where(['=', '_by', $this->_by]);
        }
        if (!empty($params['IncomeSearch']['type'])) {
            $query->andFilterWhere(['in', '{{%income}}.type', $params['IncomeSearch']['type']]);
        }
       // $query->andFilterWhere(['=', '{{%contracts}}.is_deleted', 0]);
        $this->amount_sum = $query->sum('amount');
        /*  if((!empty($this->date_to) or  !empty($this->date_from)) and  $params['IncomeSearch']['income_status'] == 1 ){
              $query->andWhere(['=','os_contracts.status' ,'active']);

          } if((!empty($this->date_to) or  !empty($this->date_from)) and  $params['IncomeSearch']['income_status'] == 2 ){
          $query->andWhere(['=','os_contracts.status' ,'judiciary']);
      }*/

        if (!empty($params['IncomeSearch']['number_row'])) {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['IncomeSearch']['number_row'],
                ],
            ]);
        }
        else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }
        if ((!empty($this->date_from))) {

            $query->andFilterWhere(['>=', 'date', $this->date_from]);
        }
        if ((!empty($this->date_to))) {

            $query->andFilterWhere(['<=', 'date', $this->date_to]);
        }


        return $dataProvider;
    }
    public function sumTotalCustomerPayments($params)
    {
        $query = Income::find()->innerJoin('{{%contracts}}', '{{%contracts}}.id = {{%income}}.contract_id');


        if ( !empty($params['IncomeSearch']['income_status']) and  $params['IncomeSearch']['income_status'] == 1) {
            $query ->andWhere(['not in','os_income.contract_id',ArrayHelper::map(Judiciary::find()->all(),'contract_id','contract_id')]) ;
        }
        if (!empty($params['IncomeSearch']['income_status']) and  $params['IncomeSearch']['income_status'] == 2) {
            $query ->andWhere(['in','os_income.contract_id',ArrayHelper::map(Judiciary::find()->all(),'contract_id','contract_id')]) ;

        }



        $this->load($params);

        // if ($this->validate()) {
        //     // uncomment the following line if you do not want to return any records when validation fails
        //     // $query->where('0=1');
        //     return $dataProvider;
        // }
        if ((!empty($params['IncomeSearch']['from_date']))) {
            $query->andFilterWhere(['>=', '{{%contracts}}.Date_of_sale', $params['IncomeSearch']['from_date']]);

        }
        if ((!empty($params['IncomeSearch']['to_date']))) {
            $query->andFilterWhere(['<=', '{{%contracts}}.Date_of_sale', $params['IncomeSearch']['to_date']]);
        }
        $query->andFilterWhere([
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'date' => $this->date,
            'amount' => $this->amount,
            '{{%income}}.created_by' => $this->created_by,
            'financial_transaction_id' => $this->financial_transaction_id,
            'document_number' => $this->document_number,
        ]);

        $query->andFilterWhere(['like', 'payment_type', $this->payment_type])
            ->andFilterWhere(['like', '_by', $this->_by])
            ->andFilterWhere(['like', 'receipt_bank', $this->receipt_bank])
            ->andFilterWhere(['like', 'payment_purpose', $this->payment_purpose])
            ->andFilterWhere(['like', 'notes', $this->notes]);
        if (!empty($params['IncomeSearch']['company_id'])) {
            $query->andFilterWhere(['{{%contracts}}.company_id' => $params['IncomeSearch']['company_id']]);
        }
        if (!empty($params['IncomeSearch']['payment_type'])) {
            $query->andFilterWhere(['=', 'payment_type', $params['IncomeSearch']['payment_type']]);
        }


        if ((empty($this->date_to) and empty($this->date_from) and empty($params['IncomeSearch']['_by']))) {
            $query->andFilterWhere(['=', 'date', '1000-01-01']);
        }

        if (!empty($params['IncomeSearch']['followed_by'])) {
            if (!empty($params['IncomeSearch']['created_by'])) {
                $query->andWhere(['=', '{{%income}}.created_by', $params['IncomeSearch']['created_by']]);
            }
            $query->where(['followed_by' => $params['IncomeSearch']['followed_by']]);
        }
        else {

            if (!empty($params['IncomeSearch']['created_by'])) {
                $query->andWhere(['=', '{{%income}}.created_by', $params['IncomeSearch']['created_by']]);
            }
        }

        if (!empty('_by') && $this->_by != null) {
            $query->where(['=', '_by', $this->_by]);
        }
        if (!empty($params['IncomeSearch']['type'])) {
            $query->andFilterWhere(['in', '{{%income}}.type', $params['IncomeSearch']['type']]);
        }
        // $query->andFilterWhere(['=', '{{%contracts}}.is_deleted', 0]);
        $this->amount_sum = $query->sum('amount');
        /*  if((!empty($this->date_to) or  !empty($this->date_from)) and  $params['IncomeSearch']['income_status'] == 1 ){
              $query->andWhere(['=','os_contracts.status' ,'active']);

          } if((!empty($this->date_to) or  !empty($this->date_from)) and  $params['IncomeSearch']['income_status'] == 2 ){
          $query->andWhere(['=','os_contracts.status' ,'judiciary']);
      }*/

        if (!empty($params['IncomeSearch']['number_row'])) {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['IncomeSearch']['number_row'],
                ],
            ]);
        }
        else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }

        if ((!empty($this->date_from))) {

            $query->andFilterWhere(['>=', 'date', $this->date_from]);
        }
        if ((!empty($this->date_to))) {

            $query->andFilterWhere(['<=', 'date', $this->date_to]);
        }
        return $query->sum('os_income.amount');
    }

    public function totalJudiciaryCustomerPayments($params)
    {

        $query = Income::find()
            ->innerJoin('{{%judiciary}}', '{{%income}}.contract_id = {{%judiciary}}.contract_id ')->
            innerJoin('{{%contracts}}', '{{%contracts}}.id = {{%income}}.contract_id')
            ->andWhere(['=', '{{%contracts}}.is_deleted', 0])
            ->andWhere(['=', '{{%judiciary}}.is_deleted', 0]);

        if (!empty($params['IncomeSearch']['followed_by'])) {
            $query = $query->where(['followed_by' => $params['IncomeSearch']['followed_by']]);
        }
        if (!empty($params['IncomeSearch']['number_row'])) {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['IncomeSearch']['number_row'],
                ],
            ]);
        }
        else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }


        $query->andFilterWhere(['like', 'payment_type', $this->payment_type])
            ->andFilterWhere(['like', '_by', $this->_by])
            ->andFilterWhere(['like', 'receipt_bank', $this->receipt_bank])
            ->andFilterWhere(['like', 'payment_purpose', $this->payment_purpose])
            ->andFilterWhere(['in', 'type', $this->type])
            ->andFilterWhere(['like', 'notes', $this->notes]);
        if ((!empty($this->date_from))) {

            $query->andFilterWhere(['>=', 'date', $this->date_from]);
        }
        if ((!empty($this->date_to))) {

            $query->andFilterWhere(['<=', 'date', $this->date_to]);
        }


        if (!empty($params['IncomeSearch']['followed_by'])) {
            if (!empty($params['IncomeSearch']['created_by'])) {
                $query->andWhere(['=', '{{%income}}.created_by', $params['IncomeSearch']['created_by']]);
            }
        }
        else {
            if (!empty($params['IncomeSearch']['created_by'])) {
                $query->andWhere(['=', '{{%income}}.created_by', $params['IncomeSearch']['created_by']]);
            }
        }
        if((!empty($this->date_to) or  !empty($this->date_from)) and  $params['IncomeSearch']['income_status'] == 1 ){
            $query->andWhere(['=','os_contracts.status' ,'active']);

        } if((!empty($this->date_to) or  !empty($this->date_from)) and  $params['IncomeSearch']['income_status'] == 2 ){
        $query->andWhere(['=','os_contracts.status' ,'judiciary']);
    }
        if((!empty($this->date_to) or  !empty($this->date_from)) and  empty($params['IncomeSearch']['income_status'] ) ){
            $query->andWhere(['in','os_contracts.status', ['active','judiciary']]);
        }

        if (!empty('_by') && $this->_by != null) {
            $query->where(['=', '_by', $this->_by]);
        }

        return $dataProvider;
    }

    public function sumTotalJudiciaryCustomerPayments($params)
    {

        $query = Income::find()
            ->innerJoin('{{%judiciary}}', '{{%income}}.contract_id = {{%judiciary}}.contract_id ')->
            innerJoin('{{%contracts}}', '{{%contracts}}.id = {{%income}}.contract_id')
            ->andWhere(['=', '{{%contracts}}.is_deleted', 0])
            ->andWhere(['=', '{{%judiciary}}.is_deleted', 0]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }


        $query->andFilterWhere(['like', 'payment_type', $this->payment_type])
            ->andFilterWhere(['like', '_by', $this->_by])
            ->andFilterWhere(['like', 'receipt_bank', $this->receipt_bank])
            ->andFilterWhere(['like', 'payment_purpose', $this->payment_purpose])
            ->andFilterWhere(['like', 'notes', $this->notes]);
        if ((!empty($this->date_from))) {

            $query->andFilterWhere(['>=', 'date', $this->date_from]);
        }
        if ((!empty($this->date_to))) {

            $query->andFilterWhere(['<=', 'date', $this->date_to]);
        }

        if (!empty($params['IncomeSearch']['followed_by'])) {
            if (!empty($params['IncomeSearch']['created_by'])) {
                $query->andWhere(['=', '{{%income}}.created_by', $params['IncomeSearch']['created_by']]);
            }
        }
        else {

            if (!empty($params['IncomeSearch']['created_by'])) {
                $query->andWhere(['=', '{{%income}}.created_by', $params['IncomeSearch']['created_by']]);
            }
        }
        $query->orFilterWhere(['{{%income}}.type' => 9]);
        if (!empty('_by') && $this->_by != null) {
            $query->where(['=', '_by', $this->_by]);
        }


        return $query->sum('amount');
    }


}
