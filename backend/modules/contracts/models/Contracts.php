<?php

namespace backend\modules\contracts\models;

use backend\modules\customers\models\ContractsCustomers;
use Yii;
use DateTime;
use Exception;
use \common\models\User;
use yii\helpers\ArrayHelper;
use yii\behaviors\BlameableBehavior;
use backend\modules\followUp\models\FollowUp;
use noam148\imagemanager\models\ImageManager;
use backend\modules\companies\models\Companies;
use backend\modules\customers\models\Customers;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\inventoryItems\models\ContractInventoryItem;
use backend\modules\loanScheduling\models\LoanScheduling;
use backend\modules\followUp\helper\ContractCalculations;
use yii\db;

/**
 * This is the model class for table "{{%contracts}}".
 *
 * @property int $id
 * @property string $type
 * @property int|null $seller_id
 * @property string|null $Date_of_sale
 * @property string|null $from_date
 * @property string|null $to_date
 * @property float|null $total_value
 * @property float|null $first_installment_value value of first intallment amount
 * @property string|null $first_installment_date
 * @property string|null $monthly_installment_value
 * @property string|null $notes
 * @property string|null $job_title
 * @property string $status
 * @property string|null $updated_at
 * @property int|null $is_deleted
 * @property int|null $loss_commitment
 * @property int|null $number_row
 *
 * @property ContractsCustomers[] $contractsCustomers
 * @property FollwUps[] $follwUps
 * @property Customers $customer
 * @property Customers[] $guarantor
 * @property string $due_date
 * @property integer $selected_image
 * @property ImageManager $selectedImg
 * @property string $selectedImagePath
 * @property float $commitment_discount
 * @property boolean $is_can_not_contact
 * @property int $created_by
 * @property string $created_at
 * @property int $follow_up_lock_by
 * @property int $inventoryItemValue
 * @property string $follow_up_lock_at
 * @property int|null $followed_by
 * @property int|null $job_Type
 * @property int $is_legal_department
 */
class Contracts extends \yii\db\ActiveRecord
{
    public $to_date;
    public $from_date;
    public $job_title;
    public $loan_date;
    public $inventory_items;
    public $customers_ids;
    public $customer_id;
    public $guarantors_ids;
    public $contract_images;
    public $image_manager_id;
    public $due_date;
    public $loan_scheduling_new_instalment_date;
    public $is_loan = 0;
    public $phone_number;
    const FINISH_STATUS = 'finished';
    const CANCEL_STATUS = 'canceled';
    const STATUS_ACTIVE = 'active';
    const STATUS_LEGAL_DEPARTMENT = 'legal_department';
    const STATUS_JUDICIARY = 'judiciary';
    const STATUS_SETTLEMENT = 'settlement';

