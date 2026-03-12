<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  شاشة التسويات المالية — Enterprise Comfort Mode
 *  ─────────────────────────────────────────────────────────────────
 *  1. شريط الملخص (Overview)
 *  2. شريط الأدوات (Actions)
 *  3. فلاتر ذكية (Smart Filters)
 *  4. جدول احترافي + بطاقات للجوال
 * ═══════════════════════════════════════════════════════════════════
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use common\helper\Permissions;

$this->title = 'الإدارة المالية';
$this->params['breadcrumbs'][] = $this->title;

/* ═══ تعريف الفرز ═══ */
$sort = $dataProvider->sort;
$sort->attributes = array_merge($sort->attributes, [
    'contract_id'           => ['asc' => ['contract_id' => SORT_ASC],           'desc' => ['contract_id' => SORT_DESC],           'label' => 'العقد'],
    'monthly_installment'   => ['asc' => ['monthly_installment' => SORT_ASC],   'desc' => ['monthly_installment' => SORT_DESC],   'label' => 'القسط الشهري'],
    'first_installment_date'=> ['asc' => ['first_installment_date' => SORT_ASC],'desc' => ['first_installment_date' => SORT_DESC],'label' => 'تاريخ أول قسط'],
    'new_installment_date'  => ['asc' => ['new_installment_date' => SORT_ASC],  'desc' => ['new_installment_date' => SORT_DESC],  'label' => 'تاريخ القسط الجديد'],
    'created_by'            => ['asc' => ['created_by' => SORT_ASC],            'desc' => ['created_by' => SORT_DESC],            'label' => 'بواسطة'],
]);
if (empty($sort->defaultOrder)) {
    $sort->defaultOrder = ['first_installment_date' => SORT_DESC];
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
?>

<?= $this->render('@app/views/layouts/_financial-tabs', ['activeTab' => 'settlements']) ?>

<div class="fin-page">

    <!-- ╔═══════════════════════════════════════════════╗
         ║  1. شريط الملخص                               ║
         ╚═══════════════════════════════════════════════╝ -->
    <section class="fin-overview" aria-label="ملخص التسويات">
        <div class="fin-ov-card fin-ov--count">
            <div class="fin-ov-icon"><i class="fa fa-list-ol"></i></div>
            <div class="fin-ov-body">
                <span class="fin-ov-num"><?= number_format($totalCount) ?></span>
                <span class="fin-ov-lbl">عدد التسويات</span>
            </div>
        </div>
        <div class="fin-ov-card fin-ov--balance">
            <div class="fin-ov-icon" style="background:var(--fin-neutral-bg,#f1f5f9);color:var(--fin-neutral,#64748b)"><i class="fa fa-calculator"></i></div>
            <div class="fin-ov-body">
                <span class="fin-ov-num"><?= number_format($totalInstallment, 2) ?></span>
                <span class="fin-ov-lbl">إجمالي الأقساط الشهرية</span>
            </div>
        </div>
        <?php if ($totalCount > 0): ?>
        <div class="fin-ov-card">
            <div class="fin-ov-icon" style="background:var(--fin-neutral-bg,#f1f5f9);color:var(--fin-neutral,#64748b)"><i class="fa fa-bar-chart"></i></div>
            <div class="fin-ov-body">
                <span class="fin-ov-num"><?= number_format($totalInstallment / $totalCount, 2) ?></span>
                <span class="fin-ov-lbl">متوسط القسط</span>
            </div>
        </div>
        <?php endif ?>
    </section>

    <!-- ╔═══════════════════════════════════════════════╗
         ║  2. شريط الأدوات                              ║
         ╚═══════════════════════════════════════════════╝ -->
    <section class="fin-actions" aria-label="إجراءات">
        <button type="button" class="fin-btn fin-btn--add" id="btnNewLoan" title="إضافة تسوية جديدة">
            <i class="fa fa-plus"></i> <span>تسوية جديدة</span>
        </button>
    </section>

    <!-- ╔═══════════════════════════════════════════════╗
         ║  3. فلاتر ذكية                                ║
         ╚═══════════════════════════════════════════════╝ -->
    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <!-- ╔═══════════════════════════════════════════════╗
         ║  4. جدول البيانات                              ║
         ╚═══════════════════════════════════════════════╝ -->
    <section class="fin-data-section">
        <div class="fin-data-bar">
            <span class="fin-data-count"><i class="fa fa-table"></i> عرض <b><?= $dataProvider->getCount() ?></b> من <b><?= $dataProvider->getTotalCount() ?></b> تسوية</span>
        </div>

        <!-- === جدول Desktop === -->
        <div class="fin-table-wrap">
            <table class="fin-table">
                <thead>
                    <tr>
                        <th class="fin-th fin-th--center" style="width:75px"><?= $sortLink('contract_id', 'fin-sort--center') ?></th>
                        <th class="fin-th fin-th--num" style="width:130px"><?= $sortLink('monthly_installment') ?></th>
                        <th class="fin-th" style="width:120px"><?= $sortLink('first_installment_date') ?></th>
                        <th class="fin-th" style="width:130px"><?= $sortLink('new_installment_date') ?></th>
                        <th class="fin-th" style="width:110px"><?= $sortLink('created_by') ?></th>
                        <th class="fin-th fin-th--center" style="width:80px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataProvider->getModels() as $m): ?>
                    <?php
                        $userName = $m->createdBy ? $m->createdBy->username : '—';
                    ?>
                    <tr class="fin-row" data-id="<?= $m->id ?>">
                        <td class="fin-td" style="text-align:center">
                            <?php if ($m->contract_id): ?>
                                <?= Html::a($m->contract_id, ['/contracts/contracts/update', 'id' => $m->contract_id], ['target' => '_blank', 'class' => 'fin-link']) ?>
                            <?php else: ?>
                                <span class="fin-na">—</span>
                            <?php endif ?>
                        </td>
                        <td class="fin-td fin-td--amount"><span class="fin-amt"><?= number_format($m->monthly_installment, 2) ?></span></td>
                        <td class="fin-td fin-td--date"><?= Html::encode($m->first_installment_date) ?></td>
                        <td class="fin-td"><?= Html::encode($m->new_installment_date ?: '—') ?></td>
                        <td class="fin-td"><?= Html::encode($userName) ?></td>
                        <td class="fin-td fin-td--acts">
                            <div class="fin-acts-wrap">
                                <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $m->id], ['class' => 'fin-act fin-act--edit', 'title' => 'تعديل']) ?>
                                <?= Html::a('<i class="fa fa-trash-o"></i>', ['delete', 'id' => $m->id], ['class' => 'fin-act fin-act--del', 'title' => 'حذف', 'data' => ['method' => 'post', 'confirm' => 'هل أنت متأكد من حذف هذه التسوية؟']]) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach ?>
                    <?php if ($dataProvider->getCount() === 0): ?>
                    <tr><td colspan="6" class="fin-td--empty"><i class="fa fa-inbox"></i> لا توجد تسويات</td></tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>

        <!-- === بطاقات Mobile === -->
        <div class="fin-cards-wrap">
            <?php foreach ($dataProvider->getModels() as $m): ?>
            <div class="fin-card">
                <div class="fin-card-head">
                    <span class="fin-card-date"><?= Html::encode($m->first_installment_date) ?></span>
                    <span class="fin-pill fin-pill--credit"><i class="fa fa-balance-scale"></i> تسوية</span>
                </div>
                <div class="fin-card-amount">
                    <?= number_format($m->monthly_installment, 2) ?> <small>د.أ/شهر</small>
                </div>
                <div class="fin-card-meta">
                    <span><i class="fa fa-file-text-o"></i> عقد #<?= (int)$m->contract_id ?></span>
                    <?php if ($m->createdBy): ?>
                    <span><i class="fa fa-user"></i> <?= Html::encode($m->createdBy->username) ?></span>
                    <?php endif ?>
                </div>
                <div class="fin-card-foot">
                    <?= Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $m->id], ['class' => 'fin-card-btn']) ?>
                    <?= Html::a('<i class="fa fa-trash-o"></i> حذف', ['delete', 'id' => $m->id], ['class' => 'fin-card-btn fin-card-btn--del', 'data' => ['method' => 'post', 'confirm' => 'هل أنت متأكد؟']]) ?>
                </div>
            </div>
            <?php endforeach ?>
            <?php if ($dataProvider->getCount() === 0): ?>
            <div class="fin-card fin-card--empty"><i class="fa fa-inbox"></i> لا توجد تسويات</div>
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
</div>

