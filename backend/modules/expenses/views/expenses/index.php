<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  شاشة المصاريف — Enterprise Comfort Mode
 *  ─────────────────────────────────────────────────────────────────
 *  1. شريط الملخص المالي (Overview)
 *  2. شريط الأدوات (Actions)
 *  3. فلاتر ذكية (Smart Filters)
 *  4. جدول احترافي + بطاقات للجوال
 * ═══════════════════════════════════════════════════════════════════
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use common\helper\Permissions;
use backend\widgets\ExportButtons;

$this->title = 'الإدارة المالية';
$this->params['breadcrumbs'][] = $this->title;

/* ═══ بيانات مرجعية (كاش) ═══ */
$cache = Yii::$app->cache;
$p     = Yii::$app->params;
$d     = $p['time_duration'];
$db    = Yii::$app->db;

$categories = ArrayHelper::map(
    $cache->getOrSet($p['key_expenses_category'], fn() => $db->createCommand($p['expenses_category_query'])->queryAll(), $d), 'id', 'name');

/* ═══ Eager loading ═══ */
$dataProvider->query->with(['category', 'createdBy']);

/* ═══ تعريف الفرز ═══ */
$sort = $dataProvider->sort;
$sort->attributes = array_merge($sort->attributes, [
    'expenses_date'   => ['asc' => ['expenses_date' => SORT_ASC], 'desc' => ['expenses_date' => SORT_DESC], 'label' => 'التاريخ'],
    'description'     => ['asc' => ['description' => SORT_ASC],   'desc' => ['description' => SORT_DESC],   'label' => 'الوصف'],
    'category_id'     => ['asc' => ['category_id' => SORT_ASC],   'desc' => ['category_id' => SORT_DESC],   'label' => 'التصنيف'],
    'amount'          => ['asc' => ['amount' => SORT_ASC],        'desc' => ['amount' => SORT_DESC],        'label' => 'المبلغ'],
    'contract_id'     => ['asc' => ['contract_id' => SORT_ASC],   'desc' => ['contract_id' => SORT_DESC],   'label' => 'العقد'],
    'document_number' => ['asc' => ['document_number' => SORT_ASC], 'desc' => ['document_number' => SORT_DESC], 'label' => 'المستند'],
    'created_by'      => ['asc' => ['created_by' => SORT_ASC],   'desc' => ['created_by' => SORT_DESC],     'label' => 'بواسطة'],
]);
if (empty($sort->defaultOrder)) {
    $sort->defaultOrder = ['expenses_date' => SORT_DESC];
}

$sortLink = function($attribute, $extraClass = '') use ($sort) {
    $orders = $sort->getAttributeOrders();
    if (isset($orders[$attribute])) {
        $icon = $orders[$attribute] === SORT_ASC
            ? ' <i class="fa fa-sort-asc"></i>'
            : ' <i class="fa fa-sort-desc"></i>';
    } else {
        $icon = ' <i class="fa fa-sort fin-sort-idle"></i>';
    }
    $link  = $sort->createUrl($attribute);
    $label = $sort->attributes[$attribute]['label'] ?? $attribute;
    return '<a href="' . Html::encode($link) . '" class="fin-sort-link ' . $extraClass . '">' . $label . $icon . '</a>';
};

/* ═══ فترة التصفية ═══ */
$filterFrom = $searchModel->date_from ?: null;
$filterTo   = $searchModel->date_to ?: null;
$periodText = '';
if ($filterFrom && $filterTo) {
    $periodText = $filterFrom . ' — ' . $filterTo;
} elseif ($filterFrom) {
    $periodText = 'من ' . $filterFrom;
} elseif ($filterTo) {
    $periodText = 'حتى ' . $filterTo;
}

$baseExp    = Permissions::EXPENSES; // الصلاحية الأساسية كـ fallback
$canCreate  = Permissions::can(Permissions::EXP_CREATE) || Yii::$app->user->can($baseExp);
$canEdit    = Permissions::can(Permissions::EXP_EDIT)   || Yii::$app->user->can($baseExp);
$canDelete  = Permissions::can(Permissions::EXP_DELETE) || Yii::$app->user->can($baseExp);
$canRevert  = Permissions::can(Permissions::EXP_REVERT) || Yii::$app->user->can($baseExp);

/* ═══ هل تم تحديد فترة زمنية؟ ═══ */
$hasDateFilter = !empty($searchModel->date_from);
?>

<?= $this->render('@app/views/layouts/_financial-tabs', ['activeTab' => 'expenses']) ?>

