<?php

namespace backend\modules\inventorySuppliers\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

class InventorySuppliers extends ActiveRecord
{
    public $number_row;

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_update_by',
            ],
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::class,
                'softDeleteAttributeValues' => ['is_deleted' => true],
                'replaceRegularDelete' => true,
            ],
        ];
    }

    public static function tableName()
    {
        return '{{%inventory_suppliers}}';
    }

    public function rules()
    {
        return [
            [['name', 'phone_number'], 'required'],
            [['company_id', 'user_id', 'created_by', 'created_at', 'updated_at', 'last_update_by', 'is_deleted', 'number_row'], 'integer'],
            [['name', 'adress'], 'string', 'max' => 250],
            [['phone_number'], 'string', 'max' => 50],
            [['phone_number'], 'unique'],
            [['name'], 'unique'],
            [['company_id', 'adress', 'user_id'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'             => 'م',
            'company_id'     => 'الشركة',
            'name'           => 'اسم المورد',
            'adress'         => 'العنوان',
            'phone_number'   => 'رقم الهاتف',
            'user_id'        => 'مستخدم النظام',
            'created_by'     => 'أنشئ بواسطة',
            'created_at'     => 'تاريخ الإنشاء',
            'updated_at'     => 'آخر تحديث',
            'last_update_by' => 'آخر تعديل بواسطة',
        ];
    }

    /**
     * هل هذا المورد مرتبط بمستخدم نظام؟
     */
    public function getIsSystemUser()
    {
        return !empty($this->user_id);
    }

    /* ── العلاقات ── */
    public function getLinkedUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    public function getCreatedByUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'created_by']);
    }

    public function getCompany()
    {
        return $this->hasOne(\backend\modules\companies\models\Companies::class, ['id' => 'company_id']);
    }

    /**
     * إنشاء/ربط سجل مورد لمستخدم نظام مصنّف كمورد
     */
    public static function ensureSupplierForUser($userId)
    {
        $user = \common\models\User::findOne($userId);
        if (!$user) return null;

        $displayName = $user->profile ? $user->profile->name : $user->username;

        /* البحث يشمل السجلات المحذوفة ناعماً أيضاً */
        $existing = parent::find()->where(['user_id' => $userId])->one();
        if ($existing) {
            if ($existing->is_deleted) {
                $existing->is_deleted = 0;
                $existing->name = $displayName;
                $existing->save(false);
            }
            return $existing;
        }

        $byName = parent::find()->where(['name' => $displayName])->andWhere(['user_id' => null])->one();
        if ($byName) {
            $byName->user_id = $userId;
            if ($byName->is_deleted) $byName->is_deleted = 0;
            $byName->save(false);
            return $byName;
        }

        $sup = new static();
        $sup->name = $displayName;
        $sup->phone_number = $user->email ?: '---';
        $sup->adress = '';
        $sup->company_id = 0;
        $sup->user_id = $userId;
        $sup->save(false);
        return $sup;
    }

    /* ── SoftDelete scope (مصلح — كان ناقص) ── */
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
