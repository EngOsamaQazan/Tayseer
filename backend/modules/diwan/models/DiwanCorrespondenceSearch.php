<?php

namespace backend\modules\diwan\models;

use yii\data\ActiveDataProvider;

class DiwanCorrespondenceSearch extends DiwanCorrespondence
{
    public $from_date;
    public $to_date;

    public function rules()
    {
        return [
            [['id', 'related_record_id', 'customer_id', 'authority_id', 'bank_id', 'job_id', 'parent_id', 'company_id'], 'integer'],
            [['communication_type', 'related_module', 'direction', 'recipient_type', 'notification_method', 'notification_result', 'purpose', 'response_result', 'status'], 'string'],
            [['from_date', 'to_date', 'correspondence_date', 'follow_up_date', 'reference_number', 'content_summary'], 'safe'],
        ];
    }

    public function search($params, $judiciaryId = null)
    {
        $query = DiwanCorrespondence::find()->chronological();

        if ($judiciaryId) {
            $query->forCase($judiciaryId);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'communication_type' => $this->communication_type,
            'customer_id' => $this->customer_id,
            'recipient_type' => $this->recipient_type,
            'notification_method' => $this->notification_method,
            'notification_result' => $this->notification_result,
            'purpose' => $this->purpose,
            'response_result' => $this->response_result,
            'status' => $this->status,
            'direction' => $this->direction,
            'authority_id' => $this->authority_id,
            'bank_id' => $this->bank_id,
            'job_id' => $this->job_id,
            'parent_id' => $this->parent_id,
        ]);

        $query->andFilterWhere(['like', 'reference_number', $this->reference_number])
              ->andFilterWhere(['like', 'content_summary', $this->content_summary]);

        if ($this->from_date) {
            $query->andWhere(['>=', 'correspondence_date', $this->from_date]);
        }
        if ($this->to_date) {
            $query->andWhere(['<=', 'correspondence_date', $this->to_date]);
        }

        return $dataProvider;
    }
}