<div class="fin-page">

    <!-- ╔═══════════════════════════════════════════════╗
         ║  1. شريط الملخص المالي                        ║
         ╚═══════════════════════════════════════════════╝ -->
    <?php if ($hasDateFilter): ?>
    <section class="fin-overview" aria-label="ملخص المصاريف">
        <div class="fin-ov-card fin-ov--count">
            <div class="fin-ov-icon"><i class="fa fa-list-ol"></i></div>
            <div class="fin-ov-body">
                <span class="fin-ov-num"><?= number_format($totalCount) ?></span>
                <span class="fin-ov-lbl">عدد المصاريف</span>
            </div>
        </div>
        <div class="fin-ov-card fin-ov--debit">
            <div class="fin-ov-icon"><i class="fa fa-credit-card"></i></div>
            <div class="fin-ov-body">
                <span class="fin-ov-num"><?= number_format($totalAmount, 2) ?></span>
                <span class="fin-ov-lbl">إجمالي المصاريف</span>
            </div>
        </div>
        <?php if ($totalCount > 0): ?>
        <div class="fin-ov-card fin-ov--balance">
            <div class="fin-ov-icon" style="background:var(--fin-neutral-bg,#f1f5f9);color:var(--fin-neutral,#64748b)"><i class="fa fa-bar-chart"></i></div>
            <div class="fin-ov-body">
                <span class="fin-ov-num"><?= number_format($totalAmount / $totalCount, 2) ?></span>
                <span class="fin-ov-lbl">متوسط المصروف</span>
            </div>
        </div>
        <?php endif ?>
        <?php if ($periodText): ?>
        <div class="fin-ov-card">
            <div class="fin-ov-icon" style="background:var(--fin-neutral-bg,#f1f5f9);color:var(--fin-neutral,#64748b)"><i class="fa fa-calendar"></i></div>
            <div class="fin-ov-body">
                <span class="fin-ov-num" style="font-size:15px;color:var(--fin-text)"><?= Html::encode($periodText) ?></span>
                <span class="fin-ov-lbl">الفترة الزمنية</span>
            </div>
        </div>
        <?php endif ?>
    </section>
    <?php endif /* hasDateFilter */ ?>

    <!-- ╔═══════════════════════════════════════════════╗
         ║  2. شريط الأدوات                              ║
         ╚═══════════════════════════════════════════════╝ -->
    <section class="fin-actions" aria-label="إجراءات">
        <?php if ($canCreate): ?>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-plus"></i> <span>مصروف جديد</span>', ['create'], [
                'class' => 'fin-btn fin-btn--add', 'title' => 'إضافة مصروف جديد',
            ]) ?>
        </div>
        <?php endif ?>
        <?php if ($hasDateFilter): ?>
        <div class="fin-act-group">
            <?= ExportButtons::widget([
                'excelRoute' => ['export-excel'],
                'pdfRoute'   => ['export-pdf'],
                'excelBtnClass' => 'fin-btn fin-btn--export',
                'pdfBtnClass'   => 'fin-btn fin-btn--export-pdf',
            ]) ?>
        </div>
        <?php endif ?>
    </section>

    <!-- ╔═══════════════════════════════════════════════╗
         ║  3. فلاتر ذكية                                ║
         ╚═══════════════════════════════════════════════╝ -->
    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <?php if (!$hasDateFilter): ?>
    <!-- ╔═══════════════════════════════════════════════╗
         ║  رسالة — يجب تحديد فترة زمنية أولاً            ║
         ╚═══════════════════════════════════════════════╝ -->
    <section class="fin-empty-state">
        <div class="fin-empty-icon"><i class="fa fa-calendar-check-o"></i></div>
        <h3 class="fin-empty-title">حدد فترة زمنية لعرض المصاريف</h3>
        <p class="fin-empty-desc">اختر تاريخ "من" في الفلاتر أعلاه لعرض البيانات.<br>إذا تركت حقل "إلى" فارغاً سيتم عرض المصاريف حتى تاريخ اليوم.</p>
    </section>
    <?php else: ?>
    <!-- ╔═══════════════════════════════════════════════╗
         ║  4. جدول البيانات                              ║
         ╚═══════════════════════════════════════════════╝ -->
    <section class="fin-data-section"
             x-data="{ showBulk: false, bulkCount: 0 }"
             @update-bulk.window="showBulk = $event.detail.show; bulkCount = $event.detail.count">
        <div class="fin-data-bar">
            <span class="fin-data-count"><i class="fa fa-table"></i> عرض <b><?= $dataProvider->getCount() ?></b> من <b><?= $dataProvider->getTotalCount() ?></b> مصروف</span>
            <?php if ($canDelete): ?>
            <div class="fin-bulk-bar" id="bulkBar" x-show="showBulk" x-transition x-cloak>
                <span class="fin-bulk-count"><i class="fa fa-check-square-o"></i> تم تحديد <b id="bulkCount" x-text="bulkCount">0</b> مصروف</span>
                <button type="button" class="fin-btn fin-btn--del fin-btn--sm" id="bulkDeleteBtn">
                    <i class="fa fa-trash-o"></i> حذف المحدد
                </button>
            </div>
            <?php endif ?>
        </div>

        <!-- === جدول Desktop === -->
        <div class="fin-table-wrap">
            <table class="fin-table">
                <thead>
                    <tr>
                        <?php if ($canDelete): ?>
                        <th class="fin-th fin-th--chk" style="width:36px"><input type="checkbox" id="chkAll" class="fin-chk"></th>
                        <?php endif ?>
                        <th class="fin-th" style="width:100px"><?= $sortLink('expenses_date') ?></th>
                        <th class="fin-th"><?= $sortLink('description') ?></th>
                        <th class="fin-th" style="width:110px"><?= $sortLink('category_id') ?></th>
                        <th class="fin-th fin-th--num" style="width:120px"><?= $sortLink('amount') ?></th>
                        <th class="fin-th fin-th--center" style="width:75px"><?= $sortLink('contract_id', 'fin-sort--center') ?></th>
                        <th class="fin-th fin-th--center" style="width:100px"><?= $sortLink('document_number', 'fin-sort--center') ?></th>
                        <th class="fin-th" style="width:90px"><?= $sortLink('created_by') ?></th>
                        <th class="fin-th" style="width:120px">ملاحظات</th>
                        <th class="fin-th fin-th--center" style="width:80px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataProvider->getModels() as $m): ?>
                    <?php
                        $catName  = $m->category ? $m->category->name : '—';
                        $userName = $m->createdBy ? $m->createdBy->username : '—';
                        $notes    = $m->notes ?: '';
                        $safeNotes = Html::encode($notes);
                        $safeDesc  = Html::encode($m->description ?: '');
                        $hasFt     = !empty($m->financial_transaction_id);
                    ?>
                    <tr class="fin-row fin-row--debit" data-id="<?= $m->id ?>">
                        <?php if ($canDelete): ?>
                        <td class="fin-td fin-td--chk"><input type="checkbox" class="fin-chk js-row-chk" value="<?= $m->id ?>"></td>
                        <?php endif ?>
                        <td class="fin-td fin-td--date"><?= Html::encode($m->expenses_date) ?></td>
                        <td class="fin-td"><span class="fin-desc" title="<?= $safeDesc ?>"><?= $safeDesc ?: '<span class="fin-na">—</span>' ?></span></td>
                        <td class="fin-td"><?= Html::encode($catName) ?></td>
                        <td class="fin-td fin-td--amount"><span class="fin-amt fin-amt--debit"><?= number_format($m->amount, 2) ?></span></td>
                        <td class="fin-td" style="text-align:center">
                            <?php if ($m->contract_id): ?>
                                <?= Html::a($m->contract_id, ['/contracts/contracts/update', 'id' => $m->contract_id], ['target' => '_blank', 'class' => 'fin-link']) ?>
                            <?php else: ?>
                                <span class="fin-na">—</span>
                            <?php endif ?>
                        </td>
                        <td class="fin-td" style="text-align:center"><?= $m->document_number ?: '<span class="fin-na">—</span>' ?></td>
                        <td class="fin-td"><?= Html::encode($userName) ?></td>
                        <td class="fin-td"><span class="fin-desc" title="<?= $safeNotes ?>"><?= $safeNotes ?: '<span class="fin-na">—</span>' ?></span></td>
                        <td class="fin-td fin-td--acts">
                            <div class="fin-acts-wrap">
                                <?php if ($canEdit): ?>
                                <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $m->id], ['class' => 'fin-act fin-act--edit', 'title' => 'تعديل']) ?>
                                <?php endif ?>
                                <?php if ($canDelete): ?>
                                <?= Html::a('<i class="fa fa-trash-o"></i>', ['delete', 'id' => $m->id], ['class' => 'fin-act fin-act--del', 'title' => 'حذف', 'data' => ['method' => 'post', 'confirm' => 'هل أنت متأكد من حذف هذا المصروف؟']]) ?>
                                <?php endif ?>
                                <?php if ($hasFt && $canRevert): ?>
                                <?= Html::a('<i class="fa fa-undo"></i>', ['back-to-financial-transaction', 'id' => $m->id, 'financial' => $m->financial_transaction_id], ['class' => 'fin-act fin-act--notes', 'title' => 'إرجاع للحركات المالية', 'data' => ['confirm' => 'هل تريد إرجاع هذا المصروف للحركات المالية؟']]) ?>
                                <?php endif ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach ?>
                    <?php if ($dataProvider->getCount() === 0): ?>
                    <tr><td colspan="<?= $canDelete ? 10 : 9 ?>" class="fin-td--empty"><i class="fa fa-inbox"></i> لا توجد مصاريف</td></tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>

        <!-- === بطاقات Mobile === -->
        <div class="fin-cards-wrap">
            <?php foreach ($dataProvider->getModels() as $m): ?>
            <div class="fin-card fin-card--debit">
                <div class="fin-card-head">
                    <span class="fin-card-date"><?= Html::encode($m->expenses_date) ?></span>
                    <span class="fin-pill fin-pill--debit"><i class="fa fa-credit-card"></i> <?= Html::encode($m->category ? $m->category->name : 'مصروف') ?></span>
                </div>
                <div class="fin-card-amount fin-amt--debit">
                    <?= number_format($m->amount, 2) ?> <small>د.أ</small>
                </div>
                <?php if (!empty($m->description)): ?>
                <div class="fin-card-desc"><?= Html::encode($m->description) ?></div>
                <?php endif ?>
                <div class="fin-card-meta">
                    <?php if ($m->contract_id): ?>
                    <span><i class="fa fa-file-text-o"></i> عقد #<?= (int)$m->contract_id ?></span>
                    <?php endif ?>
                    <?php if ($m->createdBy): ?>
                    <span><i class="fa fa-user"></i> <?= Html::encode($m->createdBy->username) ?></span>
                    <?php endif ?>
                </div>
                <div class="fin-card-foot">
                    <?php if ($canEdit): ?>
                    <?= Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $m->id], ['class' => 'fin-card-btn']) ?>
                    <?php endif ?>
                    <?php if ($canDelete): ?>
                    <?= Html::a('<i class="fa fa-trash-o"></i> حذف', ['delete', 'id' => $m->id], ['class' => 'fin-card-btn fin-card-btn--del', 'data' => ['method' => 'post', 'confirm' => 'هل أنت متأكد؟']]) ?>
                    <?php endif ?>
                </div>
            </div>
            <?php endforeach ?>
            <?php if ($dataProvider->getCount() === 0): ?>
            <div class="fin-card fin-card--empty"><i class="fa fa-inbox"></i> لا توجد مصاريف</div>
            <?php endif ?>
        </div>

        <!-- Pagination -->
        <div class="fin-pager">
            <?= \yii\widgets\LinkPager::widget([
                'pagination' => $dataProvider->getPagination(),
                'options' => ['class' => 'pagination'],
                'prevPageLabel' => '<i class="fa fa-chevron-right"></i>',
                'nextPageLabel' => '<i class="fa fa-chevron-left"></i>',
            ]) ?>
        </div>
    </section>
    <?php endif /* hasDateFilter */ ?>
