<?php
/**
 * نموذج بيانات المستخدم / الموظف — بدون ActiveForm
 * فورم HTML عادي مع CSRF يدوي
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\db\Query;

/** @var yii\web\View $this */
/** @var backend\modules\hr\models\HrEmployeeExtended $model */
/** @var array|null $userList */

/* ─── جلب بيانات القوائم المنسدلة ─── */

if (!isset($designations)) {
    try {
        $designations = ArrayHelper::map(
            (new Query())->select(['id', 'title'])->from('{{%designation}}')->where(['status' => 'active'])->orderBy(['title' => SORT_ASC])->all(),
            'id', 'title'
        );
    } catch (\Exception $e) { $designations = []; }
}

if (!isset($departments)) {
    try {
        $departments = ArrayHelper::map(
            (new Query())->select(['id', 'title'])->from('{{%department}}')->where(['status' => 'active'])->orderBy(['title' => SORT_ASC])->all(),
            'id', 'title'
        );
    } catch (\Exception $e) { $departments = []; }
}

if (!isset($grades)) {
    try {
        $grades = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%hr_grade}}')->where(['is_deleted' => 0])->all(),
            'id', 'name'
        );
    } catch (\Exception $e) { $grades = []; }
}

if (!isset($branches)) {
    try {
        $branches = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%branch}}')->where(['is_active' => 1])->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])->all(),
            'id', 'name'
        );
    } catch (\Exception $e) {
        $branches = ArrayHelper::map(
            (new Query())->select(['id', 'location'])->from('{{%location}}')->where(['status' => 'active'])->orderBy(['location' => SORT_ASC])->all(),
            'id', 'location'
        );
    }
}

if (!isset($shifts)) {
    try {
        $shifts = ArrayHelper::map(
            (new Query())->select(['id', 'title'])->from('{{%work_shift}}')->where(['is_active' => 1])->all(),
            'id', 'title'
        );
    } catch (\Exception $e) { $shifts = []; }
}

if (!isset($workZones)) {
    try {
        $workZones = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%branch}}')->where(['is_active' => 1])->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])->all(),
            'id', 'name'
        );
    } catch (\Exception $e) {
        $workZones = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%hr_work_zone}}')->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all(),
            'id', 'name'
        );
    }
}

$employeeTypes = [
    'office'  => 'مكتبي',
    'field'   => 'ميداني',
    'sales'   => 'مبيعات',
    'hybrid'  => 'مختلط',
];

$trackingModes = [
    'geofence_only' => 'سياج جغرافي فقط (تسجيل دخول/خروج تلقائي)',
    'continuous'    => 'تتبع مستمر (موظفين ميدانيين)',
    'on_task'       => 'تتبع أثناء المهام فقط',
    'disabled'      => 'معطّل',
];

if (!isset($cities)) {
    try {
        $cities = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%city}}')->all(),
            'id', 'name'
        );
    } catch (\Exception $e) { $cities = []; }
}

if (!isset($banks)) {
    try {
        $banks = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%bancks}}')->all(),
            'id', 'name'
        );
    } catch (\Exception $e) { $banks = []; }
}

$contractTypes = [
    'permanent' => 'دائم',
    'contract'  => 'مؤقت/عقد',
    'probation' => 'تحت التجربة',
    'freelance' => 'عمل حر',
];

$bloodTypes = [
    'A+'  => 'A+',  'A-'  => 'A-',
    'B+'  => 'B+',  'B-'  => 'B-',
    'AB+' => 'AB+', 'AB-' => 'AB-',
    'O+'  => 'O+',  'O-'  => 'O-',
];

$fieldRoles = [
    'collector' => 'محصّل',
    'inspector' => 'مفتش',
    'driver'    => 'سائق',
    'messenger' => 'مراسل',
    'lawyer'    => 'محامي',
    'other'     => 'أخرى',
];

$isNewRecord = $model->isNewRecord;

/* ─── فئات المستخدم ─── */
$userCategories = \backend\models\UserCategory::findActive()->orderBy(['sort_order' => SORT_ASC])->all();
$selectedCatIds = [];
if (!$isNewRecord && $model->user_id) {
    $user = \common\models\User::findOne($model->user_id);
    if ($user) $selectedCatIds = $user->getCategoryIds();
}
if (!empty(Yii::$app->request->post('user_categories'))) {
    $selectedCatIds = Yii::$app->request->post('user_categories');
}

