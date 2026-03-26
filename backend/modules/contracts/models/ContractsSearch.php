<?php

namespace backend\modules\contracts\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\contracts\models\Contracts;

/**
 * contractsSearch represents the model behind the search form about `app\models\contracts`.
 */
class ContractsSearch extends Contracts
{
    public $q;
    public $customer_name;
    public $seller_name;
    public $to_date;
    public $from_date;
    public $job_title;
    public $phone_number;
    public $number_row;
    public $job_Type;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'seller_id', 'is_deleted', 'number_row', 'job_Type'], 'integer'],
            [['Date_of_sale', 'first_installment_date', 'monthly_installment_value', 'notes', 'updated_at', 'customer_name', 'seller_name', 'from_date', 'job_Type', 'to_date', 'job_title', 'q'], 'safe'],
            [['total_value', 'first_installment_value'], 'number'],
            [['from_date', 'to_date', 'job_title'], 'string']
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
     * Normalize Arabic text for flexible matching (ة↔ه, أإآ→ا)
     */
    private static function arabicNormalize(string $text): string
    {
        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
        $text = str_replace('ة', 'ه', $text);
        $text = str_replace('ى', 'ي', $text);
        return $text;
    }

    /**
     * Apply unified search (q) to query — splits words for flexible name matching
     * Uses SQL REPLACE for Arabic normalization (ة↔ه, أإآ→ا, ى→ي)
     */
    private static $cwIdx = 0;

    private function applyUnifiedSearch($query)
    {
        if (empty($this->q)) return;
        $q = trim($this->q);

        $normExpr = "REPLACE(REPLACE(REPLACE(REPLACE(c.name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'ى', 'ي')";
        $normNoSpace = "REPLACE($normExpr, ' ', '')";
        $words = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $w) {
            $wNorm = self::arabicNormalize($w);
            $idx = self::$cwIdx++;

            if (is_numeric($w)) {
                $len = strlen($w);
                if ($len <= 4) {
                    $query->andWhere(['or',
                        ['=', 'os_contracts.id', (int)$w],
                        ['=', 'c.id', (int)$w],
                    ]);
                } else {
                    $query->andWhere(['or',
                        ['like', 'c.id_number', $w . '%', false],
                        ['like', 'c.primary_phone_number', $w . '%', false],
                    ]);
                }
            } else {
                $p1 = ':nw' . $idx . 'a';
                $p2 = ':nw' . $idx . 'b';
                $likeVal = '%' . $wNorm . '%';
                $nameExpr = new \yii\db\Expression(
                    "($normExpr LIKE $p1 OR $normNoSpace LIKE $p2)",
                    [$p1 => $likeVal, $p2 => $likeVal]
                );
                $query->andWhere($nameExpr);
            }
        }
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
        $query = contracts::find()->joinWith(['customersWithoutCondition as c'])->joinWith(['seller as s'])->joinWith('customersWithoutCondition as cc');
        if (!empty($params['ContractsSearch']['job_Type']) || !empty($params['ContractsSearch']['job_title'])) {

            $query->innerJoin("`os_jobs`  ", ' os_jobs.id = c.job_title');
            $query->innerJoin("`os_jobs_type` ", ' os_jobs_type.id = os_jobs.job_type');
        }
        if (!empty($params['ContractsSearch']['job_Type'])) {
            $query->where(['=', 'job_type', $params['ContractsSearch']['job_Type']]);
        }
        if (!empty($params['ContractsSearch']['job_title'])) {
            $query->where(['=', 'c.job_title', $params['ContractsSearch']['job_title']]);

        }
        if (!empty($params['c']['number_row'])) {

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['ContractsSearch']['number_row'],
                ],
            ]);
        } else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }
        $dataProvider->sort->attributes['seller_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['s.name' => SORT_ASC],
            'desc' => ['s.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['customer_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['c.name' => SORT_ASC],
            'desc' => ['c.name' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $this->applyUnifiedSearch($query);

        $query->andFilterWhere([
            'Date_of_sale' => $this->Date_of_sale,
            'total_value' => $this->total_value,
            'first_installment_value' => $this->first_installment_value,
            'first_installment_date' => $this->first_installment_date,
            'monthly_installment_value' => $this->monthly_installment_value,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
        ]);

        $query->andFilterWhere(['os_contracts.id' => $this->id]);
        $query->andFilterWhere(['like', 'c.name', $this->customer_name]);
        $query->andFilterWhere(['os_contracts.seller_id' => $this->seller_id]);
        $query->andFilterWhere(['like', 'notes', $this->notes]);
        if (!empty($params['ContractsSearch']['followed_by'])) {
            $query->andFilterWhere(['followed_by' => $params['ContractsSearch']['followed_by']]);
        }
        if (!empty($params['ContractsSearch']['status'])) {
            $statusVal = $params['ContractsSearch']['status'];
            if ($statusVal === 'judiciary_active') {
                $query->andWhere(['os_contracts.status' => 'judiciary']);
                $this->applyJudiciaryBalanceFilter($query, 'positive');
            } elseif ($statusVal === 'judiciary_paid') {
                $query->andWhere(['os_contracts.status' => 'judiciary']);
                $this->applyJudiciaryBalanceFilter($query, 'zero');
            } else {
                $query->andFilterWhere(['os_contracts.status' => $statusVal]);
            }
        } else {
            $query->andWhere(['<>', 'os_contracts.status', 'canceled']);
        }
        if ((!empty($this->from_date))) {
            $query->andFilterWhere(['>=', 'Date_of_sale', $this->from_date]);
        }
        if ((!empty($this->to_date))) {
            $query->andFilterWhere(['<=', 'Date_of_sale', $this->to_date]);
        }

        if (!empty($params['ContractsSearch']['phone_number'])) {
            $query->andFilterWhere(['like', 'c.primary_phone_number', $params['ContractsSearch']['phone_number']]);
        }
        $query->orderBy(['id' => SORT_DESC]);
        return $dataProvider;
    }

    /**
     * تصفية القضائي: positive = عليه رصيد, zero = مسدد بالكامل
     */
    private function applyJudiciaryBalanceFilter($query, string $mode): void
    {
        $paidIds = $this->getJudiciaryPaidIds();

        if ($mode === 'zero') {
            if (!empty($paidIds)) {
                $query->andWhere(['IN', 'os_contracts.id', $paidIds]);
            } else {
                $query->andWhere('1=0');
            }
        } else {
            if (!empty($paidIds)) {
                $query->andWhere(['NOT IN', 'os_contracts.id', $paidIds]);
            }
        }
    }

    private $_judiciaryPaidIds;

    private function getJudiciaryPaidIds(): array
    {
        if ($this->_judiciaryPaidIds !== null) {
            return $this->_judiciaryPaidIds;
        }

        $p = \Yii::$app->db->tablePrefix;
        $rows = \Yii::$app->db->createCommand(
            "SELECT contract_id FROM {$p}vw_contract_balance WHERE status = 'judiciary' AND remaining_balance <= 0.01"
        )->queryAll();

        $this->_judiciaryPaidIds = array_column($rows, 'contract_id');
        return $this->_judiciaryPaidIds;
    }

    public function searchcounter($params)
    {
        $query = contracts::find()->joinWith(['customersWithoutCondition as c'])->joinWith(['seller as s'])->joinWith('customersWithoutCondition as cc');
        if (!empty($params['ContractsSearch']['job_Type']) || !empty($params['ContractsSearch']['job_title'])) {

            $query->innerJoin("`os_jobs`  ", ' os_jobs.id = c.job_title');
            $query->innerJoin("`os_jobs_type` ", ' os_jobs_type.id = os_jobs.job_type');
        }
        if (!empty($params['ContractsSearch']['job_Type'])) {
            $query->where(['=', 'job_type', $params['ContractsSearch']['job_Type']]);
        }
        if (!empty($params['ContractsSearch']['job_title'])) {
            $query->where(['=', 'c.job_title', $params['ContractsSearch']['job_title']]);

        }
        if (!empty($params['ContractsSearch']['number_row'])) {

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['ContractsSearch']['number_row'],
                ],
            ]);
        } else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }
        $dataProvider->sort->attributes['seller_name'] = [
            'asc' => ['s.name' => SORT_ASC],
            'desc' => ['s.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['customer_name'] = [
            'asc' => ['c.name' => SORT_ASC],
            'desc' => ['c.name' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $this->applyUnifiedSearch($query);

        $query->andFilterWhere([
            'Date_of_sale' => $this->Date_of_sale,
            'total_value' => $this->total_value,
            'first_installment_value' => $this->first_installment_value,
            'first_installment_date' => $this->first_installment_date,
            'monthly_installment_value' => $this->monthly_installment_value,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
        ]);

        $query->andFilterWhere(['os_contracts.id' => $this->id]);
        $query->andFilterWhere(['like', 'c.name', $this->customer_name]);
        $query->andFilterWhere(['os_contracts.seller_id' => $this->seller_id]);
        $query->andFilterWhere(['like', 'notes', $this->notes]);
        if (!empty($params['ContractsSearch']['followed_by'])) {
            $query->andFilterWhere(['followed_by' => $params['ContractsSearch']['followed_by']]);
        }
        if (!empty($params['ContractsSearch']['status'])) {
            $statusVal = $params['ContractsSearch']['status'];
            if ($statusVal === 'judiciary_active') {
                $query->andWhere(['os_contracts.status' => 'judiciary']);
                $this->applyJudiciaryBalanceFilter($query, 'positive');
            } elseif ($statusVal === 'judiciary_paid') {
                $query->andWhere(['os_contracts.status' => 'judiciary']);
                $this->applyJudiciaryBalanceFilter($query, 'zero');
            } else {
                $query->andFilterWhere(['os_contracts.status' => $statusVal]);
            }
        } else {
            $query->andWhere(['<>', 'os_contracts.status', 'canceled']);
        }
        if ((!empty($this->from_date))) {
            $query->andFilterWhere(['>=', 'Date_of_sale', $this->from_date]);
        }
        if ((!empty($this->to_date))) {
            $query->andFilterWhere(['<=', 'Date_of_sale', $this->to_date]);
        }

        if (!empty($params['ContractsSearch']['phone_number'])) {
            $query->andFilterWhere(['like', 'c.primary_phone_number', $params['ContractsSearch']['phone_number']]);
        }
        $query->orderBy(['id' => SORT_DESC]);
        return $query->distinct()->count();
    }

    public function searchLegalDepartment($params)
    {
        $query = contracts::find()->innerJoinWith(['customersWithoutCondition as c'])->innerJoinWith(['seller as s'])->innerJoinWith('customersWithoutCondition as cc');

        $query->leftJoin('os_jobs j', 'c.job_title = j.id');
        $query->leftJoin('os_jobs_type jt', 'j.job_type = jt.id');

        $paidSubquery = '(SELECT COALESCE(SUM(amount),0) FROM os_contract_installment WHERE contract_id = os_contracts.id)';

        if (!empty($params['ContractsSearch']['number_row'])) {

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['ContractsSearch']['number_row'],
                ],
            ]);
        } else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }
        $dataProvider->sort->defaultOrder = ['id' => SORT_DESC];

        $dataProvider->sort->attributes['id'] = [
            'asc' => ['os_contracts.id' => SORT_ASC],
            'desc' => ['os_contracts.id' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['total_value'] = [
            'asc' => ['os_contracts.total_value' => SORT_ASC],
            'desc' => ['os_contracts.total_value' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['seller_name'] = [
            'asc' => ['s.name' => SORT_ASC],
            'desc' => ['s.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['customer_name'] = [
            'asc' => ['c.name' => SORT_ASC],
            'desc' => ['c.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['job_name'] = [
            'asc' => ['j.name' => SORT_ASC],
            'desc' => ['j.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['job_type_name'] = [
            'asc' => ['jt.name' => SORT_ASC],
            'desc' => ['jt.name' => SORT_DESC],
        ];
        $remainingExpr = "(os_contracts.total_value - $paidSubquery)";
        $dataProvider->sort->attributes['remaining'] = [
            'asc'  => [$remainingExpr => SORT_ASC],
            'desc' => [$remainingExpr => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        if (!empty($params['ContractsSearch']['phone_number'])) {
            $query->andFilterWhere(['c.primary_phone_number' => $params['ContractsSearch']['phone_number']]);
        }
        $query->andFilterWhere([
            'Date_of_sale' => $this->Date_of_sale,
            'total_value' => $this->total_value,
            'first_installment_value' => $this->first_installment_value,
            'first_installment_date' => $this->first_installment_date,
            'monthly_installment_value' => $this->monthly_installment_value,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
        ]);

        $query->andFilterWhere(['os_contracts.id' => $this->id]);
        $query->andFilterWhere(['like', 'c.name', $this->customer_name]);
        $query->andFilterWhere(['os_contracts.seller_id' => $this->seller_id]);
        $query->andFilterWhere(['like', 'notes', $this->notes]);
        if (!empty($params['ContractsSearch']['followed_by'])) {
            $query->andFilterWhere(['followed_by' => $params['ContractsSearch']['followed_by']]);
        }
        if (!empty($params['ContractsSearch']['from_date'])) {
            $query->andFilterWhere(['>=', 'os_contracts.Date_of_sale', $params['ContractsSearch']['from_date']]);
        }
        if (!empty($params['ContractsSearch']['to_date'])) {
            $query->andFilterWhere(['<=', 'os_contracts.Date_of_sale', $params['ContractsSearch']['to_date']]);
        }
        if (!empty($params['ContractsSearch']['type'])) {
            $query->andFilterWhere(['=', 'os_contracts.type', $params['ContractsSearch']['type']]);
        }
        if (!empty($params['ContractsSearch']['job_title'])) {
            $query->andFilterWhere(['cc.job_title' => $params['ContractsSearch']['job_title']]);
        }
        if (!empty($params['ContractsSearch']['job_Type'])) {
            $query->andFilterWhere(['j.job_type' => $params['ContractsSearch']['job_Type']]);
        }
        $query->andWhere(['os_contracts.status' => Contracts::STATUS_LEGAL_DEPARTMENT]);

        return $dataProvider;
    }

    public function searchLegalDepartmentCount($params)
    {
        $query = contracts::find()->innerJoinWith(['customersWithoutCondition as c'])->joinWith(['seller as s'])->joinWith('customersWithoutCondition as cc');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $dataProvider->sort->attributes['seller_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['s.name' => SORT_ASC],
            'desc' => ['s.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['customer_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['c.name' => SORT_ASC],
            'desc' => ['c.name' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'Date_of_sale' => $this->Date_of_sale,
            'total_value' => $this->total_value,
            'first_installment_value' => $this->first_installment_value,
            'first_installment_date' => $this->first_installment_date,
            'monthly_installment_value' => $this->monthly_installment_value,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
        ]);
        if (!empty($params['ContractsSearch']['phone_number'])) {
            $query->andFilterWhere(['c.primary_phone_number' => $params['ContractsSearch']['phone_number']]);
        }

        $query->andFilterWhere(['os_contracts.id' => $this->id]);
        $query->andFilterWhere(['like', 'c.name', $this->customer_name]);
        $query->andFilterWhere(['os_contracts.seller_id' => $this->seller_id]);
        $query->andFilterWhere(['like', 'notes', $this->notes]);
        if (!empty($params['ContractsSearch']['followed_by'])) {
            $query->andFilterWhere(['followed_by' => $params['ContractsSearch']['followed_by']]);
        }
        if (!empty($params['ContractsSearch']['from_date'])) {
            $query->andFilterWhere(['>=', 'os_contracts.Date_of_sale', $params['ContractsSearch']['from_date']]);
        }
        if (!empty($params['ContractsSearch']['to_date'])) {
            $query->andFilterWhere(['<=', 'os_contracts.Date_of_sale', $params['ContractsSearch']['to_date']]);
        }
        if (!empty($params['ContractsSearch']['type'])) {
            $query->andFilterWhere(['=', 'os_contracts.type', $params['ContractsSearch']['type']]);
        }
        if (!empty($params['ContractsSearch']['job_title'])) {

            $query->andFilterWhere(['cc.job_title' => $params['ContractsSearch']['job_title']]);

        }
        $query->andWhere(['os_contracts.status' => Contracts::STATUS_LEGAL_DEPARTMENT]);

        $query->orderBy(['id' => SORT_DESC]);

        return $query->count();
    }
}
