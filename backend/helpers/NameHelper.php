<?php

namespace backend\helpers;

use OsamaQazan\ArabicName\Shortener;

class NameHelper
{
    public static function short(string $full): string
    {
        return Shortener::shorten($full);
    }
}
