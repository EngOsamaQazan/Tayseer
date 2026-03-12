<?php

namespace backend\modules\financialTransaction\models;

use backend\modules\expenses\models\Expenses;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

use  backend\modules\financialTransaction\models\FinancialTransaction;
/**
 * ExpensesSearch represents the model behind the search form about `common\models\Expenses`.
 */
class FinancialTransactionSearch extends FinancialTransaction
{

    /**
     * @inheritdoc
     */
    public $Restriction;
    public $created;
    public $updated;
    public $number_row;

    public function rules()
    {
        return [
            [['id', 'category_id', 'contract_id', 'type', 'created_at', 'created_by', 'updated_at', 'last_updated_by', 'is_deleted', 'receiver_number', 'document_number', 'company_id', 'is_transfer','bank_id','number_row'], 'integer'],
            [['description', 'company_id', 'date', 'Restriction', 'updated', 'created','bank_id'], 'safe'],
            [['amount'], 'number'],
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
    public function search($params, $documentNumber = null)
    {
        $query = FinancialTransaction::find();

        /* إصلاح: إزالة totalCount الثابت - Yii2 يحسبه تلقائياً من الاستعلام المفلتر */
        if(!empty($params['FinancialTransactionSearch']['number_row'])){
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['FinancialTransactionSearch']['number_row'],
                ],
            ]);
        }else{
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

        $Restriction = $this->Restriction;

        if ($Restriction == self::RESTRICTED && $this->type == self::TYPE_INCOME) {
            $query->andFilterWhere(['type' => self::TYPE_INCOME])
                ->andFilterWhere(['income_type' => self::TYPE_INCOME_MONTHLY])->
                Where([
                    'not', ['contract_id' => null]
                ]);
        } elseif ($Restriction == self::RESTRICTED && $this->type == self::TYPE_OUTCOME) {
            $query->andFilterWhere(['type' => self::TYPE_OUTCOME])
                ->Where([
                    'not', ['category_id' => null]
                ]);
        }
        if ($Restriction == self::UNBOUND && $this->type == self::TYPE_INCOME) {
            $query->andFilterWhere(['type' => self::TYPE_INCOME])
                ->Where(['or', ['income_type' => null], ['contract_id' => null]]);

        } elseif ($Restriction == self::UNBOUND && $this->type == self::TYPE_OUTCOME) {
            $query->andFilterWhere(['type' => self::TYPE_OUTCOME])
                ->Where(['category_id' => null]);
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'created_at' => $this->created_at,
            'company_id' => $this->company_id,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'last_updated_by' => $this->last_updated_by,
            'is_deleted' => $this->is_deleted,
            'amount' => $this->amount,
            'receiver_number' => $this->receiver_number,
            'document_number' => $this->document_number,
            'type' => $this->type,
            'date' => $this->date,
        ]);
        if ($documentNumber != null) {
            $query->andFilterWhere(['document_number' => $documentNumber]);
        }
        $query->andFilterWhere(['like', 'description', $this->description])->where(['is_deleted' => false])->where(['is_transfer' => 0]);
        return $dataProvider;
    }

    public function searchDocument($params)
    {

        $query = FinancialTransaction::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $Restriction = $this->Restriction;

        if ($Restriction == self::RESTRICTED && $this->type == self::TYPE_INCOME) {
            $query->andFilterWhere(['type' => self::TYPE_INCOME])
                ->andFilterWhere(['income_type' => self::TYPE_INCOME_MONTHLY])->
                Where([
                    'not', ['contract_id' => null]
                ]);
        } elseif ($Restriction == self::RESTRICTED && $this->type == self::TYPE_OUTCOME) {
            $query->andFilterWhere(['type' => self::TYPE_OUTCOME])
                ->Where([
                    'not', ['category_id' => null]
                ]);
        }
        if ($Restriction == self::UNBOUND && $this->type == self::TYPE_INCOME) {
            $query->andFilterWhere(['type' => FinancialTransaction::TYPE_INCOME])
                ->Where(['or', ['income_type' => null], ['contract_id' => null]]);

        } elseif ($Restriction == self::UNBOUND && $this->type == self::TYPE_OUTCOME) {
            $query->andFilterWhere(['type' => FinancialTransaction::TYPE_OUTCOME])
                ->Where(['category_id' => null]);
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'created_at' => $this->created_at,
            'company_id' => $this->company_id,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'last_updated_by' => $this->last_updated_by,
            'is_deleted' => $this->is_deleted,
            'amount' => $this->amount,
            'receiver_number' => $this->receiver_number,
            'type' => $this->type,
            'document_number' => $params['document_number'],
            'date' => $this->date,
            'bank_description' => $this->bank_description,
            'bank_description' => $this->bank_description,

        ]);

        $query->andFilterWhere(['like', 'description', $this->description])->where(['is_deleted' => false]);
        return $dataProvider;
    }

    public static function CountDataTransfer()
    {

        return FinancialTransaction::find()->Where(['type' => self::TYPE_INCOME])->andWhere(['income_type' => 8])->andWhere([
            'not', ['contract_id' => null]
        ])->orWhere(['not', ['income_type' => 8]])->andWhere(['is_transfer' => 0])->count();
    }

    public static function DataTransfer()
    {
        return FinancialTransaction::find()->Where(['type' => self::TYPE_INCOME])->andWhere(['income_type' => self::TYPE_INCOME_MONTHLY])->andWhere([
            'not', ['contract_id' => null]
        ])->andWhere(['is_transfer' => 0])->all();
    }

    public static function CountDataTransferExpenses()
    {
        return self::find()->Where(['type' => self::TYPE_OUTCOME])
            ->andWhere([
                'not', ['category_id' => null]
            ])->andWhere(['is_transfer' => 0])->count();
    }

    public static function DataTransferExpenses()
    {
        return self::find()->Where(['type' => self::TYPE_OUTCOME])
            ->andWhere([
                'not', ['category_id' => null]
            ])->andWhere(['is_transfer' => 0])->all();
    }
}
