<?php

namespace backend\modules\collection\models;

use backend\modules\customers\models\Customers;
use Yii;

/**
 * This is the model class for table "os_collection".
 *
 * @property int $id
 * @property int|null $contract_id
 * @property string|null $date
 * @property float|null $amount
 * @property string|null $notes
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $created_by
 * @property int|null $last_updated_by
 * @property int|null $custamers_id
 * @property int|null $judiciary_id
 * @property double|null $total_amount
 * @property int|null $is_deleted
 *
 * @property Contracts $contract
 * @property User $createdBy
 * @property User $lastUpdatedBy
 */
class Collection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_collection';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_id', 'created_at', 'updated_at', 'created_by', 'last_updated_by', 'is_deleted', 'custamers_id', 'judiciary_id'], 'integer'],
            [[ 'custamers_id', 'judiciary_id','amount','date'], 'required'],
            [['date'], 'safe'],
            [['amount'], 'number'],
            [['notes'], 'string'],
            [['total_amount'], 'double'],
            [['contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => \backend\modules\contracts\models\Contracts::className(), 'targetAttribute' => ['contract_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => \common\models\User::className(), 'targetAttribute' => ['created_by' => 'id']],
            [['last_updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => \common\models\User::className(), 'targetAttribute' => ['last_updated_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'contract_id' => Yii::t('app', 'Contract ID'),
            'date' => Yii::t('app', 'Date'),
            'amount' => Yii::t('app', 'Amount'),
            'notes' => Yii::t('app', 'Notes'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
            'last_updated_by' => Yii::t('app', 'Last Updated By'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
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
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }

    /**
     * Gets query for [[LastUpdatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLastUpdatedBy()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'last_updated_by']);
    }

    function numberResolvingIssues()
    {
        $count_contract = yii\helpers\ArrayHelper::map(\backend\modules\collection\models\Collection::find()->all(), 'contract_id', 'contract_id');
        $count_contract = count($count_contract);
        if($count_contract != 0){
            $count_contract = $count_contract;
        }
        return $count_contract;
    }

    function availableToCatch()
    {
        $amount = 0;
        $all_models = \backend\modules\collection\models\Collection::find()->all();
        foreach ($all_models as $model) {
            $d1 = new \DateTime($model->date);
            $d2 = new \DateTime(date('Y-m-d'));
            $interval = $d1->diff($d2);
            $diffInMonths = $interval->m; //4
            $diffInMonths = $diffInMonths + 1; //4
            $revares_courts = \backend\modules\financialTransaction\models\FinancialTransaction::find()->where(['contract_id' => $model->contract_id])->andWhere(['income_type' => 11])->all();
            $revares = 0;
            foreach ($revares_courts as $revares_court) {
                $revares = $revares + $revares_court->amount;
            }
            $value = ($diffInMonths * $model->amount) - $revares;
            $amount = $amount + $value;
        }
        return $amount;
    }
     function findCustamer($id){
        $model = Customers::findOne(['id'=>$id]);

        return $model->name;
     }
}