/* ─── فحص وجود فئة موظف محددة (أي فئة تتطلب بيانات HR) ─── */
$hrSlugs = ['employee', 'sales_employee', 'branch_manager', 'court_agent', 'manager'];
$isEmployeeSelected = false;
foreach ($userCategories as $cat) {
    if (in_array($cat->slug, $hrSlugs) && in_array($cat->id, $selectedCatIds)) {
        $isEmployeeSelected = true;
        break;
    }
}

/* فرع الموظف (لموظف مبيعات فقط) — من os_user.location */
$currentUserLocation = null;
if ($model->user_id) {
    $u = \common\models\User::findOne($model->user_id);
    if ($u && isset($u->location)) $currentUserLocation = $u->location;
}
$selectedLocation = Yii::$app->request->post('user_location', $currentUserLocation);
$salesEmployeeCatId = null;
foreach ($userCategories as $cat) {
    if ($cat->slug === 'sales_employee') $salesEmployeeCatId = $cat->id;
}
$isBranchRoleSelected = $salesEmployeeCatId && in_array($salesEmployeeCatId, $selectedCatIds);

$formAction = Url::to($isNewRecord ? ['create'] : ['update', 'id' => $model->isNewRecord ? 0 : $model->id]);
?>

<div class="hr-employee-form">

    <!-- ══════ فورم HTML عادي — بدون ActiveForm ══════ -->
    <form method="post" action="<?= $formAction ?>" id="hr-plain-form">
        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">

        <?php if ($model->hasErrors()): ?>
            <div class="alert alert-danger" style="border-radius:10px;border:none;font-size:13px;">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>يرجى تصحيح الأخطاء التالية:</strong>
                <ul style="margin:8px 0 0;padding-right:20px;">
                    <?php foreach ($model->getErrors() as $attr => $errs): ?>
                        <?php foreach ($errs as $err): ?>
                            <li><?= Html::encode($err) ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger" style="border-radius:10px;border:none;font-size:13px;">
                <i class="fa fa-exclamation-triangle"></i>
                <?= Yii::$app->session->getFlash('error') ?>
            </div>
        <?php endif; ?>

        <!-- ═══════════════════════════════════════════
             القسم 0: تصنيف المستخدم (فئات تشغيلية)
             ═══════════════════════════════════════════ -->
        <div class="hr-form-section">
            <div class="hr-form-section-header">
                <i class="fa fa-tags"></i>
                <span>تصنيف المستخدم</span>
            </div>
            <div class="hr-form-section-body">
                <p style="font-size:12px;color:#64748B;margin-bottom:10px">
                    <i class="fa fa-info-circle"></i>
                    اختر فئة واحدة أو أكثر. إذا تم اختيار "موظف" ستظهر جميع حقول HR التفصيلية.
                </p>
                <div class="hr-category-picker" id="hr-category-picker">
                    <?php foreach ($userCategories as $cat): ?>
                    <label class="hr-cat-card <?= in_array($cat->id, $selectedCatIds) ? 'selected' : '' ?>"
                           data-slug="<?= Html::encode($cat->slug) ?>">
                        <input type="checkbox" name="user_categories[]" value="<?= $cat->id ?>"
                            <?= in_array($cat->id, $selectedCatIds) ? 'checked' : '' ?>
                            class="hr-cat-check" style="display:none">
                        <div class="hr-cat-icon" style="background:<?= Html::encode($cat->color) ?>20;color:<?= Html::encode($cat->color) ?>">
                            <i class="fa <?= Html::encode($cat->icon) ?>"></i>
                        </div>
                        <div class="hr-cat-info">
                            <div class="hr-cat-name"><?= Html::encode($cat->name_ar) ?></div>
                            <?php if ($cat->name_en): ?>
                            <div class="hr-cat-name-en"><?= Html::encode($cat->name_en) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="hr-cat-check-icon"><i class="fa fa-check-circle"></i></div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- اختيار الفرع — يظهر فقط عند اختيار "موظف مبيعات" -->
        <div class="hr-form-section hr-branch-section" id="hr-branch-section" style="<?= $isBranchRoleSelected ? '' : 'display:none' ?>">
            <div class="hr-form-section-header">
                <i class="fa fa-building"></i>
                <span>الفرع (لربط موظف المبيعات بالفواتير وإشعارات الاستلام)</span>
            </div>
            <div class="hr-form-section-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="hr-form-label" for="user-location">الفرع <span class="text-danger">*</span></label>
                            <select name="user_location" id="user-location" class="form-control hr-form-input">
                                <option value="">— اختر الفرع —</option>
                                <?php foreach ($branches as $bid => $bname): ?>
                                <option value="<?= (int)$bid ?>" <?= ((string)$selectedLocation === (string)$bid) ? 'selected' : '' ?>><?= Html::encode($bname) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="hint-block">يرتبط موظف المبيعات بفرع واحد لاستلام إشعارات الفواتير وتأكيد الاستلام.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
             القسم 1: بيانات أساسية
             ═══════════════════════════════════════════ -->
        <div class="hr-form-section">
            <div class="hr-form-section-header">
                <i class="fa fa-user"></i>
                <span>بيانات أساسية</span>
            </div>
            <div class="hr-form-section-body">

                <?php if ($isNewRecord): ?>
                    <input type="hidden" name="create_new_user" id="hr-create-new-user" value="0">

                    <div class="hr-user-mode-toggle">
                        <button type="button" class="hr-mode-btn active" data-mode="existing">
                            <i class="fa fa-user"></i> اختيار مستخدم موجود
                        </button>
                        <button type="button" class="hr-mode-btn" data-mode="new">
                            <i class="fa fa-user-plus"></i> إنشاء مستخدم جديد
                        </button>
                    </div>

                    <!-- Mode 1: اختيار مستخدم موجود -->
                    <div id="hr-existing-user-panel">
                        <?php if (!empty($userList)): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="hr-form-label">المستخدم <span class="text-danger">*</span></label>
                                    <?= Html::activeDropDownList($model, 'user_id', $userList, [
                                        'class' => 'form-control hr-form-input',
                                        'prompt' => '— اختر المستخدم —',
                                        'id' => 'hr-user-id-select',
                                    ]) ?>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning" style="border-radius:10px;border:none;margin:12px 0">
                            <i class="fa fa-info-circle"></i>
                            لا يوجد مستخدمون بدون ملفات موظفين موسعة. استخدم "إنشاء مستخدم جديد" لإضافة موظف.
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mode 2: إنشاء مستخدم جديد -->
                    <div id="hr-new-user-panel" style="display:none">
                        <div class="hr-new-user-card">
                            <div class="hr-new-user-card-header">
                                <i class="fa fa-user-plus"></i> بيانات المستخدم الجديد
                            </div>
                            <div class="hr-new-user-card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="hr-form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                            <input type="text" name="new_user_name" class="form-control hr-form-input"
                                                   placeholder="مثال: أحمد محمد" dir="rtl"
                                                   value="<?= Html::encode(Yii::$app->request->post('new_user_name', '')) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="hr-form-label">اسم المستخدم <span class="text-danger">*</span></label>
                                            <input type="text" name="new_user_username" class="form-control hr-form-input"
                                                   placeholder="مثال: ahmed.m" dir="ltr" style="text-align:left"
                                                   value="<?= Html::encode(Yii::$app->request->post('new_user_username', '')) ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="hr-form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                            <input type="email" name="new_user_email" class="form-control hr-form-input"
                                                   placeholder="مثال: ahmed@company.com" dir="ltr" style="text-align:left"
                                                   value="<?= Html::encode(Yii::$app->request->post('new_user_email', '')) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="hr-form-label">كلمة المرور <span class="text-danger">*</span></label>
                                            <input type="password" name="new_user_password" class="form-control hr-form-input"
                                                   placeholder="6 أحرف على الأقل" dir="ltr" style="text-align:left">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="hr-form-label">رقم الجوال</label>
                                            <input type="text" name="new_user_mobile" class="form-control hr-form-input"
                                                   placeholder="07XXXXXXXX" dir="ltr" style="text-align:left"
                                                   value="<?= Html::encode(Yii::$app->request->post('new_user_mobile', '')) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$isNewRecord && $model->user_id): ?>
                    <?php
                    $editUser = \common\models\User::findOne($model->user_id);
                    ?>
                    <?php if ($editUser): ?>
                    <div class="hr-new-user-card" style="border-style:solid;background:#f8fafc;margin-bottom:16px;">
                        <div class="hr-new-user-card-header" style="background:#e2e8f0;border-color:#cbd5e1;color:#1e293b;">
                            <i class="fa fa-pencil"></i> تعديل بيانات المستخدم
                        </div>
                        <div class="hr-new-user-card-body" style="padding:18px;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hr-form-label">الاسم الكامل</label>
                                        <input type="text" name="edit_user_name" class="form-control hr-form-input"
                                               value="<?= Html::encode($editUser->name) ?>" dir="rtl">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hr-form-label">اسم المستخدم</label>
                                        <input type="text" name="edit_user_username" class="form-control hr-form-input"
                                               value="<?= Html::encode($editUser->username) ?>" dir="ltr" style="text-align:left">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hr-form-label">البريد الإلكتروني</label>
                                        <input type="email" name="edit_user_email" class="form-control hr-form-input"
                                               value="<?= Html::encode($editUser->email) ?>" dir="ltr" style="text-align:left">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hr-form-label">كلمة المرور الجديدة</label>
                                        <input type="password" name="edit_user_password" class="form-control hr-form-input"
                                               placeholder="اتركها فارغة إذا لا تريد التغيير" dir="ltr" style="text-align:left">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hr-form-label">رقم الجوال</label>
                                        <input type="text" name="edit_user_mobile" class="form-control hr-form-input"
                                               value="<?= Html::encode($editUser->mobile) ?>" dir="ltr" style="text-align:left"
                                               placeholder="07XXXXXXXX">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- المسمى الوظيفي والقسم — يظهر فقط للموظف -->
                <div class="hr-employee-only" style="<?= $isEmployeeSelected ? '' : 'display:none' ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="hr-form-label" for="user-designation">المسمى الوظيفي <span class="text-danger">*</span></label>
                                <select name="user_designation" id="user-designation" class="form-control hr-form-input">
                                    <option value="">— اختر المسمى الوظيفي —</option>
                                    <?php foreach ($designations as $dId => $dTitle): ?>
                                    <option value="<?= $dId ?>"
                                        <?= (Yii::$app->request->post('user_designation') == $dId || (!$model->isNewRecord && $model->user && $model->user->job_title == $dId)) ? 'selected' : '' ?>>
                                        <?= Html::encode($dTitle) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="hint-block">سيتم إسناد الصلاحيات تلقائياً بناءً على المسمى الوظيفي</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="hr-form-label" for="user-department">القسم</label>
                                <select name="user_department" id="user-department" class="form-control hr-form-input">
                                    <option value="">— اختر القسم —</option>
                                    <?php foreach ($departments as $depId => $depTitle): ?>
                                    <option value="<?= $depId ?>"
                                        <?= (Yii::$app->request->post('user_department') == $depId || (!$model->isNewRecord && $model->user && $model->user->department == $depId)) ? 'selected' : '' ?>>
                                        <?= Html::encode($depTitle) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="hr-form-label" for="emp-code">رقم الموظف</label>
                                <?= Html::activeTextInput($model, 'employee_code', [
                                    'class' => 'form-control hr-form-input text-left',
                                    'maxlength' => 20,
                                    'placeholder' => 'مثال: EMP-0001',
                                    'dir' => 'ltr',
                                    'id' => 'emp-code',
                                ]) ?>
                                <span class="hint-block">اتركه فارغاً للتوليد التلقائي</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="hr-form-label" for="emp-natid">رقم الهوية الوطنية</label>
                                <?= Html::activeTextInput($model, 'national_id', [
                                    'class' => 'form-control hr-form-input text-left',
                                    'maxlength' => 20,
                                    'placeholder' => 'رقم الهوية الوطنية',
                                    'dir' => 'ltr',
                                    'id' => 'emp-natid',
                                ]) ?>
                            </div>
                        </div>
                        <div class="col-md-4"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="hr-form-label" for="emp-dob">تاريخ الميلاد</label>
                                <?= Html::activeInput('date', $model, 'date_of_birth', [
                                    'class' => 'form-control hr-form-input text-left',
                                    'id' => 'emp-dob',
                                ]) ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="hr-form-label" for="emp-blood">فصيلة الدم</label>
                                <?= Html::activeDropDownList($model, 'blood_type', $bloodTypes, [
                                    'class' => 'form-control hr-form-input',
                                    'prompt' => '— اختر فصيلة الدم —',
                                    'id' => 'emp-blood',
                                ]) ?>
                            </div>
                        </div>
                        <div class="col-md-4"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="hr-form-label" for="emp-ctype">نوع العقد</label>
                                <?= Html::activeDropDownList($model, 'contract_type', $contractTypes, [
                                    'class' => 'form-control hr-form-input',
                                    'prompt' => '— اختر نوع العقد —',
                                    'id' => 'emp-ctype',
                                ]) ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="hr-form-label" for="emp-cstart">بداية العقد</label>
                                <?= Html::activeInput('date', $model, 'contract_start', [
                                    'class' => 'form-control hr-form-input text-left',
                                    'id' => 'emp-cstart',
                                ]) ?>
                            </div>
                        </div>
                        <div class="col-md-4"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="hr-form-label" for="emp-cend">نهاية العقد</label>
                                <?= Html::activeInput('date', $model, 'contract_end', [
                                    'class' => 'form-control hr-form-input text-left',
                                    'id' => 'emp-cend',
                                ]) ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="hr-form-label" for="emp-probend">نهاية فترة التجربة</label>
                                <?= Html::activeInput('date', $model, 'probation_end', [
                                    'class' => 'form-control hr-form-input text-left',
                                    'id' => 'emp-probend',
                                ]) ?>
                            </div>
                        </div>
                        <div class="col-md-4"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
             القسم 2: بيانات مالية (موظف فقط)
             ═══════════════════════════════════════════ -->
        <div class="hr-form-section hr-employee-only" style="<?= $isEmployeeSelected ? '' : 'display:none' ?>">
            <div class="hr-form-section-header">
                <i class="fa fa-university"></i>
                <span>بيانات مالية</span>
            </div>
            <div class="hr-form-section-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-salary">الراتب الأساسي</label>
                            <?= Html::activeInput('number', $model, 'basic_salary', [
                                'class' => 'form-control hr-form-input text-left',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '0.00',
                                'dir' => 'ltr',
                                'id' => 'emp-salary',
                            ]) ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-bank">البنك</label>
                            <?= Html::activeDropDownList($model, 'bank_id', $banks, [
                                'class' => 'form-control hr-form-input',
                                'prompt' => '— اختر البنك —',
                                'id' => 'emp-bank',
                            ]) ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-bankno">رقم الحساب البنكي</label>
                            <?= Html::activeTextInput($model, 'bank_account_no', [
                                'class' => 'form-control hr-form-input text-left',
                                'maxlength' => 50,
                                'placeholder' => 'رقم الحساب البنكي',
                                'dir' => 'ltr',
                                'id' => 'emp-bankno',
                            ]) ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-ssn">رقم الضمان الاجتماعي</label>
                            <?= Html::activeTextInput($model, 'social_security_no', [
                                'class' => 'form-control hr-form-input text-left',
                                'maxlength' => 50,
                                'placeholder' => 'رقم الضمان الاجتماعي',
                                'dir' => 'ltr',
                                'id' => 'emp-ssn',
                            ]) ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-tax">الرقم الضريبي</label>
                            <?= Html::activeTextInput($model, 'tax_number', [
                                'class' => 'form-control hr-form-input text-left',
                                'maxlength' => 50,
                                'placeholder' => 'الرقم الضريبي',
                                'dir' => 'ltr',
                                'id' => 'emp-tax',
                            ]) ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-city">المدينة</label>
                            <?= Html::activeDropDownList($model, 'city_id', $cities, [
                                'class' => 'form-control hr-form-input',
                                'prompt' => '— اختر المدينة —',
                                'id' => 'emp-city',
                            ]) ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-addr">العنوان التفصيلي</label>
                            <?= Html::activeTextarea($model, 'address_text', [
                                'class' => 'form-control hr-form-textarea',
                                'rows' => 2,
                                'placeholder' => 'العنوان التفصيلي',
                                'id' => 'emp-addr',
                            ]) ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-iban">IBAN</label>
                            <?= Html::activeTextInput($model, 'iban', [
                                'class' => 'form-control hr-form-input text-left',
                                'maxlength' => 34,
                                'placeholder' => 'مثال: SA0000000000000000000000',
                                'dir' => 'ltr',
                                'style' => 'letter-spacing:1px;font-family:monospace',
                                'id' => 'emp-iban',
                            ]) ?>
                        </div>
                    </div>
                    <div class="col-md-6"></div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
             القسم 3: بيانات وظيفية (موظف فقط)
             ═══════════════════════════════════════════ -->
        <div class="hr-form-section hr-employee-only" style="<?= $isEmployeeSelected ? '' : 'display:none' ?>">
            <div class="hr-form-section-header">
                <i class="fa fa-briefcase"></i>
                <span>بيانات وظيفية</span>
            </div>
            <div class="hr-form-section-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-grade">الدرجة الوظيفية</label>
                            <?= Html::activeDropDownList($model, 'grade_id', $grades, [
                                'class' => 'form-control hr-form-input',
                                'prompt' => '— اختر الدرجة الوظيفية —',
                                'id' => 'emp-grade',
                            ]) ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-branch">الفرع</label>
                            <?= Html::activeDropDownList($model, 'unified_branch_id', $branches, [
                                'class' => 'form-control hr-form-input',
                                'prompt' => '— اختر الفرع —',
                                'id' => 'emp-branch',
                            ]) ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-shift">الوردية</label>
                            <?= Html::activeDropDownList($model, 'shift_id', $shifts, [
                                'class' => 'form-control hr-form-input',
                                'prompt' => '— اختر الوردية —',
                                'id' => 'emp-shift',
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
             القسم 4: التتبع ومنطقة العمل (موظف فقط)
             ═══════════════════════════════════════════ -->
        <div class="hr-form-section hr-employee-only" style="<?= $isEmployeeSelected ? '' : 'display:none' ?>">
            <div class="hr-form-section-header">
                <i class="fa fa-map-marker"></i>
                <span>التتبع ومنطقة العمل</span>
            </div>
            <div class="hr-form-section-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-type">نوع الموظف</label>
                            <?= Html::activeDropDownList($model, 'employee_type', $employeeTypes, [
                                'class' => 'form-control hr-form-input',
                                'id' => 'emp-type',
                                'onchange' => 'onEmployeeTypeChange(this.value)',
                            ]) ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-wzone">منطقة العمل (Geofence)</label>
                            <?= Html::activeDropDownList($model, 'work_zone_id', $workZones, [
                                'class' => 'form-control hr-form-input',
                                'prompt' => '— اختر منطقة العمل —',
                                'id' => 'emp-wzone',
                            ]) ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-tracking">وضع التتبع</label>
                            <?= Html::activeDropDownList($model, 'tracking_mode', $trackingModes, [
                                'class' => 'form-control hr-form-input',
                                'id' => 'emp-tracking',
                            ]) ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="hr-form-label">موظف ميداني</label>
                            <div class="hr-checkbox-wrap">
                                <label style="font-size:13px;font-weight:500;color:#475569;cursor:pointer">
                                    <input type="hidden" name="HrEmployeeExtended[is_field_staff]" value="0">
                                    <input type="checkbox" name="HrEmployeeExtended[is_field_staff]" value="1"
                                           id="is-field-staff-checkbox"
                                           <?= $model->is_field_staff ? 'checked' : '' ?>>
                                    هذا الموظف يعمل في الميدان
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" id="field-role-wrapper" style="<?= $model->is_field_staff ? '' : 'display:none' ?>">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-frole">الدور الميداني</label>
                            <?= Html::activeDropDownList($model, 'field_role', $fieldRoles, [
                                'class' => 'form-control hr-form-input',
                                'prompt' => '— اختر الدور —',
                                'id' => 'emp-frole',
                            ]) ?>
                        </div>
                    </div>
                </div>
                <div class="row" style="margin-top:8px">
                    <div class="col-md-12">
                        <div id="emp-type-hint" class="hr-type-hint" style="display:none; padding:10px 14px; border-radius:8px; font-size:12px; background:#f0f9ff; color:#0c4a6e; border:1px solid #bae6fd;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
             القسم 5: ملاحظات
             ═══════════════════════════════════════════ -->
        <div class="hr-form-section">
            <div class="hr-form-section-header">
                <i class="fa fa-sticky-note-o"></i>
                <span>ملاحظات</span>
            </div>
            <div class="hr-form-section-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="hr-form-label" for="emp-notes">ملاحظات إضافية</label>
                            <?= Html::activeTextarea($model, 'notes', [
                                'class' => 'form-control hr-form-textarea',
                                'rows' => 4,
                                'placeholder' => 'أي ملاحظات إضافية حول الموظف...',
                                'id' => 'emp-notes',
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
             أزرار الإرسال
             ═══════════════════════════════════════════ -->
        <div class="hr-form-actions">
            <button type="submit" class="btn hr-btn-primary hr-btn-lg">
                <i class="fa <?= $isNewRecord ? 'fa-plus-circle' : 'fa-check-circle' ?>"></i>
                <?= $isNewRecord ? 'إنشاء المستخدم' : 'حفظ التعديلات' ?>
            </button>
            <?= Html::a(
                '<i class="fa fa-times"></i> إلغاء',
                ['index'],
                ['class' => 'btn btn-secondary hr-btn-lg']
            ) ?>
        </div>

    </form>

</div><!-- /.hr-employee-form -->

<script>
(function() {
    // ─── بطاقات فئات المستخدم (toggle) ───
    document.querySelectorAll('.hr-cat-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            var cb = this.querySelector('.hr-cat-check');
            cb.checked = !cb.checked;
            this.classList.toggle('selected', cb.checked);
            toggleHrSections();
        });
    });

    var employeeSlugs = ['employee', 'sales_employee', 'branch_manager', 'court_agent', 'manager'];

    function toggleHrSections() {
        var hasEmployee = false;
        var hasBranchRole = false;
        document.querySelectorAll('.hr-cat-check:checked').forEach(function(cb) {
            var slug = cb.closest('.hr-cat-card').dataset.slug;
            if (employeeSlugs.indexOf(slug) !== -1) hasEmployee = true;
            if (slug === 'sales_employee') hasBranchRole = true;
        });
        document.querySelectorAll('.hr-employee-only').forEach(function(s) {
            s.style.display = hasEmployee ? '' : 'none';
        });
        var branchSection = document.getElementById('hr-branch-section');
        if (branchSection) branchSection.style.display = hasBranchRole ? '' : 'none';
    }

    // ─── تبديل وضع المستخدم (موجود / جديد) ───
    document.querySelectorAll('.hr-mode-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var mode = this.dataset.mode;
            document.querySelectorAll('.hr-mode-btn').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');

            var existingPanel = document.getElementById('hr-existing-user-panel');
            var newPanel = document.getElementById('hr-new-user-panel');
            var hiddenFlag = document.getElementById('hr-create-new-user');

            if (mode === 'new') {
                if (existingPanel) existingPanel.style.display = 'none';
                if (newPanel) newPanel.style.display = '';
                if (hiddenFlag) hiddenFlag.value = '1';
            } else {
                if (newPanel) newPanel.style.display = 'none';
                if (existingPanel) existingPanel.style.display = '';
                if (hiddenFlag) hiddenFlag.value = '0';
            }
        });
    });

    // ─── إظهار/إخفاء الدور الميداني ───
    var fieldCheckbox = document.getElementById('is-field-staff-checkbox');
    if (fieldCheckbox) {
        fieldCheckbox.addEventListener('change', function() {
            var wrapper = document.getElementById('field-role-wrapper');
            if (wrapper) wrapper.style.display = this.checked ? '' : 'none';
        });
    }

    // ─── منطق نوع الموظف وتوصيات التتبع ───
    window.onEmployeeTypeChange = function(val) {
        var tracking = document.getElementById('emp-tracking');
        var hint = document.getElementById('emp-type-hint');
        var cb = document.getElementById('is-field-staff-checkbox');
        var msgs = {
            'office':  'موظف مكتبي: سيتم تسجيل الدخول/الخروج تلقائياً عند دخول/مغادرة منطقة العمل.',
            'field':   'موظف ميداني: يُنصح بتفعيل التتبع المستمر لمتابعة التنقلات الميدانية.',
            'sales':   'موظف مبيعات: تتبع أثناء المهام لضمان زيارة العملاء المحددين.',
            'hybrid':  'موظف مختلط: سياج جغرافي للمكتب + تتبع أثناء المهام الخارجية.',
        };
        if (hint && msgs[val]) {
            hint.textContent = msgs[val];
            hint.style.display = '';
        }
        if (tracking && !tracking.dataset.manuallySet) {
            var defaults = { 'office': 'geofence_only', 'field': 'continuous', 'sales': 'on_task', 'hybrid': 'geofence_only' };
            if (defaults[val]) tracking.value = defaults[val];
        }
        if (cb && (val === 'field' || val === 'sales')) {
            cb.checked = true;
            cb.dispatchEvent(new Event('change'));
        }
    };
    var trackSel = document.getElementById('emp-tracking');
    if (trackSel) trackSel.addEventListener('change', function(){ this.dataset.manuallySet = '1'; });

    // ─── جلب بيانات المستخدم عند اختياره من القائمة ───
    var userSelect = document.getElementById('hr-user-id-select');
    if (userSelect) {
        userSelect.addEventListener('change', function() {
            var userId = this.value;
            if (!userId) return;

            var baseUrl = '<?= Url::to(['user-info']) ?>';
            var sep = baseUrl.indexOf('?') !== -1 ? '&' : '?';
            fetch(baseUrl + sep + 'id=' + encodeURIComponent(userId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;

                // تحديث بطاقات الفئات بناءً على فئات المستخدم المحفوظة
                var catIds = data.category_ids || [];
                document.querySelectorAll('.hr-cat-card').forEach(function(card) {
                    var cb = card.querySelector('.hr-cat-check');
                    var catVal = parseInt(cb.value, 10);
                    var isSelected = catIds.indexOf(catVal) !== -1;
                    cb.checked = isSelected;
                    card.classList.toggle('selected', isSelected);
                });

                // تحديث القوائم المنسدلة بالبيانات الموجودة
                var u = data.user;
                if (u.job_title) {
                    var desSel = document.getElementById('user-designation');
                    if (desSel) desSel.value = u.job_title;
                }
                if (u.department) {
                    var depSel = document.getElementById('user-department');
                    if (depSel) depSel.value = u.department;
                }
                if (u.location) {
                    var locSel = document.getElementById('user-location');
                    if (locSel) locSel.value = u.location;
                }

                // تحديث إظهار/إخفاء الأقسام
                toggleHrSections();
            })
            .catch(function(err) {
                console.error('Error loading user info:', err);
            });
        });
    }

    // ─── تشغيل toggle الأقسام عند التحميل ───
    toggleHrSections();
})();
</script>

