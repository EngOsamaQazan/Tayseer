<?php

namespace backend\modules\income\models;

/**
 * This is the ActiveQuery class for [[Income]].
 *
 * @see Income
 */
class IncomeQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Income[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Income|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
