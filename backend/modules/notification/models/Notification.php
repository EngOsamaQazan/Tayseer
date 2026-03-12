<?php

namespace backend\modules\notification\models;

use Yii;

/**
 * This is the model class for table "os_notification".
 *
 * @property int $id
 * @property int|null $sender_id
 * @property int|null $recipient_id
 * @property int|null $type_of_notification
 * @property string|null $title_html
 * @property string|null $body_html
 * @property string|null $href
 * @property string|null $is_unread
 * @property string|null $is_hidden
 * @property int|null $created_time
 */
class Notification extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    const IS_UNREAD = 'Yes';
    const IS_NOT_UNREAD = 'No';
    const IS_HIDDEN = 'Yes';
    const IS_NOT_HIDDEN = 'No';
    const SYSTEM_SENDER = 0;
    const GENERAL = 1;
    const INVOICE_PENDING_RECEPTION = 1001;
    const INVOICE_PENDING_MANAGER = 1003;

    public static function tableName()
    {
        return 'os_notification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sender_id', 'recipient_id', 'type_of_notification', 'created_time', 'is_unread', 'is_hidden'], 'integer'],
            [['body_html'], 'string'],
            [['title_html', 'href'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'sender_id' => Yii::t('app', 'Sender ID'),
            'recipient_id' => Yii::t('app', 'Recipient ID'),
            'type_of_notification' => Yii::t('app', 'Type Of Notification'),
            'title_html' => Yii::t('app', 'Title Html'),
            'body_html' => Yii::t('app', 'Body Html'),
            'href' => Yii::t('app', 'Href'),
            'is_unread' => Yii::t('app', 'Is Unread'),
            'is_hidden' => Yii::t('app', 'Is Hidden'),
            'created_time' => Yii::t('app', 'Created Time'),
        ];
    }
    public function getSender()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'sender_id']);
    }

    public function getRecipient()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'recipient_id']);
    }
}
