<?php

namespace backend\modules\customers\models;

/**
 * ActiveRecord for `os_customer_ss_salaries`.
 *
 * One row per yearly salary entry appearing on a Social Security statement.
 *
 * @property int         $id
 * @property int         $statement_id
 * @property int         $customer_id
 * @property int         $year
 * @property string|null $salary
 * @property string|null $establishment_no
 * @property string|null $establishment_name
 *
 * @property CustomerSsStatement $statement
 */
class CustomerSsSalary extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%customer_ss_salaries}}';
    }

    public function rules()
    {
        return [
            [['statement_id', 'customer_id', 'year'], 'required'],
            [['statement_id', 'customer_id', 'year'], 'integer'],
            [['salary'], 'number'],
            [['establishment_no'], 'string', 'max' => 64],
            [['establishment_name'], 'string', 'max' => 255],
        ];
    }

    public function getStatement()
    {
        return $this->hasOne(CustomerSsStatement::class, ['id' => 'statement_id']);
    }
}
