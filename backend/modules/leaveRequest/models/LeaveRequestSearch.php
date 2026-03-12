<?php

namespace backend\modules\leaveRequest\models;

use backend\modules\leaveRequest\models\LeaveRequest;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;


/**
 * LeaveRequestSearch represents the model behind the search form about `common\models\LeaveRequest`.
 */
class LeaveRequestSearch extends LeaveRequest
{

    public $employeeName, $actionBy, $leavePolicy;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'attachment', 'leave_policy', 'action_by', 'created_by', 'created_at', 'updated_at', 'proved_at'], 'integer'],
            [['reason', 'start_at', 'end_at', 'employeeName', 'actionBy', 'status', 'leavePolicy', 'proved_at'], 'safe'],
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
        $query = LeaveRequest::find();
        $query->joinWith(['createdBy']);
        $query->joinWith(['leavePolicy']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $dataProvider->sort->attributes['employeeName'] = [
            'asc' => ['os_user.name' => SORT_ASC], // TABLE_NAME.COLUMN_NAME
            'desc' => ['os_user.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['leavePolicy'] = [
            'asc' => ['os_leave_policy.title' => SORT_ASC], // TABLE_NAME.COLUMN_NAME
            'desc' => ['os_leave_policy.title' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['actionBy'] = [
            'asc' => ['os_user.name' => SORT_ASC], // TABLE_NAME.COLUMN_NAME
            'desc' => ['os_user.name' => SORT_DESC],
        ];
        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'attachment' => $this->attachment,
            'leave_policy' => $this->leave_policy,
            'action_by' => $this->action_by,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'reason', $this->reason]);
        $query->andFilterWhere(['like', 'os_user.name', $this->employeeName]);
        $query->andFilterWhere(['like', 'os_user.name', $this->actionBy]);
        $query->andFilterWhere(['=', 'os_leave_request.status', $this->status]);
        $query->andFilterWhere(['=', 'os_leave_policy.title', $this->leavePolicy]);
        $query->andFilterWhere(['=', 'proved_at', $this->proved_at]);
        return $dataProvider;
    }

    public function searchSuspendedVacations($params)
    {
        $query = LeaveRequest::find();
        $query->joinWith(['createdBy']);
        $query->joinWith(['leavePolicy']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $dataProvider->sort->attributes['employeeName'] = [
            'asc' => ['os_user.name' => SORT_ASC], // TABLE_NAME.COLUMN_NAME
            'desc' => ['os_user.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['leavePolicy'] = [
            'asc' => ['os_leave_policy.title' => SORT_ASC], // TABLE_NAME.COLUMN_NAME
            'desc' => ['os_leave_policy.title' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['actionBy'] = [
            'asc' => ['os_user.name' => SORT_ASC], // TABLE_NAME.COLUMN_NAME
            'desc' => ['os_user.name' => SORT_DESC],
        ];
        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'attachment' => $this->attachment,
            'leave_policy' => $this->leave_policy,
            'action_by' => $this->action_by,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'reason', $this->reason]);
        $query->andFilterWhere(['like', 'os_user.name', $this->employeeName]);
        $query->andFilterWhere(['like', 'os_user.name', $this->actionBy]);
        $query->andFilterWhere(['=', 'os_leave_request.status', 'under review']);
        $query->andFilterWhere(['>', 'os_leave_request.end_at', date('Y-m-d')]);
        $query->andFilterWhere(['=', 'os_leave_policy.title', $this->leavePolicy]);
        $query->andFilterWhere(['=', 'proved_at', $this->proved_at]);
        return $dataProvider;
    }

}