<?php
$css = <<<CSS

/* ─── Form Sections ─── */
.hr-form-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.hr-form-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
}
.hr-form-section-header i {
    color: #800020;
    font-size: 16px;
    width: 20px;
    text-align: center;
}
.hr-form-section-body {
    padding: 20px;
}

/* ─── Form Fields ─── */
.hr-form-label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
    display: block;
}
.hr-form-input {
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    font-size: 13px !important;
    padding: 8px 12px !important;
    height: auto !important;
    min-height: 38px;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.hr-form-input:focus {
    border-color: #800020 !important;
    box-shadow: 0 0 0 3px rgba(128,0,32,0.08) !important;
}
.hr-form-textarea {
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    font-size: 13px !important;
    padding: 10px 14px !important;
    resize: vertical;
    min-height: 80px;
}
.hr-form-textarea:focus {
    border-color: #800020 !important;
    box-shadow: 0 0 0 3px rgba(128,0,32,0.08) !important;
}
.hr-employee-form .form-group {
    margin-bottom: 16px;
}
.hr-employee-form .hint-block {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
}
.text-left {
    text-align: left !important;
    direction: ltr !important;
}

/* ─── Checkbox ─── */
.hr-checkbox-wrap {
    padding: 10px 0;
}

/* ─── Category Picker ─── */
.hr-category-picker { display:flex; gap:10px; flex-wrap:wrap; }
.hr-cat-card {
    display:flex; align-items:center; gap:10px; padding:10px 16px;
    border:2px solid #E2E8F0; border-radius:10px; cursor:pointer;
    transition:all .2s; background:#fff; min-width:160px; position:relative;
}
.hr-cat-card:hover { border-color:#94A3B8; background:#F8FAFC; }
.hr-cat-card.selected { border-color:#800020; background:#FDF2F4; box-shadow:0 0 0 3px rgba(128,0,32,.1); }
.hr-cat-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.hr-cat-name { font-size:13px; font-weight:600; color:#1E293B; }
.hr-cat-name-en { font-size:10px; color:#94A3B8; }
.hr-cat-check-icon { display:none; color:#800020; font-size:16px; margin-right:auto; }
.hr-cat-card.selected .hr-cat-check-icon { display:block; }

/* ─── Form Actions ─── */
.hr-form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-start;
    padding: 16px 0;
    margin-top: 8px;
}
.hr-btn-lg {
    padding: 10px 24px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 10px;
}
.hr-btn-primary {
    background: #800020;
    color: #fff;
    border: none;
}
.hr-btn-primary:hover {
    background: #6b001a;
    color: #fff;
}

/* ─── User Mode Toggle ─── */
.hr-user-mode-toggle {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    padding: 4px;
    background: #f1f5f9;
    border-radius: 10px;
}
.hr-mode-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border: 2px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
}
.hr-mode-btn:hover {
    background: #fff;
    color: #334155;
}
.hr-mode-btn.active {
    background: #fff;
    color: #800020;
    border-color: #800020;
    box-shadow: 0 1px 4px rgba(128,0,32,0.12);
}
.hr-mode-btn i {
    font-size: 15px;
}

/* ─── New User Card ─── */
.hr-new-user-card {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 8px;
    background: #fefce8;
}
.hr-new-user-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;
    background: #fef9c3;
    border-bottom: 1px solid #fde68a;
    font-size: 14px;
    font-weight: 700;
    color: #92400e;
}
.hr-new-user-card-body {
    padding: 18px;
}

CSS;

$this->registerCss($css);
?>
