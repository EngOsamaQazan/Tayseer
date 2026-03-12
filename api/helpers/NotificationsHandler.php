<?php

namespace api\helpers;

use Yii;
use api\helpers\ApiResponse;
use api\helpers\Messages;
use api\helpers\PushNotifications;
use common\models\Notification;
use common\models\DeviceToken;
use yii\db\Query;
use common\models\NotificationType;
use common\models\Project;
use common\models\RfiType;
use common\models\DailyLogType;
use common\models\PunshListType;
use common\models\MaintenanceType;
use common\models\ChangeOrderType;

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
        return true;
        if ($to_user_id != Yii::$app->user->id) {
            $model = new Notification();
            $data = ['Notification' => [
                'sender_id' => $from_user_id,
                'recipient_id' => $to_user_id,
                'type_of_notification' => $type_id,
                'entity_id' => $entity_id,
                'params' => json_encode($params),
            ]];
            $model->load($data);
            $isSaved = $model->save();
            if ($isSaved && $send_push_notification) {
                self::sendPushNotification($to_user_id, $type_id, $params);
            } else {
                return $isSaved;
            }
        }
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
