<?php

namespace backend\modules\shares\models;

/**
 * This is the ActiveQuery class for [[Shares]].
 *
 * @see Shares
 */
class SharesQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Shares[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Shares|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
