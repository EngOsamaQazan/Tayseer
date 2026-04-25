<?php

namespace backend\modules\judiciary\models;

use yii\db\ActiveRecord;

/**
 * Active-record for table "os_judiciary_batch_items".
 *
 * One row per contract queued in a batch. Carries the per-row outcome,
 * the contract.status snapshot taken before the batch overwrote it (so a
 * revert can restore it), and any per-row override values that diverged
 * from the batch's shared_data.
 *
 * @property int $id
 * @property int $batch_id
 * @property int $contract_id
 * @property int|null $judiciary_id
 * @property string|null $previous_contract_status
 * @property string $status  pending|success|failed|skipped|reverted
 * @property string|null $error_message
 * @property string|null $overrides  JSON
 * @property int $created_at
 */
class JudiciaryBatchItem extends ActiveRecord
{
    const STATUS_PENDING  = 'pending';
    const STATUS_SUCCESS  = 'success';
    const STATUS_FAILED   = 'failed';
    const STATUS_SKIPPED  = 'skipped';
    const STATUS_REVERTED = 'reverted';

    public static function tableName()
    {
        return 'os_judiciary_batch_items';
    }

    public function rules()
    {
        return [
            [['batch_id', 'contract_id', 'created_at', 'status'], 'required'],
            [['batch_id', 'contract_id', 'judiciary_id', 'created_at'], 'integer'],
            [['error_message', 'overrides'], 'safe'],
            [['previous_contract_status'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 16],
        ];
    }

    public function getOverridesArray(): array
    {
        if (empty($this->overrides)) {
            return [];
        }
        $decoded = json_decode($this->overrides, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setOverridesArray(array $data): void
    {
        $this->overrides = empty($data) ? null : json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function getBatch()
    {
        return $this->hasOne(JudiciaryBatch::class, ['id' => 'batch_id']);
    }

    public function getJudiciary()
    {
        return $this->hasOne(Judiciary::class, ['id' => 'judiciary_id']);
    }

    public function getContract()
    {
        return $this->hasOne(\backend\modules\contracts\models\Contracts::class, ['id' => 'contract_id']);
    }
}
