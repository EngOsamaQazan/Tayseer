<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * ActiveRecord for os_user_category_map (pivot table).
 *
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property int|null $assigned_by
 * @property string $created_at
 */
class UserCategoryMap extends ActiveRecord
{
    public static function tableName()
    {
        return 'os_user_category_map';
    }

    public function rules()
    {
        return [
            [['user_id', 'category_id'], 'required'],
            [['user_id', 'category_id', 'assigned_by'], 'integer'],
            [['created_at'], 'safe'],
            [['user_id', 'category_id'], 'unique', 'targetAttribute' => ['user_id', 'category_id']],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    public function getCategory()
    {
        return $this->hasOne(UserCategory::class, ['id' => 'category_id']);
    }

    public static function syncUserCategories($userId, array $categoryIds, $assignedBy = null)
    {
        $db = Yii::$app->db;
        $db->createCommand()->delete('os_user_category_map', ['user_id' => $userId])->execute();

        $hasVendor = false;
        foreach ($categoryIds as $catId) {
            $catId = (int)$catId;
            if ($catId <= 0) continue;
            $db->createCommand()->insert('os_user_category_map', [
                'user_id' => $userId,
                'category_id' => $catId,
                'assigned_by' => $assignedBy,
            ])->execute();

            $cat = UserCategory::findOne($catId);
            if ($cat && $cat->slug === 'vendor') {
                $hasVendor = true;
            }
        }

        /* توحيد الموردين: إنشاء/ربط سجل مورد تلقائياً عند تصنيف المستخدم كمورد */
        if ($hasVendor) {
            \backend\modules\inventorySuppliers\models\InventorySuppliers::ensureSupplierForUser($userId);
        }
    }
}
