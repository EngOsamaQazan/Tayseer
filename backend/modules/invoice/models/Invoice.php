<?php

namespace backend\modules\invoice\models;

use Yii;

/**
 * This is the model class for table "{{%invoice}}".
 *
 * @property int $id
 * @property string $title
 * @property int $number
 * @property int $total
 * @property string $date
 * 
 * @property Items[] $items
 */
class Invoice extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%invoice}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'number', 'total', 'date'], 'required'],
            [['number', 'total'], 'integer'],
            [['date'], 'safe'],
            [['title'], 'string', 'max' => 250],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'number' => Yii::t('app', 'Number'),
            'total' => Yii::t('app', 'Total'),
            'date' => Yii::t('app', 'Date'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(Items::className(), ['invoice_number' => 'id']);
    }

    /**
     * @inheritdoc
     * @return invoiceQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new invoiceQuery(get_called_class());
    }
}
