<?php

namespace backend\modules\followUp\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $text
 * @property int|null $created_by
 * @property string $created_at
 */
class SmsDraft extends ActiveRecord
{
    const MAX_DRAFTS = 10;

    public static function tableName()
    {
        return '{{%sms_drafts}}';
    }

    public function rules()
    {
        return [
            [['name', 'text'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['text'], 'string'],
            [['created_by'], 'integer'],
            [['created_at'], 'safe'],
        ];
    }

    public static function getAllDrafts()
    {
        return static::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(self::MAX_DRAFTS)
            ->asArray()
            ->all();
    }

    public static function saveDraft($name, $text)
    {
        $count = static::find()->count();
        if ($count >= self::MAX_DRAFTS) {
            $oldest = static::find()->orderBy(['created_at' => SORT_ASC])->one();
            if ($oldest) $oldest->delete();
        }

        $model = new static();
        $model->name = $name;
        $model->text = $text;
        $model->created_by = Yii::$app->user->id ?? null;
        return $model->save();
    }
}
