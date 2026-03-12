<?php

namespace api\helpers;

class Messages {

    public static function t($message, $category = 'api/translations', $params = [], $lang = null) {
        $lang = (($lang == null) ? \Yii::$app->language : $lang);
        return \Yii::t("{$category}/$lang", $message, $params);
    }
}