</div>

<?php
/* ═══ JavaScript ═══ */
$bulkDeleteUrl = Url::to(['/expenses/expenses/bulk-delete']);

$jsCode = <<<'JSBLOCK'

/* Tooltips */
$(".fin-act[title], .fin-btn[title]").tooltip({placement:"bottom",container:"body"});

/* ═══ تحديد الصفوف والحذف الجماعي ═══ */
function updateBulkBar(){
    var c=$(".js-row-chk:checked").length;
    /* OLD jQuery - replaced by Alpine.js */
    // $("#bulkCount").text(c);
    // if(c>0){$("#bulkBar").stop().slideDown(200);}else{$("#bulkBar").stop().slideUp(200);}
    window.dispatchEvent(new CustomEvent('update-bulk',{detail:{show:c>0,count:c}}));
    var total=$(".js-row-chk").length;
    var chkAll=document.getElementById("chkAll");
    if(chkAll){
        chkAll.checked=(c>0 && c===total);
        chkAll.indeterminate=(c>0 && c<total);
    }
}

$("#chkAll").on("click",function(){
    var checked=this.checked;
    $(".js-row-chk").each(function(){
        this.checked=checked;
        $(this).closest(".fin-row").toggleClass("fin-row--selected",checked);
    });
    updateBulkBar();
});

$(document).on("click",".js-row-chk",function(){
    $(this).closest(".fin-row").toggleClass("fin-row--selected",this.checked);
    updateBulkBar();
});

$("#bulkDeleteBtn").on("click",function(){
    var ids=[];
    $(".js-row-chk:checked").each(function(){ids.push(this.value);});
    if(!ids.length) return;
    if(!confirm("هل أنت متأكد من حذف "+ids.length+" مصروف؟")) return;
    var btn=$(this);
    btn.prop("disabled",1).html('<i class="fa fa-spinner fa-spin"></i> جاري الحذف...');
    $.ajax({
        url:expUrls.bulkDelete, type:"POST",
        data:{pks:ids.join(",")}, dataType:"json",
        success:function(res){location.reload();},
        error:function(){alert("خطأ في الاتصال");btn.prop("disabled",0).html('<i class="fa fa-trash-o"></i> حذف المحدد');}
    });
});
JSBLOCK;

$jsUrls = 'var expUrls={bulkDelete:"' . $bulkDeleteUrl . '"};';

$this->registerJs($jsUrls . $jsCode, \yii\web\View::POS_READY);
?>
