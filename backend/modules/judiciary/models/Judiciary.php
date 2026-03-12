<?php

namespace backend\modules\judiciary\models;


use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use \backend\modules\contracts\models\Contracts;
use \backend\modules\lawyers\models\Lawyers;
use \backend\modules\court\models\Court;
use \backend\modules\customers\models\Customers;
use \backend\modules\judiciaryType\models\JudiciaryType;
use \common\models\User;
use backend\modules\JudiciaryInformAddress\model\JudiciaryInformAddress;

/**
 * This is the model class for table "os_judiciary".
 *
 * @property int $id
 * @property int $court_id
 * @property int $type_id
 * @property float $case_cost
 * @property float $lawyer_cost
 * @property int $lawyer_id
 * @property int $company_id
 * @property int $created_at
 * @property int $updated_at
 * @property int $created_by
 * @property int $last_update_by
 * @property int|null $is_deleted
 * @property int $contract_id
 * @property string $income_date
 * @property int $judiciary_number
 * @property int $number_row
 * @property int $input_method
 * @property string $year
 * @property int $judiciary_inform_address_id
 * @property string|null $last_check_date
 * @property string|null $case_status
 *
 * @property Court $court
 * @property User $createdBy
 * @property Lawyers $lawyer
 * @property JudiciaryType $type
 */
class Judiciary extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public $from_income_date;
    public $to_income_date;
    public $number_row;
    public $input_method;
    public $jobs_type;
    public $job_title;
    public $status;
    public $judiciary_actions;


    const ACTIVE = "فعال";
    const FINISHED = " منتهي";

    public static function tableName()
    {
        return 'os_judiciary';
    }

    /**
     *
     * @return type
     */
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
            [['court_id', 'type_id', 'lawyer_cost', 'lawyer_id', 'contract_id', 'judiciary_inform_address_id'], 'required'],
            [['court_id', 'type_id', 'lawyer_id', 'created_at', 'updated_at', 'created_by', 'last_update_by', 'is_deleted', 'judiciary_number', 'number_row', 'input_method', 'case_cost', 'judiciary_inform_address_id', 'company_id'], 'integer'],
            [['lawyer_cost', 'contract_id'], 'number'],
            [['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => JudiciaryType::class, 'targetAttribute' => ['type_id' => 'id']],
            [['lawyer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Lawyers::class, 'targetAttribute' => ['lawyer_id' => 'id']],
            [['court_id'], 'exist', 'skipOnError' => true, 'targetClass' => Court::class, 'targetAttribute' => ['court_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
            [['judiciary_inform_address_id'], 'exist', 'skipOnError' => true, 'targetClass' => JudiciaryInformAddress::class, 'targetAttribute' => ['judiciary_inform_address_id' => 'id']],
            [['income_date', 'year', 'last_check_date', 'case_status'], 'string'],
            [['from_income_date', 'to_income_date', 'company_id', 'last_check_date', 'case_status'], 'safe'],
            [['from_income_date', 'to_income_date'], 'string']

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'court_id' => Yii::t('app', 'Court ID'),
            'type_id' => Yii::t('app', 'Type ID'),
            'case_cost' => Yii::t('app', 'Case Cost'),
            'lawyer_cost' => Yii::t('app', 'Lawyer Cost'),
            'lawyer_id' => Yii::t('app', 'Lawyer ID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
            'contract_id' => Yii::t('app', 'Contract Id'),
            'last_update_by' => Yii::t('app', 'Last Update By'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'contract_not_in_status' => Yii::t('app', 'Contract Not In Status'),
            'number_row' => Yii::t('app', 'Number Row'),
            'judiciary_inform_address_id' => Yii::t('app', 'judiciary inform address'),
        ];
    }

    /**
     * Gets query for [[Court]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCourt()
    {
        return $this->hasOne(Court::class, ['id' => 'court_id']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[Lawyer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLawyer()
    {
        return $this->hasOne(Lawyers::class, ['id' => 'lawyer_id']);
    }

    /**
     * Gets query for [[Type]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(JudiciaryType::class, ['id' => 'type_id']);
    }

    public static function findWithAlias($alias = null)
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', [
            'class' => SoftDeleteQueryBehavior::class,
            'deletedCondition' => function ($query) use ($alias) {
                // Use the provided alias or default to the table name
                $column = ($alias ? $alias . '.' : '') . 'is_deleted';
                return [$column => false];
            }
        ]);
        return $query->notDeleted();
    }



    public function getCustomers()
    {
        return $this->hasMany(Customers::class, ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'contract_id'], function ($query) {
                $query->onCondition(['customer_type' => 'client']);
            });
    }

    public function getCustomersGuarantor()
    {
        return $this->hasMany(Customers::class, ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'contract_id'], function ($query) {
                $query->onCondition(['customer_type' => 'guarantor']);
            });
    }
    public function getCustomersAndGuarantor()
    {
        return $this->hasMany(Customers::class, ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'contract_id']);
    }
    public function getContract()
    {
        return $this->hasOne(Contracts::class, ['id' => 'contract_id']);
    }

    public function year()
    {
        $year = [];

        for ($y = 2010; $y <= date('Y'); $y = $y + 1) {
            $year[$y] = $y;
        }
        return $year;
    }

    public function getInformAddress()
    {
        return $this->hasOne(JudiciaryInformAddress::class, ['id' => 'judiciary_inform_address_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($this->contract_id) {
            Contracts::refreshContractStatus((int)$this->contract_id);
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();
        if ($this->contract_id) {
            Contracts::refreshContractStatus((int)$this->contract_id);
        }
    }
}
