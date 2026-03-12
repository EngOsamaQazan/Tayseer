<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

/**
 * This is the model class for table "{{%hr_evaluation}}".
 * تقييمات الأداء
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $evaluator_id
 * @property int|null $template_id
 * @property string|null $evaluation_period
 * @property string|null $evaluation_date
 * @property float|null $total_score
 * @property string|null $rating
 * @property string|null $status
 * @property string|null $comments
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrEvaluation extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_evaluation}}';
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
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::class,
                'softDeleteAttributeValues' => [
                    'is_deleted' => true,
                ],
                'replaceRegularDelete' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'evaluator_id', 'template_id', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['total_score'], 'number'],
            [['evaluation_period'], 'string', 'max' => 50],
            [['evaluation_date'], 'safe'],
            [['rating'], 'string', 'max' => 30],
            [['status'], 'string', 'max' => 30],
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
            'user_id' => Yii::t('app', 'الموظف'),
            'evaluator_id' => Yii::t('app', 'المُقيّم'),
            'template_id' => Yii::t('app', 'نموذج التقييم'),
            'evaluation_period' => Yii::t('app', 'فترة التقييم'),
            'evaluation_date' => Yii::t('app', 'تاريخ التقييم'),
            'total_score' => Yii::t('app', 'الدرجة الإجمالية'),
            'rating' => Yii::t('app', 'التصنيف'),
            'status' => Yii::t('app', 'الحالة'),
            'comments' => Yii::t('app', 'الملاحظات'),
            'is_deleted' => Yii::t('app', 'محذوف'),
            'created_at' => Yii::t('app', 'تاريخ الإنشاء'),
            'created_by' => Yii::t('app', 'أنشئ بواسطة'),
            'updated_at' => Yii::t('app', 'تاريخ التعديل'),
            'updated_by' => Yii::t('app', 'عُدّل بواسطة'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvaluator()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'evaluator_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTemplate()
    {
        return $this->hasOne(HrKpiTemplate::class, ['id' => 'template_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getScores()
    {
        return $this->hasMany(HrEvaluationScore::class, ['evaluation_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     */
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
