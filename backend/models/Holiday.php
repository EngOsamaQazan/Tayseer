<?php

namespace backend\models;

use Yii;

/**
 * @property int $id
 * @property string $holiday_date
 * @property string $name
 * @property int $year
 * @property string $source  api|manual
 * @property int $created_at
 */
class Holiday extends \yii\db\ActiveRecord
{
    const SOURCE_API    = 'api';
    const SOURCE_MANUAL = 'manual';

    public static function tableName()
    {
        return 'os_official_holidays';
    }

    public function rules()
    {
        return [
            [['holiday_date', 'name', 'year'], 'required'],
            [['holiday_date'], 'date', 'format' => 'php:Y-m-d'],
            [['holiday_date'], 'unique'],
            [['name'], 'string', 'max' => 255],
            [['year', 'created_at'], 'integer'],
            [['source'], 'in', 'range' => [self::SOURCE_API, self::SOURCE_MANUAL]],
            [['source'], 'default', 'value' => self::SOURCE_MANUAL],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'holiday_date' => 'تاريخ العطلة',
            'name' => 'اسم العطلة',
            'year' => 'السنة',
            'source' => 'المصدر',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert && !$this->created_at) {
            $this->created_at = time();
        }
        return true;
    }
}
