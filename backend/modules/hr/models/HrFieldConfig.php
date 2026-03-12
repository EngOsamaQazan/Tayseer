<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "{{%hr_field_config}}".
 *
 * @property int $id
 * @property string|null $config_key
 * @property string|null $config_value
 * @property string|null $config_group
 * @property string|null $description
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrFieldConfig extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_field_config}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['config_key'], 'required'],
            [['created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['config_key'], 'string', 'max' => 100],
            [['config_value'], 'string'],
            [['config_group'], 'string', 'max' => 50],
            [['description'], 'string', 'max' => 255],
            [['config_key'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'config_key' => Yii::t('app', 'مفتاح الاعداد'),
            'config_value' => Yii::t('app', 'قيمة الاعداد'),
            'config_group' => Yii::t('app', 'مجموعة الاعداد'),
            'description' => Yii::t('app', 'الوصف'),
            'created_at' => Yii::t('app', 'تاريخ الانشاء'),
            'created_by' => Yii::t('app', 'انشئ بواسطة'),
            'updated_at' => Yii::t('app', 'تاريخ التعديل'),
            'updated_by' => Yii::t('app', 'عدل بواسطة'),
        ];
    }
}
