<?php

namespace backend\modules\diwan\models;

use Yii;
use common\models\User;

/**
 * تتبع وثائق الديوان (من يحمل كل عقد حالياً)
 *
 * @property int $id
 * @property string $contract_number
 * @property int|null $contract_id
 * @property int|null $current_holder_id
 * @property int|null $last_transaction_id
 * @property string|null $status
 * @property int|null $updated_at
 */
class DiwanDocumentTracker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_diwan_document_tracker';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_number'], 'required'],
            [['contract_id', 'current_holder_id', 'last_transaction_id', 'updated_at'], 'integer'],
            [['contract_number'], 'string', 'max' => 100],
            [['contract_number'], 'unique'],
            [['status'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'contract_number' => 'رقم العقد',
            'contract_id' => 'ربط العقد',
            'current_holder_id' => 'الحامل الحالي',
            'last_transaction_id' => 'آخر معاملة',
            'status' => 'الحالة',
            'updated_at' => 'آخر تحديث',
        ];
    }

    /* ═══ العلاقات ═══ */

    public function getCurrentHolder()
    {
        return $this->hasOne(User::class, ['id' => 'current_holder_id']);
    }

    public function getLastTransaction()
    {
        return $this->hasOne(DiwanTransaction::class, ['id' => 'last_transaction_id']);
    }

    /**
     * تحديث حامل الوثيقة بعد معاملة
     */
    public static function updateHolder($contractNumber, $holderId, $transactionId)
    {
        $tracker = self::findOne(['contract_number' => $contractNumber]);
        if (!$tracker) {
            $tracker = new self();
            $tracker->contract_number = $contractNumber;
        }
        $tracker->current_holder_id = $holderId;
        $tracker->last_transaction_id = $transactionId;
        $tracker->status = 'مع ' . (($user = User::findOne($holderId)) ? ($user->name ?: $user->username) : '—');
        $tracker->updated_at = time();
        return $tracker->save(false);
    }
}
