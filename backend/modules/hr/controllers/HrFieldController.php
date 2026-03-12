<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use backend\modules\hr\models\HrFieldTask;
use backend\modules\hr\models\HrFieldSession;
use backend\modules\hr\models\HrFieldEvent;
use backend\modules\hr\models\HrFieldConfig;
use backend\modules\hr\models\HrLocationPoint;
use backend\modules\location\models\Location;
use common\models\User;
use common\helper\Permissions;

/**
 * HrFieldController — Field operations management
 * يتطلب أحد صلاحيات الموارد البشرية.
 */
class HrFieldController extends Controller
{
    /**
     * Disable CSRF for API endpoints (mobile fetch calls)
     */
    public function beforeAction($action)
    {
        $apiActions = ['api-start-session', 'api-end-session', 'api-send-location', 'api-tasks', 'api-task-update', 'api-log-event'];
        if (in_array($action->id, $apiActions)) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function ($rule, $action) {
                    // For mobile actions: redirect to our lightweight login instead of heavy backend login
                    $mobileActions = ['mobile', 'api-start-session', 'api-end-session',
                        'api-send-location', 'api-tasks', 'api-task-update', 'api-log-event'];
                    if (in_array($action->id, $mobileActions)) {
                        return $action->controller->redirect(['mobile-login']);
                    }
                    // Default: redirect to standard login
                    return $action->controller->redirect(['/site/login']);
                },
                'rules' => [
                    [
                        'actions' => ['mobile-login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        // نظام الحضور والانصراف متاح لجميع الموظفين المسجّلين دون اشتراط صلاحية معيّنة
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'save-location' => ['POST'],
                    'delete-location' => ['POST'],
                    'api-start-session' => ['POST'],
                    'api-end-session' => ['POST'],
                    'api-send-location' => ['POST'],
                    'api-task-update' => ['POST'],
                    'api-log-event' => ['POST'],
                    'mobile-login' => ['GET', 'POST'],
                ],
            ],
        ];
    }