    /** @deprecated kept for backward compatibility only */
    const STATUS_PENDING = 'pending';
    /** @deprecated kept for backward compatibility only */
    const STATUS_REFUSED = 'refused';
    const DEFAUULT_TOTAL_VALUE = 640;
    const MONTHLY_INSTALLMENT_VALE = 20;
    public $number_row;
    public $job_Type;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%contracts}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['seller_id', 'is_deleted', 'created_by', 'updated_by', 'follow_up_lock_by', 'loss_commitment', 'followed_by', 'company_id', 'number_row'], 'integer'],
            [['is_can_not_contact', 'is_legal_department'], 'boolean'],
            [['job_Type'], 'integer'],
            [['Date_of_sale', 'first_installment_date', 'monthly_installment_value', 'updated_at', 'customers_ids', 'customer_id', 'guarantors_ids', 'image_manager_id', 'selected_image', 'contract_images', 'company_id', 'created_at', 'follow_up_lock_at', 'inventory_items'], 'safe'],
            [['type', 'status', 'seller_id', 'Date_of_sale', 'first_installment_date', 'monthly_installment_value', 'total_value', 'first_installment_value', 'created_by', 'commitment_discount'], 'required'],
            [['total_value', 'first_installment_value', 'commitment_discount'], 'number'],
            [['followed_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['followed_by' => 'id']],
            [['notes'], 'string'],
            //['monthly_installment_value', 'compare', 'compareAttribute' => 'total_value', 'operator' => '<', 'enableClientValidation' => true],
            ['first_installment_date', 'compare', 'compareAttribute' => 'Date_of_sale', 'operator' => '>', 'enableClientValidation' => true],
            ['customers_ids', 'required',
                'whenClient' => "function(attribute, value) {
                                     return  $(\"[name='Contracts[type]']:checked\").val()=='solidarity';
                                }"
            ],
//            [['customer_id', 'guarantors_ids'], 'required',
//                'whenClient' => "function(attribute, value) {
//                        return  $(\"[name='Contracts[type]']:checked\").val()=='normal';
//                }"
//            ]
            [['customer_id'], 'required',
                'whenClient' => "function(attribute, value) {
                        return  $(\"[name='Contracts[type]']:checked\").val()=='normal';
                }"
            ],
            [['from_date', 'to_date', 'job_title'], 'string'],
            [['from_date', 'to_date', 'job_title'], 'safe']
        ];
    }

    public function afterFind()
    {
        parent::afterFind(); // TODO: Change the autogenerated stub
        $this->Date_of_sale;
        try {
            $saleDay = (int)date('d', strtotime($this->Date_of_sale));

            if ($saleDay > 15) {
                $nextMonth = date('m', strtotime('+1 month', strtotime($this->Date_of_sale)));
                $year = date('Y', strtotime('+1 month', strtotime($this->Date_of_sale)));
                $this->due_date = "{$year}-{$nextMonth}-28";
            } else {
                $currentMonth = date('m', strtotime($this->Date_of_sale));
                $year = date('Y', strtotime($this->Date_of_sale));
                $this->due_date = "{$year}-{$currentMonth}-28";
            }
        } catch (Exception $exception) {
            $this->due_date = '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function seller()
    {
        $duration = 60;     // cache query results for 60 seconds.
        $db = Yii::$app->getDb();

        $result = $db->cache(function ($db) {

            // ... perform SQL queries here ...

            return yii\helpers\ArrayHelper::map(\common\models\User::find()->all(), 'id', 'username');

        }, $duration);

        return $result;
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'seller_id' => Yii::t('app', 'Seller Name'),
            'Date_of_sale' => Yii::t('app', 'Date Of Sale'),
            'total_value' => Yii::t('app', 'Total Value'),
            'first_installment_value' => Yii::t('app', 'First Installment Value'),
            'first_installment_date' => Yii::t('app', 'First Installment Date'),
            'monthly_installment_value' => Yii::t('app', 'Monthly Installment Value'),
            'notes' => Yii::t('app', 'Notes'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'type' => Yii::t('app', 'Type'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'follow_up_lock_by' => 'follow up lock by',
            'created_by' => 'follow up lock at',
            'commitment_discount' => Yii::t('app', 'Commitment Discount'),
            'loss_commitment' => Yii::t('app', 'Loss Commitment'),
            'followed_by' => Yii::t('app', 'Followed By'),
            'phone_number' => Yii::t('app', 'Phone Number'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContractsCustomers()
    {
        return $this->hasMany(\backend\modules\customers\models\ContractsCustomers::class, ['contract_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFollwUps()
    {
        return $this->hasMany(FollowUp::class, ['contract_id' => 'id']);
    }

    public function getInventoryItemValue()
    {
        $items = ContractInventoryItem::find()->andWhere(['contract_id' => $this->id])->all();
        if ($items) {
            $array = [];
            foreach ($items as $item) {
                $array[$item->item->id] = $item->item->item_name;
            }
            return $array;
        }
        return false;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomers()
    {
        return $this->hasMany(Customers::class, ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'id'], function ($query) {
                $query->onCondition(['customer_type' => 'client']);
            });
    }

    public function getCustomersGuarantor()
    {
        return $this->hasMany(Customers::class, ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'id'], function ($query) {
                $query->onCondition(['customer_type' => 'guarantor']);
            });
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomersAndGuarantor()
    {
        return $this->hasMany(Customers::class, ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomersWithoutCondition()
    {
        return $this->hasMany(Customers::class, ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {

        return $this->hasOne(Customers::class, ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'id'], function ($query) {
                $query->onCondition(['customer_type' => 'client']);
            });
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGuarantor()
    {
        return $this->hasMany(Customers::class, ['id' => 'customer_id'])
            ->viaTable('os_contracts_customers', ['contract_id' => 'id'], function ($query) {
                $query->onCondition(['customer_type' => 'guarantor']);
            });
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Companies::class, ['id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSeller()
    {
        return $this->hasOne(\dektrium\user\models\User::class, ['id' => 'seller_id']);
    }

    /**
     * {@inheritdoc}
     * @return ContractsQuery the active query used by this AR class.
     */
//    public static function find()
//    {
//        return new ContractsQuery(get_called_class());
//    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSelectedImg()
    {
        return $this->hasOne(ImageManager::class, ['id' => 'selected_image']);
    }

    public function getSelectedImagePath()
    {
        if ($this->selectedImg) {
            $file_hash = $this->selectedImg->fileHash;
            $file_extention = pathinfo($this->selectedImg->fileName, PATHINFO_EXTENSION);
            $path = '/images/imagemanager/' . $this->selected_image . '_' . $file_hash . '.' . $file_extention;
            if (is_file(\Yii::getAlias('@webroot') . $path)) {
                return $path;
            }
        }
        return null;
    }

    public function unlock()
    {
        $this->follow_up_lock_at = NULL;
        $this->follow_up_lock_by = 0;
        if (!$this->save(false)) {
            echo '<pre>';
            $this->getErrors();
            echo '</pre>';
            die();
        }
    }

    public function is_locked()
    {
        $date_expire = $this->follow_up_lock_at;
        if (empty($date_expire)) {
            return false;
        }
        $date = new DateTime($date_expire);
        $now = new DateTime();
        $years = $date->diff($now)->format("%y") * 365 * 24 * 60;
        $months = $date->diff($now)->format("%M") * 30 * 24 * 60;
        $days = $date->diff($now)->format("%d") * 24 * 60;
        $hours = $date->diff($now)->format("%h") * 60;
        $minutes = $date->diff($now)->format("%m");
        $total = $years + $months + $days + $hours + $minutes;
        if ($this->follow_up_lock_by != Yii::$app->user->id && $this->follow_up_lock_by != 0 && $total < 30) {
            return true;
        } else {
            return false;
        }
    }

    public function lock()
    {
        $this->follow_up_lock_at = date('Y-m-d H:i:s');
        $this->follow_up_lock_by = Yii::$app->user->id;
        if (!$this->save(false)) {
            echo '<pre>';
            $this->getErrors();
            echo '</pre>';
            die();
        }
    }

    public function firstInstallmentValue()
    {
        return Contracts::find()->where(['first_installment_date' => date('Y-m-d')])->sum('first_installment_value');
    }

    /** @deprecated Status is now computed automatically. Use refreshStatus() instead. */
    public function finish()
    {
        $this->status = self::FINISH_STATUS;
        if (!$this->save(false)) {
            echo '<pre>';
            $this->getErrors();
            echo '</pre>';
            die();
        }
    }

    public function cancel()
    {
        $this->status = self::CANCEL_STATUS;
        if (!$this->save(false)) {
            echo '<pre>';
            $this->getErrors();
            echo '</pre>';
            die();
        }
    }

    /** @deprecated Status is now computed automatically. Use toggleLegalDepartment() instead. */
    public function legalDepartment()
    {
        $this->status = self::STATUS_LEGAL_DEPARTMENT;
        if (!$this->save(false)) {
            echo '<pre>';
            $this->getErrors();
            echo '</pre>';
            die();
        }
    }

    /* ═══════════════════════════════════════════════════
     *  Automatic Status Computation
     * ═══════════════════════════════════════════════════ */

    /**
     * حساب الحالة الفعلية للعقد بناءً على البيانات الحقيقية
     */
    public function computeStatus(): string
    {
        if ($this->status === self::CANCEL_STATUS) {
            return self::CANCEL_STATUS;
        }

        $hasJudiciary = Judiciary::find()
            ->where(['contract_id' => $this->id, 'is_deleted' => 0])
            ->exists();

        if ($hasJudiciary) {
            return self::STATUS_JUDICIARY;
        }

        $hasSettlement = LoanScheduling::find()
            ->where(['contract_id' => $this->id])
            ->exists();

        if ($hasSettlement) {
            return self::STATUS_SETTLEMENT;
        }

        if ($this->is_legal_department) {
            return self::STATUS_LEGAL_DEPARTMENT;
        }

        $calc = new ContractCalculations($this->id);
        if ($calc->remainingAmount() <= 0) {
            return self::FINISH_STATUS;
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * إعادة حساب وتحديث حالة العقد
     */
    public function refreshStatus(): string
    {
        $newStatus = $this->computeStatus();
        if ($this->status !== $newStatus) {
            self::updateAll(['status' => $newStatus], ['id' => $this->id]);
            $this->status = $newStatus;
        }
        return $newStatus;
    }

    /**
     * Static helper — يُستدعى من afterSave/afterDelete في الموديلات المرتبطة
     */
    public static function refreshContractStatus(int $contractId): void
    {
        $contract = self::findOne($contractId);
        if ($contract) {
            $contract->refreshStatus();
        }
    }

    /**
     * هل العقد مسدّد بالكامل مع وجود قضية (قضائي - مسدد)
     */
    public function isJudiciaryPaid(): bool
    {
        if ($this->status !== self::STATUS_JUDICIARY) {
            return false;
        }
        $calc = new ContractCalculations($this->id);
        return $calc->remainingAmount() <= 0;
    }

    /**
     * تفعيل / إلغاء تحويل العقد للدائرة القانونية
     */
    public function toggleLegalDepartment(bool $enable = true): string
    {
        $this->is_legal_department = $enable ? 1 : 0;
        $this->save(false);
        return $this->refreshStatus();
    }

    public function getFollowedBy()
    {
        return $this->hasOne(User::class, ['id' => 'followed_by']);
    }

    public function getJudiciary()
    {
        return $this->hasMany(Judiciary::class, ['contract_id' => 'id']);
    }

    public function getAdjustments()
    {
        return $this->hasMany(ContractAdjustment::class, ['contract_id' => 'id'])
            ->andWhere(['is_deleted' => 0]);
    }
    public function getCustomersName($id)
    {
        $customers = [];


        $contractCustamer  =  ContractsCustomers::find()->where(['contract_id'=>$id])->all();
        foreach ($contractCustamer as $custamer) {
            $custam = Customers::findOne(['id' => $custamer['customer_id']]);
            array_push($customers, $custam->name);
        }
        return $customers;
    }
    public  function  getMinCollection(){

//SELECT COUNT(id), followed_by FROM `os_contracts` WHERE followed_by in (SELECT id FROM os_user WHERE job_title = 4) GROUP BY followed_by;
        $users =\yii\helpers\ArrayHelper::map( \common\models\User::find()->where(['job_title'=>4])->all(),'id','id');
        $infos = \backend\modules\contracts\models\Contracts::find()->select(['COUNT(id) as count_id', 'followed_by'])->where(['in','followed_by',$users])->groupBy('followed_by')->createCommand()->getRawSql();
        print_r( $infos);
/*SELECT
    MIN(mycount)
FROM
    (
    SELECT
        followed_by,
        COUNT(id) mycount
    FROM
        os_contracts
    WHERE
        `followed_by` IN(73, 74, 75)
    GROUP BY
        followed_by
);*/
        exit();
    }
}
