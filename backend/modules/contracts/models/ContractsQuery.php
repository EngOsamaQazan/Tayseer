<?php

namespace backend\modules\contracts\models;

/**
 * This is the ActiveQuery class for [[Contracts]].
 *
 * @see Contracts
 */
class ContractsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return Contracts[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return Contracts|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
