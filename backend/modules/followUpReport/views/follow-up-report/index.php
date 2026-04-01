<?php
/**
 * تقرير المتابعة — واجهة V2 حديثة ومتجاوبة بالكامل
 * متوافق: هواتف · Samsung Fold · لابتوب Dell Latitude 5420 · 27" · 32"
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\LinkPager;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use backend\widgets\ExportButtons;
use backend\helpers\NameHelper;
use backend\modules\jobs\models\Jobs;
use yii\web\View;

/* Assets */
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/contracts-v2.css?v=' . Yii::$app->params['assetVersion']);
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/unified-search.css?v=' . Yii::$app->params['assetVersion']);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/contracts-v2.js?v=' . Yii::$app->params['assetVersion'], [
    'depends' => [\yii\web\JqueryAsset::class],
]);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/unified-search.js?v=' . Yii::$app->params['assetVersion'], [
    'depends' => [\yii\web\JqueryAsset::class],
]);
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/pin-system.css?v=' . Yii::$app->params['assetVersion']);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/pin-system.js?v=' . Yii::$app->params['assetVersion'], [
    'depends' => [\yii\web\JqueryAsset::class],
]);
$this->registerCss('.content-header,.page-header{display:none!important}');

/* هل الوضع الحالي = بدون أرقام تواصل؟ */
$isNoContact = ((int)($searchModel->is_can_not_contact ?? 0)) === 1;
$pageTitle = $isNoContact ? 'عقود بدون أرقام تواصل' : 'تقرير المتابعة';
$pageIcon  = $isNoContact ? 'ban' : 'phone';

$this->title = $pageTitle;
$this->params['breadcrumbs'][] = $this->title;

/* Data */
$isManager  = Yii::$app->user->can('مدير') || Yii::$app->user->can('مدير التحصيل');
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

/* Sort helper */
$sortOrders = $sort->getAttributeOrders();
$sortLink = function ($attr, $label) use ($sort, $sortOrders) {
    $url = $sort->createUrl($attr);
    $icon = isset($sortOrders[$attr])
        ? ($sortOrders[$attr] === SORT_ASC ? ' <i class="fa fa-sort-up ct-sort-icon active"></i>' : ' <i class="fa fa-sort-down ct-sort-icon active"></i>')
        : ' <i class="fa fa-sort ct-sort-icon"></i>';
    return '<a href="' . Html::encode($url) . '">' . $label . $icon . '</a>';
};
$begin = $pagination->getOffset() + 1;
$end   = $begin + count($models) - 1;

/* Status map — aligned with Contracts model */
$statusMap = [
    'active' => ['label' => 'نشط', 'color' => '#4CAF50'],
    'settlement' => ['label' => 'تسوية', 'color' => '#2196F3'],
    'judiciary' => ['label' => 'قضائي فعّال', 'color' => '#F44336'],
    'judiciary_paid' => ['label' => 'قضائي مسدد', 'color' => '#009688'],
    'legal_department' => ['label' => 'دائرة قانونية', 'color' => '#9C27B0'],
];
$statusList = ['' => 'جميع الحالات'];
foreach ($statusMap as $k => $v) $statusList[$k] = $v['label'];
$jobs = Yii::$app->cache->getOrSet('lookup_jobs', fn() => ArrayHelper::map(
    Jobs::find()->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]])->orderBy(['name' => SORT_ASC])->asArray()->all(),
    'id', 'name'
), 3600);
?>

<style>
/* ═══════════════════════════════════════════════════════════
   FOLLOW-UP REPORT — Responsive Design System
   ═══════════════════════════════════════════════════════════ */
.ct-followup-page{max-width:100%}

