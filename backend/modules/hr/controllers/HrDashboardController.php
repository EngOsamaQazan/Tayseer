<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use common\helper\Permissions;

/**
 * HrDashboardController - لوحة تحكم الموارد البشرية
 * يتطلب أحد صلاحيات الموارد البشرية.
 */
class HrDashboardController extends Controller
{
    /**
     * {@inheritdoc}
     */
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
     * Dashboard index - عرض لوحة التحكم الرئيسية للموارد البشرية
     *
     * @return string
     */
    public function actionIndex()
    {
        $db = Yii::$app->db;
        $today = date('Y-m-d');
        $thirtyDaysLater = date('Y-m-d', strtotime('+30 days'));
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

        // Total employees (confirmed users)
        $totalEmployees = 0;
        try {
            $totalEmployees = (int) $db->createCommand(
                "SELECT COUNT(*) FROM os_user WHERE confirmed_at IS NOT NULL"
            )->queryScalar();
        } catch (\Exception $e) {
            Yii::error('HR Dashboard - Total employees query failed: ' . $e->getMessage());
        }

        // Active employees
        $activeEmployees = 0;
        try {
            $activeEmployees = (int) $db->createCommand(
                "SELECT COUNT(*) FROM os_user WHERE confirmed_at IS NOT NULL AND employee_type = 'Active'"
            )->queryScalar();
        } catch (\Exception $e) {
            Yii::error('HR Dashboard - Active employees query failed: ' . $e->getMessage());
        }

        // Present today
        $presentToday = 0;
        try {
            $presentToday = (int) $db->createCommand(
                "SELECT COUNT(*) FROM os_hr_attendance WHERE attendance_date = :today AND status = 'present'",
                [':today' => $today]
            )->queryScalar();
        } catch (\Exception $e) {
            Yii::error('HR Dashboard - Present today query failed: ' . $e->getMessage());
        }

        // On leave today (from attendance OR leave requests)
        $onLeaveToday = 0;
        try {
            $fromAttendance = (int) $db->createCommand(
                "SELECT COUNT(*) FROM os_hr_attendance WHERE attendance_date = :today AND status = 'leave'",
                [':today' => $today]
            )->queryScalar();

            $fromLeaveRequest = (int) $db->createCommand(
                "SELECT COUNT(DISTINCT created_by) FROM os_leave_request WHERE status = 'approved' AND :today BETWEEN start_at AND end_at",
                [':today' => $today]
            )->queryScalar();

            $onLeaveToday = max($fromAttendance, $fromLeaveRequest);
        } catch (\Exception $e) {
            Yii::error('HR Dashboard - On leave today query failed: ' . $e->getMessage());
        }

        // Pending leave requests
        $pendingLeaveRequests = 0;
        try {
            $pendingLeaveRequests = (int) $db->createCommand(
                "SELECT COUNT(*) FROM os_leave_request WHERE status = 'under review'"
            )->queryScalar();
        } catch (\Exception $e) {
            Yii::error('HR Dashboard - Pending leave requests query failed: ' . $e->getMessage());
        }

        // Department headcount
        $departmentHeadcount = [];
        try {
            $departmentHeadcount = $db->createCommand(
                "SELECT d.title AS department_name, COUNT(u.id) AS headcount
                 FROM os_user u
                 LEFT JOIN os_department d ON d.id = u.department
                 WHERE u.confirmed_at IS NOT NULL
                 GROUP BY u.department, d.title
                 ORDER BY headcount DESC"
            )->queryAll();
        } catch (\Exception $e) {
            Yii::error('HR Dashboard - Department headcount query failed: ' . $e->getMessage());
        }

        // Recent 30 days attendance rate
        $attendanceRate = 0;
        try {
            $totalWorkDays = (int) $db->createCommand(
                "SELECT COUNT(*) FROM os_hr_attendance WHERE attendance_date >= :fromDate AND attendance_date <= :today",
                [':fromDate' => $thirtyDaysAgo, ':today' => $today]
            )->queryScalar();

            $presentDays = (int) $db->createCommand(
                "SELECT COUNT(*) FROM os_hr_attendance WHERE attendance_date >= :fromDate AND attendance_date <= :today AND status = 'present'",
                [':fromDate' => $thirtyDaysAgo, ':today' => $today]
            )->queryScalar();

            $attendanceRate = $totalWorkDays > 0 ? round(($presentDays / $totalWorkDays) * 100, 1) : 0;
        } catch (\Exception $e) {
            Yii::error('HR Dashboard - Attendance rate query failed: ' . $e->getMessage());
        }

        // Expiring documents (within next 30 days)
        $expiringDocuments = 0;
        try {
            $expiringDocuments = (int) $db->createCommand(
                "SELECT COUNT(*) FROM os_hr_employee_document WHERE expiry_date BETWEEN :today AND :futureDate AND is_deleted = 0",
                [':today' => $today, ':futureDate' => $thirtyDaysLater]
            )->queryScalar();
        } catch (\Exception $e) {
            Yii::error('HR Dashboard - Expiring documents query failed: ' . $e->getMessage());
        }

        // Field staff on duty
        $fieldStaffOnDuty = 0;
        try {
            $fieldStaffOnDuty = (int) $db->createCommand(
                "SELECT COUNT(*) FROM os_hr_field_session WHERE status = 'active'"
            )->queryScalar();
        } catch (\Exception $e) {
            Yii::error('HR Dashboard - Field staff on duty query failed: ' . $e->getMessage());
        }

        // Monthly payroll total (latest payroll run)
        $monthlyPayrollTotal = 0;
        try {
            $monthlyPayrollTotal = (float) $db->createCommand(
                "SELECT COALESCE(total_net, 0) FROM os_hr_payroll_run ORDER BY id DESC LIMIT 1"
            )->queryScalar();
        } catch (\Exception $e) {
            Yii::error('HR Dashboard - Monthly payroll total query failed: ' . $e->getMessage());
        }

        return $this->render('index', [
            'totalEmployees' => $totalEmployees,
            'activeEmployees' => $activeEmployees,
            'presentToday' => $presentToday,
            'onLeaveToday' => $onLeaveToday,
            'pendingLeaveRequests' => $pendingLeaveRequests,
            'departmentHeadcount' => $departmentHeadcount,
            'attendanceRate' => $attendanceRate,
            'expiringDocuments' => $expiringDocuments,
            'fieldStaffOnDuty' => $fieldStaffOnDuty,
            'monthlyPayrollTotal' => $monthlyPayrollTotal,
        ]);
    }
}