    /**
     * Field tasks board (Kanban-style view).
     *
     * @return string
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $filterAssignee = $request->get('assignee', '');
        $filterPriority = $request->get('priority', '');
        $filterType = $request->get('task_type', '');

        // Group tasks by status for Kanban board
        $statuses = ['assigned', 'accepted', 'en_route', 'arrived', 'in_progress', 'completed', 'failed', 'cancelled'];
        $kanbanData = [];

        foreach ($statuses as $status) {
            $query = HrFieldTask::find()
                ->where(['status' => $status, 'is_deleted' => 0]);

            if (!empty($filterAssignee)) {
                $query->andWhere(['assigned_to' => $filterAssignee]);
            }
            if (!empty($filterPriority)) {
                $query->andWhere(['priority' => $filterPriority]);
            }
            if (!empty($filterType)) {
                $query->andWhere(['task_type' => $filterType]);
            }

            $kanbanData[$status] = $query->orderBy(['due_date' => SORT_ASC, 'priority' => SORT_DESC])->all();
        }

        // Field staff list for filter
        $fieldStaff = ArrayHelper::map(
            (new Query())
                ->select(['u.id', 'u.username', 'u.name'])
                ->from('{{%user}} u')
                ->innerJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_field_staff = 1 AND ext.is_deleted = 0')
                ->where(['IS', 'u.blocked_at', null])
                ->all(),
            'id',
            function ($row) {
                return $row['name'] ?: $row['username'];
            }
        );

        // Today's task statistics
        $todayStats = Yii::$app->db->createCommand("
            SELECT
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status IN ('assigned','accepted','en_route','arrived','in_progress') THEN 1 ELSE 0 END) as active
            FROM {{%hr_field_task}}
            WHERE DATE(FROM_UNIXTIME(created_at)) = CURDATE()
              AND is_deleted = 0
        ")->queryOne();

        return $this->render('index', [
            'kanbanData' => $kanbanData,
            'statuses' => $statuses,
            'fieldStaff' => $fieldStaff,
            'filterAssignee' => $filterAssignee,
            'filterPriority' => $filterPriority,
            'filterType' => $filterType,
            'todayStats' => $todayStats,
        ]);
    }

    /**
     * Live map showing field staff locations.
     *
     * @return string
     */
    public function actionMap()
    {
        // Get active field sessions with latest location
        $activeSessions = (new Query())
            ->select([
                'fs.id as session_id',
                'fs.user_id',
                'fs.started_at',
                'u.username',
                'u.name',
                'u.avatar',
            ])
            ->from('{{%hr_field_session}} fs')
            ->innerJoin('{{%user}} u', 'u.id = fs.user_id')
            ->where(['fs.status' => 'active'])
            ->all();

        $staffLocations = [];
        foreach ($activeSessions as $session) {
            // Get latest location point for this session
            $lastPoint = (new Query())
                ->select(['latitude', 'longitude', 'accuracy', 'speed', 'captured_at', 'battery_level'])
                ->from('{{%hr_location_point}}')
                ->where(['session_id' => $session['session_id']])
                ->orderBy(['captured_at' => SORT_DESC])
                ->limit(1)
                ->one();

            // Get current task (if any)
            $currentTask = (new Query())
                ->select(['id', 'title', 'status', 'task_type', 'target_address'])
                ->from('{{%hr_field_task}}')
                ->where([
                    'assigned_to' => $session['user_id'],
                    'is_deleted' => 0,
                ])
                ->andWhere(['in', 'status', ['accepted', 'en_route', 'arrived', 'in_progress']])
                ->orderBy(['updated_at' => SORT_DESC])
                ->limit(1)
                ->one();

            $staffLocations[] = [
                'session' => $session,
                'lastPoint' => $lastPoint,
                'currentTask' => $currentTask,
            ];
        }

        // Active sessions count
        $activeSessionCount = count($activeSessions);

        // Tasks in progress today
        $tasksInProgress = (int) Yii::$app->db->createCommand("
            SELECT COUNT(*)
            FROM {{%hr_field_task}}
            WHERE status IN ('accepted','en_route','arrived','in_progress')
              AND is_deleted = 0
        ")->queryScalar();

        $savedLocations = (new Query())
            ->select(['id', 'location as name', 'description', 'latitude', 'longitude', 'radius', 'status'])
            ->from('{{%location}}')
            ->where(['status' => 'active'])
            ->all();

        $request = Yii::$app->request;
        if ($request->isAjax && !$request->get('withLocations')) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'staffLocations' => $staffLocations,
                'activeSessionCount' => $activeSessionCount,
                'tasksInProgress' => $tasksInProgress,
            ];
        }

