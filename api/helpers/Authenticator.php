<?php

namespace api\helpers;

use yii\filters\auth\AuthMethod;

class Authenticator {

    public static function test() {
        throw new \Exception('You are not allowed to access this page');
    }

}
