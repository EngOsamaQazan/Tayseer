<?php

namespace backend\modules\expenses\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii\behaviors\BlameableBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

/**
 * This is the model class for table "os_expenses".
 *
 * @property int $id
 * @property int|null $category_id
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $last_updated_by
 * @property int $is_deleted
 * @property string $description
 * @property float $amount
 * @property float $amount_from
 * @property float $amount_to
 * @property string $expenses_date
 * @property string $date_from
 * @property string $date_to
 * @property int $receiver_number
 * @property int $contract_id
 * @property int|null $financial_transaction_id
* @property int $document_number
* @property int $number_row
 * @property string $notes
 */
class Expenses extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public $date_from;
    public $date_to;
    public $amount_from;
    public $amount_to;
    public $number_row;
    public static function tableName()
    {
        return 'os_expenses';
    }
    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_updated_by',
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
            [['category_id', 'receiver_number', 'financial_transaction_id','document_number','contract_id','number_row'], 'integer'],
            [['description', 'amount', 'receiver_number'], 'required'],
            [['description','expenses_date','date_from','date_to'], 'string'],
            [['amount','amount_from','amount_to'], 'number'],
            [['notes'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'category_id' => Yii::t('app', 'Category ID'),
            'created_at' => Yii::t('app', 'Created At'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'last_updated_by' => Yii::t('app', 'Last Updated By'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'description' => Yii::t('app', 'Description'),
            'amount' => Yii::t('app', 'Amount'),
            'receiver_number' => Yii::t('app', 'Receiver Number'),
            'financial_transaction_id' => Yii::t('app', 'Financial Transaction ID'),
            'expenses_date' => Yii::t('app', 'Expenses Date'),
            'date_from' => Yii::t('app', ' Date From'),
            'date_to' => Yii::t('app', ' Date To'),
            'amount_from' => Yii::t('app', 'Amount From'),
            'amount_to' => Yii::t('app', 'Amount To'),
            'document_number' => Yii::t('app', 'Document Number'),
            'notes' => Yii::t('app', 'notes'),
            'Expenses Date' => Yii::t('app', 'Expenses Date'),
            'contract_id' => Yii::t('app', 'Contract ID'),
        ];
    }
    public function getCategory() {
        return $this->hasOne(\backend\modules\expenseCategories\models\ExpenseCategories::className(), ['id' => 'category_id']);
    }

    public function getCreatedBy() {
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }

    public function getUpdatedBy() {
        return $this->hasOne(\common\models\User::className(), ['id' => 'last_updated_by']);
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
}
