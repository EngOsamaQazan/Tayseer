<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of model
 *
 * @author elsabeeh
 */
class Model extends \yii\db\ActiveRecord {

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' =>time(),
            ],
        ];
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            $this->created_by = Yii::$app->user->id;
            return true;
        }
    }

    /**
     * Creates and populates a set of models.
     *
     * @param string $modelClass
     * @param array $multipleModels
     * @return array
     */
    public static function createMultiple($modelClass, $multipleModels = []) {
        $model = new $modelClass;
        $formName = $model->formName();
        $post = Yii::$app->request->post($formName);
        $models = [];

        if (!empty($multipleModels)) {
            $keys = array_keys(yii\helpers\ArrayHelper::map($multipleModels, 'id', 'id'));
            // حماية: إذا اختلف عدد العناصر بسبب تكرار المعرفات (IDs مكررة أو فارغة)
            if (count($keys) === count($multipleModels)) {
                $multipleModels = array_combine($keys, $multipleModels);
            } else {
                // إعادة فهرسة باستخدام المعرفات المتاحة مع تجاهل التكرارات
                $reindexed = [];
                foreach ($multipleModels as $m) {
                    $id = isset($m->id) ? $m->id : null;
                    if ($id !== null && $id !== '') {
                        $reindexed[$id] = $m;
                    }
                }
                $multipleModels = $reindexed;
            }
        }

        if ($post && is_array($post)) {
            foreach ($post as $i => $item) {
                if (isset($item['id']) && !empty($item['id']) && isset($multipleModels[$item['id']])) {
                    $models[] = $multipleModels[$item['id']];
                } else {
                    $models[] = new $modelClass;
                }
            }
        }

        unset($model, $formName, $post);

        return $models;
    }

}
