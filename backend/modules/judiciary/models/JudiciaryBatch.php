<?php

namespace backend\modules\judiciary\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Active-record for table "os_judiciary_batches".
 *
 * Created by BatchCreateService when the user starts a batch from the
 * unified wizard, then progressively updated as chunks complete and
 * (optionally) when a revert happens within the 72-hour window.
 *
 * @property int $id
 * @property string $batch_uuid
 * @property int $created_by
 * @property int $created_at
 * @property string $entry_method  paste|excel|selection
 * @property int $contract_count
 * @property int $success_count
 * @property int $failed_count
 * @property string|null $shared_data  JSON
 * @property string $status  running|completed|partial|reverted
 * @property int|null $reverted_at
 * @property int|null $reverted_by
 * @property string|null $revert_reason
 */
class JudiciaryBatch extends ActiveRecord
{
    const STATUS_RUNNING   = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_PARTIAL   = 'partial';
    const STATUS_REVERTED  = 'reverted';

    const ENTRY_PASTE      = 'paste';
    const ENTRY_EXCEL      = 'excel';
    const ENTRY_SELECTION  = 'selection';

    /** Window during which a batch can be reverted, in seconds. */
    const REVERT_WINDOW_SECONDS = 72 * 3600;

    public static function tableName()
    {
        return 'os_judiciary_batches';
    }

    public function rules()
    {
        return [
            [['batch_uuid', 'created_by', 'created_at', 'entry_method', 'status'], 'required'],
            [['created_by', 'created_at', 'contract_count', 'success_count', 'failed_count', 'reverted_at', 'reverted_by'], 'integer'],
            [['shared_data'], 'safe'],
            [['batch_uuid'], 'string', 'max' => 36],
            [['entry_method', 'status'], 'string', 'max' => 16],
            [['revert_reason'], 'string', 'max' => 255],
        ];
    }

    /**
     * Decoded shared_data array. Returns [] when empty/invalid.
     */
    public function getSharedData(): array
    {
        if (empty($this->shared_data)) {
            return [];
        }
        $decoded = json_decode($this->shared_data, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setSharedData(array $data): void
    {
        $this->shared_data = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function getItems()
    {
        return $this->hasMany(JudiciaryBatchItem::class, ['batch_id' => 'id']);
    }

    public function getCreator()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'created_by']);
    }

    public function isWithinRevertWindow(): bool
    {
        return (time() - (int)$this->created_at) <= self::REVERT_WINDOW_SECONDS;
    }
}