/* ── Stat cards ── */
.fur-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.fur-stat{display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #eee;border-radius:12px;padding:14px 16px;text-decoration:none!important;color:inherit!important;transition:box-shadow .2s,transform .15s}
.fur-stat:hover{box-shadow:0 4px 16px rgba(0,0,0,.08);transform:translateY(-1px)}
.fur-stat.fur-stat-active{border:2px solid var(--clr-primary,#800020);background:#fdf2f4}
.fur-stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0}
.fur-stat-val{font-size:22px;font-weight:700;line-height:1.2}
.fur-stat-lbl{font-size:12px;color:#666;margin-top:1px}

/* ── Table wrap — سكرول عرضي بدلاً من كروت ── */
.ct-followup-page .ct-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
/* إلغاء تحويل الجدول لكروت من contracts-v2.css */
.ct-followup-page .ct-table{display:table!important}
.ct-followup-page .ct-table thead{display:table-header-group!important}
.ct-followup-page .ct-table tbody{display:table-row-group!important}
.ct-followup-page .ct-table tbody tr{display:table-row!important;border-bottom:1px solid #f0f0f0}
.ct-followup-page .ct-table tbody td{display:table-cell!important;white-space:nowrap}
.ct-followup-page .ct-table tbody td::before{display:none!important}

/* ── Row styles (لا تتعارض مع contracts-v2.css) ── */
tr.fur-row-nf{background:#FFF8E1!important}
tr.fur-row-nf:hover{background:#FFF3C4!important}
.fur-nf-badge{display:block;font-size:10px;color:#E65100;font-weight:700;margin-top:2px}
.fur-nf-label{color:#E65100;font-size:11px;font-weight:600}
.fur-overdue{color:#C62828;font-weight:600}
.fur-due-badge{background:#E3F2FD;color:#1565C0;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:700;display:inline-block}
.fur-status{display:inline-block;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:600}
.fur-amount-red{color:#C62828;font-weight:700}
.fur-btn-follow{display:inline-flex;align-items:center;gap:4px;padding:5px 12px;font-size:12px;font-weight:600;border:1px solid var(--clr-primary,#800020);color:var(--clr-primary,#800020);background:#fff;border-radius:8px;text-decoration:none!important;transition:.15s}
.fur-btn-follow:hover{background:var(--clr-primary,#800020);color:#fff!important}
a.fur-id-link{color:var(--clr-primary,#800020);font-weight:700;text-decoration:none}
a.fur-id-link:hover{text-decoration:underline}

/* ═══ RESPONSIVE — كروت الإحصائيات والفلاتر فقط ═══ */
@media(min-width:2200px){.fur-stat-val{font-size:26px}}
@media(max-width:1599px){.fur-stats{gap:10px}.fur-stat{padding:12px}.fur-stat-val{font-size:18px}.fur-stat-icon{width:40px;height:40px;font-size:17px}}
@media(max-width:991px){.fur-stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.fur-stats{grid-template-columns:1fr 1fr;gap:8px}.fur-stat{padding:10px}.fur-stat-val{font-size:16px}.fur-stat-lbl{font-size:10px}}
@media(max-width:480px){.fur-stats{grid-template-columns:1fr 1fr}.fur-stat{padding:8px;gap:8px}.fur-stat-icon{width:34px;height:34px;font-size:15px}.fur-stat-val{font-size:16px}.ct-page-hdr{flex-direction:column;align-items:flex-start;gap:8px}.ct-title-area h1{font-size:18px}}
@media(max-width:380px){.fur-stats{grid-template-columns:1fr}.fur-stat-val{font-size:18px}}
</style>

<div class="ct-page ct-followup-page" role="main" aria-label="<?= $pageTitle ?>">

    <!-- Flash (Alpine.js) -->
    <?php foreach (['success'=>'check-circle','error'=>'exclamation-circle','warning'=>'exclamation-triangle'] as $type=>$icon): ?>
        <?php if (Yii::$app->session->hasFlash($type)): ?>
            <div class="ct-alert ct-alert-<?= $type==='error'?'danger':$type ?>" role="alert"
                 x-data="{ show: true }" x-show="show" x-transition x-cloak
                 x-init="setTimeout(() => show = false, 5000)">
                <i class="fa fa-<?= $icon ?>"></i><span><?= Yii::$app->session->getFlash($type) ?></span>
                <button class="ct-alert-close" aria-label="إغلاق" @click="show = false">&times;</button>
            </div>
        <?php endif ?>
    <?php endforeach ?>

    <!-- ═══ HEADER ═══ -->
    <div class="ct-page-hdr">
        <div class="ct-title-area">
            <h1><i class="fa fa-<?= $pageIcon ?>" style="margin-left:8px;opacity:.7"></i><?= $pageTitle ?></h1>
            <span class="ct-count"><?= number_format($dataCount) ?></span>
        </div>
        <div class="ct-hdr-actions">
            <?php if ($isNoContact): ?>
                <a href="<?= Url::to(['index']) ?>" class="ct-btn ct-btn-outline"><i class="fa fa-arrow-right"></i> <span class="ct-hide-xs">تقرير المتابعة</span></a>
            <?php endif ?>
            <?= ExportButtons::widget([
                'excelRoute' => ['export-excel'],
                'pdfRoute' => ['export-pdf'],
                'excelBtnClass' => 'ct-btn ct-btn-outline ct-hide-sm',
                'pdfBtnClass' => 'ct-btn ct-btn-outline ct-hide-sm',
                'passQueryParams' => true,
            ]) ?>
            <button class="ct-btn ct-btn-ghost ct-show-sm" id="ctFilterToggle" aria-label="فتح الفلاتر"><i class="fa fa-sliders" style="font-size:18px"></i></button>
        </div>
    </div>

    <!-- ═══ STAT CARDS ═══ -->
    <div class="fur-stats">
        <a href="<?= Url::to(['index']) ?>" class="fur-stat <?= !$isNoContact ? 'fur-stat-active' : '' ?>">
            <div class="fur-stat-icon" style="background:#E3F2FD;color:#1565C0"><i class="fa fa-list-alt"></i></div>
            <div><div class="fur-stat-val"><?= number_format($activeCount) ?></div><div class="fur-stat-lbl">إجمالي العقود للمتابعة</div></div>
        </a>
        <a href="<?= Url::to(['index', 'FollowUpReportSearch[never_followed]' => 1]) ?>" class="fur-stat">
            <div class="fur-stat-icon" style="background:#FFF3E0;color:#E65100"><i class="fa fa-exclamation-circle"></i></div>
            <div><div class="fur-stat-val"><?= number_format($neverFollowedCount) ?></div><div class="fur-stat-lbl">لم يُتابع أبداً</div></div>
        </a>
        <a href="<?= Url::to(['index', 'FollowUpReportSearch[promise_to_pay_at]' => date('Y-m-d')]) ?>" class="fur-stat">
            <div class="fur-stat-icon" style="background:#FCE4EC;color:#C62828"><i class="fa fa-clock-o"></i></div>
            <div><div class="fur-stat-val"><?= number_format($overduePromiseCount) ?></div><div class="fur-stat-lbl">وعود دفع متأخرة</div></div>
        </a>
        <a href="<?= Url::to(['index', 'FollowUpReportSearch[is_can_not_contact]' => 1]) ?>" class="fur-stat <?= $isNoContact ? 'fur-stat-active' : '' ?>">
            <div class="fur-stat-icon" style="background:#EFEBE9;color:#4E342E"><i class="fa fa-ban"></i></div>
            <div><div class="fur-stat-val"><?= number_format($noContactCount) ?></div><div class="fur-stat-lbl">بدون أرقام تواصل</div></div>
        </a>
    </div>

    <!-- ═══ Pinned Items Bar ═══ -->
    <div class="pin-bar" id="pin-bar"></div>

    <!-- ═══ FILTER PANEL ═══ -->
    <div class="ct-filter-wrap" id="ctFilterWrap">
        <div class="ct-filter-backdrop" id="ctFilterBackdrop"></div>
        <div class="ct-filter-panel" id="ctFilterPanel">
            <div class="ct-drawer-handle ct-show-sm"></div>
            <div class="ct-filter-hdr">
                <h3><i class="fa fa-search"></i> بحث وفلترة</h3>
                <span class="ct-filter-toggle-icon ct-hide-sm"><i class="fa fa-chevron-up"></i></span>
                <button class="ct-btn ct-btn-ghost ct-show-sm" id="ctDrawerClose" aria-label="إغلاق" style="font-size:20px;padding:4px 8px">&times;</button>
            </div>
            <div class="ct-filter-body">
                <?php $form = ActiveForm::begin(['id'=>'fur-search','method'=>'get','action'=>['index'],'options'=>['class'=>'ct-filter-form']]); ?>
                <?= $form->field($searchModel, 'is_can_not_contact', ['template' => '{input}'])->hiddenInput()->label(false) ?>
                <div class="ct-filter-rows">
                    <div class="ct-filter-col-wide ct-filter-search">
                        <label><i class="fa fa-user"></i> اسم العميل</label>
                        <div class="us-wrap" id="fur-name-wrap">
                            <?= Html::activeTextInput($searchModel, 'customer_name', [
                                'id' => 'fur-name',
                                'class' => 'form-control us-input',
                                'placeholder' => 'ابحث باسم العميل...',
                                'aria-label' => 'بحث باسم العميل',
                                'autocomplete' => 'off',
                            ]) ?>
                            <span class="us-spinner" style="display:none"><i class="fa fa-circle-o-notch fa-spin"></i></span>
                            <div class="us-dropdown" style="display:none"></div>
                        </div>
                    </div>
                    <div class="ct-filter-col ct-filter-search">
                        <label><i class="fa fa-hashtag"></i> رقم العقد</label>
                        <div class="us-wrap" id="fur-id-wrap">
                            <?= Html::activeTextInput($searchModel, 'id', [
                                'id' => 'fur-id',
                                'class' => 'form-control us-input',
                                'placeholder' => 'رقم العقد...',
                                'aria-label' => 'بحث برقم العقد',
                                'autocomplete' => 'off',
                            ]) ?>
                            <span class="us-spinner" style="display:none"><i class="fa fa-circle-o-notch fa-spin"></i></span>
                            <div class="us-dropdown" style="display:none"></div>
                        </div>
                    </div>
                    <div class="ct-filter-col ct-filter-search">
                        <label><i class="fa fa-id-card"></i> الرقم الوطني</label>
                        <div class="us-wrap" id="fur-idn-wrap">
                            <?= Html::activeTextInput($searchModel, 'id_number', [
                                'id' => 'fur-idn',
                                'class' => 'form-control us-input',
                                'placeholder' => 'الرقم الوطني...',
                                'aria-label' => 'بحث بالرقم الوطني',
                                'autocomplete' => 'off',
                            ]) ?>
                            <span class="us-spinner" style="display:none"><i class="fa fa-circle-o-notch fa-spin"></i></span>
                            <div class="us-dropdown" style="display:none"></div>
                        </div>
                    </div>
                    <div class="ct-filter-col ct-filter-search">
                        <label><i class="fa fa-phone"></i> رقم الهاتف</label>
                        <div class="us-wrap" id="fur-phone-wrap">
                            <?= Html::activeTextInput($searchModel, 'phone_number', [
                                'id' => 'fur-phone',
                                'class' => 'form-control us-input',
                                'placeholder' => 'رقم الهاتف...',
                                'aria-label' => 'بحث برقم الهاتف',
                                'autocomplete' => 'off',
                            ]) ?>
                            <span class="us-spinner" style="display:none"><i class="fa fa-circle-o-notch fa-spin"></i></span>
                            <div class="us-dropdown" style="display:none"></div>
                        </div>
                    </div>
                    <div class="ct-filter-col">
                        <label>حالة العقد</label>
                        <?= $form->field($searchModel, 'status', ['template'=>'{input}'])->dropDownList($statusList, ['class'=>'form-control']) ?>
                    </div>
                    <div class="ct-filter-col">
                        <label>جهة العمل</label>
                        <?= $form->field($searchModel, 'job_title', ['template'=>'{input}'])->dropDownList($jobs, [
                            'prompt' => '-- جهة العمل --',
                            'class' => 'form-control',
                            'aria-label' => 'جهة العمل',
                        ]) ?>
                    </div>
                    <?php if ($isManager): ?>
                    <div class="ct-filter-col">
                        <label>المتابع</label>
                        <?= $form->field($searchModel, 'followed_by', ['template'=>'{input}'])->dropDownList($allUsers, [
                            'prompt' => '-- المتابع --',
                            'class' => 'form-control',
                            'aria-label' => 'المتابع',
                        ]) ?>
                    </div>
                    <?php endif ?>
                    <?php if (!$isNoContact): ?>
                    <div class="ct-filter-col">
                        <label>حالة المتابعة</label>
                        <?= $form->field($searchModel, 'never_followed', ['template'=>'{input}'])->dropDownList([
                            ''=>'الكل','1'=>'لم يُتابع أبداً','0'=>'تمت متابعته',
                        ], ['class'=>'form-control']) ?>
                    </div>
                    <div class="ct-filter-col">
                        <label>التذكير حتى</label>
                        <?= $form->field($searchModel, 'reminder', ['template'=>'{input}'])->widget(FlatpickrWidget::class, [
                            'options'=>['placeholder'=>'التذكير حتى','class'=>'form-control','autocomplete'=>'off'],
                            'pluginOptions'=>['dateFormat'=>'Y-m-d'],
                        ]) ?>
                    </div>
                    <div class="ct-filter-col">
                        <label>وعد بالدفع حتى</label>
                        <?= $form->field($searchModel, 'promise_to_pay_at', ['template'=>'{input}'])->widget(FlatpickrWidget::class, [
                            'options'=>['placeholder'=>'وعد بالدفع حتى','class'=>'form-control','autocomplete'=>'off'],
                            'pluginOptions'=>['dateFormat'=>'Y-m-d'],
                        ]) ?>
                    </div>
                    <?php endif ?>
                    <div class="ct-filter-col">
                        <label>نتائج/صفحة</label>
                        <?= $form->field($searchModel, 'number_row', ['template'=>'{input}'])->dropDownList([
                            ''=>'20','50'=>'50','100'=>'100','200'=>'200',
                        ], ['class'=>'form-control']) ?>
                    </div>
                    <div class="ct-filter-actions">
                        <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class'=>'ct-btn ct-btn-primary']) ?>
                        <a href="<?= Url::to($isNoContact ? ['index','FollowUpReportSearch[is_can_not_contact]'=>1] : ['index']) ?>" class="ct-btn ct-btn-outline"><i class="fa fa-refresh"></i> مسح</a>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

    <div class="ct-chips" id="ctChips" aria-label="الفلاتر النشطة"></div>

    <!-- ═══ TOOLBAR ═══ -->
    <div class="ct-toolbar">
        <div class="ct-summary">
            <?php if ($dataCount > 0): ?>
                عرض <strong><?= number_format($begin) ?>–<?= number_format($end) ?></strong> من أصل <strong><?= number_format($dataCount) ?></strong> عقد
            <?php else: ?>
                لا توجد نتائج
            <?php endif ?>
        </div>
        <div class="ct-quick-search">
            <i class="fa fa-search"></i>
            <input type="text" id="ctQuickSearch" placeholder="بحث سريع في النتائج..." aria-label="بحث سريع">
        </div>
    </div>

    <!-- ═══ DATA TABLE ═══ -->
    <div class="ct-table-wrap">
        <?php if (empty($models)): ?>
            <div class="ct-empty"><i class="fa fa-inbox"></i><p>لا توجد عقود مطابقة</p>
                <a href="<?= Url::to(['index']) ?>" class="ct-btn ct-btn-outline"><i class="fa fa-refresh"></i> عرض الكل</a></div>
        <?php else: ?>
            <table class="ct-table" role="grid">
                <thead><tr>
                    <th class="ct-th-id"><?= $sortLink('id', '#') ?></th>
                    <th>العميل</th>
                    <th><?= $sortLink('effective_installment', 'القسط') ?></th>
                    <th><?= $sortLink('due_installments', 'أقساط مستحقة') ?></th>
                    <th><?= $sortLink('due_amount', 'المبلغ المستحق') ?></th>
                    <th><?= $sortLink('last_follow_up', 'آخر متابعة') ?></th>
                    <th>التذكير</th>
                    <th>وعد بالدفع</th>
                    <th>الحالة</th>
                    <th>المتابع</th>
                    <th data-label="" style="text-align:center">إجراءات</th>
                </tr></thead>
                <tbody>
                <?php
                ?>
                <?php foreach ($models as $m):
                    $nm = ($namesMap ?? [])[$m->id] ?? null;
                    $customerNamesFull = $nm ? ($nm['client_names'] ?: '—') : '—';
                    $customerNamesShort = $nm ? implode('، ', array_map([NameHelper::class, 'short'], array_filter(explode('، ', $nm['client_names'] ?? '')))) ?: '—' : '—';
                    $followName = $allUsers[$m->followed_by] ?? ($m->followedBy ? $m->followedBy->username : '—');
                    $dueAmt = (float)($m->due_amount ?? 0);
                    $stKey = $m->status;
                    if ($stKey === 'judiciary' && $dueAmt <= 0) $stKey = 'judiciary_paid';
                    $st = $statusMap[$stKey] ?? ['label' => $m->status, 'color' => '#999'];
                    $isNF = (int)$m->never_followed === 1;
                    $panelUrl = Url::to(['/followUp/follow-up/panel', 'contract_id' => $m->id]);
                ?>
                <tr data-id="<?= $m->id ?>" <?= $isNF ? 'class="fur-row-nf"' : '' ?>>
                    <td class="ct-td-id" data-label="#">
                        <a href="<?= $panelUrl ?>" class="fur-id-link"><?= $m->id ?></a>
                        <?php if ($isNF): ?><span class="fur-nf-badge"><i class="fa fa-exclamation-circle"></i> لم يُتابع</span><?php endif ?>
                    </td>
                    <td data-label="العميل" title="<?= Html::encode($customerNamesFull) ?>"><?= Html::encode($customerNamesShort) ?></td>
                    <td data-label="القسط"><?= number_format($m->effective_installment ?? $m->monthly_installment_value ?? 0, 0) ?></td>
                    <td data-label="أقساط مستحقة" style="text-align:center"><span class="fur-due-badge"><?= (int)($m->due_installments ?? 0) ?></span></td>
                    <td data-label="المبلغ المستحق"><span class="<?= $dueAmt > 0 ? 'fur-amount-red' : 'fur-amount-green' ?>"><?= number_format($dueAmt, 0) ?></span></td>
                    <td data-label="آخر متابعة">
                        <?php if ($isNF): ?><span class="fur-nf-label">لم يُتابع أبداً</span>
                        <?php elseif (!empty($m->last_follow_up)): ?><?= date('Y-m-d', strtotime($m->last_follow_up)) ?>
                        <?php else: ?>—<?php endif ?>
                    </td>
                    <td data-label="التذكير">
                        <?php if (!empty($m->reminder)): ?><span class="<?= strtotime($m->reminder) <= strtotime('today') ? 'fur-overdue' : '' ?>"><?= $m->reminder ?></span>
                        <?php else: ?>—<?php endif ?>
                    </td>
                    <td data-label="وعد بالدفع">
                        <?php if (!empty($m->promise_to_pay_at)): ?><span class="<?= strtotime($m->promise_to_pay_at) <= strtotime('today') ? 'fur-overdue' : '' ?>"><?= $m->promise_to_pay_at ?></span>
                        <?php else: ?>—<?php endif ?>
                    </td>
                    <td data-label="الحالة"><span class="fur-status" style="background:<?= $st['color'] ?>18;color:<?= $st['color'] ?>"><?= $st['label'] ?></span></td>
                    <td data-label="المتابع">
                        <?php if ($isManager): ?>
                            <?= Html::dropDownList('followedBy', $m->followed_by, $allUsers, [
                                'class' => 'ct-follow-select followUpUser',
                                'data-contract-id' => $m->id,
                                'prompt' => '-- اختر --',
                            ]) ?>
                        <?php else: ?><?= Html::encode($followName) ?><?php endif ?>
                    </td>
                    <td data-label="" style="white-space:nowrap">
                        <button type="button" class="pin-btn" data-item-id="<?= $m->id ?>" data-label="عقد <?= $m->id ?>" data-extra="<?= Html::encode($customerNamesShort) ?>" title="تثبيت"><i class="fa fa-thumb-tack"></i></button>
                        <a href="<?= $panelUrl ?>" class="fur-btn-follow"><i class="fa fa-dashboard"></i> متابعة</a>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>

    <!-- ═══ PAGINATION ═══ -->
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
$suggestUrl = Url::to(['field-suggest']);
$this->registerJs(<<<JS
if (typeof UnifiedSearch !== 'undefined') {
    UnifiedSearch.init({inputId:'fur-name',  url:'{$suggestUrl}?field=customer_name', minChars:2, delay:300, formSelector:'#fur-search'});
    UnifiedSearch.init({inputId:'fur-id',    url:'{$suggestUrl}?field=id',            minChars:1, delay:300, formSelector:'#fur-search'});
    UnifiedSearch.init({inputId:'fur-idn',   url:'{$suggestUrl}?field=id_number',     minChars:2, delay:300, formSelector:'#fur-search'});
    UnifiedSearch.init({inputId:'fur-phone', url:'{$suggestUrl}?field=phone_number',  minChars:2, delay:300, formSelector:'#fur-search'});
}
JS
, View::POS_READY);
?>

<?php
$this->registerJs(<<<'JS'
PinSystem.init({
    type: 'followup_contract',
    barSelector: '#pin-bar',
    buildUrl: function(itemId) {
        return '/followUp/follow-up/panel?contract_id=' + itemId;
    }
});

$(document).on('click', '.pin-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var id = $(this).data('item-id');
    var label = $(this).data('label') || '';
    var extra = $(this).data('extra') || '';
    PinSystem.togglePin(id, label, extra);
});
JS
);
?>