<!-- ╔═══════════════════════════════════════════════╗
     ║  مودال إنشاء / تعديل تسوية                    ║
     ╚═══════════════════════════════════════════════╝ -->
<div class="modal fade" id="loanModal" tabindex="-1" role="dialog" aria-labelledby="loanModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--fin-primary,#800020);color:#fff;border-radius:8px 8px 0 0">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:.8"><span>&times;</span></button>
                <h4 class="modal-title" id="loanModalLabel">تسوية جديدة</h4>
            </div>
            <div class="modal-body" id="loanModalBody">
                <div style="text-align:center;padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

<?php
$createUrl = Url::to(['create']);
$updateUrl = Url::to(['update', 'id' => '__ID__']);

$jsModal = <<<JSBLOCK
/* ═══ فتح مودال إنشاء تسوية ═══ */
$("#btnNewLoan").on("click",function(){
    var modal=$("#loanModal");
    modal.find("#loanModalLabel").text("تسوية جديدة");
    modal.find("#loanModalBody").html('<div style="text-align:center;padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
    modal.modal("show");
    $.ajax({
        url:"$createUrl", type:"GET", dataType:"json",
        success:function(res){
            modal.find("#loanModalBody").html(res.content);
            bindModalForm("$createUrl");
        },
        error:function(){modal.find("#loanModalBody").html('<div class="alert alert-danger">خطأ في تحميل النموذج</div>');}
    });
});

/* ═══ فتح مودال تعديل تسوية ═══ */
$(document).on("click",".fin-act--edit",function(e){
    e.preventDefault();
    var url=$(this).attr("href");
    var modal=$("#loanModal");
    modal.find("#loanModalLabel").text("تعديل التسوية");
    modal.find("#loanModalBody").html('<div style="text-align:center;padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
    modal.modal("show");
    $.ajax({
        url:url, type:"GET", dataType:"json",
        success:function(res){
            modal.find("#loanModalBody").html(res.content);
            bindModalForm(url);
        },
        error:function(){modal.find("#loanModalBody").html('<div class="alert alert-danger">خطأ في تحميل النموذج</div>');}
    });
});

/* ═══ ربط submit الفورم داخل المودال ═══ */
function bindModalForm(actionUrl){
    var modal=$("#loanModal");
    modal.find("form").on("submit",function(e){
        e.preventDefault();
        var form=$(this);
        var btn=form.find("[type=submit]");
        btn.prop("disabled",true).prepend('<i class="fa fa-spinner fa-spin"></i> ');
        $.ajax({
            url:actionUrl, type:"POST", data:form.serialize(), dataType:"json",
            success:function(res){
                if(res.success){
                    modal.modal("hide");
                    location.reload();
                } else if(res.content){
                    modal.find("#loanModalBody").html(res.content);
                    bindModalForm(actionUrl);
                }
            },
            error:function(){
                btn.prop("disabled",false).find(".fa-spinner").remove();
                alert("خطأ في الاتصال بالخادم");
            }
        });
    });
}
JSBLOCK;

$this->registerJs($jsModal, \yii\web\View::POS_READY);
?>
