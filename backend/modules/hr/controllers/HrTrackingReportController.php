<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use common\helper\Permissions;

/**
 * تقارير وتحليلات نظام التتبع والحضور
 */
class HrTrackingReportController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::hasAnyPermission(Permissions::getHrPermissions());
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * لوحة التحكم الرئيسية — Dashboard
     */
    public function actionIndex()
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $todayStats = $this->getDateStats($today);

        $monthlyTrend = (new Query())
            ->select([
                'attendance_date',
                'total'    => 'COUNT(*)',
                'present'  => "SUM(CASE WHEN status IN ('present','late','field_duty','half_day') THEN 1 ELSE 0 END)",
                'late'     => "SUM(CASE WHEN status='late' THEN 1 ELSE 0 END)",
                'absent'   => "SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END)",
            ])
            ->from('{{%hr_attendance_log}}')
            ->where(['between', 'attendance_date', $monthStart, $monthEnd])
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->all();

        $topLateEmployees = (new Query())
            ->select([
                'a.user_id',
                'u.name',
                'total_late' => 'SUM(a.late_minutes)',
                'late_days'  => 'COUNT(*)',
                'avg_late'   => 'ROUND(AVG(a.late_minutes),0)',
            ])
            ->from('{{%hr_attendance_log}} a')
            ->leftJoin('{{%user}} u', 'u.id = a.user_id')
            ->where(['between', 'a.attendance_date', $monthStart, $monthEnd])
            ->andWhere(['>', 'a.late_minutes', 0])
            ->groupBy(['a.user_id', 'u.name'])
            ->orderBy(['total_late' => SORT_DESC])
            ->limit(10)
            ->all();

        $avgWorkHours = (new Query())
            ->select(['avg_minutes' => 'ROUND(AVG(total_minutes),0)'])
            ->from('{{%hr_attendance_log}}')
            ->where(['between', 'attendance_date', $monthStart, $monthEnd])
            ->andWhere(['>', 'total_minutes', 0])
            ->scalar();

        $mockCount = (new Query())
            ->from('{{%hr_attendance_log}}')
            ->where(['between', 'attendance_date', $monthStart, $monthEnd])
            ->andWhere(['is_mock_location' => 1])
            ->count();

        $methodBreakdown = (new Query())
            ->select(['clock_in_method', 'cnt' => 'COUNT(*)'])
            ->from('{{%hr_attendance_log}}')
            ->where(['between', 'attendance_date', $monthStart, $monthEnd])
            ->groupBy('clock_in_method')
            ->all();

        return $this->render('index', [
            'todayStats' => $todayStats,
            'monthlyTrend' => $monthlyTrend,
            'topLateEmployees' => $topLateEmployees,
            'avgWorkMinutes' => (int)$avgWorkHours,
            'mockCount' => (int)$mockCount,
            'methodBreakdown' => $methodBreakdown,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
        ]);
    }

    /**
     * تقرير الحضور الشهري التفصيلي
     */
    public function actionMonthly()
    {
        $request = Yii::$app->request;
        $month = $request->get('month', date('Y-m'));
        $filterType = $request->get('employee_type', '');

        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        $daysInMonth = (int)date('t', strtotime($startDate));

        $query = (new Query())
            ->select([
                'a.user_id',
                'u.name',
                'e.employee_type',
                'present_days'    => "SUM(CASE WHEN a.status IN ('present','late','field_duty','half_day') THEN 1 ELSE 0 END)",
                'absent_days'     => "SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END)",
                'late_days'       => "SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END)",
                'leave_days'      => "SUM(CASE WHEN a.status='on_leave' THEN 1 ELSE 0 END)",
                'total_work_min'  => 'SUM(a.total_minutes)',
                'total_late_min'  => 'SUM(a.late_minutes)',
                'total_overtime'  => 'SUM(a.overtime_minutes)',
                'total_early'     => 'SUM(a.early_leave_minutes)',
                'mock_count'      => 'SUM(a.is_mock_location)',
            ])
            ->from('{{%hr_attendance_log}} a')
            ->leftJoin('{{%user}} u', 'u.id = a.user_id')
            ->leftJoin('{{%hr_employee_extended}} e', 'e.user_id = a.user_id')
            ->where(['between', 'a.attendance_date', $startDate, $endDate]);

        if ($filterType) {
            $query->andWhere(['e.employee_type' => $filterType]);
        }

        $query->groupBy(['a.user_id', 'u.name', 'e.employee_type'])
              ->orderBy(['u.name' => SORT_ASC]);

        $data = $query->all();

        return $this->render('monthly', [
            'data' => $data,
            'month' => $month,
            'filterType' => $filterType,
            'daysInMonth' => $daysInMonth,
        ]);
    }

    /**
     * تقرير الانضباط الوظيفي
     */
    public function actionPunctuality()
    {
        $request = Yii::$app->request;
        $dateFrom = $request->get('from', date('Y-m-01'));
        $dateTo = $request->get('to', date('Y-m-d'));

        $data = (new Query())
            ->select([
                'a.user_id',
                'u.name',
                'e.employee_type',
                'e.shift_id',
                'ws.title as shift_name',
                'total_days'      => 'COUNT(*)',
                'on_time_days'    => "SUM(CASE WHEN a.late_minutes = 0 AND a.status IN ('present','field_duty') THEN 1 ELSE 0 END)",
                'late_days'       => "SUM(CASE WHEN a.late_minutes > 0 THEN 1 ELSE 0 END)",
                'absent_days'     => "SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END)",
                'avg_clock_in'    => "TIME(SEC_TO_TIME(AVG(TIME_TO_SEC(TIME(a.clock_in_at)))))",
                'avg_late'        => 'ROUND(AVG(CASE WHEN a.late_minutes > 0 THEN a.late_minutes END), 0)',
                'total_late_min'  => 'SUM(a.late_minutes)',
                'early_leave_cnt' => "SUM(CASE WHEN a.early_leave_minutes > 0 THEN 1 ELSE 0 END)",
                'avg_work_min'    => 'ROUND(AVG(CASE WHEN a.total_minutes > 0 THEN a.total_minutes END), 0)',
            ])
            ->from('{{%hr_attendance_log}} a')
            ->leftJoin('{{%user}} u', 'u.id = a.user_id')
            ->leftJoin('{{%hr_employee_extended}} e', 'e.user_id = a.user_id')
            ->leftJoin('{{%work_shift}} ws', 'ws.id = e.shift_id')
            ->where(['between', 'a.attendance_date', $dateFrom, $dateTo])
            ->groupBy(['a.user_id', 'u.name', 'e.employee_type', 'e.shift_id', 'ws.title'])
            ->orderBy(['total_late_min' => SORT_DESC])
            ->all();

        foreach ($data as &$row) {
            $totalDays = max(1, (int)$row['total_days']);
            $onTime = (int)$row['on_time_days'];
            $row['punctuality_score'] = round(($onTime / $totalDays) * 100);
        }
        unset($row);

        return $this->render('punctuality', [
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * تقرير المخالفات (مواقع مزيّفة، خارج المنطقة)
     */
    public function actionViolations()
    {
        $request = Yii::$app->request;
        $dateFrom = $request->get('from', date('Y-m-01'));
        $dateTo = $request->get('to', date('Y-m-d'));

        $mockLogs = (new Query())
            ->select(['a.*', 'u.name'])
            ->from('{{%hr_attendance_log}} a')
            ->leftJoin('{{%user}} u', 'u.id = a.user_id')
            ->where(['between', 'a.attendance_date', $dateFrom, $dateTo])
            ->andWhere(['a.is_mock_location' => 1])
            ->orderBy(['a.attendance_date' => SORT_DESC])
            ->all();

        $mockSummary = (new Query())
            ->select(['a.user_id', 'u.name', 'cnt' => 'COUNT(*)'])
            ->from('{{%hr_attendance_log}} a')
            ->leftJoin('{{%user}} u', 'u.id = a.user_id')
            ->where(['between', 'a.attendance_date', $dateFrom, $dateTo])
            ->andWhere(['a.is_mock_location' => 1])
            ->groupBy(['a.user_id', 'u.name'])
            ->orderBy(['cnt' => SORT_DESC])
            ->all();

        $outsideZoneEvents = (new Query())
            ->select([
                'ge.user_id',
                'u.name',
                'exit_count' => 'COUNT(*)',
            ])
            ->from('{{%hr_geofence_event}} ge')
            ->leftJoin('{{%user}} u', 'u.id = ge.user_id')
            ->where(['between', 'ge.triggered_at', $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->andWhere(['ge.event_type' => 'exit'])
            ->groupBy(['ge.user_id', 'u.name'])
            ->orderBy(['exit_count' => SORT_DESC])
            ->limit(20)
            ->all();

        return $this->render('violations', [
            'mockLogs' => $mockLogs,
            'mockSummary' => $mockSummary,
            'outsideZoneEvents' => $outsideZoneEvents,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    private function getDateStats($date)
    {
        return Yii::$app->db->createCommand("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('present','late','field_duty','half_day') THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status='on_leave' THEN 1 ELSE 0 END) as on_leave,
                ROUND(AVG(CASE WHEN total_minutes>0 THEN total_minutes END),0) as avg_work_min,
                ROUND(AVG(CASE WHEN late_minutes>0 THEN late_minutes END),0) as avg_late_min
            FROM {{%hr_attendance_log}} WHERE attendance_date = :date
        ", [':date' => $date])->queryOne();
    }
}
