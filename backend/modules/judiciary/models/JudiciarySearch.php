<?php

namespace backend\modules\judiciary\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\judiciary\models\Judiciary;
use yii\data\SqlDataProvider;
use yii\db\Query;

/**
 * JudiciarySearch represents the model behind the search form about `backend\modules\judiciary\models\Judiciary`.
 */
class JudiciarySearch extends Judiciary
{

    /**
     * @inheritdoc
     */
    public $contract_id;
    public $number_row;
    public $jobs_type;
    public $job_title;
    public $status;
    public $judiciary_actions;
    public $company_id;

    public $contract_not_in_status;
    public $party_name;
    public $last_party_action;

    public function rules()
    {
        return [
            [['id', 'court_id', 'type_id', 'lawyer_id', 'created_at', 'updated_at', 'created_by', 'last_update_by', 'is_deleted', 'number_row', 'case_cost'], 'integer'],
            [['lawyer_cost', 'contract_id'], 'number'],
            [['income_date', 'year', 'from_income_date', 'to_income_date'], 'string'],
            [['from_income_date', 'to_income_date', 'contract_not_in_status', 'company_id', 'judiciary_number', 'party_name', 'last_party_action', 'job_title', 'jobs_type'], 'safe']
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
        // ─── استعلام أساسي خفيف بدون JOINs غير ضرورية ───
        $query = Judiciary::find()
            ->alias('j')
            ->with(['court', 'lawyer', 'type', 'customersAndGuarantor']); // Eager load للعرض

        // ─── شرط أساسي: غير المحذوفة ───
        $query->andWhere(['j.is_deleted' => false]);

        // ─── فلاتر مباشرة على أعمدة os_judiciary (بدون JOIN) ───
        if (!empty($params['JudiciarySearch']['court_id'])) {
            $query->andWhere(['j.court_id' => $params['JudiciarySearch']['court_id']]);
        }

        if (!empty($params['JudiciarySearch']['contract_id'])) {
            $query->andWhere(['j.contract_id' => $params['JudiciarySearch']['contract_id']]);
        }

        if (!empty($params['JudiciarySearch']['lawyer_cost'])) {
            $query->andWhere(['j.lawyer_cost' => $params['JudiciarySearch']['lawyer_cost']]);
        }

        if (!empty($params['JudiciarySearch']['case_cost'])) {
            $query->andWhere(['j.case_cost' => $params['JudiciarySearch']['case_cost']]);
        }

        if (!empty($params['JudiciarySearch']['judiciary_number'])) {
            $val = trim($params['JudiciarySearch']['judiciary_number']);
            if (ctype_digit($val)) {
                $query->andWhere(['j.judiciary_number' => (int)$val]);
            } else {
                $query->andWhere(['like', 'CAST(j.judiciary_number AS CHAR)', $val, false]);
            }
        }

        if (!empty($params['JudiciarySearch']['year'])) {
            $query->andWhere(['j.year' => $params['JudiciarySearch']['year']]);
        }

        if (!empty($params['JudiciarySearch']['type_id'])) {
            $query->andWhere(['j.type_id' => $params['JudiciarySearch']['type_id']]);
        }

        if (!empty($params['JudiciarySearch']['lawyer_id'])) {
            $query->andWhere(['j.lawyer_id' => $params['JudiciarySearch']['lawyer_id']]);
        }

        if (!empty($params['JudiciarySearch']['from_income_date'])) {
            $query->andWhere(['>=', 'j.income_date', $params['JudiciarySearch']['from_income_date']]);
        }
        if (!empty($params['JudiciarySearch']['to_income_date'])) {
            $query->andWhere(['<=', 'j.income_date', $params['JudiciarySearch']['to_income_date']]);
        }

        // ─── JOIN مشترك للعميل (party_name / job_title / jobs_type) ───
        $needsCustomerJoin = !empty($params['JudiciarySearch']['party_name'])
            || !empty($params['JudiciarySearch']['job_title'])
            || !empty($params['JudiciarySearch']['jobs_type']);

        if ($needsCustomerJoin) {
            $query->innerJoin('{{%contracts_customers}} cc', 'cc.contract_id = j.contract_id');
            $query->innerJoin('{{%customers}} cust', 'cust.id = cc.customer_id');
            $query->distinct();

            if (!empty($params['JudiciarySearch']['party_name'])) {
                $words = preg_split('/\s+/', trim($params['JudiciarySearch']['party_name']), -1, PREG_SPLIT_NO_EMPTY);
                foreach ($words as $word) {
                    $query->andWhere(['like', 'cust.name', $word]);
                }
            }
            if (!empty($params['JudiciarySearch']['job_title'])) {
                $query->andWhere(['cust.job_title' => $params['JudiciarySearch']['job_title']]);
            }
            if (!empty($params['JudiciarySearch']['jobs_type'])) {
                $query->innerJoin('{{%jobs}} jb', 'cust.job_title = jb.id');
                $query->innerJoin('{{%jobs_type}} jt', 'jb.job_type = jt.id');
                $query->andWhere(['jt.id' => $params['JudiciarySearch']['jobs_type']]);
            }
        }

        // ─── JOIN فقط عند الحاجة: فلتر حالة العقد ───
        if (!empty($params['JudiciarySearch']['status'])) {
            $query->innerJoin('{{%contracts}} ct', 'ct.id = j.contract_id');
            if ($params['JudiciarySearch']['status'] == 'Available') {
                $query->andWhere(['not in', 'ct.status', ['canceled', 'finished']]);
            } elseif ($params['JudiciarySearch']['status'] == 'unAvailable') {
                $query->andWhere(['in', 'ct.status', ['canceled', 'finished']]);
            }
        }

        // ─── JOIN فقط عند الحاجة: فلتر آخر إجراء ───
        if (!empty($params['JudiciarySearch']['judiciary_actions'])) {
            $subQuery = (new Query())
                ->select(['judiciary_id', 'max_action_id' => 'MAX(judiciary_actions_id)'])
                ->from('{{%judiciary_customers_actions}}')
                ->groupBy('judiciary_id');
            $query->leftJoin(['lastAction' => $subQuery], 'j.id = lastAction.judiciary_id');
            $query->leftJoin('{{%judiciary_actions}} ja', 'ja.id = lastAction.max_action_id');
            $query->andWhere(['ja.id' => $params['JudiciarySearch']['judiciary_actions']]);
        }

        // ─── فلتر: آخر إجراء على أطراف القضية ───
        if (!empty($params['JudiciarySearch']['last_party_action'])) {
            $actionId = (int)$params['JudiciarySearch']['last_party_action'];
            $latestPerParty = (new Query())
                ->select(['judiciary_id'])
                ->from('{{%judiciary_customers_actions}} jca_lp')
                ->where(['jca_lp.is_deleted' => 0])
                ->andWhere('jca_lp.id = (
                    SELECT MAX(j3.id) FROM {{%judiciary_customers_actions}} j3
                    WHERE j3.judiciary_id = jca_lp.judiciary_id
                      AND j3.customers_id = jca_lp.customers_id
                      AND j3.is_deleted = 0
                )')
                ->andWhere(['jca_lp.judiciary_actions_id' => $actionId])
                ->distinct();
            $query->andWhere(['in', 'j.id', $latestPerParty]);
        }

        // ─── فلتر: فقط القضايا التي لديها طلبات معلّقة ───
        if (!empty($params['pending_requests'])) {
            $query->innerJoin(
                '{{%judiciary_customers_actions}} jca_pending',
                'jca_pending.judiciary_id = j.id AND jca_pending.request_status = :pstat AND (jca_pending.is_deleted = 0 OR jca_pending.is_deleted IS NULL)',
                [':pstat' => 'pending']
            );
            $query->distinct();
        }

        // ─── Pagination مع حد افتراضي ───
        $pageSize = !empty($params['JudiciarySearch']['number_row'])
            ? (int) $params['JudiciarySearch']['number_row']
            : 10;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $pageSize],
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return ['dataProvider' => $dataProvider, 'count' => 0];
        }

