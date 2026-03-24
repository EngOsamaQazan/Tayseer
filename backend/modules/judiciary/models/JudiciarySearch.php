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
        $query = Judiciary::find();
        $query->joinWith('contract');
        $query->joinWith('lawyer');
        $query->joinWith('court');

        if (!empty($params['JudiciarySearch']['number_row'])) {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['JudiciarySearch']['number_row'],
                ],
            ]);
        } else {
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'last_update_by' => $this->last_update_by,
        ]);

        if (!empty($params['JudiciarySearch']['from_income_date'])) {
            $query->where(['>=', 'income_date', $params['JudiciarySearch']['from_income_date']]);
        }
        if (!empty($params['JudiciarySearch']['to_income_date'])) {
            $query->where(['<=', 'income_date', $params['JudiciarySearch']['to_income_date']]);
        }
        $query->andFilterWhere(['year' => $this->year]);
        $query->andFilterWhere(['type_id' => $this->type_id]);
        $query->andFilterWhere(['case_cost' => $this->case_cost]);
        $query->andFilterWhere(['contract_id' => $this->contract_id]);
        $query->andFilterWhere(['lawyer_cost' => $this->lawyer_cost]);
        $query->andFilterWhere(['lawyer_id' => $this->lawyer_id]);
        $query->andFilterWhere(['judiciary_number' => $this->judiciary_number]);
        $query->andFilterWhere(['court_id' => $this->court_id]);
        $query->andWhere(['os_judiciary.is_deleted' => false]);
        $query->where(['!=', 'judiciary_number', ' ']);
        return ['dataProvider' => $dataProvider, 'count' => $query->count()];
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

        $query = "SELECT
            `os_judiciary`.`contract_id` as contract_id ,
            `os_court`.`name` as court_name,
            CONCAT(`os_judiciary`.`judiciary_number`,'-',`os_judiciary`.`year`) as judiciary_number ,
            `os_judiciary`.`lawyer_cost` as lawyer_cost,
            `os_lawyers`.`name` as lawyer_name,
            `c`.`name` as customer_name,
            `os_judiciary_actions`.`name` as action_name,
            `jcc`.`action_date` as customer_date
            FROM
            `os_judiciary`
            LEFT JOIN `os_court` ON(
            `os_judiciary`.`court_id` = `os_court`.`id`
            ) AND(`os_court`.`is_deleted` = FALSE)
            LEFT JOIN `os_lawyers` ON(
            `os_judiciary`.`lawyer_id` = `os_lawyers`.`id`
            ) AND(`os_lawyers`.`is_deleted` = FALSE)
            LEFT JOIN `os_contracts` ON `os_judiciary`.`contract_id` = `os_contracts`.`id` AND(
            `os_contracts`.`status` NOT IN('finished', 'canceld', 'pending')
            )
            LEFT JOIN `os_judiciary_customers_actions` AS `jcc`
            ON
            `jcc`.`judiciary_id` = `os_judiciary`.`id` AND(`jcc`.`is_deleted` = FALSE)
            LEFT JOIN `os_customers` AS c
            ON
            `jcc`.`customers_id` = `c`.`id`
            LEFT JOIN `os_judiciary_actions` ON `os_judiciary_actions`.`id` = `jcc`.`judiciary_actions_id`
            WHERE
            (`jcc`.`is_deleted` = FALSE) AND(`os_judiciary`.`is_deleted` = FALSE) AND(
            `jcc`.`action_date` =(
            SELECT
            MAX(action_date)
            FROM
            os_judiciary_customers_actions
            WHERE
            os_judiciary_customers_actions.customers_id = c.id AND os_judiciary_customers_actions.judiciary_id = os_judiciary.id AND os_judiciary_customers_actions.is_deleted = FALSE
            )
            )
            ORDER BY
            `jcc`.`action_date`
        DESC";
        $count = Yii::$app->db->createCommand("SELECT
                    count(`os_judiciary`.`contract_id`)
                     FROM `os_judiciary`
                    LEFT JOIN `os_court` ON (`os_judiciary`.`court_id` = `os_court`.`id`) AND(`os_court`.`is_deleted` = FALSE)
                    LEFT JOIN `os_lawyers` ON (`os_judiciary`.`lawyer_id` = `os_lawyers`.`id`) AND(`os_lawyers`.`is_deleted` = FALSE)
                    LEFT JOIN `os_contracts` ON `os_judiciary`.`contract_id` = `os_contracts`.`id` AND(`os_contracts`.`status` NOT IN('finished', 'canceld', 'pending'))
                    LEFT JOIN `os_judiciary_customers_actions` as `jcc` ON `jcc`.`judiciary_id` = `os_judiciary`.`id` AND(`jcc`.`is_deleted` = FALSE)
                    LEFT JOIN `os_customers` as c ON `jcc`.`customers_id` = `c`.`id`
                    LEFT JOIN `os_judiciary_actions` ON `os_judiciary_actions`.`id` = `jcc`.`judiciary_actions_id`
                    WHERE (`jcc`.`is_deleted`=FALSE)
                    AND(`os_judiciary`.`is_deleted` = FALSE)
                    AND(`jcc`.`action_date`=(
                     SELECT MAX(action_date)
                          FROM os_judiciary_customers_actions
                          WHERE os_judiciary_customers_actions.customers_id = c.id
                          AND os_judiciary_customers_actions.judiciary_id = os_judiciary.id
                         AND os_judiciary_customers_actions.is_deleted =FALSE
                    )
                       )  
                ORDER BY `jcc`.`action_date`  DESC")->queryScalar();;
        $dataProvider = new SqlDataProvider([
            'sql' => $query,
        ]);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        return ['dataProvider' => $dataProvider, 'count' => $count];
    }
}
