<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  نظام إدارة الصلاحيات — الصفحة الرئيسية
 *  ──────────────────────────────────────
 *  تبويبات: إسناد الصلاحيات | الأدوار | مصفوفة الصلاحيات
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\Url;
use common\helper\Permissions;

/** @var array $users */
/** @var array $namedPermissions */
/** @var array $roles */
/** @var array $roleUserCounts */
/** @var array $rolePermCounts */
/** @var array $groups */
/** @var int $totalUsers */
/** @var int $usersWithPerms */
/** @var int $totalNamedPerms */
/** @var int $totalRoles */

$this->title = 'إدارة الصلاحيات';

/* ─── تسجيل CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/permissions.css', ['depends' => ['yii\web\YiiAsset']]);

/* ─── URLs لطلبات AJAX ─── */
$getUserPermsUrl    = Url::to(['permissions-management/get-user-permissions']);
$saveUserPermsUrl   = Url::to(['permissions-management/save-user-permissions']);
$saveRoleUrl        = Url::to(['permissions-management/save-role']);
$deleteRoleUrl      = Url::to(['permissions-management/delete-role']);
$applyRoleUrl       = Url::to(['permissions-management/apply-role-to-user']);
$clonePermsUrl      = Url::to(['permissions-management/clone-permissions']);
$revokeAllUrl       = Url::to(['permissions-management/revoke-all']);
$toggleUserUrl      = Url::to(['permissions-management/toggle-user-status']);
$seedRolesUrl       = Url::to(['permissions-management/seed-roles']);
$getRolePermsUrl    = Url::to(['permissions-management/get-role-permissions']);
$ensurePermsUrl     = Url::to(['permissions-management/ensure-permissions']);
$ensureSystemAdminUrl = Url::to(['permissions-management/ensure-system-admin']);

/* ─── تحويل المجموعات إلى JSON للجافاسكربت ─── */
$groupsJson = json_encode($groups, JSON_UNESCAPED_UNICODE);

/* ─── بيانات المستخدمين JSON ─── */
$usersForClone = [];
foreach ($users as $u) {
    if ($u['perm_count'] > 0) {
        $usersForClone[] = ['id' => $u['id'], 'name' => $u['username'], 'avatar' => $u['avatar']];
    }
}
$usersJson = json_encode($usersForClone, JSON_UNESCAPED_UNICODE);

/* ─── صورة افتراضية ─── */
$defaultAvatar = Yii::getAlias('@web') . '/img/default-avatar.png';
?>

