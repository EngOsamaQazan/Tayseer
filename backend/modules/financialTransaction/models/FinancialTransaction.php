<?php

namespace backend\modules\financialTransaction\models;

use Cassandra\Date;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\behaviors\BlameableBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use backend\modules\expenseCategories\models\ExpenseCategories;
use backend\modules\contracts\models\Contracts;
use backend\modules\companies\models\Companies;

/**
 * This is the model class for table os_financial-transaction".
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
 * @property int $receiver_number
 * @property int $document_number
 * @property int $contract_id
 * @property int $type
 * @property int $company_id
 * @property int $bank_number
 * @property int $bank_id
 * @property int $number_row
 * @property int $is_transfer
 * @property string $date
 * @property string $bank_description
 * @property ExpenseCategories $category
 */
class FinancialTransaction extends \yii\db\ActiveRecord
{

    const TYPE_INCOME = 1;
    const TYPE_OUTCOME = 2;
    const RESTRICTED = 1;
    const UNBOUND = 2;
    const TYPE_INCOME_NONE = 0;
    const TYPE_INCOME_MONTHLY = 1;
    const TYPE_INCOME_OTHER = 2;
    const CUSTOMER_PAYMENTS = 8;
    const COURT_RESPONSES = 11;

    public $excel_file;
    public $Restriction;
    public $created;
    public $updated;
    public $number_row;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_financial_transaction';
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
            [['category_id', 'created_at', 'created_by', 'updated_at', 'last_updated_by', 'is_deleted', 'receiver_number', 'document_number', 'contract_id', 'type', 'income_type', 'company_id', 'is_transfer', 'bank_id', 'number_row'], 'integer'],
            [['description', 'amount', 'receiver_number', 'document_number', 'company_id', 'is_transfer'], 'required', 'on' => 'others'],
            [['bank_description', 'amount', 'receiver_number', 'document_number', 'company_id', 'is_transfer', 'date'], 'required', 'on' => 'ImportFile'],
            [['description', 'bank_description', 'bank_number'], 'string'],
            [['amount'], 'number'],
            [['amount'], 'required'],
            [['excel_file'], 'safe'],
            [['excel_file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'xls,xlsx'],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => ExpenseCategories::className(), 'targetAttribute' => ['category_id' => 'id']],
            [['contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => Contracts::className(), 'targetAttribute' => ['contract_id' => 'id']],
            ['income_type', 'required', 'when' => function ($model) {
                return ($model->type == self::TYPE_INCOME && !empty($model->type)) ? true : false;
            }, 'whenClient' => "function(){
                if($('#type').val() === undefined){
                     false;
                }else {
                     true;
                }
            }", 'on' => 'createAndUpadte'],
            [['income_type'], 'safe', 'on' => 'ImportFile'],
            [['Restriction'], 'safe'],
            [['bank_id'], 'required']
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
            'document_number' => Yii::t('app', 'Document Number'),
            'receiver_number' => Yii::t('app', 'Receiver Number'),
            'excel_file' => Yii::t('app', 'Excel File'),
            'contract_id' => Yii::t('app', 'Contract Id'),
            'Type' => Yii::t('app', 'Type'),
            'Restriction' => Yii::t('app', 'Restriction'),
            'Company Id' => Yii::t('app', 'Company Id'),
            'income_type' => Yii::t('app', 'Income Type'),
            'date' => Yii::t('app', 'Date'),
            'note' => Yii::t('app', 'Notes'),
            'bank_description' => Yii::t('app', 'Bank Description'),
        ];
    }

    /**
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(ExpenseCategories::className(), ['id' => 'category_id']);
    }

    public function getCreatedBy()
    {
        return $this->hasOne(Users::className(), ['id' => 'created_by']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'last_updated_by']);
    }

    public function getContract()
    {
        return $this->hasOne(Contracts::className(), ['id' => 'contract_id']);
    }

    public function getCompany()
    {
        return $this->hasOne(\backend\modules\companies\models\Companies::className(), ['id' => 'company_id']);
    }

    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query->notDeleted();
    }

    public function exelFileValidator($sheetData)
    {
        $sheetDataCount = count($sheetData);
        if ($sheetDataCount < 3) {
            return false;
        }
        for ($i = 3; $i < $sheetDataCount; $i++) {
            if (is_null($sheetData[$i]['E']) || is_null($sheetData[$i]['A']) || is_null(($sheetData[$i]['B']) || is_null($sheetData[$i]['C'])))
                return false;
        }
        return true;
    }
}
