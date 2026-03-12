<?php
/**
 * شاشة الأرقام التسلسلية — عرض وإدارة كل قطعة فردية (IMEI/Serial)
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use backend\modules\inventoryItems\models\InventorySerialNumber;
use backend\widgets\ExportButtons;

$this->title = 'الأرقام التسلسلية — إدارة المخزون';
CrudAsset::register($this);
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
?>

<?= $this->render('@app/views/layouts/_inventory-tabs', ['activeTab' => 'serials']) ?>

<style>
/* ── تعريف المتغيرات المطلوبة من fin-transactions.css ── */
.sn-page {
    color: #1e293b;
    --fin-font: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
    --fin-credit: #166534;
    --fin-credit-bg: #dcfce7;
    --fin-debit: #991b1b;
    --fin-debit-bg: #fee2e2;
    --fin-gold: #92400e;
    --fin-gold-bg: #fef3c7;
    --fin-neutral: #475569;
    --fin-neutral-bg: #f1f5f9;
    --fin-border: #cbd5e1;
    --fin-bg: #f8fafc;
    --fin-surface: #ffffff;
    --fin-text: #1e293b;
    --fin-text2: #475569;
    --fin-r: 10px;
    --fin-r-sm: 6px;
    --fin-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
    --fin-shadow-md: 0 4px 12px rgba(0,0,0,0.06);
    --fin-primary: #075985;
    --clr-primary-400: #075985;
    --clr-primary-600: #0c4a6e;
}

