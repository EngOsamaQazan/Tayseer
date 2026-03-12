<?php

namespace backend\modules\contracts\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $contract_id
 * @property string $type      discount|write_off|waiver|free_discount
 * @property float $amount
 * @property string|null $reason
 * @property int|null $approved_by
 * @property int|null $created_by
 * @property string $created_at
 * @property int $is_deleted
 *
 * @property Contracts $contract
 */
class ContractAdjustment extends ActiveRecord
{
    const TYPE_DISCOUNT      = 'discount';
    const TYPE_WRITE_OFF     = 'write_off';
    const TYPE_WAIVER        = 'waiver';
    const TYPE_FREE_DISCOUNT = 'free_discount';

    public static function tableName()
    {
        return '{{%contract_adjustments}}';
    }

    public function rules()
    {
        return [
            [['contract_id', 'amount', 'type'], 'required'],
            [['contract_id', 'approved_by', 'created_by', 'is_deleted'], 'integer'],
            [['amount'], 'number', 'min' => 0.01],
            [['type'], 'in', 'range' => [self::TYPE_DISCOUNT, self::TYPE_WRITE_OFF, self::TYPE_WAIVER, self::TYPE_FREE_DISCOUNT]],
            [['reason'], 'string'],
            [['created_at'], 'safe'],
            [['contract_id'], 'exist', 'targetClass' => Contracts::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'          => 'ID',
            'contract_id' => 'رقم العقد',
            'type'        => 'نوع التسوية',
            'amount'      => 'المبلغ',
            'reason'      => 'السبب',
            'approved_by' => 'الموافق',
            'created_by'  => 'أنشئ بواسطة',
            'created_at'  => 'تاريخ الإنشاء',
            'is_deleted'  => 'محذوف',
        ];
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_DISCOUNT      => 'خصم تجاري',
            self::TYPE_WRITE_OFF     => 'شطب',
            self::TYPE_WAIVER        => 'إعفاء',
            self::TYPE_FREE_DISCOUNT => 'خصم مجاني',
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_by = Yii::$app->user->id ?? null;
        }
        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        Contracts::refreshContractStatus($this->contract_id);
    }

    public function afterDelete()
    {
        parent::afterDelete();
        Contracts::refreshContractStatus($this->contract_id);
    }

    public function getContract()
    {
        return $this->hasOne(Contracts::class, ['id' => 'contract_id']);
    }

    public function getApprover()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'approved_by']);
    }

    public function getCreator()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'created_by']);
    }
}
