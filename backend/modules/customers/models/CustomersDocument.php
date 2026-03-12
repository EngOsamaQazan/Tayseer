<?php

namespace backend\modules\customers\models;

use Yii;
use common\models\Model;

/**
 * This is the model class for table "{{%customers_document}}".
 *
 * @property int $id
 * @property int $customer_id
 * @property int $created_at
 * @property int $updated_at
 * @property int $created_by
 * @property int $images
 *
 * @property Customers $customer
 */
class CustomersDocument extends Model {

    public $image_manager_id;
    /**
     * {@inheritdoc}
     */
    public static function tableName() {
        return '{{%customers_document}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            [['document_type', 'document_number'], 'required'],
            [['customer_id', 'created_at', 'updated_at', 'created_by'], 'integer'],
            [['customer_images','customer_id','image_manager_id','document_image','images'], 'safe'],
            [['document_type', 'document_number'], 'string', 'max' => 100],
            [['document_image', 'images'], 'string', 'max' => 255],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customers::className(), 'targetAttribute' => ['customer_id' => 'id']],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels() {
        return [
            'id' => Yii::t('app', 'ID'),
            'customer_id' => Yii::t('app', 'Customer ID'),
            'document_type' => Yii::t('app', 'Document Type'),
            'document_number' => Yii::t('app', 'Document Number'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
        ];
    }

    /**
     * Gets query for [[Customer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer() {
        return $this->hasOne(Customers::className(), ['id' => 'customer_id']);
    }


}
