<?php

namespace backend\modules\customers\models;

/**
 * ActiveRecord for `os_customer_ss_subscriptions`.
 *
 * One row per subscription period appearing on a Social Security statement.
 *
 * @property int         $id
 * @property int         $statement_id
 * @property int         $customer_id
 * @property string|null $from_date          YYYY-MM-DD
 * @property string|null $to_date            YYYY-MM-DD; null = active period
 * @property string|null $salary
 * @property string|null $reason
 * @property string|null $establishment_no
 * @property string|null $establishment_name
 * @property int|null    $months
 * @property int         $sort_order
 *
 * @property CustomerSsStatement $statement
 */
class CustomerSsSubscription extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%customer_ss_subscriptions}}';
    }

    public function rules()
    {
        return [
            [['statement_id', 'customer_id'], 'required'],
            [['statement_id', 'customer_id', 'months', 'sort_order'], 'integer'],
            [['from_date', 'to_date'], 'date', 'format' => 'php:Y-m-d'],
            [['salary'], 'number'],
            [['reason', 'establishment_no'], 'string', 'max' => 64],
            [['establishment_name'], 'string', 'max' => 255],
        ];
    }

    public function getStatement()
    {
        return $this->hasOne(CustomerSsStatement::class, ['id' => 'statement_id']);
    }

    /**
     * Convenience flag — period is currently active when there is no
     * `to_date` (the SS payload uses null for "still subscribed").
     */
    public function isActive(): bool
    {
        return empty($this->to_date);
    }
}
