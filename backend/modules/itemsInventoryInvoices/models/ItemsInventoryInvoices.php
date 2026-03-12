<?php

namespace backend\modules\itemsInventoryInvoices\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

/**
 * This is the model class for table "os_items_inventory_invoices".
 *
 * @property int $id
 * @property int|null $number
 * @property float|null $single_price
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $created_by
 * @property int|null $inventory_items_id
 * @property int|null $inventory_invoices_id
 * @property int|null $last_updated_by
 * @property int|null $total_amount
 * @property int|null $is_deleted
 */
class ItemsInventoryInvoices extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_items_inventory_invoices';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_updated_by',
            ],
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'is_deleted' => true
                ],

                'replaceRegularDelete' => true // mutate native `delete()` method
            ],

        ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['number','inventory_items_id','single_price'],'required'],
            [['number', 'created_at', 'updated_at', 'created_by', 'inventory_items_id', 'inventory_invoices_id', 'last_updated_by', 'is_deleted'], 'integer'],
            [['single_price','total_amount'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'number' => 'Number',
            'single_price' => 'Single Price',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'created_by' => 'Created By',
            'inventory_items_id' => 'Inventory Items ID',
            'inventory_invoices_id' => 'Inventory Invoices ID',
            'last_updated_by' => 'Last Updated By',
            'is_deleted' => 'Is Deleted',
        ];
    }
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query->notDeleted();
    }
}
