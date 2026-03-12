<?php
/**
 * تقرير العقود بدون أرقام تواصل — واجهة V2
 * No-Contact Contracts Report — Modern V2 UI
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\LinkPager;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\followUp\helper\ContractCalculations;
use common\helper\Permissions;
use backend\helpers\NameHelper;
use backend\widgets\ExportButtons;

/* Assets */
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/contracts-v2.css?v=' . time());
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/contracts-v2.js?v=' . time(), [
    'depends' => [\yii\web\JqueryAsset::class],
]);
$this->registerCss('.content-header,.page-header { display: none !important; }');

$this->title = 'عقود بدون أرقام تواصل';
$this->params['breadcrumbs'][] = ['label' => 'تقرير المتابعة', 'url' => ['/followUpReport/follow-up-report/index']];
$this->params['breadcrumbs'][] = $this->title;

/* Data */
$isManager  = Yii::$app->user->can(Permissions::MANAGER);
$models     = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
$sort       = $dataProvider->getSort();
$allUsers   = $isManager
    ? ArrayHelper::map(
        Yii::$app->db->createCommand(
            "SELECT DISTINCT u.id, u.username FROM {{%user}} u
             INNER JOIN {{%auth_assignment}} a ON a.user_id = u.id
             WHERE u.blocked_at IS NULL AND u.employee_type = 'Active'
             ORDER BY u.username"
        )->queryAll(),
        'id', 'username'
    ) : [];

$statusLabels = [
    'active' => 'نشط', 'judiciary' => 'قضائي فعّال',
    'judiciary_paid' => 'قضائي مسدد',
    'legal_department' => 'قانوني', 'settlement' => 'تسوية',
    'finished' => 'منتهي', 'canceled' => 'ملغي',
];

/* Sort helper */
$sortOrders = $sort->getAttributeOrders();
$sortLink = function ($attribute, $label) use ($sort, $sortOrders) {
    $url = $sort->createUrl($attribute);
    $icon = '';
    if (isset($sortOrders[$attribute])) {
        $icon = $sortOrders[$attribute] === SORT_ASC
            ? ' <i class="fa fa-sort-up ct-sort-icon active"></i>'
            : ' <i class="fa fa-sort-down ct-sort-icon active"></i>';
    } else {
        $icon = ' <i class="fa fa-sort ct-sort-icon"></i>';
    }
    return '<a href="' . Html::encode($url) . '">' . $label . $icon . '</a>';
};

$begin = $pagination->getOffset() + 1;
$end   = $begin + count($models) - 1;
?>

