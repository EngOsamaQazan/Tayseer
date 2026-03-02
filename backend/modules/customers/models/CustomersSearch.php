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
                $paidSql = "(SELECT ct.id FROM {{%contracts}} ct
                    WHERE ct.status = 'judiciary'
                      AND (ct.is_deleted = 0 OR ct.is_deleted IS NULL)
                      AND (ct.total_value - COALESCE((SELECT SUM(i.payment_value) FROM {{%income}} i WHERE i.contract_id = ct.id AND (i.is_deleted = 0 OR i.is_deleted IS NULL)), 0)
                                           - COALESCE((SELECT SUM(a.amount) FROM {{%contract_adjustments}} a WHERE a.contract_id = ct.id), 0)) <= 0)";
                if ($ct === 'judiciary_paid') {
                    $query->andWhere("os_contracts.id IN $paidSql");
                } else {
                    $query->andWhere("os_contracts.id NOT IN $paidSql");
                }
            } else {
                $query = Customers::find()->innerJoin(
                    "(`os_contracts_customers` INNER JOIN os_contracts ON contract_id = os_contracts.id AND os_contracts.status = :ctStatus) ON customer_id = os_customers.id",
                    [':ctStatus' => $ct]
                );
            }
        }
        if (!empty($params['CustomersSearch']['job_Type'])) {

            $query->innerJoin("`os_jobs` ",' os_jobs.id = job_title') ;
            $query->innerJoin("`os_jobs_type` ",' os_jobs_type.id = os_jobs.job_type' );
            $query->where(['=','job_type',$params['CustomersSearch']['job_Type']]);

        }
        if(!empty($params['CustomersSearch']['number_row'])){

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
                'pagination' => [
                    'pageSize' => $params['CustomersSearch']['number_row'],
                ],
            ]);
        }else{
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
                $paidSql = "(SELECT ct.id FROM {{%contracts}} ct
                    WHERE ct.status = 'judiciary'
                      AND (ct.is_deleted = 0 OR ct.is_deleted IS NULL)
                      AND (ct.total_value - COALESCE((SELECT SUM(i.payment_value) FROM {{%income}} i WHERE i.contract_id = ct.id AND (i.is_deleted = 0 OR i.is_deleted IS NULL)), 0)
                                           - COALESCE((SELECT SUM(a.amount) FROM {{%contract_adjustments}} a WHERE a.contract_id = ct.id), 0)) <= 0)";
                if ($ct === 'judiciary_paid') {
                    $query->andWhere("os_contracts.id IN $paidSql");
                } else {
                    $query->andWhere("os_contracts.id NOT IN $paidSql");
                }
            } else {
                $query = Customers::find()->innerJoin(
                    "(`os_contracts_customers` INNER JOIN os_contracts ON contract_id = os_contracts.id AND os_contracts.status = :ctStatus) ON customer_id = os_customers.id",
                    [':ctStatus' => $ct]
                );
            }
        }
        if (!empty($params['CustomersSearch']['job_Type'])) {

            $query->innerJoin("`os_jobs` ",' os_jobs.id = job_title') ;
            $query->innerJoin("`os_jobs_type` ",' os_jobs_type.id = os_jobs.job_type' );
            $query->where(['=','job_type',$params['CustomersSearch']['job_Type']]);

        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
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
            $p = ':cw' . (self::$cwIdx++);
            $nameExpr = new \yii\db\Expression(
                "($nameNorm LIKE $p OR $nameNormNoSpace LIKE $p)",
                [$p => '%' . $wNorm . '%']
            );
            $or = ['or', $nameExpr,
                ['like', 'os_customers.id_number', $w],
                ['like', 'os_customers.primary_phone_number', $w],
            ];
            if (is_numeric($w)) {
                $or[] = ['=', 'os_customers.id', (int)$w];
            }
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
