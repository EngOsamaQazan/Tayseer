<?php

namespace backend\models;

use Yii;
use backend\modules\judiciary\models\Judiciary;

/**
 * @property int $id
 * @property int $judiciary_id
 * @property int $customer_id
 * @property string $current_stage
 * @property string|null $stage_updated_at
 * @property string|null $notification_date
 * @property string|null $comprehensive_request_date
 * @property string|null $notes
 */
class JudiciaryDefendantStage extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'os_judiciary_defendant_stage';
    }

    public function rules()
    {
        return [
            [['judiciary_id', 'customer_id'], 'required'],
            [['judiciary_id', 'customer_id'], 'integer'],
            [['current_stage'], 'string', 'max' => 30],
            [['current_stage'], 'default', 'value' => Judiciary::STAGE_CASE_PREPARATION],
            [['stage_updated_at', 'notification_date', 'comprehensive_request_date'], 'safe'],
            [['notes'], 'string'],
            [['judiciary_id', 'customer_id'], 'unique', 'targetAttribute' => ['judiciary_id', 'customer_id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'judiciary_id' => 'القضية',
            'customer_id' => 'المحكوم عليه',
            'current_stage' => 'المرحلة الحالية',
            'stage_updated_at' => 'تاريخ تحديث المرحلة',
            'notification_date' => 'تاريخ التبليغ',
            'comprehensive_request_date' => 'تاريخ تقديم الطلب الشامل',
            'notes' => 'ملاحظات',
        ];
    }

    public function getJudiciary()
    {
        return $this->hasOne(Judiciary::class, ['id' => 'judiciary_id']);
    }

    public function getCustomer()
    {
        return $this->hasOne(\backend\modules\customers\models\Customers::class, ['id' => 'customer_id']);
    }

    public function getStageLabel()
    {
        return Judiciary::getStageLabel($this->current_stage);
    }

    /**
     * Advance to the next stage and refresh parent case stages.
     */
    public function advanceTo($newStage)
    {
        $newRank = Judiciary::getStageRank($newStage);
        $currentRank = Judiciary::getStageRank($this->current_stage);

        if ($newRank <= $currentRank && $newStage !== Judiciary::STAGE_CASE_CLOSURE) {
            return false;
        }

        $this->current_stage = $newStage;
        $this->stage_updated_at = date('Y-m-d H:i:s');

        if ($this->save(false)) {
            $this->judiciary->refreshCaseStages();
            return true;
        }
        return false;
    }
}
