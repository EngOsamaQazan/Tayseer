<?php

namespace common\components;

use backend\modules\authAssignment\models\AuthAssignment;
use Yii;
use yii\base\Component;
use backend\modules\notification\models\Notification;

class notificationComponent extends Component
{

    public function init()
    {
        parent::init();
    }

    public function add($href, $type_of_notification, $title_html, $body_html, $sender_id, $recipient_id)
    {

        $model = new Notification();
        if ($sender_id) {
            $model->sender_id = $sender_id;
        } else {
            $model->sender_id = Yii::$app->user->id;
        }
        $model->type_of_notification = $type_of_notification;
        $model->title_html = $title_html;
        $model->body_html = $body_html;
        $model->href = $href;
        $model->is_unread = 1;
        $model->is_hidden = 0;
        $model->recipient_id = $recipient_id;
        $model->created_time = time();
        $model->save();
    }

    public function setReaded($id)
    {
        $model = Notification::findOne(['id' => $id]);
        if ($model) {
            $model->is_unread = 0;
            $model->save(false);
        } else {
            return Yii::t('app', 'result not found');
        }


    }

    public function setReadedAll($userId = null)
    {
        $uid = $userId ?: Yii::$app->user->id;
        Notification::updateAll(['is_unread' => 0], ['recipient_id' => $uid, 'is_unread' => 1]);
    }

    public function sendByRule($rule, $href, $type_of_notification, $title_html, $body_html, $sender_id)
    {
        $actualSenderId = $sender_id ?: Yii::$app->user->id;
        $recipientIds = AuthAssignment::find()
            ->select('user_id')
            ->where(['in', 'item_name', $rule])
            ->column();
        $recipientIds = array_unique($recipientIds);

        foreach ($recipientIds as $rid) {
            if ((int)$rid === (int)$actualSenderId) {
                continue;
            }
            $this->add($href, $type_of_notification, $title_html, $body_html, $actualSenderId, (int)$rid);
        }
    }

    public function setHidden($id)
    {
        $model = Notification::findOne(['id' => $id]);
        if ($model) {
            $model->is_hidden = 1;
            $model->save(false);
        } else {
            return Yii::t('app', 'result not found');
        }
    }

}

?>