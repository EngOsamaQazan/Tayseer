<?php

namespace backend\modules\LawyersImage\models;

use Yii;

/**
 * This is the model class for table "os_lawyers_image".
 *
 * @property int $id
 * @property int|null $lawyer_id
 * @property string|null $image
 */
class LawyersImage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_lawyers_image';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['lawyer_id'], 'integer'],
            [['image'],'string']
       
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'lawyer_id' => 'Lawyer ID',
            'image' => 'Image',
        ];
    }
    
}
