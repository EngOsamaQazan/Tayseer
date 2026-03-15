<?php

namespace backend\modules\diwan\models;

use yii\db\ActiveQuery;

class DiwanCorrespondenceQuery extends ActiveQuery
{
    public function notifications()
    {
        return $this->andWhere(['communication_type' => 'notification']);
    }

    public function outgoingLetters()
    {
        return $this->andWhere(['communication_type' => 'outgoing_letter']);
    }

    public function incomingResponses()
    {
        return $this->andWhere(['communication_type' => 'incoming_response']);
    }

    public function forCase($judiciaryId)
    {
        return $this->andWhere(['related_module' => 'judiciary', 'related_record_id' => $judiciaryId]);
    }

    public function forDefendant($customerId)
    {
        return $this->andWhere(['customer_id' => $customerId]);
    }

    public function toRecipient($type, $id)
    {
        $this->andWhere(['recipient_type' => $type]);
        switch ($type) {
            case 'bank':
                return $this->andWhere(['bank_id' => $id]);
            case 'employer':
                return $this->andWhere(['job_id' => $id]);
            case 'administrative':
                return $this->andWhere(['authority_id' => $id]);
            default:
                return $this;
        }
    }

    public function pending()
    {
        return $this->andWhere(['IN', 'status', ['draft', 'sent']]);
    }

    public function needsFollowUp()
    {
        return $this->andWhere(['<=', 'follow_up_date', date('Y-m-d')])
            ->andWhere(['NOT IN', 'status', ['responded', 'closed']]);
    }

    public function overdue()
    {
        return $this->innerJoinWith(['deadlines' => function ($q) {
            $q->andWhere(['os_judiciary_deadlines.status' => 'expired']);
        }]);
    }

    public function chronological()
    {
        return $this->orderBy(['correspondence_date' => SORT_DESC, 'id' => SORT_DESC]);
    }

    public function active()
    {
        return $this->andWhere(['is_deleted' => 0]);
    }
}
