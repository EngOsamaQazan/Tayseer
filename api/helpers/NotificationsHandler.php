<?php

namespace api\helpers;

use Yii;
use api\helpers\PushNotifications;
use backend\modules\notification\models\Notification;
use yii\db\Query;

/**
 * All methods in this class must be work as a background proccess
 */
class NotificationsHandler
{

    /**
     *
     * @param int $from_user_id
     * @param int $to_user_id
     * @param int $entity_id
     * @param int $type_id
     * @param json $params
     * @param bool $is_request
     */
    public static function send($from_user_id, $to_user_id, $type_id, $entity_id = 0, $send_push_notification = false, $params = null)
    {
        if ($to_user_id == Yii::$app->user->id) {
            return true;
        }

        $model = new Notification();
        $model->sender_id = $from_user_id;
        $model->recipient_id = $to_user_id;
        $model->type_of_notification = $type_id;
        $model->title_html = '';
        $model->body_html = is_array($params) ? json_encode($params) : '';
        $model->href = '';
        $model->is_unread = 1;
        $model->is_hidden = 0;
        $model->created_time = time();
        $isSaved = $model->save();

        if ($isSaved && $send_push_notification) {
            self::sendPushNotification($to_user_id, $type_id, $params);
        }
        return $isSaved;
    }

    public static function sendPushNotification($to_user_id, $type_id, $params)
    {
        if (!is_array($to_user_id)) {
            $to_user_id = [$to_user_id];
        }

        $users = implode(',', $to_user_id);

        $query = (new Query())
            ->select(['device_token' => 'deviceToken.token_id', 'device_os' => 'deviceToken.device_os'])
            ->from('device_token deviceToken')
            ->innerJoin('user userProfile', "userProfile.id = deviceToken.user_id")
            ->where(['deviceToken.user_id' => $users]);

        $tparams = [
            'projectName' => $projectName,
            'type' => $type,
        ];

        $message = Messages::t($type_id, 'api/translations', $tparams);
        $message = str_replace(':projectName', '{projectName}', $message);
        $message = str_replace(':type', '{type}', $message);
        $message = Messages::t($message, 'api/translations', $tparams);

        foreach ($query->batch(1000) as $tokens) {
            foreach ($tokens as $token) {
                if ($token != 'undefined') {
                    $data = ['message' => $message];
                    if ($token['device_os'] == 1 && YII_ENV == 'prod') {
                        PushNotifications::android($data, $token['device_token']);
                    } elseif ($token['device_os'] == 2) {
                        PushNotifications::ios($data, $token['device_token']);
                    }
                }
            }
        }
    }

}
