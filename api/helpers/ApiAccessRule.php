<?php

namespace api\helpers;

use yii\filters\AccessRule;
use api\helpers\ApiResponse;

class ApiAccessRule extends AccessRule {

    protected function matchRole($user) {

        if (empty($this->roles)) {
            return true;
        }
        foreach ($this->roles as $role) {
            if ($role === '?' && $user->getIsGuest()) {
                return true;
            } elseif ($role === '@' && !$user->getIsGuest()) {
                return true;
            } elseif (!$user->getIsGuest()) {
               return true;
            }
        }
        return ApiResponse::get(403);
    }

}
