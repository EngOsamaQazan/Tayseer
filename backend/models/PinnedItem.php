<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;

class PinnedItem extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%pinned_items}}';
    }

    public function rules()
    {
        return [
            [['user_id', 'item_type', 'item_id'], 'required'],
            [['user_id', 'item_id'], 'integer'],
            [['item_type'], 'string', 'max' => 50],
            [['label'], 'string', 'max' => 255],
            [['extra_info'], 'string', 'max' => 500],
            [['created_at'], 'safe'],
            [['user_id', 'item_type', 'item_id'], 'unique', 'targetAttribute' => ['user_id', 'item_type', 'item_id']],
        ];
    }

    public static function ensureTable()
    {
        $db = Yii::$app->db;
        $tableName = $db->tablePrefix . 'pinned_items';
        $exists = $db->createCommand("SHOW TABLES LIKE '{$tableName}'")->queryScalar();
        if (!$exists) {
            $db->createCommand("
                CREATE TABLE `{$tableName}` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `item_type` VARCHAR(50) NOT NULL,
                    `item_id` INT NOT NULL,
                    `label` VARCHAR(255) DEFAULT NULL,
                    `extra_info` VARCHAR(500) DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY `uk_user_item` (`user_id`, `item_type`, `item_id`),
                    KEY `idx_user_type` (`user_id`, `item_type`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ")->execute();
        }
    }

    public static function isItemPinned($userId, $itemType, $itemId)
    {
        return static::find()
            ->where(['user_id' => $userId, 'item_type' => $itemType, 'item_id' => $itemId])
            ->exists();
    }

    public static function getPinnedItems($userId, $itemType)
    {
        return static::find()
            ->where(['user_id' => $userId, 'item_type' => $itemType])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }

    public static function togglePin($userId, $itemType, $itemId, $label = null, $extraInfo = null)
    {
        $existing = static::findOne([
            'user_id' => $userId,
            'item_type' => $itemType,
            'item_id' => $itemId,
        ]);

        if ($existing) {
            $existing->delete();
            return ['pinned' => false];
        }

        $pin = new static();
        $pin->user_id = $userId;
        $pin->item_type = $itemType;
        $pin->item_id = $itemId;
        $pin->label = $label;
        $pin->extra_info = $extraInfo;
        $pin->save();
        return ['pinned' => true, 'id' => $pin->id];
    }
}
