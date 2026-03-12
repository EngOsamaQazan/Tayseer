<?php

namespace backend\modules\employee\models;

use Yii;
use common\models\User;

/**
 * This is the model class for table "os_employee_files".
 *
 * @property int $id
 * @property int $user_id
 * @property int $type 0:Avatar | 1:Attachment
 * @property string $file_name
 * @property string $path
 *
 * @property User $user
 */
class EmployeeFiles extends \yii\db\ActiveRecord
{
    const TYPE_AVATAR = 0;
    const TYPE_ATTACHMENT = 1;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_employee_files';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'type', 'file_name', 'path'], 'required'],
            [['user_id', 'type'], 'integer'],
            [['file_name', 'path'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'type' => Yii::t('app', 'Type'),
            'file_name' => Yii::t('app', 'File Name'),
            'path' => Yii::t('app', 'Path'),
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
