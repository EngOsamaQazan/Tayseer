<?php

namespace api\modules\v1;

use Yii;
use api\helpers\RequestHandler;
use common\models\User;
use api\modules\v1\models\Settings;

class Module extends \yii\base\Module {

    public $controllerNamespace = 'api\modules\v1\controllers';

    public function init() {
        parent::init();
        $this->beforeInit();
        $this->prepareUserRequest();
    }

    public function beforeInit() {
        //header('Access-Control-Allow-Origin: *');
        clearstatcache();
        \Yii::$app->user->enableSession = false;
    }

    public function prepareUserRequest() {
        $this->checkRequestToken();
        $this->setupSession();
        $this->setupLanguage();
    }

    public function setupSession() {
        clearstatcache();
        \Yii::$app->user->enableSession = false;
    }

    public function setupLanguage() {
        $languages = Yii::$app->params['languages'];
        $language_id = RequestHandler::get('language_id');
        $language_id = (!empty($language_id)) ? $language_id : 1;
        if (!empty($language_id) && isset($languages[$language_id])) {
            $lang = $languages[$language_id];
            \Yii::$app->language = $lang;
        } else
            \Yii::$app->language = 'en-US';
        $key = \Yii::$app->params['languages_keys'][Yii::$app->language];
        \Yii::$app->params['autoName'] = "{$key}_name";
        \Yii::$app->params['language_id'] = $language_id;
    }

    public function checkRequestToken() {
        $accessToken = RequestHandler::get('auth_key');
        $identity = User::findIdentityByAccessToken($accessToken);
        if ($identity != null && $accessToken != null) {
            return \Yii::$app->user->login($identity, 0);
        }
        return false;
    }

}
