<?php

namespace backend\modules\shareholders\models;

/**
 * This is the ActiveQuery class for [[Shareholders]].
 *
 * @see Shareholders
 */
class ShareholdersQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Shareholders[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Shareholders|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
