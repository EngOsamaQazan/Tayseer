<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  شاشة أصناف المخزون — التصميم الاحترافي الموحد
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use common\helper\Permissions;
use backend\widgets\ExportButtons;

$this->title = 'إدارة المخزون';

CrudAsset::register($this);
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
?>

<?= $this->render('@app/views/layouts/_inventory-tabs', ['activeTab' => 'items']) ?>

<style>
/* ── ألوان المخزون ── */
.inv-page { --inv-primary: #0369a1; --inv-primary-bg: #e0f2fe; --inv-pending: #d97706; --inv-pending-bg: #fef3c7; --inv-approved: #15803d; --inv-approved-bg: #dcfce7; --inv-rejected: #dc2626; --inv-rejected-bg: #fee2e2; --inv-draft: #64748b; --inv-draft-bg: #f1f5f9; }

/* ── بطاقات الإحصائيات ── */
.inv-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 18px; }
.inv-stat-card { display: flex; align-items: center; gap: 14px; padding: 18px 20px; border-radius: 10px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; transition: all 0.2s; }
.inv-stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-2px); }
.inv-stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.inv-stat-body { display: flex; flex-direction: column; }
.inv-stat-num { font-size: 22px; font-weight: 800; line-height: 1.2; color: #1e293b; font-family: 'Cairo', sans-serif; }
.inv-stat-lbl { font-size: 12.5px; font-weight: 600; color: #64748b; margin-top: 2px; }
.inv-stat--total .inv-stat-icon { background: var(--inv-primary-bg); color: var(--inv-primary); }
.inv-stat--pending .inv-stat-icon { background: var(--inv-pending-bg); color: var(--inv-pending); }
.inv-stat--approved .inv-stat-icon { background: var(--inv-approved-bg); color: var(--inv-approved); }
.inv-stat--rejected .inv-stat-icon { background: var(--inv-rejected-bg); color: var(--inv-rejected); }
.inv-stat--total .inv-stat-num { color: var(--inv-primary); }
.inv-stat--pending .inv-stat-num { color: var(--inv-pending); }
.inv-stat--approved .inv-stat-num { color: var(--inv-approved); }
.inv-stat--rejected .inv-stat-num { color: var(--inv-rejected); }

/* ── شارات الحالة ── */
.inv-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.inv-badge--draft { background: var(--inv-draft-bg); color: var(--inv-draft); }
.inv-badge--pending { background: var(--inv-pending-bg); color: var(--inv-pending); }
.inv-badge--approved { background: var(--inv-approved-bg); color: var(--inv-approved); }
.inv-badge--rejected { background: var(--inv-rejected-bg); color: var(--inv-rejected); }

/* ── أزرار الموافقة ── */
.inv-approve-btn { background: #15803d; color: #fff; border: none; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; margin-left: 4px; }
.inv-approve-btn:hover { background: #166534; }
.inv-reject-btn { background: #dc2626; color: #fff; border: none; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; }
.inv-reject-btn:hover { background: #b91c1c; }

/* ── تجاوب ── */
@media (max-width: 900px) { .inv-stats { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 500px) { .inv-stats { grid-template-columns: 1fr; } }

/* ── تنسيق الجدول ── */
.inv-page .kv-grid-table th { background: #f8fafc; font-weight: 700; font-size: 13px; color: #334155; border-bottom: 2px solid #e2e8f0; }
.inv-page .kv-grid-table td { font-size: 13.5px; vertical-align: middle; }
.inv-page .panel { border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.inv-page .panel-heading { background: #f8fafc !important; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #334155; border-radius: 10px 10px 0 0 !important; }
</style>

<div class="inv-page">

    <!-- ═══ إحصائيات ═══ -->
    <div class="inv-stats">
        <div class="inv-stat-card inv-stat--total">
            <div class="inv-stat-icon"><i class="fa fa-cubes"></i></div>
            <div class="inv-stat-body">
                <span class="inv-stat-num"><?= number_format($stats['total']) ?></span>
                <span class="inv-stat-lbl">إجمالي الأصناف</span>
            </div>
        </div>
        <div class="inv-stat-card inv-stat--pending">
            <div class="inv-stat-icon"><i class="fa fa-clock-o"></i></div>
            <div class="inv-stat-body">
                <span class="inv-stat-num"><?= number_format($stats['pending']) ?></span>
                <span class="inv-stat-lbl">بانتظار الموافقة</span>
            </div>
        </div>
        <div class="inv-stat-card inv-stat--approved">
            <div class="inv-stat-icon"><i class="fa fa-check-circle"></i></div>
            <div class="inv-stat-body">
                <span class="inv-stat-num"><?= number_format($stats['approved']) ?></span>
                <span class="inv-stat-lbl">معتمد</span>
            </div>
        </div>
        <div class="inv-stat-card inv-stat--rejected">
            <div class="inv-stat-icon"><i class="fa fa-times-circle"></i></div>
            <div class="inv-stat-body">
                <span class="inv-stat-num"><?= number_format($stats['rejected']) ?></span>
                <span class="inv-stat-lbl">مرفوض</span>
            </div>
        </div>
    </div>

    <!-- ═══ أزرار الإجراءات ═══ -->
    <section class="fin-actions" aria-label="إجراءات">
        <?php if (Yii::$app->user->can(Permissions::INVENTORY_ITEMS)): ?>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-plus"></i> <span>صنف جديد</span>', ['create'], [
                'class' => 'fin-btn fin-btn--add', 'title' => 'إضافة صنف جديد', 'role' => 'modal-remote',
            ]) ?>
        </div>
        <?php endif ?>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-refresh"></i> <span>تحديث</span>', ['index'], [
                'class' => 'fin-btn fin-btn--reset', 'title' => 'تحديث القائمة',
            ]) ?>
        </div>
    </section>

    <!-- ═══ الجدول ═══ -->
    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => require(__DIR__ . '/_columns.php'),
            'summary' => '<span style="font-size:13px;color:#64748b"><i class="fa fa-table"></i> عرض <b>{begin}-{end}</b> من <b>{totalCount}</b> صنف</span>',
            'toolbar' => [
                ['content' =>
                    ExportButtons::widget([
                        'excelRoute' => ['export-excel'],
                        'pdfRoute'   => ['export-pdf'],
                    ]) . ' {toggleData}'
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'type' => 'default',
                'heading' => '<i class="fa fa-cubes"></i> أصناف المخزون <span class="badge" style="background:#0369a1;margin-right:6px">' . $searchCounter . '</span>',
            ],
            'pjax' => true,
            'pjaxSettings' => [
                'options' => ['id' => 'crud-datatable-pjax'],
            ],
        ]) ?>
    </div>
</div>

<?php Modal::begin([
    'id' => 'ajaxCrudModal',
    'footer' => '',
    'options' => ['class' => 'modal fade', 'tabindex' => false],
    'size' => Modal::SIZE_LARGE,
]) ?>
<?php Modal::end(); ?>

<?php
$approveBaseUrl = Url::to(['approve', 'id' => '__ID__']);
$rejectBaseUrl  = Url::to(['reject', 'id' => '__ID__']);
$csrfToken  = Yii::$app->request->csrfToken;

$js = <<<JS
/* ── اعتماد صنف ── */
$(document).on('click', '.inv-approve-btn', function(e){
    e.preventDefault();
    var id = $(this).data('id');
    if (!confirm('هل تريد اعتماد هذا الصنف؟')) return;
    var url = '{$approveBaseUrl}'.replace('__ID__', id);
    $.post(url, { _csrf: '{$csrfToken}' }, function(resp){
        if (resp.success) {
            $.pjax.reload({container: '#crud-datatable-pjax'});
        } else {
            alert(resp.message || 'خطأ');
        }
    }, 'json');
});

/* ── رفض صنف ── */
$(document).on('click', '.inv-reject-btn', function(e){
    e.preventDefault();
    var id = $(this).data('id');
    var reason = prompt('سبب الرفض (اختياري):');
    if (reason === null) return;
    var url = '{$rejectBaseUrl}'.replace('__ID__', id);
    $.post(url, { reason: reason, _csrf: '{$csrfToken}' }, function(resp){
        if (resp.success) {
            $.pjax.reload({container: '#crud-datatable-pjax'});
        } else {
            alert(resp.message || 'خطأ');
        }
    }, 'json');
});
JS;
$this->registerJs($js);
?>