<div class="perm-page">

    <!-- ╔═══════════════════════════════════════╗
         ║  شريط الملخص العلوي                  ║
         ╚═══════════════════════════════════════╝ -->
    <section class="perm-overview">
        <div class="perm-ov-card">
            <div class="perm-ov-icon" style="background:rgba(128,0,32,.1);color:#800020">
                <i class="fa fa-users"></i>
            </div>
            <div class="perm-ov-info">
                <p class="perm-ov-label">إجمالي المستخدمين</p>
                <p class="perm-ov-value"><?= $totalUsers ?></p>
            </div>
        </div>
        <div class="perm-ov-card">
            <div class="perm-ov-icon" style="background:rgba(21,128,61,.1);color:#15803d">
                <i class="fa fa-user-plus"></i>
            </div>
            <div class="perm-ov-info">
                <p class="perm-ov-label">مستخدمون بصلاحيات</p>
                <p class="perm-ov-value"><?= $usersWithPerms ?></p>
            </div>
        </div>
        <div class="perm-ov-card">
            <div class="perm-ov-icon" style="background:rgba(29,78,216,.1);color:#1d4ed8">
                <i class="fa fa-key"></i>
            </div>
            <div class="perm-ov-info">
                <p class="perm-ov-label">الصلاحيات المسمّاة</p>
                <p class="perm-ov-value"><?= $totalNamedPerms ?></p>
            </div>
        </div>
        <div class="perm-ov-card">
            <div class="perm-ov-icon" style="background:rgba(180,83,9,.1);color:#b45309">
                <i class="fa fa-shield"></i>
            </div>
            <div class="perm-ov-info">
                <p class="perm-ov-label">الأدوار</p>
                <p class="perm-ov-value"><?= $totalRoles ?></p>
            </div>
        </div>
    </section>


    <!-- ╔═══════════════════════════════════════╗
         ║  تبويبات                              ║
         ╚═══════════════════════════════════════╝ -->
    <nav class="perm-tabs" id="permTabs">
        <button class="perm-tab active" data-tab="assignments" type="button">
            <i class="fa fa-user-plus"></i>
            <span>إسناد الصلاحيات</span>
            <span class="perm-tab-badge"><?= $usersWithPerms ?></span>
        </button>
        <button class="perm-tab" data-tab="roles" type="button">
            <i class="fa fa-shield"></i>
            <span>الأدوار</span>
            <span class="perm-tab-badge"><?= $totalRoles ?></span>
        </button>
        <button class="perm-tab" data-tab="matrix" type="button">
            <i class="fa fa-th"></i>
            <span>مصفوفة الصلاحيات</span>
        </button>
    </nav>


    <!-- ══════════════════════════════════════════════════════
         تبويب 1: إسناد الصلاحيات
         ══════════════════════════════════════════════════════ -->
    <div class="perm-tab-content active" id="tab-assignments">

        <!-- شريط البحث والإجراءات -->
        <div class="perm-toolbar">
            <div class="perm-search">
                <input type="text" id="userSearch" placeholder="ابحث عن مستخدم بالاسم أو البريد..." autocomplete="off">
                <i class="fa fa-search perm-search-icon"></i>
            </div>
            <button class="perm-btn perm-btn--outline perm-btn--sm" id="btnFilterWithPerms" type="button" title="إظهار المستخدمين بصلاحيات فقط">
                <i class="fa fa-filter"></i> بصلاحيات فقط
            </button>
            <button class="perm-btn perm-btn--outline perm-btn--sm" id="btnFilterAll" type="button" title="إظهار الجميع">
                <i class="fa fa-list"></i> الجميع
            </button>
            <button class="perm-btn perm-btn--success perm-btn--sm" id="btnEnsurePermissions" type="button" title="إنشاء جميع الصلاحيات المعرّفة في النظام في قاعدة البيانات">
                <i class="fa fa-database"></i> ضمان الصلاحيات
            </button>
            <?php
            $auth = Yii::$app->authManager;
            $currentUserRoles = array_keys($auth->getRolesByUser(Yii::$app->user->id));
            $canAssignSystemAdmin = in_array('مدير النظام', $currentUserRoles, true);
            if ($canAssignSystemAdmin): ?>
            <span class="perm-toolbar-sep" style="margin:0 8px;color:var(--perm-border);">|</span>
            <input type="email" id="ensureSystemAdminEmail" placeholder="بريد المستخدم لتعيين مدير نظام" style="width:220px;padding:6px 10px;border:1px solid var(--perm-border);border-radius:4px;font-size:13px;" dir="ltr">
            <button class="perm-btn perm-btn--primary perm-btn--sm" id="btnEnsureSystemAdmin" type="button" title="تعيين دور «مدير النظام» لهذا البريد">
                <i class="fa fa-user-plus"></i> تعيين مدير نظام
            </button>
            <?php endif; ?>
        </div>

        <!-- بطاقات المستخدمين -->
        <?php
        // فصل المستخدمين الفعالين عن المعطلين
        $activeUsers = [];
        $suspendedUsers = [];
        foreach ($users as $user) {
            $empStatus = $user['employee_type'] ?? '';
            $isBlocked = !empty($user['blocked_at'] ?? null);
            $isSuspended = ($empStatus === 'Suspended' || $isBlocked);
            if ($isSuspended) {
                $suspendedUsers[] = $user;
            } else {
                $activeUsers[] = $user;
            }
        }
        ?>
        <div class="perm-users-grid" id="usersGrid">
            <?php foreach ($activeUsers as $user): ?>
                <?php
                    $rawAvatar = $user['avatar'] ?? '';
                    $avatarSrc = (!empty($rawAvatar) && (strpos($rawAvatar, '/') !== false || strpos($rawAvatar, '.') !== false))
                        ? $rawAvatar : $defaultAvatar;
                    $hasPerms  = $user['perm_count'] > 0;
                ?>
                <div class="perm-user-card <?= !$hasPerms ? 'perm-user-card--no-perms' : '' ?>"
                     data-user-id="<?= $user['id'] ?>"
                     data-username="<?= Html::encode($user['username']) ?>"
                     data-email="<?= Html::encode($user['email']) ?>"
                     data-perm-count="<?= $user['perm_count'] ?>"
                     data-avatar="<?= Html::encode($avatarSrc) ?>"
                     data-status="Active">

                    <img class="perm-user-avatar" src="<?= Html::encode($avatarSrc) ?>"
                         onerror="this.src='<?= $defaultAvatar ?>'" alt="">

                    <div class="perm-user-info">
                        <p class="perm-user-name"><?= Html::encode($user['username']) ?></p>
                        <p class="perm-user-email"><?= Html::encode($user['email']) ?></p>
                        <div class="perm-user-meta">
                            <span class="perm-user-badge perm-user-badge--perms">
                                <i class="fa fa-key"></i> <?= $user['perm_count'] ?> صلاحية
                            </span>
                            <span class="perm-user-badge perm-user-badge--active">نشط</span>
                        </div>
                    </div>

                    <div class="perm-user-actions">
                        <?php if ($user['id'] != Yii::$app->user->id): ?>
                        <button type="button" class="perm-user-action-btn btn-toggle-user"
                                data-user-id="<?= $user['id'] ?>" title="تعطيل المستخدم">
                            <i class="fa fa-ban"></i>
                        </button>
                        <?php endif ?>
                    </div>

                    <i class="fa fa-chevron-left perm-user-arrow"></i>
                </div>
            <?php endforeach ?>

            <?php if (!empty($suspendedUsers)): ?>
            <!-- فاصل بصري بين الفعالين والمعطلين -->
            <div class="perm-users-separator" id="suspendedSeparator">
                <div class="perm-users-separator-line"></div>
                <span class="perm-users-separator-label">
                    <i class="fa fa-ban"></i> مستخدمون معطّلون (<?= count($suspendedUsers) ?>)
                </span>
                <div class="perm-users-separator-line"></div>
            </div>

            <?php foreach ($suspendedUsers as $user): ?>
                <?php
                    $rawAvatar = $user['avatar'] ?? '';
                    $avatarSrc = (!empty($rawAvatar) && (strpos($rawAvatar, '/') !== false || strpos($rawAvatar, '.') !== false))
                        ? $rawAvatar : $defaultAvatar;
                    $hasPerms  = $user['perm_count'] > 0;
                ?>
                <div class="perm-user-card perm-user-card--suspended <?= !$hasPerms ? 'perm-user-card--no-perms' : '' ?>"
                     data-user-id="<?= $user['id'] ?>"
                     data-username="<?= Html::encode($user['username']) ?>"
                     data-email="<?= Html::encode($user['email']) ?>"
                     data-perm-count="<?= $user['perm_count'] ?>"
                     data-avatar="<?= Html::encode($avatarSrc) ?>"
                     data-status="Suspended">

                    <img class="perm-user-avatar" src="<?= Html::encode($avatarSrc) ?>"
                         onerror="this.src='<?= $defaultAvatar ?>'" alt="">

                    <div class="perm-user-info">
                        <p class="perm-user-name"><?= Html::encode($user['username']) ?></p>
                        <p class="perm-user-email"><?= Html::encode($user['email']) ?></p>
                        <div class="perm-user-meta">
                            <span class="perm-user-badge perm-user-badge--perms">
                                <i class="fa fa-key"></i> <?= $user['perm_count'] ?> صلاحية
                            </span>
                            <span class="perm-user-badge perm-user-badge--suspended"><i class="fa fa-ban"></i> معطّل</span>
                        </div>
                    </div>

                    <div class="perm-user-actions">
                        <button type="button" class="perm-user-action-btn btn-toggle-user"
                                data-user-id="<?= $user['id'] ?>" title="تفعيل المستخدم">
                            <i class="fa fa-check-circle"></i>
                        </button>
                    </div>

                    <i class="fa fa-chevron-left perm-user-arrow"></i>
                </div>
            <?php endforeach ?>
            <?php endif ?>
        </div>
    </div>


    <!-- ══════════════════════════════════════════════════════
         تبويب 2: الأدوار
         ══════════════════════════════════════════════════════ -->
    <div class="perm-tab-content" id="tab-roles">

        <div class="perm-toolbar">
            <button class="perm-btn perm-btn--primary" id="btnCreateRole" type="button">
                <i class="fa fa-plus"></i> إنشاء دور جديد
            </button>
            <?php if (empty($roles)): ?>
            <button class="perm-btn perm-btn--success" id="btnSeedRoles" type="button">
                <i class="fa fa-magic"></i> إنشاء الأدوار الافتراضية
            </button>
            <?php endif ?>
        </div>

        <div class="perm-roles-grid" id="rolesGrid">
            <?php if (empty($roles)): ?>
                <!-- بطاقة إنشاء أول دور -->
                <div class="perm-role-card perm-role-card--add" id="roleAddCard">
                    <i class="fa fa-shield"></i>
                    <span>أنشئ أول دور الآن</span>
                    <small style="color:var(--perm-text3);font-size:12px">الأدوار تسهّل إدارة صلاحيات المستخدمين بشكل جماعي</small>
                </div>
            <?php else: ?>
                <?php foreach ($roles as $role): ?>
                    <div class="perm-role-card" data-role-name="<?= Html::encode($role->name) ?>">
                        <div class="perm-role-card-header">
                            <div>
                                <h4 class="perm-role-name"><?= Html::encode($role->name) ?></h4>
                                <p class="perm-role-desc"><?= Html::encode($role->description ?: '—') ?></p>
                            </div>
                            <div class="perm-role-actions">
                                <button class="perm-btn perm-btn--outline perm-btn--xs btn-edit-role" type="button"
                                        data-role="<?= Html::encode($role->name) ?>" title="تعديل">
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <button class="perm-btn perm-btn--danger perm-btn--xs btn-delete-role" type="button"
                                        data-role="<?= Html::encode($role->name) ?>" title="حذف">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="perm-role-stats">
                            <span class="perm-role-stat">
                                <i class="fa fa-key"></i>
                                <strong><?= $rolePermCounts[$role->name] ?? 0 ?></strong> صلاحية
                            </span>
                            <span class="perm-role-stat">
                                <i class="fa fa-users"></i>
                                <strong><?= $roleUserCounts[$role->name] ?? 0 ?></strong> مستخدم
                            </span>
                        </div>
                    </div>
                <?php endforeach ?>
            <?php endif ?>
        </div>
    </div>


    <!-- ══════════════════════════════════════════════════════
         تبويب 3: مصفوفة الصلاحيات
         ══════════════════════════════════════════════════════ -->
    <div class="perm-tab-content" id="tab-matrix">
        <?php
        /* ── إعداد بيانات المصفوفة ── */
        $matrixUsers = array_filter($users, fn($u) => $u['perm_count'] > 0);
        $db = Yii::$app->db;
        $userPermsMap = [];
        foreach ($matrixUsers as $mu) {
            $perms = $db->createCommand("
                SELECT item_name FROM {{%auth_assignment}}
                WHERE user_id = :uid AND item_name NOT LIKE '/%'
            ", [':uid' => $mu['id']])->queryColumn();
            $userPermsMap[$mu['id']] = $perms;
        }
        ?>
        <div class="perm-matrix-wrap">
            <table class="perm-matrix">
                <thead>
                    <tr>
                        <th>المستخدم</th>
                        <?php foreach ($groups as $gKey => $g): ?>
                            <th title="<?= Html::encode($g['label']) ?>">
                                <i class="fa <?= $g['icon'] ?>" style="margin-left:4px"></i>
                                <?= Html::encode($g['label']) ?>
                            </th>
                        <?php endforeach ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matrixUsers as $mu): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <img src="<?= Html::encode(!empty($mu['avatar']) ? $mu['avatar'] : $defaultAvatar) ?>"
                                         onerror="this.src='<?= $defaultAvatar ?>'"
                                         style="width:28px;height:28px;border-radius:50%;object-fit:cover">
                                    <span><?= Html::encode($mu['username']) ?></span>
                                </div>
                            </td>
                            <?php foreach ($groups as $gKey => $g): ?>
                                <?php
                                    $groupPerms = $g['permissions'];
                                    $userP = $userPermsMap[$mu['id']] ?? [];
                                    $has = count(array_intersect($groupPerms, $userP));
                                    $total = count($groupPerms);
                                    if ($has === $total && $total > 0) {
                                        $cls = 'perm-matrix-check--yes';
                                        $icon = '<i class="fa fa-check"></i>';
                                    } elseif ($has > 0) {
                                        $cls = 'perm-matrix-check--partial';
                                        $icon = $has . '/' . $total;
                                    } else {
                                        $cls = 'perm-matrix-check--no';
                                        $icon = '—';
                                    }
                                ?>
                                <td>
                                    <span class="perm-matrix-check <?= $cls ?>" title="<?= $has ?> من <?= $total ?>"><?= $icon ?></span>
                                </td>
                            <?php endforeach ?>
                        </tr>
                    <?php endforeach ?>
                    <?php if (empty($matrixUsers)): ?>
                        <tr>
                            <td colspan="<?= count($groups) + 1 ?>" style="text-align:center;padding:40px;color:var(--perm-text3)">
                                لا يوجد مستخدمون بصلاحيات حالياً
                            </td>
                        </tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.perm-page -->


