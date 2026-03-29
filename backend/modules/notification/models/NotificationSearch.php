<?php

namespace backend\modules\notification\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class NotificationSearch extends Notification
{
    public function rules()
    {
        return [
            [['id', 'sender_id', 'recipient_id', 'type_of_notification', 'is_unread', 'is_hidden'], 'integer'],
            [['title_html', 'body_html', 'href'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Notification::find()->with(['sender', 'recipient']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['created_time' => SORT_DESC]],
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'recipient_id' => $this->recipient_id,
            'type_of_notification' => $this->type_of_notification,
            'is_unread' => $this->is_unread,
            'is_hidden' => $this->is_hidden,
        ]);

        $query->andFilterWhere(['like', 'title_html', $this->title_html])
            ->andFilterWhere(['like', 'body_html', $this->body_html])
            ->andFilterWhere(['like', 'href', $this->href]);

        return $dataProvider;
    }

    /**
     * Current user's notifications with optional filters.
     */
    public function userNotifications($params)
    {
        $query = Notification::find()
            ->where(['recipient_id' => Yii::$app->user->id])
            ->with(['sender']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['created_time' => SORT_DESC]],
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'type_of_notification' => $this->type_of_notification,
            'is_unread' => $this->is_unread,
            'is_hidden' => $this->is_hidden,
        ]);

        $query->andFilterWhere(['like', 'title_html', $this->title_html])
            ->andFilterWhere(['like', 'body_html', $this->body_html]);

        return $dataProvider;
    }
}
