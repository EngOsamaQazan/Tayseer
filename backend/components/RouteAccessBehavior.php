<?php

namespace backend\components;

use Yii;
use yii\base\Behavior;
use yii\web\Application;
use yii\web\ForbiddenHttpException;
use common\helper\Permissions;

/**
 * سلوك التحقق من صلاحية الوصول حسب المسار والإجراء
 * ─────────────────────────────────────────────────────
 * طبقتان من الحماية:
 *   1) مستوى المتحكم (Controller) — يجب أن يملك المستخدم أي صلاحية من صلاحيات الوحدة
 *   2) مستوى الإجراء (Action) — يجب أن يملك صلاحية CRUD المحددة (مشاهدة/إضافة/تعديل/حذف)
 * يُربط بالتطبيق في backend\config\main.php كـ 'as routeAccess'.
 */
class RouteAccessBehavior extends Behavior
{
    /** مسارات يُسمح بالوصول لها دون فحص صلاحية (تسجيل الدخول/الخروج، خطأ، إلخ). لوحة التحكم site/index تحتاج صلاحية لوحة التحكم */
    public static $publicRoutes = [
        'site/logout',
        'site/error',
        'site/login',
        'site/import',
        'user/login',
        'user/security/login',
        'dektrium/user/security/login',
        'dektrium/user/registration/register',
        'dektrium/user/recovery/request',
        'dektrium/user/recovery/reset',
        'dektrium/user/profile/show',
        'dektrium/user/settings/profile',
        'dektrium/user/settings/account',
        'debug',
        'gii',
        'pin/toggle',
        'pin/list',
        'theme/save',
        'theme/current',
    ];

    public function events()
    {
        return [
            Application::EVENT_BEFORE_REQUEST => 'checkRouteAccess',
        ];
    }

    /**
     * استخراج معرّف المتحكم من pathInfo (مثلاً hr/hr-employee/index → hr/hr-employee)
     */
    protected static function getControllerUniqueIdFromPath($pathInfo)
    {
        $path = trim($pathInfo ?? '', '/');
        if ($path === '') {
            return 'site'; // الصفحة الرئيسية = site/index
        }
        $parts = explode('/', $path);
        if (count($parts) >= 2) {
            array_pop($parts);
            return implode('/', $parts);
        }
        return $path;
    }

    /**
     * استخراج اسم الإجراء (action) من pathInfo (مثلاً customers/customers/create → create)
     */
    protected static function getActionFromPath($pathInfo)
    {
        $path = trim($pathInfo ?? '', '/');
        if ($path === '') {
            return 'index';
        }
        $parts = explode('/', $path);
        if (count($parts) >= 2) {
            return array_pop($parts);
        }
        return 'index';
    }

    /**
     * حل المسار المختصر إلى المسار الكامل عبر urlManager.
     * مثلاً: inventoryInvoices/view/1 → inventoryInvoices/inventory-invoices/view
     */
    protected static function resolveRoute($rawPathInfo)
    {
        try {
            $result = Yii::$app->urlManager->parseRequest(Yii::$app->request);
            if ($result !== false) {
                return $result[0]; // المسار الكامل (route)
            }
        } catch (\Exception $e) {
            // في حال فشل التحليل نستخدم المسار الخام
        }
        return $rawPathInfo;
    }

    /**
     * التحقق من صلاحية الوصول للمسار الحالي
     * @param \yii\base\Event $event
     * @throws ForbiddenHttpException
     */
    public function checkRouteAccess($event)
    {
        if (!Yii::$app->request->isGet && !Yii::$app->request->isPost) {
            return;
        }
        if (Yii::$app->user->isGuest) {
            return;
        }

        $rawPathInfo = trim(Yii::$app->request->pathInfo ?? '', '/');

        // تجاهل الملفات الثابتة (صور، CSS، JS، خطوط) التي تمر عبر PHP خطأً
        if (preg_match('/\.(jpe?g|png|gif|webp|svg|ico|css|js|woff2?|ttf|eot|map|pdf)$/i', $rawPathInfo)) {
            return;
        }

        foreach (self::$publicRoutes as $public) {
            $p = trim($public, '/');
            if ($rawPathInfo === $p || $rawPathInfo === '' && $p === 'site' || strpos($rawPathInfo . '/', $p . '/') === 0) {
                return;
            }
        }

        /* حل المسار المختصر (مثل inventoryInvoices/view/1) إلى الكامل (inventoryInvoices/inventory-invoices/view) */
        $resolvedRoute = self::resolveRoute($rawPathInfo);
        $controllerId = self::getControllerUniqueIdFromPath($resolvedRoute);

        /* ── الطبقة 1: فحص المسار الكامل (controller/action) أولاً ثم الرجوع لمستوى المتحكم ── */
        $permissions = Permissions::getRequiredPermissionsForRoute($resolvedRoute);
        if ($permissions === null) {
            $permissions = Permissions::getRequiredPermissionsForRoute($controllerId);
        }
        if ($permissions === null) {
            Yii::warning("Route not in permission map: {$controllerId} (raw: {$rawPathInfo})", __METHOD__);
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }
        if ($permissions === []) {
            return;
        }

        if (!Permissions::hasAnyPermission($permissions)) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }

        /* ── الطبقة 2: فحص مستوى الإجراء (CRUD) ── */
        $actionId = self::getActionFromPath($resolvedRoute);
        $actionPerm = Permissions::getActionPermission($controllerId, $actionId);
        if ($actionPerm !== null) {
            $permsToCheck = is_array($actionPerm) ? $actionPerm : [$actionPerm];
            if (!Permissions::hasAnyPermission($permsToCheck)) {
                throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
            }
        }
    }
}