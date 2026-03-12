<?php

namespace common\components;

use backend\modules\authAssignment\models\AuthAssignment;
use common\models\User;
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

    public function setReadedAll()
    {
        $models = Notification::find()->all();
        foreach ($models as $model) {
            if ($model) {
                $model->is_unread = 0;
                $model->save(false);
            } else {
                return Yii::t('app', 'result not found');
            }
        }


    }

    public function sendByRule($rule, $href, $type_of_notification, $title_html, $body_html, $sender_id)
    {

        $recipients = AuthAssignment::find()->where(['in', 'item_name', $rule])->all();
        foreach ($recipients as $recipient) {
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
            $model->recipient_id = $recipient->user_id;
            $model->created_time = time();
            $model->save();
        }

    }

    public function getSender()
    {
        return $this->hasOne(User::className(), ['id' => 'sender_id']);
    }

    public function getRecipient()
    {
        return $this->hasOne(User::className(), ['id' => 'recipient_id']);
    }

    public function setHidden($id)
    {
        $model = Notification::findOne(['id' => $id]);
        if ($model) {
            $model->is_hidden = 0;
            $model->save(false);
        } else {
            return Yii::t('app', 'result not found');
        }
    }

}

?>