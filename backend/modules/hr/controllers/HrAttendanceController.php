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
use backend\modules\hr\models\HrAttendance;
use common\models\User;
use common\helper\Permissions;

/**
 * HrAttendanceController — Attendance management
 * يتطلب أحد صلاحيات الموارد البشرية.
 */
class HrAttendanceController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::hasAnyPermission(Permissions::getHrPermissions());
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Attendance board showing today's attendance with filters
     * (date, department, status).
     *
     * @return string
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $filterDate = $request->get('date', date('Y-m-d'));
        $filterDepartment = $request->get('department', '');
        $filterStatus = $request->get('status', '');

        $query = HrAttendance::find()
            ->alias('a')
            ->leftJoin('{{%user}} u', 'u.id = a.user_id')
            ->leftJoin('{{%department}} d', 'd.id = u.department')
            ->where(['a.attendance_date' => $filterDate, 'a.is_deleted' => 0]);

        if (!empty($filterDepartment)) {
            $query->andWhere(['u.department' => $filterDepartment]);
        }
        if (!empty($filterStatus)) {
            $query->andWhere(['a.status' => $filterStatus]);
        }

        $query->orderBy(['a.check_in_time' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
        ]);

        // Department list for filter dropdown
        $departments = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%department}}')->all(),
            'id',
            'name'
        );

        // Today's summary stats
        $todayStats = Yii::$app->db->createCommand("
            SELECT COUNT(*) as total_records,
                SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status='leave' THEN 1 ELSE 0 END) as `leave`,
                SUM(CASE WHEN status='holiday' THEN 1 ELSE 0 END) as holiday,
                SUM(CASE WHEN status='half_day' THEN 1 ELSE 0 END) as half_day,
                SUM(CASE WHEN status='remote' THEN 1 ELSE 0 END) as remote
            FROM {{%hr_attendance}} WHERE attendance_date = :date AND is_deleted = 0
        ", [':date' => $filterDate])->queryOne();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'filterDate' => $filterDate,
            'filterDepartment' => $filterDepartment,
            'filterStatus' => $filterStatus,
            'departments' => $departments,
            'todayStats' => $todayStats,
        ]);
    }

    /**
     * Manual check-in (AJAX).
     *
     * @return array
     */
    public function actionCheckIn()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if (!$request->isAjax && !$request->isPost) {
            return ['success' => false, 'message' => 'طلب غير صالح.'];
        }

        $userId = $request->post('user_id', Yii::$app->user->id);
        $today = date('Y-m-d');

        // Check if already checked in today
        $existing = HrAttendance::find()
            ->where([
                'user_id' => $userId,
                'attendance_date' => $today,
                'is_deleted' => 0,
            ])
            ->one();

        if ($existing && $existing->check_in_time) {
            return ['success' => false, 'message' => 'تم تسجيل الحضور مسبقاً لهذا اليوم.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($existing) {
                $model = $existing;
            } else {
                $model = new HrAttendance();
                $model->user_id = $userId;
                $model->attendance_date = $today;
                $model->created_at = time();
                $model->created_by = Yii::$app->user->id;
            }

            $model->check_in_time = date('Y-m-d H:i:s');
            $model->check_in_method = 'manual';
            $model->check_in_lat = $request->post('latitude');
            $model->check_in_lng = $request->post('longitude');
            $model->status = 'present';
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            // Determine late_minutes based on shift (simplified)
            $hour = (int) date('H');
            $minute = (int) date('i');
            if ($hour > 9 || ($hour === 9 && $minute > 15)) {
                $model->late_minutes = ($hour - 9) * 60 + $minute - 0;
            }

            if (!$model->save()) {
                throw new \Exception('فشل تسجيل الحضور: ' . implode(', ', $model->getFirstErrors()));
            }

            $transaction->commit();
            return [
                'success' => true,
                'message' => 'تم تسجيل الحضور بنجاح.',
                'time' => $model->check_in_time,
                'status' => $model->status,
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Manual check-out (AJAX).
     *
     * @return array
     */
    public function actionCheckOut()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if (!$request->isAjax && !$request->isPost) {
            return ['success' => false, 'message' => 'طلب غير صالح.'];
        }

        $userId = $request->post('user_id', Yii::$app->user->id);
        $today = date('Y-m-d');

        $model = HrAttendance::find()
            ->where([
                'user_id' => $userId,
                'attendance_date' => $today,
                'is_deleted' => 0,
            ])
            ->one();

        if (!$model || !$model->check_in_time) {
            return ['success' => false, 'message' => 'لم يتم تسجيل حضور اليوم بعد.'];
        }

        if ($model->check_out_time) {
            return ['success' => false, 'message' => 'تم تسجيل الانصراف مسبقاً.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model->check_out_time = date('Y-m-d H:i:s');
            $model->check_out_method = 'manual';
            $model->check_out_lat = $request->post('latitude');
            $model->check_out_lng = $request->post('longitude');
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            // Calculate total hours
            $checkIn = strtotime($model->check_in_time);
            $checkOut = strtotime($model->check_out_time);
            $model->total_hours = round(($checkOut - $checkIn) / 3600, 2);

            // Calculate overtime (assuming 8-hour workday)
            if ($model->total_hours > 8) {
                $model->overtime_hours = round($model->total_hours - 8, 2);
            }

            if (!$model->save()) {
                throw new \Exception('فشل تسجيل الانصراف: ' . implode(', ', $model->getFirstErrors()));
            }

            $transaction->commit();
            return [
                'success' => true,
                'message' => 'تم تسجيل الانصراف بنجاح.',
                'time' => $model->check_out_time,
                'total_hours' => $model->total_hours,
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Admin manual attendance entry.
     *
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new HrAttendance();
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->is_adjusted = 1;
            $model->adjusted_by = Yii::$app->user->id;
            $model->adjustment_reason = $request->post('adjustment_reason', 'إدخال يدوي');
            $model->created_at = time();
            $model->updated_at = time();
            $model->created_by = Yii::$app->user->id;
            $model->updated_by = Yii::$app->user->id;

            // Calculate total hours if both check-in and check-out provided
            if ($model->check_in_time && $model->check_out_time) {
                $checkIn = strtotime($model->check_in_time);
                $checkOut = strtotime($model->check_out_time);
                $model->total_hours = round(($checkOut - $checkIn) / 3600, 2);
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل إنشاء سجل الحضور: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء سجل الحضور بنجاح.');
                return $this->redirect(['index']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        // Employee list
        $employees = ArrayHelper::map(
            User::find()->where(['IS', 'blocked_at', null])->orderBy(['username' => SORT_ASC])->asArray()->all(),
            'id',
            'username'
        );

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'إدخال حضور يدوي',
                'content' => $this->renderAjax('create', [
                    'model' => $model,
                    'employees' => $employees,
                ]),
            ];
        }

        return $this->render('create', [
            'model' => $model,
            'employees' => $employees,
        ]);
    }

    /**
     * Edit attendance record.
     *
     * @param int $id
     * @return string|Response
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->is_adjusted = 1;
            $model->adjusted_by = Yii::$app->user->id;
            $model->adjustment_reason = $request->post('adjustment_reason', $model->adjustment_reason);
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            // Recalculate total hours
            if ($model->check_in_time && $model->check_out_time) {
                $checkIn = strtotime($model->check_in_time);
                $checkOut = strtotime($model->check_out_time);
                $model->total_hours = round(($checkOut - $checkIn) / 3600, 2);
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل تحديث سجل الحضور: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم تحديث سجل الحضور بنجاح.');
                return $this->redirect(['index']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'تعديل سجل الحضور',
                'content' => $this->renderAjax('update', [
                    'model' => $model,
                ]),
            ];
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Monthly attendance summary view.
     *
     * @return string
     */
    public function actionSummary()
    {
        $request = Yii::$app->request;
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $departmentId = $request->get('department', '');

        $query = "
            SELECT
                u.id as user_id,
                u.username,
                u.name,
                d.name as department_name,
                COUNT(a.id) as total_records,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN a.status = 'leave' THEN 1 ELSE 0 END) as leave_days,
                SUM(CASE WHEN a.status = 'half_day' THEN 1 ELSE 0 END) as half_days,
                SUM(CASE WHEN a.status = 'remote' THEN 1 ELSE 0 END) as remote_days,
                SUM(COALESCE(a.total_hours, 0)) as total_hours,
                SUM(COALESCE(a.overtime_hours, 0)) as total_overtime,
                SUM(COALESCE(a.late_minutes, 0)) as total_late_minutes
            FROM {{%user}} u
            LEFT JOIN {{%hr_attendance}} a ON a.user_id = u.id
                AND MONTH(a.attendance_date) = :month
                AND YEAR(a.attendance_date) = :year
                AND a.is_deleted = 0
            LEFT JOIN {{%department}} d ON d.id = u.department
            WHERE u.blocked_at IS NULL
        ";

        $params = [':month' => $month, ':year' => $year];

        if (!empty($departmentId)) {
            $query .= " AND u.department = :dept";
            $params[':dept'] = $departmentId;
        }

        $query .= " GROUP BY u.id, u.username, u.name, d.name ORDER BY u.name ASC";

        $summary = Yii::$app->db->createCommand($query, $params)->queryAll();

        $departments = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%department}}')->all(),
            'id',
            'name'
        );

        return $this->render('summary', [
            'summary' => $summary,
            'month' => $month,
            'year' => $year,
            'departmentId' => $departmentId,
            'departments' => $departments,
        ]);
    }

    /**
     * Bulk check-in for multiple employees.
     *
     * @return array
     */
    public function actionBulkCheckIn()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if (!$request->isPost) {
            return ['success' => false, 'message' => 'طلب غير صالح.'];
        }

        $userIds = $request->post('user_ids', []);
        if (empty($userIds) || !is_array($userIds)) {
            return ['success' => false, 'message' => 'يرجى اختيار موظف واحد على الأقل.'];
        }

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $currentUserId = Yii::$app->user->id;
        $successCount = 0;
        $errors = [];

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($userIds as $userId) {
                // Check if already checked in
                $existing = HrAttendance::find()
                    ->where([
                        'user_id' => $userId,
                        'attendance_date' => $today,
                        'is_deleted' => 0,
                    ])
                    ->one();

                if ($existing && $existing->check_in_time) {
                    continue; // Skip already checked-in
                }

                if ($existing) {
                    $model = $existing;
                } else {
                    $model = new HrAttendance();
                    $model->user_id = $userId;
                    $model->attendance_date = $today;
                    $model->created_at = time();
                    $model->created_by = $currentUserId;
                }

                $model->check_in_time = $now;
                $model->check_in_method = 'manual';
                $model->status = 'present';
                $model->is_adjusted = 1;
                $model->adjusted_by = $currentUserId;
                $model->adjustment_reason = 'تسجيل حضور جماعي';
                $model->updated_at = time();
                $model->updated_by = $currentUserId;

                if ($model->save()) {
                    $successCount++;
                } else {
                    $errors[] = "الموظف #{$userId}: " . implode(', ', $model->getFirstErrors());
                }
            }

            $transaction->commit();
            return [
                'success' => true,
                'message' => "تم تسجيل حضور {$successCount} موظف بنجاح.",
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Finds the HrAttendance model based on its primary key.
     *
     * @param int $id
     * @return HrAttendance
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = HrAttendance::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('سجل الحضور المطلوب غير موجود.');
    }
}
