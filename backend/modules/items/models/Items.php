<?php

namespace backend\modules\items\models;

use Yii;

/**
 * This is the model class for table "{{%items}}".
 *
 * @property int $id
 * @property string $name
 * @property int $cost
 * @property int $price
 * @property int $invoice_number
 * @property string $notes
 * @property int $is_sold
 * @property int $sold_to
 *
 * @property Installment[] $installments
 * @property Customers $soldTo
 * @property Invoice $invoiceNumber
 */
class Items extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%items}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'cost', 'price', 'invoice_number'], 'required'],
            [['cost', 'price', 'invoice_number', 'is_sold', 'sold_to'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['notes'], 'string', 'max' => 250],
            [['sold_to'], 'exist', 'skipOnError' => true, 'targetClass' => Customers::className(), 'targetAttribute' => ['sold_to' => 'id']],
            [['invoice_number'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::className(), 'targetAttribute' => ['invoice_number' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'cost' => Yii::t('app', 'Cost'),
            'price' => Yii::t('app', 'Price'),
            'invoice_number' => Yii::t('app', 'Invoice Number'),
            'notes' => Yii::t('app', 'Notes'),
            'is_sold' => Yii::t('app', 'Is Sold'),
            'sold_to' => Yii::t('app', 'Sold To'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstallments()
    {
        return $this->hasMany(Installment::className(), ['item_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSoldTo()
    {
        return $this->hasOne(Customers::className(), ['id' => 'sold_to']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceNumber()
    {
        return $this->hasOne(Invoice::className(), ['id' => 'invoice_number']);
    }

    /**
     * {@inheritdoc}
     * @return ItemsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ItemsQuery(get_called_class());
    }
}
