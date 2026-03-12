<?php

namespace api\helpers;

use api\helpers\Messages;
use yii\helpers\Json;

class ApiResponse
{

    /**
     * @param integer $code # reponse status code
     * @param array $data # On success data
     * @param array $errors # On error data
     */
    public static function get($code = 200, $data = [], $errors = [], $extra = [])
    {
        header('Content-Type: application/json');
        header("HTTP/1.1 {$code}");

        //header('Access-Control-Allow-Origin: *');
        $data = (empty($data) ? [] : $data);
        $errors = (empty($errors) ? [] : $errors);
        $extra = (empty($extra) ? [] : [$extra]);

        $response = [
            'code' => "{$code}",
            'message' => Messages::t($code, 'api/response'),
            'data' => (!is_array($data)) ? [$data] : $data,
            'errors' => (!is_array($errors)) ? [$errors] : $errors,
            'extra' => (!is_array($extra)) ? [$extra] : $extra,
        ];

        exit(Json::encode($response));
    }
    /**
     * @param integer $code # reponse status code
     * @param array $data # On success data
     * @param array $errors # On error data
     */
    public static function get_flat($data = [])
    {
        header('Content-Type: application/json');

        //header('Access-Control-Allow-Origin: *');
        $data = (empty($data) ? [] : $data);

        $response = [
            $data
        ];

        exit(Json::encode($response));
    }
}
