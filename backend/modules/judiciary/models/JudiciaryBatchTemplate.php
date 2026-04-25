<?php

namespace backend\modules\judiciary\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Active-record for table "os_judiciary_batch_templates".
 *
 * Reusable presets of the wizard's "shared common-data" panel.
 * Visible to every JUD_CREATE user (company-wide), but only the creator
 * or a manager may delete a template.
 *
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property int $created_at
 * @property int|null $updated_at
 * @property string $data  JSON snapshot of common-data fields
 * @property int $usage_count
 * @property int $is_deleted
 */
class JudiciaryBatchTemplate extends ActiveRecord
{
    public static function tableName()
    {
        return 'os_judiciary_batch_templates';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['name', 'created_by', 'data'], 'required'],
            [['created_by', 'created_at', 'updated_at', 'usage_count', 'is_deleted'], 'integer'],
            [['data'], 'safe'],
            [['name'], 'string', 'max' => 100],
        ];
    }

    public function getDataArray(): array
    {
        if (empty($this->data)) {
            return [];
        }
        $decoded = json_decode($this->data, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setDataArray(array $data): void
    {
        $this->data = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function getCreator()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'created_by']);
    }

    public static function find()
    {
        return parent::find()->andWhere(['is_deleted' => 0]);
    }
}
