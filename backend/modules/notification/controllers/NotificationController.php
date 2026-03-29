<?php

namespace backend\modules\notification\controllers;

use Yii;
use backend\modules\notification\models\Notification;
use backend\modules\notification\models\NotificationSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\helpers\Html;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class NotificationController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['is-read', 'mark-read', 'see-all-msg', 'poll', 'center'],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'bulk-delete'],
                        'roles' => ['الاشعارات'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'bulk-delete' => ['POST'],
                    'is-read' => ['POST'],
                    'mark-read' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new NotificationSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "الإشعار #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button(Yii::t('app', 'إغلاق'), ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal']),
            ];
        }
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new Notification();
        $model->sender_id = Yii::$app->user->id;
        $model->is_unread = 1;
        $model->is_hidden = 0;
        $model->created_time = time();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'تم إرسال الإشعار بنجاح'));
            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'تم تحديث الإشعار بنجاح'));
            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    public function actionBulkDelete()
    {
        $pks = explode(',', Yii::$app->request->post('pks'));
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    /**
     * Mark all notifications as read for current user (bell dropdown AJAX).
     */
    public function actionIsRead()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $userId = Yii::$app->user->id;
        $count = Notification::updateAll(
            ['is_unread' => 0, 'read_at' => time()],
            ['recipient_id' => $userId, 'is_unread' => 1]
        );
        return ['success' => true, 'marked' => $count];
    }

    /**
     * Mark a single notification as read.
     */
    public function actionMarkRead($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = Notification::findOne([
            'id' => $id,
            'recipient_id' => Yii::$app->user->id,
        ]);
        if (!$model) {
            return ['success' => false];
        }
        $model->is_unread = 0;
        $model->read_at = time();
        $model->save(false);
        return ['success' => true];
    }

    /**
     * Notification center: user's notifications with filters.
     */
    public function actionCenter()
    {
        $searchModel = new NotificationSearch();
        $dataProvider = $searchModel->userNotifications(Yii::$app->request->queryParams);

        return $this->render('center', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * AJAX poll endpoint for real-time badge updates.
     */
    public function actionPoll()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $userId = Yii::$app->user->id;
        $since = (int) Yii::$app->request->get('since', 0);

        $count = (int) Notification::find()
            ->where(['recipient_id' => $userId, 'is_unread' => 1])
            ->count();

        $newItems = Notification::find()
            ->where(['recipient_id' => $userId])
            ->andWhere(['>', 'id', $since])
            ->orderBy(['id' => SORT_DESC])
            ->limit(5)
            ->asArray()
            ->all();

        $lastId = $since;
        if (!empty($newItems)) {
            $lastId = (int) $newItems[0]['id'];
        }

        $items = [];
        foreach ($newItems as $n) {
            $items[] = [
                'id' => (int) $n['id'],
                'title' => $n['title_html'] ?: $n['body_html'],
                'type' => (int) $n['type_of_notification'],
                'icon' => Notification::getTypeIcon((int) $n['type_of_notification']),
                'color' => Notification::getTypeColor((int) $n['type_of_notification']),
                'href' => $n['href'] ?: '',
                'time' => $n['created_time'] ? Yii::$app->formatter->asRelativeTime($n['created_time']) : '',
            ];
        }

        return ['count' => $count, 'items' => $items, 'lastId' => $lastId];
    }

    /**
     * @deprecated Use actionCenter() instead
     */
    public function actionSeeAllMsg()
    {
        return $this->redirect(['center']);
    }

    protected function findModel($id)
    {
        if (($model = Notification::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'الصفحة المطلوبة غير موجودة.'));
    }
}
