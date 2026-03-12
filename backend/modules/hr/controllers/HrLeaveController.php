<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use common\models\User;
use common\helper\Permissions;

/**
 * HrLeaveController — Unified Leave Management
 * ──────────────────────────────────────────────
 * طلبات الإجازات + العطل الرسمية + أنواع الإجازات
 * + سياسة الإجازات + أيام العمل ← شاشة واحدة موحدة
 * يتطلب أحد صلاحيات الموارد البشرية.
 */
class HrLeaveController extends Controller
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
                    ['actions' => ['login', 'error'], 'allow' => true],
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
                    'delete-request'  => ['POST'],
                    'delete-holiday'  => ['POST'],
                    'delete-type'     => ['POST'],
                    'delete-policy'   => ['POST'],
                    'approve-request' => ['POST'],
                    'reject-request'  => ['POST'],
                    'save-workdays'   => ['POST'],
                ],
            ],
        ];
    }

    /* ═══════════════════════════════════════════════
     *  INDEX — الشاشة الموحدة
     * ═══════════════════════════════════════════════ */
    public function actionIndex()
    {
        $tab = Yii::$app->request->get('tab', 'requests');
        $statusFilter = Yii::$app->request->get('status', '');

        // ─── 1. طلبات الإجازات ───
        $requestsQuery = (new Query())
            ->select([
                'lr.id', 'lr.reason', 'lr.start_at', 'lr.end_at',
                'lr.status', 'lr.created_at', 'lr.created_by', 'lr.proved_at',
                'lr.leave_policy', 'lr.action_by',
                'u.username as employee_name',
                'lp.title as policy_title',
                'lt.title as type_title',
            ])
            ->from('{{%leave_request}} lr')
            ->leftJoin('{{%user}} u', 'u.id = lr.created_by')
            ->leftJoin('{{%leave_policy}} lp', 'lp.id = lr.leave_policy')
            ->leftJoin('{{%leave_types}} lt', 'lt.id = lp.leave_type')
            ->orderBy(['lr.created_at' => SORT_DESC]);

        if (!empty($statusFilter)) {
            $requestsQuery->andWhere(['lr.status' => $statusFilter]);
        }

        $requestsProvider = new ActiveDataProvider([
            'query' => $requestsQuery,
            'pagination' => ['pageSize' => 15],
            'key' => 'id',
        ]);

        // ─── KPIs ───
        $kpis = Yii::$app->db->createCommand("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'under review' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM {{%leave_request}}
        ")->queryOne();

        // ─── 2. العطل الرسمية ───
        $holidaysQuery = (new Query())
            ->select(['id', 'title', 'start_at', 'end_at', 'created_at'])
            ->from('{{%holidays}}')
            ->orderBy(['start_at' => SORT_DESC]);

        $holidaysProvider = new ActiveDataProvider([
            'query' => $holidaysQuery,
            'pagination' => ['pageSize' => 20],
            'key' => 'id',
        ]);

        // ─── 3. أنواع الإجازات ───
        $typesData = Yii::$app->db->createCommand("
            SELECT lt.*,
                (SELECT COUNT(*) FROM {{%leave_policy}} WHERE leave_type = lt.id) as policies_count,
                (SELECT COUNT(*) FROM {{%leave_request}} lr
                    INNER JOIN {{%leave_policy}} lp ON lp.id = lr.leave_policy
                    WHERE lp.leave_type = lt.id
                ) as requests_count
            FROM {{%leave_types}} lt
            ORDER BY lt.id
        ")->queryAll();

        // ─── 4. سياسات الإجازات ───
        $policiesData = Yii::$app->db->createCommand("
            SELECT lp.*, lt.title as type_title,
                d.title as dept_name,
                des.title as desig_name
            FROM {{%leave_policy}} lp
            LEFT JOIN {{%leave_types}} lt ON lt.id = lp.leave_type
            LEFT JOIN {{%department}} d ON d.id = lp.department
            LEFT JOIN {{%designation}} des ON des.id = lp.designation
            ORDER BY lp.year DESC, lp.id DESC
        ")->queryAll();

        // ─── 5. أيام العمل ───
        $workdays = Yii::$app->db->createCommand("
            SELECT * FROM {{%workdays}} ORDER BY FIELD(day_name, 'Sundays','Mondays','Tuesdays','Wednesdays','Thursdays','Fridays','Saturdays')
        ")->queryAll();

        // ─── 6. ورديات العمل ───
        $shifts = Yii::$app->db->createCommand("
            SELECT * FROM {{%work_shift}} ORDER BY id
        ")->queryAll();

        // ─── Dropdowns ───
        $employees = ArrayHelper::map(
            User::find()->where(['IS', 'blocked_at', null])->orderBy(['username' => SORT_ASC])->asArray()->all(),
            'id', 'username'
        );

        $leaveTypes = ArrayHelper::map(
            Yii::$app->db->createCommand("SELECT id, title FROM {{%leave_types}} WHERE status = 'active'")->queryAll(),
            'id', 'title'
        );

        $departments = ArrayHelper::map(
            Yii::$app->db->createCommand("SELECT id, title FROM {{%department}}")->queryAll(),
            'id', 'title'
        );

        $designations = ArrayHelper::map(
            Yii::$app->db->createCommand("SELECT id, title FROM {{%designation}}")->queryAll(),
            'id', 'title'
        );

        $leavePolicies = ArrayHelper::map(
            Yii::$app->db->createCommand("
                SELECT lp.id, CONCAT(lt.title, ' — ', lp.title, ' (', lp.year, ')') as label
                FROM {{%leave_policy}} lp
                LEFT JOIN {{%leave_types}} lt ON lt.id = lp.leave_type
                WHERE lp.status = 'active'
            ")->queryAll(),
            'id', 'label'
        );

        return $this->render('index', [
            'tab' => $tab,
            'statusFilter' => $statusFilter,
            'requestsProvider' => $requestsProvider,
            'kpis' => $kpis,
            'holidaysProvider' => $holidaysProvider,
            'typesData' => $typesData,
            'policiesData' => $policiesData,
            'workdays' => $workdays,
            'shifts' => $shifts,
            'employees' => $employees,
            'leaveTypes' => $leaveTypes,
            'departments' => $departments,
            'designations' => $designations,
            'leavePolicies' => $leavePolicies,
        ]);
    }

    /* ═══════════════════════════════════════════════
     *  LEAVE REQUESTS — طلبات الإجازات
     * ═══════════════════════════════════════════════ */

    /**
     * Create a leave request.
     */
    public function actionCreateRequest()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            throw new NotFoundHttpException();
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $leavePolicy = $request->post('leave_policy');
            $startAt     = $request->post('start_at');
            $endAt       = $request->post('end_at');
            $reason      = $request->post('reason', '');

            if (empty($leavePolicy) || empty($startAt) || empty($endAt)) {
                throw new \Exception('يرجى تعبئة جميع الحقول المطلوبة.');
            }

            // Validate dates
            $start = new \DateTime($startAt);
            $end   = new \DateTime($endAt);
            if ($end < $start) {
                throw new \Exception('تاريخ الانتهاء يجب أن يكون بعد تاريخ البداية.');
            }

            $db->createCommand()->insert('{{%leave_request}}', [
                'leave_policy' => (int) $leavePolicy,
                'start_at'     => $startAt,
                'end_at'       => $endAt,
                'reason'       => $reason,
                'status'       => 'under review',
                'action_by'    => 0,
                'created_by'   => Yii::$app->user->id,
                'created_at'   => time(),
                'updated_at'   => time(),
            ])->execute();

            $transaction->commit();
            return ['success' => true, 'message' => 'تم تقديم طلب الإجازة بنجاح.'];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Approve a leave request.
     */
    public function actionApproveRequest($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $db = Yii::$app->db;
        $row = $db->createCommand("SELECT * FROM {{%leave_request}} WHERE id = :id", [':id' => $id])->queryOne();
        if (!$row) {
            return ['success' => false, 'message' => 'طلب الإجازة غير موجود.'];
        }
        if ($row['status'] !== 'under review') {
            return ['success' => false, 'message' => 'لا يمكن تعديل حالة هذا الطلب.'];
        }

        $db->createCommand()->update('{{%leave_request}}', [
            'status'     => 'approved',
            'action_by'  => Yii::$app->user->id,
            'proved_at'  => time(),
            'updated_at' => time(),
        ], ['id' => $id])->execute();

        return ['success' => true, 'message' => 'تمت الموافقة على طلب الإجازة.'];
    }

    /**
     * Reject a leave request.
     */
    public function actionRejectRequest($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $db = Yii::$app->db;
        $row = $db->createCommand("SELECT * FROM {{%leave_request}} WHERE id = :id", [':id' => $id])->queryOne();
        if (!$row) {
            return ['success' => false, 'message' => 'طلب الإجازة غير موجود.'];
        }
        if ($row['status'] !== 'under review') {
            return ['success' => false, 'message' => 'لا يمكن تعديل حالة هذا الطلب.'];
        }

        $db->createCommand()->update('{{%leave_request}}', [
            'status'     => 'rejected',
            'action_by'  => Yii::$app->user->id,
            'proved_at'  => time(),
            'updated_at' => time(),
        ], ['id' => $id])->execute();

        return ['success' => true, 'message' => 'تم رفض طلب الإجازة.'];
    }

    /**
     * Delete a leave request (soft — set status to empty string).
     */
    public function actionDeleteRequest($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $db = Yii::$app->db;
        $db->createCommand()->delete('{{%leave_request}}', ['id' => $id])->execute();

        return ['success' => true, 'message' => 'تم حذف طلب الإجازة.'];
    }

    /* ═══════════════════════════════════════════════
     *  HOLIDAYS — العطل الرسمية
     * ═══════════════════════════════════════════════ */

    public function actionCreateHoliday()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        try {
            $title   = $request->post('title');
            $startAt = $request->post('start_at');
            $endAt   = $request->post('end_at');

            if (empty($title) || empty($startAt) || empty($endAt)) {
                throw new \Exception('يرجى تعبئة جميع الحقول المطلوبة.');
            }

            Yii::$app->db->createCommand()->insert('{{%holidays}}', [
                'title'      => $title,
                'start_at'   => $startAt,
                'end_at'     => $endAt,
                'created_by' => Yii::$app->user->id,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();

            return ['success' => true, 'message' => 'تمت إضافة العطلة بنجاح.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionUpdateHoliday($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        try {
            $title   = $request->post('title');
            $startAt = $request->post('start_at');
            $endAt   = $request->post('end_at');

            Yii::$app->db->createCommand()->update('{{%holidays}}', [
                'title'      => $title,
                'start_at'   => $startAt,
                'end_at'     => $endAt,
                'updated_at' => time(),
            ], ['id' => $id])->execute();

            return ['success' => true, 'message' => 'تم تحديث العطلة بنجاح.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionDeleteHoliday($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->db->createCommand()->delete('{{%holidays}}', ['id' => $id])->execute();
        return ['success' => true, 'message' => 'تم حذف العطلة.'];
    }

    /* ═══════════════════════════════════════════════
     *  LEAVE TYPES — أنواع الإجازات
     * ═══════════════════════════════════════════════ */

    public function actionCreateType()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        try {
            $title       = $request->post('title');
            $description = $request->post('description', '');

            if (empty($title)) {
                throw new \Exception('اسم نوع الإجازة مطلوب.');
            }

            Yii::$app->db->createCommand()->insert('{{%leave_types}}', [
                'title'       => $title,
                'description' => $description,
                'status'      => 'active',
                'created_by'  => Yii::$app->user->id,
                'created_at'  => time(),
                'updated_at'  => time(),
            ])->execute();

            return ['success' => true, 'message' => 'تمت إضافة نوع الإجازة بنجاح.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionUpdateType($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        try {
            $title       = $request->post('title');
            $description = $request->post('description', '');
            $status      = $request->post('status', 'active');

            Yii::$app->db->createCommand()->update('{{%leave_types}}', [
                'title'       => $title,
                'description' => $description,
                'status'      => $status,
                'updated_at'  => time(),
            ], ['id' => $id])->execute();

            return ['success' => true, 'message' => 'تم تحديث نوع الإجازة بنجاح.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionDeleteType($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Check if type is used in policies
        $count = Yii::$app->db->createCommand("SELECT COUNT(*) FROM {{%leave_policy}} WHERE leave_type = :id", [':id' => $id])->queryScalar();
        if ($count > 0) {
            return ['success' => false, 'message' => 'لا يمكن حذف نوع إجازة مرتبط بسياسات (' . $count . ' سياسة).'];
        }

        Yii::$app->db->createCommand()->update('{{%leave_types}}', ['status' => 'unActive', 'updated_at' => time()], ['id' => $id])->execute();
        return ['success' => true, 'message' => 'تم تعطيل نوع الإجازة.'];
    }

    /* ═══════════════════════════════════════════════
     *  LEAVE POLICIES — سياسات الإجازات
     * ═══════════════════════════════════════════════ */

    public function actionCreatePolicy()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        try {
            $title      = $request->post('title');
            $leaveType  = $request->post('leave_type');
            $year       = $request->post('year', date('Y'));
            $totalDays  = $request->post('total_days');
            $department = $request->post('department', 0);
            $designation = $request->post('designation', 0);
            $gender     = $request->post('gender', 'all');
            $maritalStatus = $request->post('marital_status', 'all');

            if (empty($title) || empty($leaveType) || empty($totalDays)) {
                throw new \Exception('يرجى تعبئة جميع الحقول المطلوبة.');
            }

            Yii::$app->db->createCommand()->insert('{{%leave_policy}}', [
                'title'          => $title,
                'leave_type'     => (int) $leaveType,
                'year'           => (int) $year,
                'total_days'     => (int) $totalDays,
                'department'     => (int) $department,
                'designation'    => (int) $designation,
                'location'       => 0,
                'gender'         => $gender,
                'marital_status' => $maritalStatus,
                'status'         => 'active',
                'created_by'     => Yii::$app->user->id,
                'created_at'     => time(),
                'updated_at'     => time(),
            ])->execute();

            return ['success' => true, 'message' => 'تمت إضافة سياسة الإجازة بنجاح.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionUpdatePolicy($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        try {
            Yii::$app->db->createCommand()->update('{{%leave_policy}}', [
                'title'          => $request->post('title'),
                'leave_type'     => (int) $request->post('leave_type'),
                'year'           => (int) $request->post('year', date('Y')),
                'total_days'     => (int) $request->post('total_days'),
                'department'     => (int) $request->post('department', 0),
                'designation'    => (int) $request->post('designation', 0),
                'gender'         => $request->post('gender', 'all'),
                'marital_status' => $request->post('marital_status', 'all'),
                'status'         => $request->post('status', 'active'),
                'updated_at'     => time(),
            ], ['id' => $id])->execute();

            return ['success' => true, 'message' => 'تم تحديث سياسة الإجازة بنجاح.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionDeletePolicy($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $count = Yii::$app->db->createCommand("SELECT COUNT(*) FROM {{%leave_request}} WHERE leave_policy = :id", [':id' => $id])->queryScalar();
        if ($count > 0) {
            return ['success' => false, 'message' => 'لا يمكن حذف سياسة مرتبطة بطلبات إجازة (' . $count . ' طلب).'];
        }

        Yii::$app->db->createCommand()->update('{{%leave_policy}}', ['status' => 'unActive', 'updated_at' => time()], ['id' => $id])->execute();
        return ['success' => true, 'message' => 'تم تعطيل سياسة الإجازة.'];
    }

    /* ═══════════════════════════════════════════════
     *  WORKDAYS — أيام العمل والورديات
     * ═══════════════════════════════════════════════ */

    public function actionSaveWorkdays()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $days = $request->post('days', []);

            foreach ($days as $dayData) {
                if (empty($dayData['id'])) continue;

                Yii::$app->db->createCommand()->update('{{%workdays}}', [
                    'start_at'   => $dayData['start_at'] ?? '08:00:00',
                    'end_at'     => $dayData['end_at'] ?? '16:00:00',
                    'status'     => $dayData['status'] ?? 'working_day',
                    'updated_at' => time(),
                ], ['id' => $dayData['id']])->execute();
            }

            $transaction->commit();
            return ['success' => true, 'message' => 'تم حفظ جدول أيام العمل بنجاح.'];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Save/Update a work shift.
     */
    public function actionSaveShift()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        try {
            $id      = $request->post('id');
            $title   = $request->post('title');
            $startAt = $request->post('start_at');
            $endAt   = $request->post('end_at');

            if (empty($title) || empty($startAt) || empty($endAt)) {
                throw new \Exception('يرجى تعبئة جميع الحقول المطلوبة.');
            }

            $data = [
                'title'      => $title,
                'start_at'   => $startAt,
                'end_at'     => $endAt,
                'updated_at' => time(),
            ];

            if (!empty($id)) {
                Yii::$app->db->createCommand()->update('{{%work_shift}}', $data, ['id' => $id])->execute();
            } else {
                $data['created_by'] = Yii::$app->user->id;
                $data['created_at'] = time();
                Yii::$app->db->createCommand()->insert('{{%work_shift}}', $data)->execute();
            }

            return ['success' => true, 'message' => 'تم حفظ الوردية بنجاح.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionDeleteShift($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->db->createCommand()->delete('{{%work_shift}}', ['id' => $id])->execute();
        return ['success' => true, 'message' => 'تم حذف الوردية.'];
    }
}
