<?php

namespace backend\modules\notification\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\notification\models\Notification;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * NotificationSearch represents the model behind the search form about `common\models\Notification`.
 */
class NotificationSearch extends Notification
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_time',
                'value' => time(),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['id', 'sender_id', 'recipient_id', 'type_of_notification', 'is_unread', 'is_hidden'], 'integer'],
            [['title_html', 'body_html', 'href'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Notification::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'recipient_id' => $this->recipient_id,
            'type_of_notification' => $this->type_of_notification,
            'is_unread' => $this->is_unread,
            'is_hidden' => $this->is_hidden,
            'created_time' => $this->created_time,
        ]);

        $query->andFilterWhere(['like', 'title_html', $this->title_html])
            ->andFilterWhere(['like', 'body_html', $this->body_html])
            ->andFilterWhere(['like', 'href', $this->href])->orderBy(['created_time'=>SORT_DESC]);

        return $dataProvider;
    }

    public function searchCounter($params)
    {
        $query = Notification::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'recipient_id' => $this->recipient_id,
            'type_of_notification' => $this->type_of_notification,
            'is_unread' => $this->is_unread,
            'is_hidden' => $this->is_hidden,
            'created_time' => $this->created_time,
        ]);

        $query->andFilterWhere(['like', 'title_html', $this->title_html])
            ->andFilterWhere(['like', 'body_html', $this->body_html])
            ->andFilterWhere(['like', 'href', $this->href]);

        return $query->count();
    }

    public function allUserMsg($params)
    {
        $query = Notification::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'type_of_notification' => $this->type_of_notification,
            'is_unread' => $this->is_unread,
            'is_hidden' => $this->is_hidden,
            'created_time' => $this->created_time,
        ]);

        $query->andFilterWhere(['like', 'title_html', $this->title_html])
            ->andFilterWhere(['like', 'body_html', $this->body_html])
            ->andFilterWhere(['like', 'href', $this->href]);
        $query->where(['recipient_id' => Yii::$app->user->id])->orderBy(['created_time'=>SORT_DESC]);
        return $dataProvider;
    }
}
