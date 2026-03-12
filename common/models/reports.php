<?php

namespace app\models;

use Yii;
use app\models\Shares;

/**
 * This is the model class for table "{{%customers}}".
 *
 * @property int $id
 * @property string $name
 *
 * @property Installment[] $installments
 * @property Items[] $items
 */
use app\models\Installment;
class reports extends \yii\db\ActiveRecord {

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTotalShares() {
        return Shares::find()->sum();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTotalUnPaidInstallment() {
     return Installment::find()->select(['sum(total)',' Year(date) AS year','Month(date) AS month'])->where(['is_made_payment' => 0])->groupBy([' Year(`date`)','Month(`date`)']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserTotalPaidInstallment() {
//        return Installment::
    }

    /**
     * @inheritdoc
     * @return CustomersQuery the active query used by this AR class.
     */
    public static function find() {
        return new CustomersQuery(get_called_class());
    }

}
