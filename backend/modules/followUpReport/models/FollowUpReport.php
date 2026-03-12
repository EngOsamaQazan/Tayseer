<?php

namespace backend\modules\followUpReport\models;

use Yii;
use \backend\modules\customers\models\Customers;
use \backend\modules\contracts\models\Contracts;
use \common\models\User;
/**
 * This is the model class for table "{{%follow_up_report}}".
 *
 * @property int $id
 * @property string $type
 * @property int|null $seller_id
 * @property string|null $Date_of_sale
 * @property float|null $total_value
 * @property float|null $first_installment_value value of first intallment amount
 * @property string|null $first_installment_date
 * @property float|null $monthly_installment_value
 * @property string|null $notes
 * @property string $status
 * @property string|null $updated_at
 * @property int|null $is_deleted
 * @property string|null $selected_image
 * @property string|null $company_id
 * @property int|null $date_time
 * @property int|null $number_row
 */
class FollowUpReport extends \yii\db\ActiveRecord
{
    public $number_row;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%follow_up_report}}';
    }

    public static function primaryKey()
    {
        return ["id"];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'seller_id', 'is_deleted', 'number_row', 'never_followed', 'due_installments'], 'integer'],
            [['type', 'status'], 'required'],
            [['type', 'notes', 'status', 'company_id'], 'string'],
            [['Date_of_sale', 'first_installment_date', 'updated_at', 'last_follow_up', 'promise_to_pay_at', 'reminder'], 'safe'],
            [['total_value', 'first_installment_value', 'monthly_installment_value', 'total_paid', 'due_amount', 'effective_installment'], 'number'],
            [['selected_image'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'type' => Yii::t('app', 'Type'),
            'seller_id' => Yii::t('app', 'Seller ID'),
            'Date_of_sale' => Yii::t('app', 'Date Of Sale'),
            'total_value' => Yii::t('app', 'Total Value'),
            'first_installment_value' => Yii::t('app', 'First Installment Value'),
            'first_installment_date' => Yii::t('app', 'First Installment Date'),
            'monthly_installment_value' => Yii::t('app', 'Monthly Installment Value'),
            'notes' => Yii::t('app', 'Notes'),
            'status' => Yii::t('app', 'Status'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'selected_image' => Yii::t('app', 'Selected Image'),
            'company_id' => Yii::t('app', 'Company ID'),
            'last_follow_up' => 'آخر متابعة',
            'promise_to_pay_at' => 'وعد بالدفع',
            'reminder' => 'التذكير',
            'seller_name' => Yii::t('app', 'Seller Name'),
            'total_paid' => 'المدفوع',
            'due_installments' => 'أقساط مستحقة',
            'due_amount' => 'المبلغ المستحق',
            'effective_installment' => 'القسط الفعّال',
            'never_followed' => 'لم يُتابع أبداً',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {

        return $this->hasOne(Customers::className(), ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'id'], function ($query) {
                $query->onCondition(['customer_type' => 'client']);
            });
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSeller()
    {
        return $this->hasOne(\dektrium\user\models\User::className(), ['id' => 'seller_id']);
    }

    public function getContract()
    {
        return $this->hasOne(Contracts::className(), ['id' => 'id']);
    }

    public function getFollowedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'followed_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomers()
    {
        return $this->hasMany(Customers::className(), ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'id'], function ($query) {
                $query->onCondition(['customer_type' => 'client']);
            });
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomersWithoutCondition()
    {
        return $this->hasMany(Customers::className(), ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'id']);
    }

}
