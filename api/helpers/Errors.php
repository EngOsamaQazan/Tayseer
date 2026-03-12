<?php

namespace api\helpers;

use api\modules\v1\models\Countries;

class Errors {

    public static function prepar($errors) {
        return array_map('current', array_values($errors));
    }

}