<!-- ══════════════════════════════════════════════════════════
     مودال: محرر صلاحيات المستخدم
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade perm-modal" id="permEditorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border:none;border-radius:var(--perm-r-lg,16px);overflow:hidden">

            <!-- رأس المودال -->
            <div class="perm-modal-header" id="permModalHeader" style="display:flex!important;flex-direction:row!important;align-items:center;gap:16px;padding:20px 24px;background:linear-gradient(135deg,#800020,#a0003a)!important;color:#fff!important;flex-shrink:0;">
                <img class="perm-user-avatar" id="modalUserAvatar" src="<?= $defaultAvatar ?>" alt="" style="width:52px;height:52px;border-radius:50%;border:2px solid rgba(255,255,255,.3);flex-shrink:0;">
                <div class="perm-modal-header-info" style="flex:1;min-width:0;">
                    <h4 class="perm-modal-header-name" id="modalUserName" style="font-size:18px;font-weight:700;margin:0;color:#fff!important;line-height:1.3;">—</h4>
                    <p class="perm-modal-header-sub" id="modalUserEmail" style="font-size:13px;opacity:.8;margin:2px 0 0;color:#fff!important;">—</p>
                </div>
                <button type="button" class="perm-modal-close" data-bs-dismiss="modal" aria-label="إغلاق" style="background:rgba(255,255,255,.15);border:none;color:#fff;width:36px;height:36px;border-radius:50%;cursor:pointer;">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <!-- شريط الأدوات -->
            <div class="perm-modal-toolbar">
                <div class="perm-search" style="min-width:180px;flex:0 1 240px">
                    <input type="text" id="permSearch" placeholder="ابحث عن صلاحية..." autocomplete="off">
                    <i class="fa fa-search perm-search-icon"></i>
                </div>
                <div class="perm-toggle-btns" style="display:inline-flex;border:1px solid var(--perm-border);border-radius:var(--perm-r-sm);overflow:hidden">
                    <button class="perm-toggle-btn active" id="btnSelectAll" type="button">
                        <i class="fa fa-check-square-o"></i> تحديد الكل
                    </button>
                    <button class="perm-toggle-btn" id="btnDeselectAll" type="button">
                        <i class="fa fa-square-o"></i> إلغاء الكل
                    </button>
                </div>

                <!-- تطبيق دور -->
                <div class="perm-clone-dropdown">
                    <button class="perm-btn perm-btn--sm" id="btnApplyRole" type="button" title="تطبيق صلاحيات دور معين" style="background:#800020;color:#fff;border-color:#800020">
                        <i class="fa fa-shield"></i> تطبيق دور
                    </button>
                    <div class="perm-clone-menu" id="roleApplyMenu" style="min-width:220px">
                        <?php foreach ($roles as $role): ?>
                        <div class="perm-clone-item perm-role-apply-item" data-role-name="<?= Html::encode($role->name) ?>">
                            <i class="fa fa-shield" style="color:#800020;margin-left:6px;font-size:13px"></i>
                            <span><?= Html::encode($role->name) ?></span>
                            <small style="margin-right:auto;color:#94a3b8;font-size:10px"><?= $rolePermCounts[$role->name] ?? 0 ?> صلاحية</small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- نسخ من مستخدم آخر -->
                <div class="perm-clone-dropdown">
                    <button class="perm-btn perm-btn--outline perm-btn--sm" id="btnCloneDropdown" type="button" title="نسخ صلاحيات من مستخدم آخر">
                        <i class="fa fa-clone"></i> نسخ من
                    </button>
                    <div class="perm-clone-menu" id="cloneMenu">
                        <!-- يُملأ بالجافاسكربت -->
                    </div>
                </div>

                <span class="perm-modal-counter">
                    المحدد: <span class="count-num" id="selectedCount">0</span>
                    من <span id="totalPermsCount"><?= $totalNamedPerms ?></span>
                </span>
            </div>

            <!-- محتوى المجموعات -->
            <div class="perm-modal-body" id="permGroupsContainer" x-data="{ loading: true }">
                <div class="perm-loading" id="permLoading" x-show="loading" x-transition>
                    <div class="perm-spinner"></div>
                    <span>جارٍ تحميل الصلاحيات...</span>
                </div>

                <div class="perm-groups-grid" id="permGroupsGrid" x-show="!loading" x-transition.duration.200ms x-cloak>
                    <?php foreach ($groups as $gKey => $g): ?>
                        <div class="perm-group-card" data-group="<?= $gKey ?>" x-data="{ open: false }">
                            <div class="perm-group-header" data-toggle-group="<?= $gKey ?>"
                                 @click="if (!$event.target.closest('.perm-group-toggle')) open = !open">
                                <div class="perm-group-icon" style="background:<?= $g['color'] ?>15;color:<?= $g['color'] ?>">
                                    <i class="fa <?= $g['icon'] ?>"></i>
                                </div>
                                <div class="perm-group-info">
                                    <p class="perm-group-name"><?= Html::encode($g['label']) ?></p>
                                    <p class="perm-group-count">
                                        <span class="group-checked-count" data-group="<?= $gKey ?>">0</span>
                                        / <?= count($g['permissions']) ?> صلاحية
                                    </p>
                                </div>
                                <button class="perm-group-toggle" data-group-toggle="<?= $gKey ?>" type="button" title="تفعيل/تعطيل الكل"></button>
                                <i class="fa fa-chevron-down perm-group-chevron" :style="{ transform: open ? 'rotate(0deg)' : 'rotate(90deg)' }" style="margin-right:auto;color:#94a3b8;font-size:12px;transition:transform .2s"></i>
                            </div>
                            <div class="perm-group-body" data-group-body="<?= $gKey ?>" x-show="open" x-transition.duration.200ms x-cloak>
                                <?php foreach ($g['permissions'] as $perm):
                                    $isSub = (strpos($perm, ':') !== false);
                                    $subClass = $isSub ? 'perm-item--sub' : 'perm-item--parent';
                                ?>
                                    <div class="perm-item <?= $subClass ?>" data-perm-name="<?= Html::encode($perm) ?>" <?= $isSub ? 'style="padding-right:32px;font-size:12.5px"' : '' ?>>
                                        <input type="checkbox" class="perm-check perm-perm-check"
                                               id="perm-<?= md5($perm) ?>"
                                               value="<?= Html::encode($perm) ?>"
                                               data-group="<?= $gKey ?>">
                                        <label for="perm-<?= md5($perm) ?>">
                                            <?php if ($isSub): ?>
                                                <i class="fa fa-angle-left" style="color:#94a3b8;margin-left:4px;font-size:11px"></i>
                                            <?php endif; ?>
                                            <?= Html::encode($isSub ? explode(': ', $perm, 2)[1] : $perm) ?>
                                            <?php if (!$isSub && isset(Permissions::getPermissionHierarchy()[$perm])): ?>
                                                <span style="color:#94a3b8;font-size:11px;margin-right:4px">(الوصول الكامل)</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <!-- تذييل المودال -->
            <div class="perm-modal-footer">
                <div class="perm-modal-footer-info">
                    <i class="fa fa-info-circle"></i>
                    يتم حفظ التغييرات فورياً عند الضغط على حفظ
                </div>
                <div style="display:flex;gap:8px">
                    <button type="button" class="perm-btn perm-btn--outline" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="perm-btn perm-btn--success" id="btnSavePerms">
                        <i class="fa fa-check"></i> حفظ الصلاحيات
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     مودال: إنشاء / تعديل دور
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade perm-modal perm-role-modal" id="roleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border:none;border-radius:var(--perm-r-lg,16px);overflow:hidden">

            <div class="perm-modal-header">
                <div class="perm-modal-header-info">
                    <h4 class="perm-modal-header-name" id="roleModalTitle">إنشاء دور جديد</h4>
                    <p class="perm-modal-header-sub">حدد اسم الدور والصلاحيات التابعة له</p>
                </div>
                <button type="button" class="perm-modal-close" data-bs-dismiss="modal" aria-label="إغلاق">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div style="padding:24px;max-height:65vh;overflow-y:auto">
                <input type="hidden" id="roleOriginalName" value="">

                <div class="perm-role-form-group">
                    <label><i class="fa fa-tag" style="margin-left:6px"></i> اسم الدور</label>
                    <input type="text" id="roleName" placeholder="مثال: محاسب، مدير تحصيل..." dir="rtl">
                </div>

                <div class="perm-role-form-group">
                    <label><i class="fa fa-align-right" style="margin-left:6px"></i> الوصف</label>
                    <textarea id="roleDesc" rows="2" placeholder="وصف اختياري للدور..." dir="rtl"></textarea>
                </div>

                <div style="margin-top:20px">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--perm-text);margin-bottom:12px">
                        <i class="fa fa-key" style="margin-left:6px"></i> الصلاحيات المرتبطة
                    </label>

                    <div class="perm-search" style="margin-bottom:14px">
                        <input type="text" id="rolePermSearch" placeholder="ابحث عن صلاحية..." autocomplete="off">
                        <i class="fa fa-search perm-search-icon"></i>
                    </div>

                    <div id="rolePermGroups" style="max-height:350px;overflow-y:auto">
                        <?php foreach ($groups as $gKey => $g): ?>
                            <div style="margin-bottom:12px;border:1px solid var(--perm-border);border-radius:var(--perm-r-sm);overflow:hidden" x-data="{ open: true }">
                                <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--perm-neutral-bg);cursor:pointer"
                                     @click="if (!$event.target.closest('label,input')) open = !open">
                                    <i class="fa <?= $g['icon'] ?>" style="color:<?= $g['color'] ?>"></i>
                                    <strong style="font-size:13px;flex:1"><?= Html::encode($g['label']) ?></strong>
                                    <label style="font-size:12px;color:var(--perm-text3);cursor:pointer;margin:0" onclick="event.stopPropagation()">
                                        <input type="checkbox" class="role-group-toggle" data-rgroup="<?= $gKey ?>"
                                               style="margin-left:4px"> الكل
                                    </label>
                                </div>
                                <div style="padding:8px 14px" x-show="open" x-transition.duration.200ms x-cloak>
                                    <?php foreach ($g['permissions'] as $perm):
                                        $isSub = (strpos($perm, ':') !== false);
                                    ?>
                                        <label style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:<?= $isSub ? '12' : '13' ?>px;cursor:pointer;margin:0;<?= $isSub ? 'padding-right:20px;' : 'font-weight:500;' ?>"
                                               data-role-perm-name="<?= Html::encode($perm) ?>">
                                            <input type="checkbox" class="role-perm-check" value="<?= Html::encode($perm) ?>"
                                                   data-rgroup="<?= $gKey ?>">
                                            <?= Html::encode($isSub ? '↳ ' . explode(': ', $perm, 2)[1] : $perm) ?>
                                        </label>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>

            <div class="perm-modal-footer">
                <span style="font-size:13px;color:var(--perm-text2)">
                    المحدد: <strong id="rolePermCount">0</strong> صلاحية
                </span>
                <div style="display:flex;gap:8px">
                    <button type="button" class="perm-btn perm-btn--outline" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="perm-btn perm-btn--primary" id="btnSaveRole">
                        <i class="fa fa-check"></i> حفظ الدور
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     Toast إشعارات
     ══════════════════════════════════════════════════════════ -->
