<?php

namespace backend\modules\followUpReport\models;

use Yii;
use \backend\modules\customers\models\Customers;
use \backend\modules\contracts\models\Contracts;
use \common\models\User;

/**
 * Model for the os_follow_up_no_contact VIEW
 * عقود لا يمكن التواصل معها (is_can_not_contact = 1)
 */
class FollowUpNoContact extends \yii\db\ActiveRecord
{
    public $number_row;

    public static function tableName()
    {
        return '{{%follow_up_no_contact}}';
    }

    public static function primaryKey()
    {
        return ["id"];
    }

    public function rules()
    {
        return [
            [['id', 'seller_id', 'is_deleted', 'number_row'], 'integer'],
            [['type', 'status'], 'required'],
            [['type', 'notes', 'status', 'company_id'], 'string'],
            [['Date_of_sale', 'first_installment_date', 'updated_at', 'date_time', 'promise_to_pay_at', 'reminder'], 'safe'],
            [['total_value', 'first_installment_value', 'monthly_installment_value'], 'number'],
            [['selected_image'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'رقم العقد',
            'type' => 'النوع',
            'seller_id' => 'البائع',
            'Date_of_sale' => 'تاريخ البيع',
            'total_value' => 'إجمالي العقد',
            'first_installment_value' => 'الدفعة الأولى',
            'first_installment_date' => 'تاريخ أول قسط',
            'monthly_installment_value' => 'القسط الشهري',
            'notes' => 'ملاحظات',
            'status' => 'الحالة',
            'updated_at' => 'آخر تحديث',
            'is_deleted' => 'محذوف',
            'date_time' => 'آخر متابعة',
            'promise_to_pay_at' => 'وعد الدفع',
            'reminder' => 'التذكير',
            'due_amount' => 'المبلغ المستحق',
            'due_installments' => 'الأقساط المستحقة',
            'total_paid' => 'المدفوع',
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(Customers::class, ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'id'], function ($query) {
                $query->onCondition(['customer_type' => 'client']);
            });
    }

    public function getSeller()
    {
        return $this->hasOne(\dektrium\user\models\User::class, ['id' => 'seller_id']);
    }

    public function getContract()
    {
        return $this->hasOne(Contracts::class, ['id' => 'id']);
    }

    public function getFollowedBy()
    {
        return $this->hasOne(User::class, ['id' => 'followed_by']);
    }

    public function getCustomers()
    {
        return $this->hasMany(Customers::class, ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'id'], function ($query) {
                $query->onCondition(['customer_type' => 'client']);
            });
    }

    public function getCustomersWithoutCondition()
    {
        return $this->hasMany(Customers::class, ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'id']);
    }
}
