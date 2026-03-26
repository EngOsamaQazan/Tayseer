<?php

namespace backend\modules\customers\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use  backend\modules\customers\models\Customers;
use yii\data\SqlDataProvider;
/**
 * CustomersSearch represents the model behind the search form about `app\models\Customers`.
 */
class CustomersSearch extends Customers
{

    /**
     * @inheritdoc
     */
    public $contract_type;
    public $number_row;
    public $job_type;
    public $q;

    public function rules()
    {
        return [
            [['id', 'job_title','number_row','job_type'], 'integer'],
            [['name', 'status', 'city', 'job_title', 'id_number', 'primary_phone_number', 'contract_type', 'q'], 'safe'],
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

        $this->load($params);
        $query = Customers::find();
        if (!empty($params['CustomersSearch']['contract_type'])) {
            $ct = $params['CustomersSearch']['contract_type'];
            if ($ct === 'judiciary') {
                $query = Customers::find()->innerJoin(
                    "(`os_contracts_customers` INNER JOIN os_contracts ON contract_id = os_contracts.id AND os_contracts.status = 'judiciary') ON customer_id = os_customers.id"
                );
            } elseif ($ct === 'judiciary_active' || $ct === 'judiciary_paid') {
                $query = Customers::find()->innerJoin(
                    "(`os_contracts_customers` INNER JOIN os_contracts ON contract_id = os_contracts.id AND os_contracts.status = 'judiciary') ON customer_id = os_customers.id"
                );
                $query->innerJoin('{{%vw_contract_balance}} vcb', 'vcb.contract_id = os_contracts.id');
                if ($ct === 'judiciary_paid') {
                    $query->andWhere(['<=', 'vcb.remaining_balance', 0]);
                } else {
                    $query->andWhere(['>', 'vcb.remaining_balance', 0]);
                }
            } else {
                $query = Customers::find()->innerJoin(
                    "(`os_contracts_customers` INNER JOIN os_contracts ON contract_id = os_contracts.id AND os_contracts.status = :ctStatus) ON customer_id = os_customers.id",
                    [':ctStatus' => $ct]
                );
            }
        }
        if (!empty($params['CustomersSearch']['job_Type'])) {
            $query->innerJoin('{{%vw_customers_directory}} vcd', 'vcd.id = os_customers.id');
            $query->andWhere(['vcd.job_type_id' => $params['CustomersSearch']['job_Type']]);
        }
        if (!empty($params['CustomersSearch']['number_row'])) {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
                'pagination' => [
                    'pageSize' => $params['CustomersSearch']['number_row'],
                ],
            ]);
        } else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
            ]);
        }





        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }



        $query->andFilterWhere(['=', 'os_customers.status', $this->status])
            ->andFilterWhere(['=', 'city', $this->city])
            ->andFilterWhere(['=', 'os_customers.id', $this->id])
            ->andFilterWhere(['=', 'job_title', $this->job_title])
            ->andFilterWhere(['like', 'id_number', $this->id_number])
            ->andFilterWhere(['like', 'primary_phone_number', $this->primary_phone_number])->andWhere(['os_customers.is_deleted' => false]);
        $query->andFilterWhere(['=', 'name', $this->name]);

        if (!empty($this->q)) {
            $this->applyUnifiedSearch($query);
        }

        return $dataProvider;
    }

    public function searchCounter($params)
    {
        $query = Customers::find();
        if (!empty($params['CustomersSearch']['contract_type'])) {
            $ct = $params['CustomersSearch']['contract_type'];
            if ($ct === 'judiciary') {
                $query = Customers::find()->innerJoin(
                    "(`os_contracts_customers` INNER JOIN os_contracts ON contract_id = os_contracts.id AND os_contracts.status = 'judiciary') ON customer_id = os_customers.id"
                );
            } elseif ($ct === 'judiciary_active' || $ct === 'judiciary_paid') {
                $query = Customers::find()->innerJoin(
                    "(`os_contracts_customers` INNER JOIN os_contracts ON contract_id = os_contracts.id AND os_contracts.status = 'judiciary') ON customer_id = os_customers.id"
                );
                $query->innerJoin('{{%vw_contract_balance}} vcb', 'vcb.contract_id = os_contracts.id');
                if ($ct === 'judiciary_paid') {
                    $query->andWhere(['<=', 'vcb.remaining_balance', 0]);
                } else {
                    $query->andWhere(['>', 'vcb.remaining_balance', 0]);
                }
            } else {
                $query = Customers::find()->innerJoin(
                    "(`os_contracts_customers` INNER JOIN os_contracts ON contract_id = os_contracts.id AND os_contracts.status = :ctStatus) ON customer_id = os_customers.id",
                    [':ctStatus' => $ct]
                );
            }
        }
        if (!empty($params['CustomersSearch']['job_Type'])) {
            $query->innerJoin('{{%vw_customers_directory}} vcd', 'vcd.id = os_customers.id');
            $query->andWhere(['vcd.job_type_id' => $params['CustomersSearch']['job_Type']]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }
        $query->andFilterWhere(['=', 'os_customers.status', $this->status]);

        $query->andFilterWhere(['=', 'city', $this->city])
            ->andFilterWhere(['=', 'job_title', $this->job_title])
            ->andFilterWhere(['=', 'os_customers.id', $this->id])
            ->andFilterWhere(['like', 'id_number', $this->id_number])
            ->andFilterWhere(['like', 'primary_phone_number', $this->primary_phone_number])->andWhere(['os_customers.is_deleted' => false]);
        $query->andFilterWhere(['=', 'name', $this->name]);

        if (!empty($this->q)) {
            $this->applyUnifiedSearch($query);
        }

        return $query->count();
    }

    private static $cwIdx = 0;

    private function applyUnifiedSearch($query)
    {
        $words = preg_split('/\s+/u', trim($this->q), -1, PREG_SPLIT_NO_EMPTY);
        $nameNorm = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(os_customers.name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ى', 'ي')";
        $nameNormNoSpace = "REPLACE($nameNorm, ' ', '')";
        $hasJobJoin = false;
        foreach ($words as $w) {
            $wNorm = str_replace(['أ', 'إ', 'آ'], 'ا', $w);
            $wNorm = str_replace('ة', 'ه', $wNorm);
            $wNorm = str_replace('ى', 'ي', $wNorm);
            $idx = self::$cwIdx++;

            if (is_numeric($w)) {
                $len = strlen($w);
                if ($len <= 4) {
                    $query->andWhere(['=', 'os_customers.id', (int)$w]);
                } else {
                    $query->andWhere(['or',
                        ['like', 'os_customers.id_number', $w . '%', false],
                        ['like', 'os_customers.primary_phone_number', $w . '%', false],
                    ]);
                }
            } else {
                $p1 = ':cw' . $idx . 'a';
                $p2 = ':cw' . $idx . 'b';
                $likeVal = '%' . $wNorm . '%';
                $nameExpr = new \yii\db\Expression(
                    "($nameNorm LIKE $p1 OR $nameNormNoSpace LIKE $p2)",
                    [$p1 => $likeVal, $p2 => $likeVal]
                );
                $or = ['or', $nameExpr];
                if (!$hasJobJoin) {
                    $query->leftJoin('{{%jobs}} qj', 'qj.id = os_customers.job_title');
                    $hasJobJoin = true;
                }
                $jobNorm = "REPLACE(REPLACE(REPLACE(REPLACE(qj.name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'ى', 'ي')";
                $jp = ':jw' . (self::$cwIdx);
                $or[] = new \yii\db\Expression("$jobNorm LIKE $jp", [$jp => '%' . $wNorm . '%']);
                $query->andWhere($or);
            }
        }
    }
}