<div class="perm-toast" id="permToast"></div>


<?php
/* ═══════════════════════════════════════════════════════════════
 *  JavaScript — محرك التفاعل
 * ═══════════════════════════════════════════════════════════════ */
$js = <<<'JSBLOCK'

(function($){
    "use strict";

    /* ── متغيرات عامة ── */
    var currentUserId = null;
    var currentUserName = '';
    var GROUPS = GROUPS_DATA_PLACEHOLDER;
    var USERS_FOR_CLONE = USERS_FOR_CLONE_PLACEHOLDER;

    /* ════════════════════════════════════════════
       Toast Notification
       ════════════════════════════════════════════ */
    function showToast(msg, type) {
        var $t = $('#permToast');
        $t.text(msg).removeClass('perm-toast--success perm-toast--error perm-toast--info show')
          .addClass('perm-toast--' + (type || 'success'));
        setTimeout(function(){ $t.addClass('show'); }, 50);
        setTimeout(function(){ $t.removeClass('show'); }, 3500);
    }

    /* ════════════════════════════════════════════
       تبديل التبويبات
       ════════════════════════════════════════════ */
    $('#permTabs').on('click', '.perm-tab', function(){
        var tab = $(this).data('tab');
        $('.perm-tab').removeClass('active');
        $(this).addClass('active');
        $('.perm-tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });

    /* ════════════════════════════════════════════
       بحث المستخدمين
       ════════════════════════════════════════════ */
    var searchTimer;
    $('#userSearch').on('keyup', function(){
        clearTimeout(searchTimer);
        var q = $(this).val().toLowerCase().trim();
        searchTimer = setTimeout(function(){
            $('.perm-user-card').each(function(){
                var name  = ($(this).data('username') || '').toString().toLowerCase();
                var email = ($(this).data('email') || '').toString().toLowerCase();
                var match = !q || name.indexOf(q) > -1 || email.indexOf(q) > -1;
                $(this).toggle(match);
            });
        }, 200);
    });

    /* فلتر بصلاحيات فقط / الجميع */
    var showOnlyWithPerms = false;
    $('#btnFilterWithPerms').on('click', function(){
        showOnlyWithPerms = true;
        $(this).addClass('perm-btn--primary').removeClass('perm-btn--outline').css('color','#fff');
        $('#btnFilterAll').addClass('perm-btn--outline').removeClass('perm-btn--primary').css('color','');
        $('.perm-user-card').each(function(){
            $(this).toggle(parseInt($(this).data('perm-count')) > 0);
        });
    });
    $('#btnFilterAll').on('click', function(){
        showOnlyWithPerms = false;
        $(this).addClass('perm-btn--primary').removeClass('perm-btn--outline').css('color','#fff');
        $('#btnFilterWithPerms').addClass('perm-btn--outline').removeClass('perm-btn--primary').css('color','');
        $('.perm-user-card').show();
    });

    /* ════════════════════════════════════════════
       فتح محرر صلاحيات المستخدم
       ════════════════════════════════════════════ */
    $(document).on('click', '.perm-user-card', function(e){
        /* لا تفتح المودال إذا الضغط على زر الإجراءات */
        if ($(e.target).closest('.perm-user-actions').length) return;

        var $card     = $(this);
        currentUserId = $card.data('user-id');
        currentUserName = $card.data('username');

        /* تحديث الهيدر */
        $('#modalUserName').text($card.data('username'));
        $('#modalUserEmail').text($card.data('email'));
        var avatarVal = $card.data('avatar') || '';
        var isValidAvatar = avatarVal && avatarVal.length > 3 && (avatarVal.indexOf('/') > -1 || avatarVal.indexOf('.') > -1);
        $('#modalUserAvatar').attr('src', isValidAvatar ? avatarVal : DEFAULT_AVATAR_PLACEHOLDER);

        /* إعادة تعيين */
        $('#permSearch').val('');
        $('.perm-item').removeClass('checked perm-search-hidden');
        $('.perm-group-card').removeClass('perm-group-card--all-selected perm-search-hidden');
        $('.perm-perm-check').prop('checked', false);
        $('.perm-group-toggle').removeClass('active partial');
        var _pcData = Alpine.$data(document.getElementById('permGroupsContainer'));
        _pcData.loading = true;

        /* ملء قائمة النسخ */
        var cloneHtml = '';
        for (var i = 0; i < USERS_FOR_CLONE.length; i++) {
            var cu = USERS_FOR_CLONE[i];
            if (cu.id != currentUserId) {
                cloneHtml += '<div class="perm-clone-item" data-clone-from="' + cu.id + '">'
                    + '<img src="' + (cu.avatar || DEFAULT_AVATAR_PLACEHOLDER) + '" onerror="this.src=\'' + DEFAULT_AVATAR_PLACEHOLDER + '\'">'
                    + '<span>' + cu.name + '</span></div>';
            }
        }
        $('#cloneMenu').html(cloneHtml || '<div style="padding:12px;text-align:center;color:#999;font-size:13px">لا يوجد مستخدمون</div>');

        /* فتح المودال */
        bootstrap.Modal.getOrCreateInstance(document.getElementById('permEditorModal')).show();

        /* تحميل الصلاحيات عبر AJAX */
        $.getJSON(GET_USER_PERMS_URL_PLACEHOLDER, { id: currentUserId }, function(resp){
            if (resp.success) {
                var perms = resp.permissions || [];
                for (var p = 0; p < perms.length; p++) {
                    var $cb = $('.perm-perm-check[value="' + CSS.escape(perms[p]) + '"]');
                    $cb.prop('checked', true);
                    $cb.closest('.perm-item').addClass('checked');
                }
                updateAllGroupToggles();
                updateSelectedCount();
            }
            _pcData.loading = false;
        }).fail(function(){
            $('#permLoading').html('<div style="color:red;text-align:center">خطأ في تحميل البيانات</div>');
        });
    });

    /* ════════════════════════════════════════════
       تحديث عدد المحدد وحالة المجموعات
       ════════════════════════════════════════════ */
    function updateSelectedCount() {
        var count = $('.perm-perm-check:checked').length;
        $('#selectedCount').text(count);
    }

    function updateGroupToggle(groupKey) {
        var $checks = $('.perm-perm-check[data-group="' + groupKey + '"]');
        var total   = $checks.length;
        var checked = $checks.filter(':checked').length;
        var $toggle = $('[data-group-toggle="' + groupKey + '"]');
        var $card   = $('[data-group="' + groupKey + '"].perm-group-card');

        $toggle.removeClass('active partial');
        $card.removeClass('perm-group-card--all-selected');

        if (checked === total && total > 0) {
            $toggle.addClass('active');
            $card.addClass('perm-group-card--all-selected');
        } else if (checked > 0) {
            $toggle.addClass('partial');
        }

        $('[data-group="' + groupKey + '"].group-checked-count').text(checked);
    }

    function updateAllGroupToggles() {
        var groupKeys = Object.keys(GROUPS);
        for (var i = 0; i < groupKeys.length; i++) {
            updateGroupToggle(groupKeys[i]);
        }
    }

    /* تغيير checkbox فردي */
    $(document).on('change', '.perm-perm-check', function(){
        var $item = $(this).closest('.perm-item');
        $item.toggleClass('checked', this.checked);
        updateGroupToggle($(this).data('group'));
        updateSelectedCount();
    });

    /* Toggle مجموعة كاملة */
    $(document).on('click', '.perm-group-toggle', function(e){
        e.stopPropagation();
        var groupKey = $(this).data('group-toggle');
        var $checks  = $('.perm-perm-check[data-group="' + groupKey + '"]');
        var allChecked = $checks.length === $checks.filter(':checked').length;
        $checks.prop('checked', !allChecked).each(function(){
            $(this).closest('.perm-item').toggleClass('checked', !allChecked);
        });
        updateGroupToggle(groupKey);
        updateSelectedCount();
    });

    /* طي/فتح مجموعة — Alpine.js handles this now via x-data/x-show */

    /* تحديد الكل / إلغاء الكل */
    $('#btnSelectAll').on('click', function(){
        $('.perm-perm-check').prop('checked', true);
        $('.perm-item').addClass('checked');
        updateAllGroupToggles();
        updateSelectedCount();
        $(this).addClass('active');
        $('#btnDeselectAll').removeClass('active');
    });
    $('#btnDeselectAll').on('click', function(){
        $('.perm-perm-check').prop('checked', false);
        $('.perm-item').removeClass('checked');
        updateAllGroupToggles();
        updateSelectedCount();
        $(this).addClass('active');
        $('#btnSelectAll').removeClass('active');
    });

    /* بحث صلاحيات داخل المودال */
    var permSearchTimer;
    $('#permSearch').on('keyup', function(){
        clearTimeout(permSearchTimer);
        var q = $(this).val().toLowerCase().trim();
        permSearchTimer = setTimeout(function(){
            if (!q) {
                $('.perm-item').removeClass('perm-search-hidden');
                $('.perm-group-card').removeClass('perm-search-hidden');
                return;
            }
            $('.perm-item').each(function(){
                var name = ($(this).data('perm-name') || '').toLowerCase();
                $(this).toggleClass('perm-search-hidden', name.indexOf(q) === -1);
            });
            $('.perm-group-card').each(function(){
                var hasMatch = $(this).find('.perm-item:not(.perm-search-hidden)').length > 0;
                $(this).toggleClass('perm-search-hidden', !hasMatch);
            });
        }, 200);
    });

    /* ════════════════════════════════════════════
       نسخ صلاحيات من مستخدم آخر
       ════════════════════════════════════════════ */
    /* ════════════════════════════════════════════
       تطبيق صلاحيات دور معين
       ════════════════════════════════════════════ */
    $('#btnApplyRole').on('click', function(e){
        e.stopPropagation();
        $('#roleApplyMenu').toggleClass('show');
        $('#cloneMenu').removeClass('show');
    });

    $(document).on('click', '.perm-role-apply-item', function(){
        var roleName = $(this).data('role-name');
        $('#roleApplyMenu').removeClass('show');

        if (!confirm('سيتم تطبيق صلاحيات دور «' + roleName + '». سيتم إلغاء الصلاحيات الحالية واستبدالها. متابعة؟')) return;

        /* إلغاء الكل أولاً */
        $('.perm-perm-check').prop('checked', false);
        $('.perm-item').removeClass('checked');

        /* تحميل صلاحيات الدور */
        $.getJSON(GET_ROLE_PERMS_URL_PLACEHOLDER, { name: roleName }, function(resp){
            if (resp.success) {
                var perms = resp.permissions || [];
                for (var p = 0; p < perms.length; p++) {
                    var $cb = $('.perm-perm-check[value="' + CSS.escape(perms[p]) + '"]');
                    $cb.prop('checked', true);
                    $cb.closest('.perm-item').addClass('checked');
                }
                updateAllGroupToggles();
                updateSelectedCount();
                showToast('تم تطبيق صلاحيات «' + roleName + '» (' + perms.length + ' صلاحية) — اضغط حفظ للتأكيد', 'info');
            } else {
                showToast('فشل تحميل صلاحيات الدور', 'error');
            }
        });
    });

    $('#btnCloneDropdown').on('click', function(e){
        e.stopPropagation();
        $('#cloneMenu').toggleClass('show');
        $('#roleApplyMenu').removeClass('show');
    });
    $(document).on('click', function(){ $('#cloneMenu').removeClass('show'); $('#roleApplyMenu').removeClass('show'); });

    $(document).on('click', '.perm-clone-item', function(){
        var fromId = $(this).data('clone-from');
        $('#cloneMenu').removeClass('show');

        /* تحميل صلاحيات المستخدم المصدر */
        $.getJSON(GET_USER_PERMS_URL_PLACEHOLDER, { id: fromId }, function(resp){
            if (resp.success) {
                var perms = resp.permissions || [];
                /* إضافة صلاحياته فوق الحالية */
                for (var p = 0; p < perms.length; p++) {
                    var $cb = $('.perm-perm-check[value="' + CSS.escape(perms[p]) + '"]');
                    $cb.prop('checked', true);
                    $cb.closest('.perm-item').addClass('checked');
                }
                updateAllGroupToggles();
                updateSelectedCount();
                showToast('تم نسخ الصلاحيات — لا تنسَ الضغط على حفظ', 'info');
            }
        });
    });

    /* ════════════════════════════════════════════
       حفظ صلاحيات المستخدم
       ════════════════════════════════════════════ */
    $('#btnSavePerms').on('click', function(){
        var $btn = $(this);
        var selected = [];
        $('.perm-perm-check:checked').each(function(){ selected.push($(this).val()); });

        $btn.prop('disabled', true).html('<div class="perm-spinner" style="width:18px;height:18px;border-width:2px;display:inline-block;vertical-align:middle;margin-left:6px"></div> جارٍ الحفظ...');

        $.ajax({
            url: SAVE_USER_PERMS_URL_PLACEHOLDER,
            method: 'POST',
            data: {
                id: currentUserId,
                permissions: selected,
                _csrf: yii.getCsrfToken()
            },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    showToast(resp.message, 'success');
                    /* تحديث البطاقة */
                    var $card = $('[data-user-id="' + currentUserId + '"]');
                    $card.find('.perm-user-badge--perms').html('<i class="fa fa-key"></i> ' + resp.newCount + ' صلاحية');
                    $card.data('perm-count', resp.newCount);
                    $card.toggleClass('perm-user-card--no-perms', resp.newCount === 0);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('permEditorModal')).hide();
                } else {
                    showToast(resp.message || 'حدث خطأ', 'error');
                }
            },
            error: function(){ showToast('خطأ في الاتصال بالخادم', 'error'); },
            complete: function(){
                $btn.prop('disabled', false).html('<i class="fa fa-check"></i> حفظ الصلاحيات');
            }
        });
    });


    /* ════════════════════════════════════════════
       إدارة الأدوار
       ════════════════════════════════════════════ */
    function openRoleModal(title, name, desc, perms) {
        $('#roleModalTitle').text(title);
        $('#roleOriginalName').val(name || '');
        $('#roleName').val(name || '');
        $('#roleDesc').val(desc || '');
        /* إعادة تعيين checkboxes */
        $('.role-perm-check').prop('checked', false);
        $('.role-group-toggle').prop('checked', false);
        /* تفعيل الصلاحيات */
        if (perms && perms.length) {
            for (var i = 0; i < perms.length; i++) {
                $('.role-perm-check[value="' + CSS.escape(perms[i]) + '"]').prop('checked', true);
            }
        }
        updateRolePermCount();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('roleModal')).show();
    }

    function updateRolePermCount() {
        $('#rolePermCount').text($('.role-perm-check:checked').length);
    }

    $(document).on('change', '.role-perm-check', updateRolePermCount);

    /* Toggle group في مودال الأدوار */
    $(document).on('change', '.role-group-toggle', function(){
        var gKey = $(this).data('rgroup');
        var checked = this.checked;
        $('.role-perm-check[data-rgroup="' + gKey + '"]').prop('checked', checked);
        updateRolePermCount();
    });

    /* بحث صلاحيات في مودال الأدوار */
    $('#rolePermSearch').on('keyup', function(){
        var q = $(this).val().toLowerCase().trim();
        $('[data-role-perm-name]').each(function(){
            var name = ($(this).data('role-perm-name') || '').toLowerCase();
            $(this).toggle(!q || name.indexOf(q) > -1);
        });
    });

    /* فتح مودال إنشاء */
    $('#btnCreateRole, #roleAddCard').on('click', function(){
        openRoleModal('إنشاء دور جديد', '', '', []);
    });

    /* تعديل دور */
    $(document).on('click', '.btn-edit-role', function(e){
        e.stopPropagation();
        var roleName = $(this).data('role');
        showToast('جارٍ تحميل بيانات الدور...', 'info');
        $.ajax({
            url: GET_ROLE_PERMS_URL_PLACEHOLDER,
            method: 'GET',
            data: { name: roleName },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    var role = resp.role || {};
                    openRoleModal('تعديل الدور: ' + roleName, roleName, role.description || '', resp.permissions || []);
                } else {
                    showToast(resp.message || 'خطأ في تحميل الدور', 'error');
                }
            },
            error: function(){ showToast('خطأ في الاتصال بالخادم', 'error'); }
        });
    });

    /* حفظ الدور */
    $('#btnSaveRole').on('click', function(){
        var $btn = $(this);
        var name = $.trim($('#roleName').val());
        var desc = $.trim($('#roleDesc').val());
        var perms = [];
        $('.role-perm-check:checked').each(function(){ perms.push($(this).val()); });

        if (!name) { showToast('اسم الدور مطلوب', 'error'); return; }

        $btn.prop('disabled', true);

        $.ajax({
            url: SAVE_ROLE_URL_PLACEHOLDER,
            method: 'POST',
            data: {
                name: name,
                description: desc,
                permissions: perms,
                originalName: $('#roleOriginalName').val(),
                _csrf: yii.getCsrfToken()
            },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    showToast(resp.message, 'success');
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('roleModal')).hide();
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    showToast(resp.message, 'error');
                }
            },
            error: function(){ showToast('خطأ في الاتصال', 'error'); },
            complete: function(){ $btn.prop('disabled', false); }
        });
    });

    /* حذف دور */
    $(document).on('click', '.btn-delete-role', function(e){
        e.stopPropagation();
        var roleName = $(this).data('role');
        if (!confirm('هل أنت متأكد من حذف الدور «' + roleName + '»؟\nسيتم إزالته من جميع المستخدمين.')) return;

        $.ajax({
            url: DELETE_ROLE_URL_PLACEHOLDER,
            method: 'POST',
            data: { name: roleName, _csrf: yii.getCsrfToken() },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    showToast(resp.message, 'success');
                    $('[data-role-name="' + roleName + '"]').fadeOut(300, function(){ $(this).remove(); });
                } else {
                    showToast(resp.message, 'error');
                }
            },
            error: function(){ showToast('خطأ في الاتصال', 'error'); }
        });
    });

    /* ═══════════════════════════════════════════════════════════
     *  تعطيل / تفعيل مستخدم
     * ═══════════════════════════════════════════════════════════ */
    $(document).on('click', '.btn-toggle-user', function(e){
        e.stopPropagation();
        var $btn  = $(this);
        var $card = $btn.closest('.perm-user-card');
        var userId = $btn.data('user-id');
        var isSuspended = $card.hasClass('perm-user-card--suspended');
        var actionLabel = isSuspended ? 'تفعيل' : 'تعطيل';
        var userName = $card.data('username');

        if (!confirm('هل أنت متأكد من ' + actionLabel + ' المستخدم "' + userName + '"؟')) return;

        $.ajax({
            url: TOGGLE_USER_URL_PLACEHOLDER,
            method: 'POST',
            data: { userId: userId, _csrf: yii.getCsrfToken() },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    showToast(resp.message, 'success');
                    if (resp.newStatus === 'Suspended') {
                        $card.addClass('perm-user-card--suspended');
                        $card.attr('data-status', 'Suspended');
                        $card.find('.perm-user-badge--active').remove();
                        $card.find('.perm-user-meta').append('<span class="perm-user-badge perm-user-badge--suspended"><i class="fa fa-ban"></i> معطّل</span>');
                        $btn.html('<i class="fa fa-check-circle"></i>').attr('title', 'تفعيل المستخدم');
                    } else {
                        $card.removeClass('perm-user-card--suspended');
                        $card.attr('data-status', 'Active');
                        $card.find('.perm-user-badge--suspended').remove();
                        $card.find('.perm-user-meta').append('<span class="perm-user-badge perm-user-badge--active">نشط</span>');
                        $btn.html('<i class="fa fa-ban"></i>').attr('title', 'تعطيل المستخدم');
                    }
                } else {
                    showToast(resp.message, 'error');
                }
            },
            error: function(){ showToast('خطأ في الاتصال', 'error'); }
        });
    });


    /* ═══════════════════════════════════════════════════════════
     *  إنشاء الأدوار الافتراضية
     * ═══════════════════════════════════════════════════════════ */
    $(document).on('click', '#btnSeedRoles', function(){
        var $btn = $(this);
        if (!confirm('سيتم إنشاء الأدوار الافتراضية (مدير النظام، مستثمر، محاسب، موظفة متابعه، محامي، موظف مبيعات، مدير مبيعات، مورّد أجهزة). هل تريد المتابعة؟')) return;

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الإنشاء...');

        $.ajax({
            url: SEED_ROLES_URL_PLACEHOLDER,
            method: 'POST',
            data: { _csrf: yii.getCsrfToken() },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    showToast(resp.message, 'success');
                    setTimeout(function(){ location.reload(); }, 1200);
                } else {
                    showToast(resp.message, 'error');
                    $btn.prop('disabled', false).html('<i class="fa fa-magic"></i> إنشاء الأدوار الافتراضية');
                }
            },
            error: function(){
                showToast('خطأ في الاتصال', 'error');
                $btn.prop('disabled', false).html('<i class="fa fa-magic"></i> إنشاء الأدوار الافتراضية');
            }
        });
    });


    /* ═══════════════════════════════════════════════════════════
     *  تعيين مدير نظام — بالبريد (يعمل من المتصفح دون الحاجة لـ PHP CLI)
     * ═══════════════════════════════════════════════════════════ */
    $(document).on('click', '#btnEnsureSystemAdmin', function(){
        var email = $.trim($('#ensureSystemAdminEmail').val());
        if (!email) {
            showToast('أدخل البريد الإلكتروني للمستخدم', 'error');
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.ajax({
            url: ENSURE_SYSTEM_ADMIN_URL_PLACEHOLDER,
            method: 'POST',
            data: { email: email, _csrf: yii.getCsrfToken() },
            dataType: 'json',
            success: function(resp){
                showToast(resp.message, resp.success ? 'success' : 'error');
                if (resp.success) { setTimeout(function(){ location.reload(); }, 1500); }
            },
            error: function(){ showToast('خطأ في الاتصال', 'error'); },
            complete: function(){ $btn.prop('disabled', false); }
        });
    });


    /* ═══════════════════════════════════════════════════════════
     *  ضمان الصلاحيات — إنشاء جميع الصلاحيات المعرّفة في النظام
     * ═══════════════════════════════════════════════════════════ */
    $(document).on('click', '#btnEnsurePermissions', function(){
        var $btn = $(this);
        if (!confirm('سيتم إنشاء أي صلاحية معرّفة في مجموعات النظام (المالية، العقود، HR، المخزون، إلخ) وغير موجودة حالياً في قاعدة البيانات. هل تريد المتابعة؟')) return;

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري ضمان الصلاحيات...');

        $.ajax({
            url: ENSURE_PERMS_URL_PLACEHOLDER,
            method: 'POST',
            data: { _csrf: yii.getCsrfToken() },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    showToast(resp.message, 'success');
                    setTimeout(function(){ location.reload(); }, 1200);
                } else {
                    showToast(resp.message || 'حدث خطأ', 'error');
                }
                $btn.prop('disabled', false).html('<i class="fa fa-database"></i> ضمان الصلاحيات');
            },
            error: function(){
                showToast('خطأ في الاتصال بالخادم', 'error');
                $btn.prop('disabled', false).html('<i class="fa fa-database"></i> ضمان الصلاحيات');
            }
        });
    });

})(jQuery);

