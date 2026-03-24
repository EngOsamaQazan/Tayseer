<?php

namespace backend\modules\judiciaryCustomersActions\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions;

/**
 * JudiciaryCustomersActionsSearch represents the model behind the search form about `backend\modules\judiciary\models\JudiciaryCustomersActions`.
 */
class JudiciaryCustomersActionsSearch extends JudiciaryCustomersActions {

    /**
     * @inheritdoc
     */
    public $form_action_date;
    public $to_action_date;
    public $from_create_at;
    public $to_create_at;
    public $number_row;
    public $judiciary_number;

    public $contract_not_in_status;

    public function rules() {
        return [
            [['id', 'judiciary_id', 'customers_id', 'created_at', 'updated_at', 'created_by', 'last_update_by', 'judiciary_actions_id', 'is_deleted', 'court_name', 'contract_id', 'lawyer_name', 'number_row'], 'integer', 'on' => 'create'],
            [['id', 'judiciary_id', 'customers_id', 'created_at', 'updated_at', 'created_by', 'last_update_by', 'judiciary_actions_id', 'is_deleted', 'court_name', 'contract_id', 'lawyer_name', 'number_row'], 'integer', 'on' => 'update'],
            [['contract_id', 'judiciary_id', 'number_row'], 'integer'],
            [['note', 'year', 'form_action_date', 'to_action_date', 'court_name', 'lawyer_name', 'contract_not_in_status', 'judiciary_actions_id', 'judiciary_number', 'request_status'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios() {
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
    public function search($params) {
        $query = JudiciaryCustomersActions::find();
        $query->innerJoinWith('judiciary');
        $query->innerJoinWith('customers');
        $query->innerJoinWith('judiciaryActions');
        $query->with(['judiciary.lawyer', 'judiciary.court']);
        $query->innerJoin('os_contracts', 'os_judiciary.contract_id = os_contracts.id')
            ->where(['!=', 'os_contracts.status', 'finished']);

        $pageSize = !empty($params['JudiciaryCustomersActionsSearch']['number_row'])
            ? (int)$params['JudiciaryCustomersActionsSearch']['number_row'] : 10;
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $pageSize],
            'sort' => ['defaultOrder' => ['action_date' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'os_judiciary_customers_actions.id' => $this->id,
            'os_judiciary_customers_actions.judiciary_id' => $this->judiciary_id,
            'os_judiciary_customers_actions.customers_id' => $this->customers_id,
            'os_judiciary_customers_actions.created_by' => $this->created_by,
            'os_judiciary_customers_actions.last_update_by' => $this->last_update_by,
        ]);

        $query->andFilterWhere(['like', 'os_judiciary_customers_actions.note', $this->note]);

        if (!empty($params['JudiciaryCustomersActionsSearch']['judiciary_actions_id'])) {
            $ids = $params['JudiciaryCustomersActionsSearch']['judiciary_actions_id'];
            $ids = is_array($ids) ? $ids : [$ids];
            $query->andFilterWhere(['in', 'os_judiciary_customers_actions.judiciary_actions_id', $ids]);
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['form_action_date'])) {
            $query->andFilterWhere(['>=', 'os_judiciary_customers_actions.action_date', $params['JudiciaryCustomersActionsSearch']['form_action_date']]);
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['to_action_date'])) {
            $query->andFilterWhere(['<=', 'os_judiciary_customers_actions.action_date', $params['JudiciaryCustomersActionsSearch']['to_action_date']]);
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['from_create_at'])) {
            $query->andFilterWhere(['>=', 'os_judiciary_customers_actions.created_at', strtotime($params['JudiciaryCustomersActionsSearch']['from_create_at'])]);
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['to_create_at'])) {
            $query->andFilterWhere(['<=', 'os_judiciary_customers_actions.created_at', strtotime($params['JudiciaryCustomersActionsSearch']['to_create_at'])]);
        }

        $query->andFilterWhere(['os_judiciary.court_id' => $this->court_name]);
        $query->andFilterWhere(['os_judiciary.year' => $this->year]);
        $query->andFilterWhere(['os_judiciary.lawyer_id' => $this->lawyer_name]);
        $query->andFilterWhere(['os_judiciary.contract_id' => $this->contract_id]);
        $query->andFilterWhere(['os_judiciary.judiciary_number' => $this->judiciary_number]);
        $query->andFilterWhere(['os_judiciary_customers_actions.request_status' => $this->request_status]);

        if (!empty($params['JudiciarySearch']['contract_not_in_status'])) {
            $query->andFilterWhere(['<>', 'os_contracts.status', $params['JudiciarySearch']['contract_not_in_status']]);
        }

        return $dataProvider;
    }
    public function searchCounter($params) {
        $query = JudiciaryCustomersActions::find();
        $query->innerJoinWith('judiciary');
        $query->innerJoinWith('customers');
        $query->innerJoinWith('judiciaryActions');
        $query->innerJoin('os_contracts', 'os_judiciary.contract_id = os_contracts.id')
            ->where(['!=', 'os_contracts.status', 'finished']);

        $this->load($params);

        if (!$this->validate()) {
            return 0;
        }
//        if (empty($this->judiciary_id) && empty($params['JudiciaryCustomersActionsSearch']['customers_id']) && empty($params['JudiciaryCustomersActionsSearch']['judiciary_actions_id']) && empty($this->year) && empty($this->created_by) && empty($this->court_name) && empty($this->lawyer_name) && empty($this->contract_id) && empty($params['JudiciaryCustomersActionsSearch']['to_action_date']) && empty($params['JudiciaryCustomersActionsSearch']['form_action_date'])
//        ) {
//            $query->Where(['=', 'action_date', '1000-01-01']);
//            return $dataProvider;
//        }
        $query->andFilterWhere([
            'os_judiciary_customers_actions.id' => $this->id,
            'os_judiciary_customers_actions.judiciary_id' => $this->judiciary_id,
            'os_judiciary_customers_actions.customers_id' => $this->customers_id,
            'os_judiciary_customers_actions.created_by' => $this->created_by,
            'os_judiciary_customers_actions.last_update_by' => $this->last_update_by,
        ]);
        $query->andFilterWhere(['like', 'os_judiciary_customers_actions.note', $this->note]);

        if (!empty($params['JudiciaryCustomersActionsSearch']['judiciary_actions_id'])) {
            $ids = $params['JudiciaryCustomersActionsSearch']['judiciary_actions_id'];
            $ids = is_array($ids) ? $ids : [$ids];
            $query->andFilterWhere(['in', 'os_judiciary_customers_actions.judiciary_actions_id', $ids]);
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['form_action_date'])) {
            $query->andFilterWhere(['>=', 'os_judiciary_customers_actions.action_date', $params['JudiciaryCustomersActionsSearch']['form_action_date']]);
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['to_action_date'])) {
            $query->andFilterWhere(['<=', 'os_judiciary_customers_actions.action_date', $params['JudiciaryCustomersActionsSearch']['to_action_date']]);
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['from_create_at'])) {
            $query->andFilterWhere(['>=', 'os_judiciary_customers_actions.created_at', strtotime($params['JudiciaryCustomersActionsSearch']['from_create_at'])]);
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['to_create_at'])) {
            $query->andFilterWhere(['<=', 'os_judiciary_customers_actions.created_at', strtotime($params['JudiciaryCustomersActionsSearch']['to_create_at'])]);
        }
        $query->andFilterWhere(['os_judiciary.court_id' => $this->court_name]);
        $query->andFilterWhere(['os_judiciary.year' => $this->year]);
        $query->andFilterWhere(['os_judiciary.lawyer_id' => $this->lawyer_name]);
        $query->andFilterWhere(['os_judiciary.contract_id' => $this->contract_id]);
        $query->andFilterWhere(['os_judiciary.judiciary_number' => $this->judiciary_number]);
        $query->andFilterWhere(['os_judiciary_customers_actions.request_status' => $this->request_status]);

        if (!empty($params['JudiciarySearch']['contract_not_in_status'])) {
            $query->andFilterWhere(['<>', 'os_contracts.status', $params['JudiciarySearch']['contract_not_in_status']]);
        }

        return $query->count();
    }

}
