<?php

namespace backend\modules\LeaveTypes\models;

use Yii;

/**
 * This is the model class for table "{{%leave_types}}".
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property int $created_by
 * @property int $created_at
 * @property int|null $updated_at
 */
class LeaveTypes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%leave_types}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'title', 'created_by', 'created_at'], 'required'],
            [['id', 'created_by', 'created_at', 'updated_at'], 'integer'],
            [['status'], 'string'],
            [['title'], 'string', 'max' => 50],
            [['description'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'status' => Yii::t('app', 'Status'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
