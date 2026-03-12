<?php

namespace backend\modules\divisionsCollection\models;

use Yii;

/**
 * This is the model class for table "os_divisions_collection".
 *
 * @property int $id
 * @property int|null $collection_id
 * @property int|null $month
 * @property float|null $amount
 * @property int|null $created_at
 * @property int|null $year
 * @property int|null $updated_at
 * @property int|null $created_by
 * @property int|null $last_updated_by
 * @property int|null $is_deleted
 */
class DivisionsCollection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_divisions_collection';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['collection_id', 'month', 'created_at', 'updated_at', 'year','created_by', 'last_updated_by', 'is_deleted'], 'integer'],
            [['amount'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'collection_id' => 'Collection ID',
            'month' => 'Month',
            'amount' => 'Amount',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'created_by' => 'Created By',
            'last_updated_by' => 'Last Updated By',
            'year' => 'Year',
            'is_deleted' => 'Is Deleted',
        ];
    }
}
