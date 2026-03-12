<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use backend\modules\hr\models\HrAttendanceLog;
use backend\modules\hr\models\HrWorkZone;
use backend\modules\hr\models\HrTrackingPoint;
use backend\modules\hr\models\HrGeofenceEvent;
use backend\modules\hr\models\HrEmployeeExtended;
use backend\modules\hr\models\HrWorkShift;

/**
 * Tracking API Controller — نقاط النهاية للتطبيق المحمول
 * يتعامل مع: تسجيل الحضور، إرسال الموقع، فحص Geofence
 */
class HrTrackingApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['mobile', 'mobile-login', 'mobile-logout'],
                        'allow' => true,
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    Yii::$app->response->statusCode = 401;
                    return ['success' => false, 'message' => 'يرجى تسجيل الدخول'];
                },
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'clock-in' => ['POST'],
                    'clock-out' => ['POST'],
                    'send-location' => ['POST'],
                    'batch-locations' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * GET /hr/tracking-api/status
     * حالة الموظف الحالية: حضور اليوم + وردية + منطقة عمل + موقع آخر
     */
    public function actionStatus()
    {
        $userId = Yii::$app->user->id;
        $today = date('Y-m-d');

        $emp = HrEmployeeExtended::findOne(['user_id' => $userId]);
        $log = HrAttendanceLog::findOne(['user_id' => $userId, 'attendance_date' => $today]);

        $shift = null;
        if ($emp && $emp->shift_id) {
            $shiftModel = HrWorkShift::findOne($emp->shift_id);
            if ($shiftModel) {
                $shift = [
                    'id' => $shiftModel->id,
                    'title' => $shiftModel->title,
                    'start_at' => $shiftModel->start_at,
                    'end_at' => $shiftModel->end_at,
                    'grace_minutes' => $shiftModel->grace_minutes,
                ];
            }
        }

        $zone = null;
        if ($emp && $emp->work_zone_id) {
            $zoneModel = HrWorkZone::findOne($emp->work_zone_id);
            if ($zoneModel) {
                $zone = [
                    'id' => $zoneModel->id,
                    'name' => $zoneModel->name,
                    'latitude' => (float)$zoneModel->latitude,
                    'longitude' => (float)$zoneModel->longitude,
                    'radius_meters' => $zoneModel->radius_meters,
                    'zone_type' => $zoneModel->zone_type,
                ];
            }
        }

        $allZones = HrWorkZone::find()->where(['is_active' => 1])->asArray()->all();
        $zonesData = array_map(function ($z) {
            return [
                'id' => (int)$z['id'],
                'name' => $z['name'],
                'latitude' => (float)$z['latitude'],
                'longitude' => (float)$z['longitude'],
                'radius_meters' => (int)$z['radius_meters'],
                'zone_type' => $z['zone_type'],
            ];
        }, $allZones);

        return [
            'success' => true,
            'employee' => $emp ? [
                'employee_type' => $emp->employee_type,
                'tracking_mode' => $emp->tracking_mode,
                'is_field_staff' => (bool)$emp->is_field_staff,
            ] : null,
            'attendance' => $log ? [
                'clock_in_at' => $log->clock_in_at,
                'clock_out_at' => $log->clock_out_at,
                'clock_in_method' => $log->clock_in_method,
                'status' => $log->status,
                'total_minutes' => $log->total_minutes,
                'late_minutes' => $log->late_minutes,
                'is_mock_location' => (bool)$log->is_mock_location,
            ] : null,
            'shift' => $shift,
            'assigned_zone' => $zone,
            'all_zones' => $zonesData,
            'server_time' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * POST /hr/tracking-api/clock-in
     * تسجيل دخول يدوي أو تلقائي عبر Geofence
     */
    public function actionClockIn()
    {
        $req = Yii::$app->request;
        $userId = Yii::$app->user->id;
        $lat = (float)$req->post('latitude');
        $lng = (float)$req->post('longitude');
        $accuracy = (float)$req->post('accuracy', 0);
        $method = $req->post('method', 'manual');
        $isMock = (int)$req->post('is_mock', 0);

        if ($accuracy > 100 && $method === 'geofence_auto') {
            return ['success' => false, 'message' => 'دقة الموقع غير كافية للتسجيل التلقائي'];
        }

        if ($isMock) {
            $today = date('Y-m-d');
            $log = HrAttendanceLog::findOne(['user_id' => $userId, 'attendance_date' => $today]);
            if (!$log) {
                $log = new HrAttendanceLog();
                $log->user_id = $userId;
                $log->attendance_date = $today;
            }
            $log->is_mock_location = 1;
            $log->status = HrAttendanceLog::STATUS_ABSENT;
            $log->notes = 'تم رصد موقع وهمي (Mock Location)';
            $log->save(false);
            return ['success' => false, 'message' => 'تم رصد استخدام موقع وهمي — تم تسجيل المخالفة', 'mock_detected' => true];
        }

        $zoneId = null;
        $emp = HrEmployeeExtended::findOne(['user_id' => $userId]);
        if ($emp && $emp->work_zone_id) {
            $zone = HrWorkZone::findOne($emp->work_zone_id);
            if ($zone && !$zone->isPointInside($lat, $lng)) {
                $dist = round($zone->distanceFrom($lat, $lng));
                return [
                    'success' => false,
                    'message' => "أنت خارج منطقة العمل ({$zone->name}). المسافة: {$dist} متر",
                    'outside_zone' => true,
                    'distance' => $dist,
                ];
            }
            $zoneId = $emp->work_zone_id;
        } else {
            $zones = HrWorkZone::find()->where(['is_active' => 1])->all();
            foreach ($zones as $z) {
                if ($z->isPointInside($lat, $lng)) {
                    $zoneId = $z->id;
                    break;
                }
            }
        }

        $result = HrAttendanceLog::clockIn($userId, $lat, $lng, $method, $zoneId, $accuracy);

        if ($result['success'] ?? false) {
            $this->logGeofenceEvent($userId, $zoneId, 'enter', $lat, $lng, $accuracy, $result['log_id'] ?? null);
        }

        return $result;
    }

    /**
     * POST /hr/tracking-api/clock-out
     * تسجيل خروج يدوي أو تلقائي عبر Geofence
     */
    public function actionClockOut()
    {
        $req = Yii::$app->request;
        $userId = Yii::$app->user->id;
        $lat = (float)$req->post('latitude');
        $lng = (float)$req->post('longitude');
        $method = $req->post('method', 'manual');

        $zoneId = null;
        $zones = HrWorkZone::find()->where(['is_active' => 1])->all();
        foreach ($zones as $z) {
            if ($z->isPointInside($lat, $lng)) {
                $zoneId = $z->id;
                break;
            }
        }

        $result = HrAttendanceLog::clockOut($userId, $lat, $lng, $method, $zoneId);

        if ($result['success'] ?? false) {
            $emp = HrEmployeeExtended::findOne(['user_id' => $userId]);
            $exitZoneId = $emp ? $emp->work_zone_id : $zoneId;
            if ($exitZoneId) {
                $today = date('Y-m-d');
                $log = HrAttendanceLog::findOne(['user_id' => $userId, 'attendance_date' => $today]);
                $this->logGeofenceEvent($userId, $exitZoneId, 'exit', $lat, $lng, null, $log ? $log->id : null);
            }
        }

        return $result;
    }

    /**
     * POST /hr/tracking-api/send-location
     * إرسال نقطة موقع + فحص Geofence تلقائي
     */
    public function actionSendLocation()
    {
        $req = Yii::$app->request;
        $userId = Yii::$app->user->id;

        $result = HrTrackingPoint::recordPoint([
            'user_id'       => $userId,
            'latitude'      => (float)$req->post('latitude'),
            'longitude'     => (float)$req->post('longitude'),
            'accuracy'      => (float)$req->post('accuracy', 0),
            'speed'         => $req->post('speed') !== null ? (float)$req->post('speed') : null,
            'heading'       => $req->post('heading') !== null ? (float)$req->post('heading') : null,
            'altitude'      => $req->post('altitude') !== null ? (float)$req->post('altitude') : null,
            'battery_level' => $req->post('battery_level') !== null ? (int)$req->post('battery_level') : null,
            'is_moving'     => $req->post('is_moving') !== null ? (int)$req->post('is_moving') : null,
            'is_mock'       => (int)$req->post('is_mock', 0),
            'activity_type' => $req->post('activity_type', 'unknown'),
            'captured_at'   => date('Y-m-d H:i:s'),
        ]);

        return $result;
    }

    /**
     * POST /hr/tracking-api/batch-locations
     * إرسال دفعة من النقاط المخزّنة (offline queue)
     */
    public function actionBatchLocations()
    {
        $req = Yii::$app->request;
        $points = $req->post('points', []);
        $userId = Yii::$app->user->id;
        $saved = 0;
        $events = [];

        foreach ($points as $p) {
            $result = HrTrackingPoint::recordPoint([
                'user_id'       => $userId,
                'latitude'      => (float)($p['latitude'] ?? 0),
                'longitude'     => (float)($p['longitude'] ?? 0),
                'accuracy'      => (float)($p['accuracy'] ?? 0),
                'speed'         => isset($p['speed']) ? (float)$p['speed'] : null,
                'battery_level' => isset($p['battery_level']) ? (int)$p['battery_level'] : null,
                'is_mock'       => (int)($p['is_mock'] ?? 0),
                'captured_at'   => $p['captured_at'] ?? date('Y-m-d H:i:s'),
            ]);
            if ($result['success'] ?? false) {
                $saved++;
                if (!empty($result['events'])) {
                    $events = array_merge($events, $result['events']);
                }
            }
        }

        return ['success' => true, 'saved' => $saved, 'total' => count($points), 'events' => $events];
    }

    /**
     * GET /hr/tracking-api/live-data
     * بيانات مباشرة لخريطة التتبع (للمدراء)
     */
    public function actionLiveData()
    {
        $employees = HrEmployeeExtended::find()
            ->where(['!=', 'tracking_mode', 'disabled'])
            ->andWhere(['is_deleted' => 0])
            ->with(['user', 'workZone'])
            ->all();

        $today = date('Y-m-d');
        $data = [];

        foreach ($employees as $emp) {
            if (!$emp->user) continue;

            $lastPoint = HrTrackingPoint::find()
                ->where(['user_id' => $emp->user_id])
                ->andWhere(['>=', 'captured_at', $today . ' 00:00:00'])
                ->orderBy(['captured_at' => SORT_DESC])
                ->one();

            $attendance = HrAttendanceLog::findOne([
                'user_id' => $emp->user_id,
                'attendance_date' => $today,
            ]);

            $data[] = [
                'user_id'        => $emp->user_id,
                'name'           => $emp->user->name ?? 'N/A',
                'employee_type'  => $emp->employee_type,
                'tracking_mode'  => $emp->tracking_mode,
                'zone_name'      => $emp->workZone ? $emp->workZone->name : null,
                'latitude'       => $lastPoint ? (float)$lastPoint->latitude : null,
                'longitude'      => $lastPoint ? (float)$lastPoint->longitude : null,
                'accuracy'       => $lastPoint ? (float)$lastPoint->accuracy : null,
                'speed'          => $lastPoint ? $lastPoint->speed : null,
                'battery_level'  => $lastPoint ? $lastPoint->battery_level : null,
                'is_mock'        => $lastPoint ? (bool)$lastPoint->is_mock : false,
                'last_update'    => $lastPoint ? $lastPoint->captured_at : null,
                'attendance'     => $attendance ? [
                    'status'        => $attendance->status,
                    'clock_in_at'   => $attendance->clock_in_at,
                    'clock_out_at'  => $attendance->clock_out_at,
                    'total_minutes' => $attendance->total_minutes,
                    'late_minutes'  => $attendance->late_minutes,
                ] : null,
            ];
        }

        $zones = HrWorkZone::find()->where(['is_active' => 1])->asArray()->all();

        return [
            'success' => true,
            'employees' => $data,
            'zones' => array_map(function ($z) {
                return [
                    'id' => (int)$z['id'],
                    'name' => $z['name'],
                    'latitude' => (float)$z['latitude'],
                    'longitude' => (float)$z['longitude'],
                    'radius_meters' => (int)$z['radius_meters'],
                    'zone_type' => $z['zone_type'],
                ];
            }, $zones),
            'server_time' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * GET /hr/tracking-api/attendance-summary
     * ملخص الحضور اليومي للإدارة
     */
    public function actionAttendanceSummary()
    {
        $date = Yii::$app->request->get('date', date('Y-m-d'));

        $stats = (new \yii\db\Query())
            ->select([
                'status',
                'cnt' => 'COUNT(*)',
            ])
            ->from('{{%hr_attendance_log}}')
            ->where(['attendance_date' => $date])
            ->groupBy('status')
            ->all();

        $summary = [];
        foreach ($stats as $row) {
            $summary[$row['status']] = (int)$row['cnt'];
        }

        $totalEmployees = HrEmployeeExtended::find()->where(['is_deleted' => 0])->count();
        $totalPresent = ($summary['present'] ?? 0) + ($summary['late'] ?? 0) + ($summary['field_duty'] ?? 0);
        $totalAbsent = $totalEmployees - $totalPresent - ($summary['on_leave'] ?? 0) - ($summary['holiday'] ?? 0) - ($summary['weekend'] ?? 0);

        $lateLogs = HrAttendanceLog::find()
            ->where(['attendance_date' => $date])
            ->andWhere(['>', 'late_minutes', 0])
            ->orderBy(['late_minutes' => SORT_DESC])
            ->limit(10)
            ->with('user')
            ->all();

        $lateList = array_map(function ($l) {
            return [
                'name' => $l->user ? $l->user->name : 'N/A',
                'late_minutes' => $l->late_minutes,
                'clock_in_at' => $l->clock_in_at,
            ];
        }, $lateLogs);

        return [
            'success' => true,
            'date' => $date,
            'total_employees' => (int)$totalEmployees,
            'present' => $totalPresent,
            'absent' => max(0, $totalAbsent),
            'details' => $summary,
            'top_late' => $lateList,
        ];
    }

    /**
     * لوحة الحضور الموحّدة
     */
    public function actionAttendanceBoard()
    {
        Yii::$app->response->format = Response::FORMAT_HTML;

        $request = Yii::$app->request;
        $filterDate = $request->get('date', date('Y-m-d'));
        $filterStatus = $request->get('status', '');
        $filterType = $request->get('employee_type', '');

        $query = HrAttendanceLog::find()
            ->alias('a')
            ->leftJoin('{{%user}} u', 'u.id = a.user_id')
            ->leftJoin('{{%hr_employee_extended}} e', 'e.user_id = a.user_id')
            ->where(['a.attendance_date' => $filterDate]);

        if ($filterStatus) {
            $query->andWhere(['a.status' => $filterStatus]);
        }
        if ($filterType) {
            $query->andWhere(['e.employee_type' => $filterType]);
        }

        $query->orderBy(['a.clock_in_at' => SORT_DESC]);

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
        ]);

        $stats = Yii::$app->db->createCommand("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('present','late','field_duty','half_day') THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status='field_duty' THEN 1 ELSE 0 END) as field_duty,
                SUM(CASE WHEN status='on_leave' THEN 1 ELSE 0 END) as on_leave,
                SUM(CASE WHEN is_mock_location=1 THEN 1 ELSE 0 END) as mock_detected,
                ROUND(AVG(CASE WHEN late_minutes>0 THEN late_minutes END),0) as avg_late,
                ROUND(AVG(CASE WHEN total_minutes>0 THEN total_minutes END),0) as avg_work
            FROM {{%hr_attendance_log}} WHERE attendance_date = :date
        ", [':date' => $filterDate])->queryOne();

        return $this->render('attendance-board', [
            'dataProvider' => $dataProvider,
            'filterDate' => $filterDate,
            'filterStatus' => $filterStatus,
            'filterType' => $filterType,
            'stats' => $stats,
        ]);
    }

    /**
     * خريطة التتبع المباشر (للمدراء)
     */
    public function actionLiveMap()
    {
        Yii::$app->response->format = Response::FORMAT_HTML;
        return $this->render('live-map');
    }

    /**
     * واجهة الموبايل للحضور
     */
    public function actionMobile()
    {
        $this->layout = false;
        Yii::$app->response->format = Response::FORMAT_HTML;
        return $this->render('mobile-attendance');
    }

    /**
     * تسجيل دخول خفيف للموبايل
     */
    public function actionMobileLogin()
    {
        $this->layout = false;
        Yii::$app->response->format = Response::FORMAT_HTML;

        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['mobile']);
        }

        if (Yii::$app->request->isPost) {
            $login = trim(Yii::$app->request->post('username', ''));
            $password = Yii::$app->request->post('password');

            $user = \common\models\User::find()
                ->where(['username' => $login])
                ->orWhere(['email' => $login])
                ->one();

            if ($user && $user->blocked_at) {
                return $this->render('mobile-login', ['error' => 'هذا الحساب محظور، تواصل مع المدير']);
            }

            if ($user && \dektrium\user\helpers\Password::validate($password, $user->password_hash)) {
                Yii::$app->user->login($user, 3600 * 24 * 30);
                return $this->redirect(['mobile']);
            }

            return $this->render('mobile-login', ['error' => 'اسم المستخدم أو كلمة المرور غير صحيحة']);
        }

        return $this->render('mobile-login', ['error' => null]);
    }

    public function actionMobileLogout()
    {
        Yii::$app->user->logout();
        return $this->redirect(['mobile-login']);
    }

    private function logGeofenceEvent($userId, $zoneId, $eventType, $lat, $lng, $accuracy = null, $logId = null)
    {
        if (!$zoneId) return;
        $event = new HrGeofenceEvent([
            'user_id' => $userId,
            'zone_id' => $zoneId,
            'event_type' => $eventType,
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy' => $accuracy,
            'triggered_at' => date('Y-m-d H:i:s'),
            'processed' => 1,
            'attendance_log_id' => $logId,
        ]);
        $event->save(false);
    }
}
