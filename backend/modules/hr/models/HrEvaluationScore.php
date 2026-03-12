<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%hr_evaluation_score}}".
 * درجات التقييم
 *
 * @property int $id
 * @property int $evaluation_id
 * @property int $kpi_item_id
 * @property float|null $score
 * @property string|null $comments
 */
class HrEvaluationScore extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_evaluation_score}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['evaluation_id', 'kpi_item_id'], 'required'],
            [['evaluation_id', 'kpi_item_id'], 'integer'],
            [['score'], 'number'],
            [['comments'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'المعرف'),
            'evaluation_id' => Yii::t('app', 'التقييم'),
            'kpi_item_id' => Yii::t('app', 'مؤشر الأداء'),
            'score' => Yii::t('app', 'الدرجة'),
            'comments' => Yii::t('app', 'الملاحظات'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvaluation()
    {
        return $this->hasOne(HrEvaluation::class, ['id' => 'evaluation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getKpiItem()
    {
        return $this->hasOne(HrKpiItem::class, ['id' => 'kpi_item_id']);
    }
}