        // استخدام totalCount من DataProvider بدل count() منفصل
        return ['dataProvider' => $dataProvider, 'count' => $dataProvider->totalCount];
    }

    public function reportSearch($params)
    {
        $query = Judiciary::find()
            ->alias('j')
            ->with(['contract', 'lawyer', 'court']);

        $pageSize = !empty($params['JudiciarySearch']['number_row'])
            ? (int)$params['JudiciarySearch']['number_row'] : 20;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $pageSize],
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return ['dataProvider' => $dataProvider, 'count' => 0];
        }

        $query->andFilterWhere([
            'j.id' => $this->id,
            'j.year' => $this->year,
            'j.type_id' => $this->type_id,
            'j.case_cost' => $this->case_cost,
            'j.contract_id' => $this->contract_id,
            'j.lawyer_cost' => $this->lawyer_cost,
            'j.lawyer_id' => $this->lawyer_id,
            'j.judiciary_number' => $this->judiciary_number,
            'j.court_id' => $this->court_id,
        ]);

        if (!empty($params['JudiciarySearch']['from_income_date'])) {
            $query->andWhere(['>=', 'j.income_date', $params['JudiciarySearch']['from_income_date']]);
        }
        if (!empty($params['JudiciarySearch']['to_income_date'])) {
            $query->andWhere(['<=', 'j.income_date', $params['JudiciarySearch']['to_income_date']]);
        }

        $query->andWhere(['j.is_deleted' => false]);
        $query->andWhere(['!=', 'j.judiciary_number', ' ']);

        return ['dataProvider' => $dataProvider, 'count' => $dataProvider->totalCount];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function report()
    {
        $p = Yii::$app->db->tablePrefix;

        $sql = "SELECT
                af.contract_id,
                af.court_name,
                CONCAT(af.judiciary_number, '-', j.year) AS judiciary_number,
                j.lawyer_cost,
                af.lawyer_name,
                af.customer_name,
                af.action_name,
                af.action_date AS customer_date
            FROM {$p}vw_judiciary_actions_feed af
            INNER JOIN {$p}judiciary j ON j.id = af.judiciary_id
            WHERE (af.action_is_deleted = 0 OR af.action_is_deleted IS NULL)
              AND j.is_deleted = 0
              AND af.action_date = (
                  SELECT MAX(a2.action_date)
                  FROM {$p}judiciary_customers_actions a2
                  WHERE a2.judiciary_id = af.judiciary_id
                    AND a2.customers_id = af.customers_id
                    AND a2.is_deleted = 0
              )
            ORDER BY af.action_date DESC";

        $countSql = "SELECT COUNT(*)
            FROM {$p}vw_judiciary_actions_feed af
            INNER JOIN {$p}judiciary j ON j.id = af.judiciary_id
            WHERE (af.action_is_deleted = 0 OR af.action_is_deleted IS NULL)
              AND j.is_deleted = 0
              AND af.action_date = (
                  SELECT MAX(a2.action_date)
                  FROM {$p}judiciary_customers_actions a2
                  WHERE a2.judiciary_id = af.judiciary_id
                    AND a2.customers_id = af.customers_id
                    AND a2.is_deleted = 0
              )";

        $count = Yii::$app->db->createCommand($countSql)->queryScalar();

        $dataProvider = new SqlDataProvider([
            'sql' => $sql,
            'totalCount' => (int)$count,
        ]);

        return ['dataProvider' => $dataProvider, 'count' => (int)$count];
    }
}