<div class="ct-page" role="main" aria-label="تقرير العقود بدون أرقام تواصل">

    <!-- Flash messages (Alpine.js) -->
    <?php foreach (['success' => 'check-circle', 'error' => 'exclamation-circle', 'warning' => 'exclamation-triangle'] as $type => $icon): ?>
        <?php if (Yii::$app->session->hasFlash($type)): ?>
            <div class="ct-alert ct-alert-<?= $type === 'error' ? 'danger' : $type ?>" role="alert"
                 x-data="{ show: true }" x-show="show" x-transition x-cloak
                 x-init="setTimeout(() => show = false, 5000)">
                <i class="fa fa-<?= $icon ?>"></i>
                <span><?= Yii::$app->session->getFlash($type) ?></span>
                <button class="ct-alert-close" aria-label="إغلاق" @click="show = false">&times;</button>
            </div>
        <?php endif ?>
    <?php endforeach ?>

    <!-- ===== PAGE HEADER ===== -->
    <div class="ct-page-hdr">
        <div class="ct-title-area">
            <h1><i class="fa fa-ban" style="margin-left:8px;opacity:.7"></i>عقود بدون أرقام تواصل</h1>
            <span class="ct-count" aria-label="إجمالي العقود"><?= number_format($dataCount) ?></span>
        </div>
        <div class="ct-hdr-actions">
            <a href="<?= Url::to(['/followUpReport/follow-up-report/index']) ?>" class="ct-btn ct-btn-outline">
                <i class="fa fa-arrow-right"></i> <span class="ct-hide-xs">تقرير المتابعة</span>
            </a>
            <?= ExportButtons::widget([
                'excelRoute' => ['export-no-contact-excel'],
                'pdfRoute' => ['export-no-contact-pdf'],
                'excelBtnClass' => 'ct-btn ct-btn-outline ct-hide-sm',
                'pdfBtnClass' => 'ct-btn ct-btn-outline ct-hide-sm',
                'passQueryParams' => true,
            ]) ?>
            <button class="ct-btn ct-btn-ghost ct-show-sm" id="ctFilterToggle" aria-label="فتح الفلاتر">
                <i class="fa fa-sliders" style="font-size:18px"></i>
            </button>
        </div>
    </div>

    <!-- ===== INFO BANNER ===== -->
    <div class="ct-legal-stats">
        <div class="ct-legal-stat-card">
            <div class="ct-legal-stat-icon" style="background:#FFF3E0;color:#E65100"><i class="fa fa-ban"></i></div>
            <div class="ct-legal-stat-body">
                <span class="ct-legal-stat-value"><?= number_format($dataCount) ?></span>
                <span class="ct-legal-stat-label">عقد بدون أرقام تواصل</span>
            </div>
        </div>
        <div class="ct-legal-stat-card">
            <div class="ct-legal-stat-icon" style="background:#FFEBEE;color:#C62828"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="ct-legal-stat-body">
                <span class="ct-legal-stat-value" style="font-size:14px;font-weight:500">تحتاج تحديث بيانات</span>
                <span class="ct-legal-stat-label">هذه العقود مؤشرة كـ "لا يمكن التواصل" — يجب إضافة أرقام</span>
            </div>
        </div>
    </div>

    <!-- ===== FILTER SECTION ===== -->
    <div class="ct-filter-wrap" id="ctFilterWrap">
        <div class="ct-filter-backdrop" id="ctFilterBackdrop"></div>
        <div class="ct-filter-panel" id="ctFilterPanel">
            <div class="ct-drawer-handle ct-show-sm"></div>
            <div class="ct-filter-hdr">
                <h3><i class="fa fa-search"></i> بحث وفلترة</h3>
                <span class="ct-filter-toggle-icon ct-hide-sm"><i class="fa fa-chevron-up"></i></span>
                <button class="ct-btn ct-btn-ghost ct-show-sm" id="ctDrawerClose" aria-label="إغلاق"
                        style="font-size:20px;padding:4px 8px">&times;</button>
            </div>
            <div class="ct-filter-body">
                <?php $form = ActiveForm::begin([
                    'id' => 'no-contact-search',
                    'method' => 'get',
                    'action' => ['no-contact'],
                    'options' => ['class' => 'ct-search-form'],
                ]) ?>
                <div class="ct-filter-grid">
                    <div class="ct-filter-group">
                        <label>رقم العقد</label>
                        <?= $form->field($searchModel, 'id', ['template' => '{input}'])->textInput([
                            'placeholder' => 'رقم العقد', 'type' => 'number', 'class' => 'form-control',
                        ]) ?>
                    </div>
                    <div class="ct-filter-group" style="grid-column: span 2">
                        <label>العميل</label>
                        <?= $form->field($searchModel, 'customer_name', ['template' => '{input}'])->textInput([
                            'placeholder' => 'ابحث بالاسم...', 'class' => 'form-control',
                        ]) ?>
                    </div>
                    <div class="ct-filter-group">
                        <label>البائع</label>
                        <?= $form->field($searchModel, 'seller_name', ['template' => '{input}'])->textInput([
                            'placeholder' => 'اسم البائع', 'class' => 'form-control',
                        ]) ?>
                    </div>
                    <div class="ct-filter-group">
                        <label>الحالة</label>
                        <?= $form->field($searchModel, 'status', ['template' => '{input}'])->dropDownList([
                            '' => '-- جميع الحالات --',
                            'active' => 'نشط', 'legal_department' => 'قانوني',
                            'judiciary' => 'قضاء', 'settlement' => 'تسوية',
                        ], ['class' => 'form-control']) ?>
                    </div>
                    <?php if ($isManager || Yii::$app->user->can('مدير التحصيل')): ?>
                    <div class="ct-filter-group">
                        <label>المتابع</label>
                        <?= $form->field($searchModel, 'followed_by', ['template' => '{input}'])->widget(Select2::class, [
                            'data' => $allUsers,
                            'options' => ['placeholder' => 'اختر المتابع'],
                            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                        ]) ?>
                    </div>
                    <?php endif ?>
                    <div class="ct-filter-actions">
                        <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'ct-btn ct-btn-primary']) ?>
                        <a href="<?= Url::to(['no-contact']) ?>" class="ct-btn ct-btn-outline">
                            <i class="fa fa-refresh"></i> <span class="ct-hide-xs">إعادة تعيين</span>
                        </a>
                    </div>
                </div>
                <?php ActiveForm::end() ?>
            </div>
        </div>
    </div>

    <!-- ===== FILTER CHIPS ===== -->
    <div class="ct-chips" id="ctChips" aria-label="الفلاتر النشطة"></div>

    <!-- ===== TOOLBAR ===== -->
    <div class="ct-toolbar">
        <div class="ct-summary">
            <?php if ($dataCount > 0): ?>
                عرض <strong><?= number_format($begin) ?>–<?= number_format($end) ?></strong>
                من أصل <strong><?= number_format($dataCount) ?></strong> عقد
            <?php else: ?>
                لا توجد نتائج
            <?php endif ?>
        </div>
        <div class="ct-quick-search">
            <i class="fa fa-search"></i>
            <input type="text" id="ctQuickSearch" placeholder="بحث سريع في النتائج..."
                   aria-label="بحث سريع في النتائج المعروضة">
        </div>
    </div>

    <!-- ===== DATA TABLE ===== -->
    <div class="ct-table-wrap">
        <?php if (empty($models)): ?>
            <div class="ct-empty">
                <i class="fa fa-check-circle" style="color:#28a745"></i>
                <p>لا توجد عقود بدون أرقام تواصل — ممتاز!</p>
                <a href="<?= Url::to(['/followUpReport/follow-up-report/index']) ?>" class="ct-btn ct-btn-outline">
                    <i class="fa fa-arrow-right"></i> العودة لتقرير المتابعة
                </a>
            </div>
        <?php else: ?>
            <table class="ct-table" role="grid">
                <thead>
                    <tr>
                        <th class="ct-th-id"><?= $sortLink('id', '#') ?></th>
                        <th>العميل</th>
                        <th><?= $sortLink('seller_id', 'البائع') ?></th>
                        <th><?= $sortLink('Date_of_sale', 'تاريخ البيع') ?></th>
                        <th><?= $sortLink('total_value', 'الإجمالي') ?></th>
                        <th>المستحق</th>
                        <th>المتبقي</th>
                        <th>الحالة</th>
                        <th>آخر متابعة</th>
                        <th>المتابع</th>
                        <th style="text-align:center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    ?>
                    <?php foreach ($models as $m):
                        $customerNamesFull = implode('، ', ArrayHelper::map($m->customers, 'id', 'name')) ?: '—';
                        $customerNamesShort = implode('، ', array_map([NameHelper::class, 'short'], ArrayHelper::map($m->customers, 'id', 'name'))) ?: '—';
                        $sellerName = $m->seller->name ?? '—';
                        $followName = $allUsers[$m->followed_by] ?? ($m->followedBy->username ?? '—');

                        /* Calculations */
                        $calc = new ContractCalculations($m->id);
                        $deserved = $calc->deservedAmount() ?? 0;

                        $paid = ContractInstallment::find()->where(['contract_id' => $m->id])->sum('amount') ?? 0;
                        $remaining = ($m->total_value ?? 0) - $paid;
                    ?>
                    <tr data-id="<?= $m->id ?>">
                        <td class="ct-td-id" data-label="#"><?= $m->id ?></td>
                        <td class="ct-td-customer" data-label="العميل" title="<?= Html::encode($customerNamesFull) ?>">
                            <?= Html::encode($customerNamesShort) ?>
                        </td>
                        <td class="ct-td-seller" data-label="البائع"><?= Html::encode($sellerName) ?></td>
                        <td class="ct-td-date" data-label="تاريخ البيع"><?= $m->Date_of_sale ?></td>
                        <td class="ct-td-money" data-label="الإجمالي"><?= number_format($m->total_value ?? 0, 0) ?></td>
                        <td class="ct-td-money ct-td-due" data-label="المستحق"><?= number_format($deserved, 0) ?></td>
                        <td class="ct-td-money ct-td-remain" data-label="المتبقي"><?= number_format($remaining, 0) ?></td>
                        <?php
                            $stKey = $m->status;
                            if ($stKey === 'judiciary' && $remaining <= 0) $stKey = 'judiciary_paid';
                        ?>
                        <td class="ct-td-status" data-label="الحالة">
                            <span class="ct-badge ct-st-<?= $stKey ?>">
                                <?= $statusLabels[$stKey] ?? $m->status ?>
                            </span>
                        </td>
                        <td class="ct-td-date" data-label="آخر متابعة">
                            <?= $m->date_time ? date('Y/m/d', strtotime($m->date_time)) : '<span style="color:#dc3545">لا يوجد</span>' ?>
                        </td>
                        <td class="ct-td-follow" data-label="المتابع">
                            <?php if ($isManager): ?>
                                <?= Html::dropDownList('followedBy', $m->followed_by, $allUsers, [
                                    'class' => 'ct-follow-select followUpUser',
                                    'data-contract-id' => $m->id,
                                    'prompt' => '-- اختر --',
                                ]) ?>
                            <?php else: ?>
                                <?= Html::encode($followName) ?>
                            <?php endif ?>
                        </td>
                        <td class="ct-td-actions" data-label="">
                            <div class="ct-act-wrap">
                                <button class="ct-act-trigger" aria-label="إجراءات العقد <?= $m->id ?>"
                                        aria-haspopup="true" tabindex="0">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <div class="ct-act-menu" role="menu">
                                    <a href="<?= Url::to(['/followUp/follow-up/panel', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-dashboard text-primary"></i> لوحة التحكم
                                    </a>
                                    <a href="<?= Url::to(['/contracts/contracts/update', 'id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-pencil text-primary"></i> تعديل العقد
                                    </a>
                                    <div class="ct-act-divider"></div>
                                    <?php
                                    $firstCustomer = $m->customers[0] ?? null;
                                    if ($firstCustomer): ?>
                                    <a href="<?= Url::to(['/customers/update', 'id' => $firstCustomer->id]) ?>" role="menuitem">
                                        <i class="fa fa-user text-success"></i> تعديل بيانات العميل
                                    </a>
                                    <?php endif ?>
                                    <a href="<?= Url::to(['/followUp/follow-up/index', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-comments text-info"></i> المتابعة
                                    </a>
                                    <a href="<?= Url::to(['/contractInstallment/contract-installment/index', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-money text-success"></i> الدفعات
                                    </a>
                                    <a href="<?= Url::to(['/contracts/contracts/print-preview', 'id' => $m->id]) ?>" target="_blank" role="menuitem">
                                        <i class="fa fa-print text-info"></i> طباعة
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>

    <!-- ===== PAGINATION ===== -->
    <?php if ($dataCount > 0): ?>
    <div class="ct-pagination-wrap">
        <?= LinkPager::widget([
            'pagination' => $pagination,
            'prevPageLabel' => '<i class="fa fa-chevron-right"></i>',
            'nextPageLabel' => '<i class="fa fa-chevron-left"></i>',
            'firstPageLabel' => '<i class="fa fa-angle-double-right"></i>',
            'lastPageLabel' => '<i class="fa fa-angle-double-left"></i>',
            'maxButtonCount' => 7,
            'options' => ['class' => 'pagination', 'aria-label' => 'تصفح الصفحات'],
        ]) ?>
    </div>
    <?php endif ?>

</div>

<?php
/* OLD jQuery - replaced by Alpine.js
$this->registerJs(<<<'JS'
$('.ct-alert-close').on('click', function(){ $(this).closest('.ct-alert').fadeOut(300); });
setTimeout(function(){ $('.ct-alert').fadeOut(500); }, 5000);
JS
);
*/
?>