JSBLOCK;

/* ── استبدال placeholders بقيم PHP ── */
$js = str_replace('GROUPS_DATA_PLACEHOLDER', $groupsJson, $js);
$js = str_replace('USERS_FOR_CLONE_PLACEHOLDER', $usersJson, $js);
$js = str_replace('DEFAULT_AVATAR_PLACEHOLDER', "'" . addslashes($defaultAvatar) . "'", $js);
$js = str_replace('GET_USER_PERMS_URL_PLACEHOLDER', "'" . $getUserPermsUrl . "'", $js);
$js = str_replace('SAVE_USER_PERMS_URL_PLACEHOLDER', "'" . $saveUserPermsUrl . "'", $js);
$js = str_replace('SAVE_ROLE_URL_PLACEHOLDER', "'" . $saveRoleUrl . "'", $js);
$js = str_replace('DELETE_ROLE_URL_PLACEHOLDER', "'" . $deleteRoleUrl . "'", $js);
$js = str_replace('APPLY_ROLE_URL_PLACEHOLDER', "'" . $applyRoleUrl . "'", $js);
$js = str_replace('CLONE_PERMS_URL_PLACEHOLDER', "'" . $clonePermsUrl . "'", $js);
$js = str_replace('REVOKE_ALL_URL_PLACEHOLDER', "'" . $revokeAllUrl . "'", $js);
$js = str_replace('TOGGLE_USER_URL_PLACEHOLDER', "'" . $toggleUserUrl . "'", $js);
$js = str_replace('SEED_ROLES_URL_PLACEHOLDER', "'" . $seedRolesUrl . "'", $js);
$js = str_replace('GET_ROLE_PERMS_URL_PLACEHOLDER', "'" . $getRolePermsUrl . "'", $js);
$js = str_replace('ENSURE_PERMS_URL_PLACEHOLDER', "'" . $ensurePermsUrl . "'", $js);
$js = str_replace('ENSURE_SYSTEM_ADMIN_URL_PLACEHOLDER', "'" . $ensureSystemAdminUrl . "'", $js);

$this->registerJs($js, \yii\web\View::POS_READY);
?>
