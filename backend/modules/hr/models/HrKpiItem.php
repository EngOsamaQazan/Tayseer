<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%hr_kpi_item}}".
 * بنود مؤشرات الأداء
 *
 * @property int $id
 * @property int $template_id
 * @property string $name
 * @property float|null $weight
 * @property float|null $max_score
 * @property string|null $description
 * @property int|null $sort_order
 */
class HrKpiItem extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_kpi_item}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['template_id', 'name'], 'required'],
            [['template_id', 'sort_order'], 'integer'],
            [['weight', 'max_score'], 'number'],
            [['name'], 'string', 'max' => 150],
            [['description'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'المعرف'),
            'template_id' => Yii::t('app', 'النموذج'),
            'name' => Yii::t('app', 'اسم المؤشر'),
            'weight' => Yii::t('app', 'الوزن'),
            'max_score' => Yii::t('app', 'الدرجة القصوى'),
            'description' => Yii::t('app', 'الوصف'),
            'sort_order' => Yii::t('app', 'الترتيب'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTemplate()
    {
        return $this->hasOne(HrKpiTemplate::class, ['id' => 'template_id']);
    }
}
