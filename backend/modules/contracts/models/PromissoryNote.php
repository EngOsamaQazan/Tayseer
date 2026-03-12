<?php

namespace backend\modules\contracts\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * نموذج الكمبيالة — يخزن بيانات كل كمبيالة مرتبطة بعقد
 *
 * @property int $id
 * @property int $contract_id
 * @property int $sequence_number
 * @property float $amount
 * @property string $due_date
 * @property string $status
 * @property int $created_at
 * @property int $created_by
 */
class PromissoryNote extends ActiveRecord
{
    const STATUS_ACTIVE    = 'active';
    const STATUS_EXECUTED  = 'executed';
    const STATUS_CANCELLED = 'cancelled';

    public static function tableName()
    {
        return 'os_promissory_notes';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
            [
                'class' => BlameableBehavior::class,
                'updatedByAttribute' => false,
                'createdByAttribute' => 'created_by',
            ],
        ];
    }

    public function rules()
    {
        return [
            [['contract_id', 'sequence_number'], 'required'],
            [['contract_id', 'sequence_number', 'created_at', 'created_by'], 'integer'],
            [['amount'], 'number'],
            [['due_date'], 'string', 'max' => 30],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_EXECUTED, self::STATUS_CANCELLED]],
            [['contract_id', 'sequence_number'], 'unique', 'targetAttribute' => ['contract_id', 'sequence_number']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'              => 'رقم الكمبيالة',
            'contract_id'     => 'رقم العقد',
            'sequence_number' => 'رقم النسخة',
            'amount'          => 'المبلغ',
            'due_date'        => 'تاريخ الاستحقاق',
            'status'          => 'الحالة',
            'created_at'      => 'تاريخ الإنشاء',
            'created_by'      => 'أنشئ بواسطة',
        ];
    }

    public function getContract()
    {
        return $this->hasOne(Contracts::class, ['id' => 'contract_id']);
    }

    /**
     * رقم الكمبيالة المنسق للعرض
     * @return string مثال: "0000042"
     */
    public function getDisplayNumber()
    {
        return str_pad($this->id, 7, '0', STR_PAD_LEFT);
    }

    /**
     * إنشاء 3 كمبيالات للعقد إذا لم تكن موجودة
     * @param int $contractId
     * @param float $amount
     * @param string $dueDate
     * @return PromissoryNote[]
     */
    public static function ensureNotesExist($contractId, $amount, $dueDate)
    {
        $existing = self::find()
            ->where(['contract_id' => $contractId])
            ->orderBy('sequence_number')
            ->all();

        if (count($existing) >= 3) {
            return $existing;
        }

        $existingSeqs = array_map(function ($n) {
            return (int)$n->sequence_number;
        }, $existing);

        $notes = $existing;
        for ($i = 1; $i <= 3; $i++) {
            if (!in_array($i, $existingSeqs)) {
                $note = new self();
                $note->contract_id     = $contractId;
                $note->sequence_number = $i;
                $note->amount          = $amount;
                $note->due_date        = $dueDate;
                $note->status          = self::STATUS_ACTIVE;
                $note->save(false);
                $notes[] = $note;
            }
        }

        usort($notes, function ($a, $b) {
            return $a->sequence_number - $b->sequence_number;
        });

        return $notes;
    }
}
