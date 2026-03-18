<?php

namespace backend\modules\accounting\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $journal_entry_id
 * @property int $account_id
 * @property int|null $cost_center_id
 * @property float $debit
 * @property float $credit
 * @property string|null $description
 * @property int|null $contract_id
 * @property int|null $customer_id
 *
 * @property JournalEntry $journalEntry
 * @property Account $account
 * @property CostCenter $costCenter
 */
class JournalEntryLine extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%journal_entry_lines}}';
    }

    public function rules()
    {
        return [
            [['journal_entry_id', 'account_id'], 'required'],
            [['journal_entry_id', 'account_id', 'cost_center_id', 'contract_id', 'customer_id'], 'integer'],
            [['debit', 'credit'], 'number', 'min' => 0],
            [['description'], 'string', 'max' => 255],
            [['debit'], 'default', 'value' => 0],
            [['credit'], 'default', 'value' => 0],
            [['account_id'], 'exist', 'skipOnError' => true, 'targetClass' => Account::class, 'targetAttribute' => ['account_id' => 'id']],
            [['cost_center_id'], 'exist', 'skipOnError' => true, 'targetClass' => CostCenter::class, 'targetAttribute' => ['cost_center_id' => 'id']],
            ['debit', 'validateDebitCredit'],
        ];
    }

    public function validateDebitCredit($attribute)
    {
        if ((float)$this->debit == 0 && (float)$this->credit == 0) {
            $this->addError($attribute, 'يجب إدخال مبلغ في المدين أو الدائن');
        }
        if ((float)$this->debit > 0 && (float)$this->credit > 0) {
            $this->addError($attribute, 'لا يمكن إدخال مبلغ في المدين والدائن معا');
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'journal_entry_id' => 'القيد',
            'account_id' => 'الحساب',
            'cost_center_id' => 'مركز التكلفة',
            'debit' => 'مدين',
            'credit' => 'دائن',
            'description' => 'البيان',
            'contract_id' => 'العقد',
            'customer_id' => 'العميل',
        ];
    }

    public function getJournalEntry()
    {
        return $this->hasOne(JournalEntry::class, ['id' => 'journal_entry_id']);
    }

    public function getAccount()
    {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    public function getCostCenter()
    {
        return $this->hasOne(CostCenter::class, ['id' => 'cost_center_id']);
    }
}
