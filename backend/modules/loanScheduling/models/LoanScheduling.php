<?php

namespace backend\modules\loanScheduling\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use \common\models\User;
/**
 * This is the model class for table "os_loan_scheduling".
 *
 * @property int $id
 * @property int $contract_id
 * @property string $new_installment_date
 * @property float $monthly_installment
 * @property string $first_installment_date
 * @property string $status
 * @property int $status_action_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $created_by
 * @property int|null $last_update_by
 * @property string $settlement_type monthly|weekly
 * @property float|null $total_debt
 * @property float|null $remaining_debt
 * @property int|null $installments_count
 * @property float|null $first_payment
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $number_row
 *
 * @property Contracts $contract
 * @property User $createdBy
 * @property User $lastUpdateBy
 * @property User $statusActionBy
 */
class LoanScheduling extends \yii\db\ActiveRecord
{
    public $number_row;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_loan_scheduling';
    }
    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_update_by',
            ],
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'is_deleted' => true
                ],

                'replaceRegularDelete' => true // mutate native `delete()` method
            ],

        ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_id', 'monthly_installment', 'first_installment_date', 'settlement_type'], 'required'],
            [['contract_id',  'status_action_by', 'created_by', 'last_update_by', 'is_deleted','number_row', 'installments_count'], 'integer'],
            [['monthly_installment', 'total_debt', 'remaining_debt', 'first_payment'], 'number'],
            [['first_installment_date', 'created_at', 'updated_at'], 'safe'],
            [['status', 'new_installment_date', 'notes'], 'string'],
            [['settlement_type'], 'in', 'range' => ['monthly', 'weekly']],
            [['contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => \backend\modules\contracts\models\Contracts::className(), 'targetAttribute' => ['contract_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['created_by' => 'id']],
            [['last_update_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['last_update_by' => 'id']],
            [['status_action_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['status_action_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'contract_id' => 'رقم العقد',
            'new_installment_date' => 'تاريخ القسط الجديد',
            'monthly_installment' => 'القسط الشهري',
            'first_installment_date' => 'تاريخ أول قسط',
            'status' => 'الحالة',
            'status_action_by' => 'اتخذ القرار بواسطة',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التعديل',
            'created_by' => 'أنشئ بواسطة',
            'last_update_by' => 'آخر تعديل بواسطة',
            'is_deleted' => 'محذوف',
            'settlement_type' => 'نوع التسوية',
            'total_debt' => 'إجمالي الدين',
            'remaining_debt' => 'المبلغ المتبقي',
            'installments_count' => 'عدد الأقساط',
            'first_payment' => 'الدفعة الأولى',
            'notes' => 'ملاحظات',
        ];
    }

    /**
     * Gets query for [[Contract]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContract()
    {
        return $this->hasOne(\backend\modules\contracts\models\Contracts::className(), ['id' => 'contract_id']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    /**
     * Gets query for [[LastUpdateBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLastUpdateBy()
    {
        return $this->hasOne(User::className(), ['id' => 'last_update_by']);
    }

    /**
     * Gets query for [[StatusActionBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatusActionBy()
    {
        return $this->hasOne(User::className(), ['id' => 'status_action_by']);
    }
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query->notDeleted();
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($this->contract_id) {
            \backend\modules\contracts\models\Contracts::refreshContractStatus((int)$this->contract_id);
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();
        if ($this->contract_id) {
            \backend\modules\contracts\models\Contracts::refreshContractStatus((int)$this->contract_id);
        }
    }

    public function afterSoftDelete()
    {
        if ($this->contract_id) {
            \backend\modules\contracts\models\Contracts::refreshContractStatus((int)$this->contract_id);
        }
    }
}