/* ── إحصائيات ── */
.sn-stats { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
.sn-stat { display: flex; align-items: center; gap: 10px; padding: 12px 18px; border-radius: 10px; background: #fff; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.06); min-width: 140px; transition: all 0.2s; }
.sn-stat:hover { box-shadow: 0 3px 10px rgba(0,0,0,0.1); transform: translateY(-1px); }
.sn-stat-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.sn-stat-num { font-size: 20px; font-weight: 800; line-height: 1; font-family: 'Cairo', sans-serif; }
.sn-stat-lbl { font-size: 11px; font-weight: 600; color: #475569; margin-top: 2px; }

.sn-stat--total .sn-stat-icon { background: #dbeafe; color: #1d4ed8; }
.sn-stat--total .sn-stat-num { color: #1d4ed8; }
.sn-stat--available .sn-stat-icon { background: #dcfce7; color: #166534; }
.sn-stat--available .sn-stat-num { color: #166534; }
.sn-stat--reserved .sn-stat-icon { background: #fef3c7; color: #92400e; }
.sn-stat--reserved .sn-stat-num { color: #92400e; }
.sn-stat--sold .sn-stat-icon { background: #ede9fe; color: #5b21b6; }
.sn-stat--sold .sn-stat-num { color: #5b21b6; }
.sn-stat--returned .sn-stat-icon { background: #dbeafe; color: #075985; }
.sn-stat--returned .sn-stat-num { color: #075985; }
.sn-stat--defective .sn-stat-icon { background: #fee2e2; color: #991b1b; }
.sn-stat--defective .sn-stat-num { color: #991b1b; }

/* ── شارات الحالة — ألوان داكنة مع خلفيات فاتحة لضمان التباين ── */
.sn-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.sn-badge--available { background: #dcfce7; color: #166534; }
.sn-badge--reserved { background: #fef3c7; color: #92400e; }
.sn-badge--sold { background: #ede9fe; color: #5b21b6; }
.sn-badge--returned { background: #dbeafe; color: #1e40af; }
.sn-badge--defective { background: #fee2e2; color: #991b1b; }

/* ── الجدول ── */
.sn-page .panel { border-radius: 10px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.sn-page .panel-heading { background: #f1f5f9 !important; border-bottom: 1px solid #cbd5e1; font-weight: 700; color: #1e293b; border-radius: 10px 10px 0 0 !important; }
.sn-page .kv-grid-table th { background: #f1f5f9; font-weight: 700; font-size: 13px; color: #1e293b; border-bottom: 2px solid #cbd5e1; }
.sn-page .kv-grid-table th a { color: #1e293b !important; text-decoration: none; }
.sn-page .kv-grid-table th a:hover { color: #075985 !important; }
.sn-page .kv-grid-table td { font-size: 13.5px; vertical-align: middle; color: #1e293b; }
.sn-page .kv-grid-table td a { color: #075985; }
.sn-page .kv-grid-table .kv-row-select td { color: #1e293b; }
.sn-page .form-control { color: #1e293b; border-color: #cbd5e1; }
.sn-page .summary { color: #475569; }
.sn-page .panel-heading .badge { color: #fff; }
.sn-page .panel-footer { background: #f8fafc; border-top: 1px solid #cbd5e1; }

.sn-serial-cell { direction: ltr; font-family: 'Courier New', monospace; font-weight: 700; font-size: 13px; letter-spacing: 0.5px; color: #0f172a; }
</style>

<div class="sn-page">

    <!-- ═══ إحصائيات ═══ -->
    <div class="sn-stats">
        <div class="sn-stat sn-stat--total">
            <div class="sn-stat-icon"><i class="fa fa-barcode"></i></div>
            <div>
                <div class="sn-stat-num"><?= number_format($stats['total']) ?></div>
                <div class="sn-stat-lbl">الإجمالي</div>
            </div>
        </div>
        <div class="sn-stat sn-stat--available">
            <div class="sn-stat-icon"><i class="fa fa-check-circle"></i></div>
            <div>
                <div class="sn-stat-num"><?= number_format($stats['available']) ?></div>
                <div class="sn-stat-lbl">متاح</div>
            </div>
        </div>
        <div class="sn-stat sn-stat--reserved">
            <div class="sn-stat-icon"><i class="fa fa-clock-o"></i></div>
            <div>
                <div class="sn-stat-num"><?= number_format($stats['reserved']) ?></div>
                <div class="sn-stat-lbl">محجوز</div>
            </div>
        </div>
        <div class="sn-stat sn-stat--sold">
            <div class="sn-stat-icon"><i class="fa fa-shopping-cart"></i></div>
            <div>
                <div class="sn-stat-num"><?= number_format($stats['sold']) ?></div>
                <div class="sn-stat-lbl">مباع</div>
            </div>
        </div>
        <?php if ($stats['returned'] > 0): ?>
        <div class="sn-stat sn-stat--returned">
            <div class="sn-stat-icon"><i class="fa fa-undo"></i></div>
            <div>
                <div class="sn-stat-num"><?= number_format($stats['returned']) ?></div>
                <div class="sn-stat-lbl">مرتجع</div>
            </div>
        </div>
        <?php endif ?>
        <?php if ($stats['defective'] > 0): ?>
        <div class="sn-stat sn-stat--defective">
            <div class="sn-stat-icon"><i class="fa fa-exclamation-triangle"></i></div>
            <div>
                <div class="sn-stat-num"><?= number_format($stats['defective']) ?></div>
                <div class="sn-stat-lbl">معطل</div>
            </div>
        </div>
        <?php endif ?>
    </div>

    <!-- ═══ أزرار الإجراءات ═══ -->
    <section class="fin-actions" aria-label="إجراءات" style="margin-bottom: 14px">
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-plus"></i> <span>إضافة رقم تسلسلي</span>', ['serial-create'], [
                'class' => 'fin-btn fin-btn--add', 'title' => 'إضافة رقم تسلسلي جديد', 'role' => 'modal-remote',
            ]) ?>
        </div>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-refresh"></i> <span>تحديث</span>', ['serial-numbers'], [
                'class' => 'fin-btn fin-btn--reset',
            ]) ?>
        </div>
    </section>

    <!-- ═══ الجدول ═══ -->
    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'serial-datatable',
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => require(__DIR__ . '/_serial_columns.php'),
            'summary' => '<span style="font-size:13px;color:#64748b"><i class="fa fa-barcode"></i> عرض <b>{begin}-{end}</b> من <b>{totalCount}</b> رقم تسلسلي</span>',
            'toolbar' => [['content' =>
                ExportButtons::widget([
                    'excelRoute' => ['export-serials-excel'],
                    'pdfRoute'   => ['export-serials-pdf'],
                ]) . ' {toggleData}'
            ]],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'type' => 'default',
                'heading' => '<i class="fa fa-barcode"></i> الأرقام التسلسلية <span class="badge" style="background:#0369a1;margin-right:6px">' . $dataProvider->totalCount . '</span>',
            ],
            'pjax' => true,
            'pjaxSettings' => ['options' => ['id' => 'serial-datatable-pjax']],
        ]) ?>
    </div>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '', 'options' => ['class' => 'modal fade', 'tabindex' => false], 'size' => Modal::SIZE_LARGE]) ?>
<?php Modal::end(); ?>

<?php
$changeStatusUrl = Url::to(['serial-change-status']);
$csrfToken = Yii::$app->request->csrfToken;
$js = <<<JS
$(document).on('change', '.sn-status-select', function(){
    var el = $(this);
    var id = el.data('id');
    var status = el.val();
    if (!confirm('هل تريد تغيير الحالة؟')) { el.val(el.data('original')); return; }
    $.post('{$changeStatusUrl}', { id: id, status: status, _csrf: '{$csrfToken}' }, function(resp){
        if (resp.success) {
            $.pjax.reload({container: '#serial-datatable-pjax'});
        } else {
            alert(resp.message || 'خطأ');
            el.val(el.data('original'));
        }
    }, 'json');
});
JS;
$this->registerJs($js);
?>