        if ($request->isAjax && $request->get('withLocations')) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'staffLocations' => $staffLocations,
                'activeSessionCount' => $activeSessionCount,
                'tasksInProgress' => $tasksInProgress,
                'savedLocations' => $savedLocations,
            ];
        }

        return $this->render('map', [
            'staffLocations' => $staffLocations,
            'activeSessionCount' => $activeSessionCount,
            'tasksInProgress' => $tasksInProgress,
            'savedLocations' => $savedLocations,
        ]);
    }

    /**
     * Save (create/update) a named location from the map.
     */
    public function actionSaveLocation()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $id = $request->post('id');

        $model = $id ? Location::findOne($id) : new Location();
        if (!$model) {
            return ['success' => false, 'message' => 'الموقع غير موجود'];
        }

        $model->location = $request->post('name', '');
        $model->description = $request->post('description', '');
        $model->latitude = $request->post('latitude', 0);
        $model->longitude = $request->post('longitude', 0);
        $model->radius = $request->post('radius', 100);
        $model->status = 'active';

        if ($model->isNewRecord) {
            $model->created_by = Yii::$app->user->id;
            $model->created_at = time();
        }
        $model->updated_at = time();

        if ($model->save()) {
            return [
                'success' => true,
                'location' => [
                    'id' => $model->id,
                    'name' => $model->location,
                    'description' => $model->description,
                    'latitude' => $model->latitude,
                    'longitude' => $model->longitude,
                    'radius' => $model->radius,
                ],
            ];
        }
        return ['success' => false, 'message' => implode(', ', $model->getFirstErrors())];
    }

    /**
     * Delete a named location.
     */
    public function actionDeleteLocation()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = Yii::$app->request->post('id');
        $model = Location::findOne($id);
        if (!$model) {
            return ['success' => false, 'message' => 'الموقع غير موجود'];
        }
        $model->status = 'inactive';
        $model->save(false);
        return ['success' => true];
    }

    /**
     * Create field task.
     *
     * @return string|Response
     */
    public function actionTaskCreate()
    {
        $model = new HrFieldTask();
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->status = 'assigned';
            $model->created_at = time();
            $model->updated_at = time();
            $model->created_by = Yii::$app->user->id;
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل إنشاء المهمة الميدانية: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء المهمة الميدانية بنجاح.');

                if ($request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => true, 'message' => 'تم إنشاء المهمة بنجاح.', 'id' => $model->id];
                }

                return $this->redirect(['task-view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                if ($request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => false, 'message' => $e->getMessage()];
                }
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        // Field staff list
        $fieldStaff = ArrayHelper::map(
            (new Query())
                ->select(['u.id', 'u.username', 'u.name'])
                ->from('{{%user}} u')
                ->innerJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_field_staff = 1 AND ext.is_deleted = 0')
                ->where(['IS', 'u.blocked_at', null])
                ->all(),
            'id',
            function ($row) {
                return $row['name'] ?: $row['username'];
            }
        );

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'إنشاء مهمة ميدانية',
                'content' => $this->renderAjax('task-form', [
                    'model' => $model,
                    'fieldStaff' => $fieldStaff,
                ]),
            ];
        }

        return $this->render('task-form', [
            'model' => $model,
            'fieldStaff' => $fieldStaff,
        ]);
    }

    /**
     * Update field task.
     *
     * @param int $id
     * @return string|Response
     */
    public function actionTaskUpdate($id)
    {
        $model = $this->findTaskModel($id);
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            // Track status transition timestamps
            $newStatus = $model->status;
            if ($newStatus === 'accepted' && !$model->accepted_at) {
                $model->accepted_at = date('Y-m-d H:i:s');
            } elseif ($newStatus === 'en_route' && !$model->en_route_at) {
                $model->en_route_at = date('Y-m-d H:i:s');
            } elseif ($newStatus === 'arrived' && !$model->arrived_at) {
                $model->arrived_at = date('Y-m-d H:i:s');
            } elseif (in_array($newStatus, ['completed', 'failed']) && !$model->completed_at) {
                $model->completed_at = date('Y-m-d H:i:s');
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل تحديث المهمة: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();

                if ($request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => true, 'message' => 'تم تحديث المهمة بنجاح.'];
                }

                Yii::$app->session->setFlash('success', 'تم تحديث المهمة بنجاح.');
                return $this->redirect(['task-view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                if ($request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => false, 'message' => $e->getMessage()];
                }
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        // Field staff list
        $fieldStaff = ArrayHelper::map(
            (new Query())
                ->select(['u.id', 'u.username', 'u.name'])
                ->from('{{%user}} u')
                ->innerJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_field_staff = 1 AND ext.is_deleted = 0')
                ->where(['IS', 'u.blocked_at', null])
                ->all(),
            'id',
            function ($row) {
                return $row['name'] ?: $row['username'];
            }
        );

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'تعديل المهمة الميدانية',
                'content' => $this->renderAjax('task-form', [
                    'model' => $model,
                    'fieldStaff' => $fieldStaff,
                ]),
            ];
        }

        return $this->render('task-form', [
            'model' => $model,
            'fieldStaff' => $fieldStaff,
        ]);
    }

    /**
     * View task detail.
     *
     * @param int $id
     * @return string
     */
    public function actionTaskView($id)
    {
        $model = $this->findTaskModel($id);

        // Get related field events for this task
        $events = HrFieldEvent::find()
            ->where(['task_id' => $id, 'is_deleted' => 0])
            ->orderBy(['captured_at' => SORT_ASC])
            ->all();

        // Assigned staff info
        $staff = User::findOne($model->assigned_to);

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'تفاصيل المهمة — ' . $model->title,
                'content' => $this->renderAjax('task-view', [
                    'model' => $model,
                    'events' => $events,
                    'staff' => $staff,
                ]),
            ];
        }

        return $this->render('task-view', [
            'model' => $model,
            'events' => $events,
            'staff' => $staff,
        ]);
    }

    /**
     * List field sessions.
     *
     * @return string
     */
    public function actionSessions()
    {
        $request = Yii::$app->request;
        $filterUser = $request->get('user_id', '');
        $filterDate = $request->get('date', '');
        $filterStatus = $request->get('status', '');

        $query = HrFieldSession::find()
            ->where(['!=', 'status', '']);

        if (!empty($filterUser)) {
            $query->andWhere(['user_id' => $filterUser]);
        }
        if (!empty($filterDate)) {
            $query->andWhere(['like', 'started_at', $filterDate]);
        }
        if (!empty($filterStatus)) {
            $query->andWhere(['status' => $filterStatus]);
        }

        $query->orderBy(['started_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        // Field staff list for filter
        $fieldStaff = ArrayHelper::map(
            (new Query())
                ->select(['u.id', 'u.username', 'u.name'])
                ->from('{{%user}} u')
                ->innerJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_field_staff = 1 AND ext.is_deleted = 0')
                ->where(['IS', 'u.blocked_at', null])
                ->all(),
            'id',
            function ($row) {
                return $row['name'] ?: $row['username'];
            }
        );

        return $this->render('sessions', [
            'dataProvider' => $dataProvider,
            'fieldStaff' => $fieldStaff,
            'filterUser' => $filterUser,
            'filterDate' => $filterDate,
            'filterStatus' => $filterStatus,
        ]);
    }

    /**
     * View session with route playback.
     *
     * @param int $id Session ID
     * @return string
     */
    public function actionSessionView($id)
    {
        $session = HrFieldSession::find()
            ->where(['id' => $id])
            ->one();

        if ($session === null) {
            throw new NotFoundHttpException('جلسة العمل الميدانية غير موجودة.');
        }

        // Get all location points for this session
        $locationPoints = (new Query())
            ->select(['latitude', 'longitude', 'accuracy', 'speed', 'bearing', 'captured_at', 'battery_level', 'is_mock'])
            ->from('{{%hr_location_point}}')
            ->where(['session_id' => $id])
            ->orderBy(['captured_at' => SORT_ASC])
            ->all();

        // Get events during this session
        $events = HrFieldEvent::find()
            ->where(['session_id' => $id, 'is_deleted' => 0])
            ->orderBy(['captured_at' => SORT_ASC])
            ->all();

        $staff = User::findOne($session->user_id);

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'session' => $session->attributes,
                'locationPoints' => $locationPoints,
                'events' => array_map(function ($e) {
                    return $e->attributes;
                }, $events),
                'staff' => [
                    'id' => $staff->id,
                    'name' => $staff->name ?: $staff->username,
                ],
            ];
        }

        return $this->render('session-view', [
            'session' => $session,
            'locationPoints' => $locationPoints,
            'events' => $events,
            'staff' => $staff,
        ]);
    }

    /**
     * Field tracking configuration.
     *
     * @return string|Response
     */
    public function actionConfig()
    {
        $model = HrFieldConfig::find()->one();

        if ($model === null) {
            $model = new HrFieldConfig();
            $model->tracking_mode = 'on_duty';
            $model->location_interval_seconds = 120;
            $model->min_accuracy_meters = 50;
            $model->retention_days = 90;
            $model->require_consent = 1;
            $model->allow_offline = 1;
            $model->geofence_enabled = 1;
            $model->photo_required_on_arrival = 0;
            $model->spoofing_detection = 1;
        }

        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            if ($model->isNewRecord) {
                $model->created_at = time();
                $model->created_by = Yii::$app->user->id;
            }
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل حفظ إعدادات التتبع: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم حفظ إعدادات التتبع الميداني بنجاح.');
                return $this->redirect(['config']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('config', [
            'model' => $model,
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  Mobile Interface + API Endpoints
    // ═══════════════════════════════════════════════════════

    /**
     * صفحة تسجيل دخول خفيفة لنظام الحضور والانصراف (بدون Layout)
     * يمكن لأي شخص الوصول لها (Guest allowed)
     * يستخدم dektrium Finder + Password helper مباشرة
     */
    public function actionMobileLogin()
    {
        // If already logged in, go straight to mobile duty screen
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['mobile']);
        }

        $this->layout = false;
        $error = '';

        if (Yii::$app->request->isPost) {
            $login    = trim(Yii::$app->request->post('LoginForm')['username'] ?? '');
            $password = Yii::$app->request->post('LoginForm')['password'] ?? '';

            if (empty($login) || empty($password)) {
                $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
            } else {
                // Use dektrium Finder to locate user by username or email
                $user = null;
                try {
                    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                        $user = User::find()->where(['email' => $login])->one();
                    } else {
                        $user = User::find()->where(['username' => $login])->one();
                    }
                } catch (\Exception $e) {
                    $error = 'خطأ في النظام، حاول مرة أخرى';
                }

                if ($user && !$user->blocked_at) {
                    // Validate password using dektrium's helper
                    if (\dektrium\user\helpers\Password::validate($password, $user->password_hash)) {
                        // Login with "Remember Me" = 30 days
                        Yii::$app->user->login($user, 3600 * 24 * 30);
                        $user->updateAttributes(['last_login_at' => time()]);
                        return $this->redirect(['mobile']);
                    } else {
                        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
                    }
                } elseif ($user && $user->blocked_at) {
                    $error = 'هذا الحساب محظور، تواصل مع المدير';
                } else {
                    $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
                }
            }
        }

        return $this->renderPartial('mobile-login', [
            'error' => $error,
        ]);
    }

    /**
     * تسجيل خروج — يعيد توجيهه لصفحة تسجيل الدخول
     */
    public function actionMobileLogout()
    {
        Yii::$app->user->logout();
        return $this->redirect(['mobile-login']);
    }

    /**
     * واجهة نظام الحضور والانصراف — صفحة خفيفة بدون Layout
     */
    public function actionMobile()
    {
        $this->layout = false;
        return $this->render('mobile');
    }

    /**
     * API: بدء جلسة ميدانية
     */
    public function actionApiStartSession()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $userId = Yii::$app->user->id;
        $lat = Yii::$app->request->post('latitude');
        $lng = Yii::$app->request->post('longitude');

        try {
            // Close any existing active session
            Yii::$app->db->createCommand()->update(
                '{{%hr_field_session}}',
                ['status' => 'ended', 'ended_at' => date('Y-m-d H:i:s'), 'updated_at' => time()],
                ['user_id' => $userId, 'status' => 'active']
            )->execute();

            $session = new HrFieldSession();
            $session->user_id = $userId;
            $session->started_at = date('Y-m-d H:i:s');
            $session->start_lat = $lat;
            $session->start_lng = $lng;
            $session->status = 'active';
            $session->created_at = time();
            $session->updated_at = time();

            if ($session->save(false)) {
                // Save first location point
                $this->saveLocationPoint($userId, $session->id, $lat, $lng, null, null, null, null);

                return ['success' => true, 'session_id' => $session->id];
            }
            return ['success' => false, 'message' => 'فشل في إنشاء الجلسة'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * API: إنهاء جلسة ميدانية
     */
    public function actionApiEndSession()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $sessionId = Yii::$app->request->post('session_id');
        $lat = Yii::$app->request->post('latitude');
        $lng = Yii::$app->request->post('longitude');

        try {
            $session = HrFieldSession::findOne($sessionId);
            if (!$session || $session->user_id != Yii::$app->user->id) {
                return ['success' => false, 'message' => 'جلسة غير صالحة'];
            }

            $session->ended_at = date('Y-m-d H:i:s');
            $session->end_lat = $lat;
            $session->end_lng = $lng;
            $session->status = 'ended';
            $session->updated_at = time();
            $session->save(false);

            // Save final location
            $this->saveLocationPoint(Yii::$app->user->id, $session->id, $lat, $lng, null, null, null, null);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * API: إرسال نقطة موقع GPS
     */
    public function actionApiSendLocation()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $userId = Yii::$app->user->id;

        try {
            $this->saveLocationPoint(
                $userId,
                Yii::$app->request->post('session_id'),
                Yii::$app->request->post('latitude'),
                Yii::$app->request->post('longitude'),
                Yii::$app->request->post('accuracy'),
                Yii::$app->request->post('altitude'),
                Yii::$app->request->post('speed'),
                Yii::$app->request->post('battery_level')
            );
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * API: قائمة مهام الموظف الحالي
     */
    public function actionApiTasks()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $userId = Yii::$app->user->id;

        try {
            $tasks = (new Query())
                ->select([
                    't.id', 't.title', 't.task_type', 't.priority', 't.status',
                    't.due_date', 't.target_lat', 't.target_lng', 't.target_address',
                    't.amount_collected', 't.description',
                    'c.name as customer_name',
                ])
                ->from('{{%hr_field_task}} t')
                ->leftJoin('{{%customers}} c', 'c.id = t.customer_id')
                ->where(['t.assigned_to' => $userId, 't.is_deleted' => 0])
                ->andWhere(['or',
                    ['t.status' => ['pending', 'accepted', 'en_route', 'arrived']],
                    ['and', ['t.status' => ['completed', 'failed']], ['>=', 't.due_date', date('Y-m-d')]],
                ])
                ->orderBy(['t.priority' => SORT_DESC, 't.due_date' => SORT_ASC])
                ->all();

            return ['success' => true, 'tasks' => $tasks];
        } catch (\Exception $e) {
            return ['success' => true, 'tasks' => []];
        }
    }

    /**
     * API: تحديث حالة مهمة
     */
    public function actionApiTaskUpdate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $taskId = Yii::$app->request->post('task_id');

        try {
            $task = HrFieldTask::findOne(['id' => $taskId, 'assigned_to' => Yii::$app->user->id]);
            if (!$task) {
                return ['success' => false, 'message' => 'مهمة غير موجودة'];
            }

            $newStatus = Yii::$app->request->post('status');
            $task->status = $newStatus;

            if ($newStatus === 'arrived') {
                $task->arrived_at = date('Y-m-d H:i:s');
            } elseif ($newStatus === 'completed') {
                $task->completed_at = date('Y-m-d H:i:s');
                $task->result = Yii::$app->request->post('result', '');
                $task->notes = Yii::$app->request->post('notes', '');
                $amount = Yii::$app->request->post('amount_collected');
                if ($amount) $task->amount_collected = $amount;
            } elseif ($newStatus === 'failed') {
                $task->completed_at = date('Y-m-d H:i:s');
                $task->failure_reason = Yii::$app->request->post('notes', '');
            }

            $task->updated_at = time();
            $task->save(false);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * API: تسجيل حدث ميداني
     */
    public function actionApiLogEvent()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $event = new HrFieldEvent();
            $event->user_id = Yii::$app->user->id;
            $event->session_id = Yii::$app->request->post('session_id');
            $event->task_id = Yii::$app->request->post('task_id');
            $event->event_type = Yii::$app->request->post('event_type', 'note');
            $event->latitude = Yii::$app->request->post('latitude');
            $event->longitude = Yii::$app->request->post('longitude');
            $event->captured_at = date('Y-m-d H:i:s');
            $event->note = Yii::$app->request->post('note', '');
            $event->amount_collected = Yii::$app->request->post('amount_collected');
            $event->created_at = time();
            $event->updated_at = time();
            $event->save(false);

            return ['success' => true, 'event_id' => $event->id];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Helper: حفظ نقطة موقع GPS
     */
    private function saveLocationPoint($userId, $sessionId, $lat, $lng, $accuracy, $altitude, $speed, $battery)
    {
        Yii::$app->db->createCommand()->insert('{{%hr_location_point}}', [
            'user_id'       => $userId,
            'session_id'    => $sessionId,
            'captured_at'   => date('Y-m-d H:i:s'),
            'latitude'      => $lat,
            'longitude'     => $lng,
            'accuracy'      => $accuracy,
            'altitude'      => $altitude,
            'speed'         => $speed,
            'battery_level' => $battery,
            'is_mock'       => 0,
            'created_at'    => time(),
        ])->execute();
    }

    /**
     * Finds the HrFieldTask model.
     */
    protected function findTaskModel($id)
    {
        $model = HrFieldTask::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('المهمة الميدانية المطلوبة غير موجودة.');
    }
}
