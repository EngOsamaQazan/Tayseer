<?php
/**
 * شاشة الإعدادات — إدارة الموردين والمواقع في صفحة واحدة
 */
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'إدارة المخزون';
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
$csrfToken = Yii::$app->request->csrfToken;
?>

<?= $this->render('@app/views/layouts/_inventory-tabs', ['activeTab' => 'settings']) ?>

<style>
[x-cloak] { display: none !important; }
.st-page {
    max-width: 1100px;
    --fin-font: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
    --fin-credit: #166534; --fin-credit-bg: #dcfce7;
    --fin-debit: #991b1b; --fin-debit-bg: #fee2e2;
    --fin-gold: #92400e; --fin-gold-bg: #fef3c7;
    --fin-neutral: #475569; --fin-neutral-bg: #f1f5f9;
    --fin-border: #cbd5e1; --fin-bg: #f8fafc; --fin-surface: #ffffff;
    --fin-text: #1e293b; --fin-text2: #475569;
    --fin-r: 10px; --fin-r-sm: 6px;
    --fin-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
    --fin-shadow-md: 0 4px 12px rgba(0,0,0,0.06);
    --fin-primary: #075985; --clr-primary-400: #075985; --clr-primary-600: #0c4a6e;
}
.st-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 800px) { .st-grid { grid-template-columns: 1fr; } }

.st-card { background: #fff; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden; }
.st-card-head { padding: 14px 20px; background: #f1f5f9; border-bottom: 1px solid #cbd5e1; display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 14px; color: #0f172a; }
.st-card-body { padding: 0; max-height: 420px; overflow-y: auto; }

.st-row { display: flex; align-items: center; padding: 10px 20px; border-bottom: 1px solid #f1f5f9; gap: 12px; }
.st-row:last-child { border-bottom: none; }
.st-row:hover { background: #f8fafc; }
.st-row-name { flex: 1; font-weight: 600; font-size: 13.5px; color: #1e293b; }
.st-row-meta { font-size: 12px; color: #94a3b8; }
.st-row-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }

/* ── نموذج إضافة سريعة ── */
.st-add { padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #fafbfc; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.st-add input { flex: 1; min-width: 120px; padding: 7px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; }
.st-add input:focus { border-color: #0369a1; outline: none; box-shadow: 0 0 0 3px rgba(3,105,161,0.1); }
.st-add-btn { padding: 7px 16px; background: #0369a1; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; }
.st-add-btn:hover { background: #075985; }
.st-btn-delete-supplier { padding: 6px 10px; border: none; border-radius: 6px; background: #fef2f2; color: #b91c1c; cursor: pointer; font-size: 13px; }
.st-btn-delete-supplier:hover { background: #fee2e2; }

.st-empty { padding: 30px; text-align: center; color: #94a3b8; font-size: 13px; }
.st-count { background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 12px; font-size: 11.5px; font-weight: 700; margin-right: auto; }

/* ── تصنيف الموردين ── */
.st-section-label { padding: 8px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 11.5px; font-weight: 700; color: #64748b; display: flex; align-items: center; gap: 6px; }
.st-badge { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; margin-right: 6px; vertical-align: middle; }
.st-badge i { font-size: 9px; margin-left: 2px; }
.st-badge-user { background: #dcfce7; color: #166534; }
.st-badge-ext { background: #e0f2fe; color: #0369a1; }

/* مودال النقل: منع قص قائمة الـ select عند الفتح */
#modal-transfer-supplier .modal-content,
#modal-transfer-supplier .modal-body { overflow: visible; }
#modal-transfer-supplier .modal-dialog { overflow: visible; }
.transfer-supplier-list { min-height: 44px; max-height: 220px; overflow-y: auto; margin-bottom: 8px; }
.transfer-supplier-list .btn { display: block !important; visibility: visible !important; }
.transfer-supplier-option.selected { background: #0369a1; color: #fff; border-color: #0369a1; }
</style>

<div class="st-page">
    <div class="st-grid">

        <!-- ═══ الموردين ═══ -->
        <div class="st-card">
            <div class="st-card-head">
                <i class="fa fa-truck" style="color:#0369a1"></i> الموردين
                <span class="st-count"><?= count($suppliers) ?></span>
            </div>
            <div class="st-card-body" id="suppliers-list">
                <?php if (empty($suppliers)): ?>
                    <div class="st-empty"><i class="fa fa-truck"></i><br>لا يوجد موردين</div>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                    <div class="st-row st-row-supplier" data-supplier-id="<?= (int)$s->id ?>" data-supplier-name="<?= Html::encode($s->name) ?>">
                        <?php if ($s->isSystemUser): ?>
                        <div class="st-row-icon" style="background:#dcfce7;color:#166534"><i class="fa fa-user-circle"></i></div>
                        <?php else: ?>
                        <div class="st-row-icon" style="background:#e0f2fe;color:#0369a1"><i class="fa fa-truck"></i></div>
                        <?php endif ?>
                        <div style="flex:1">
                            <div class="st-row-name">
                                <?= Html::encode($s->name) ?>
                                <?php if ($s->isSystemUser): ?>
                                <span class="st-badge st-badge-user"><i class="fa fa-check-circle"></i> مستخدم نظام</span>
                                <?php else: ?>
                                <span class="st-badge st-badge-ext"><i class="fa fa-external-link"></i> مورد خارجي</span>
                                <?php endif ?>
                            </div>
                            <div class="st-row-meta">
                                <?= Html::encode($s->phone_number) ?>
                                <?= $s->adress ? ' · ' . Html::encode($s->adress) : '' ?>
                            </div>
                        </div>
                        <?php if (!$s->isSystemUser): ?>
                        <button type="button" class="st-btn-delete-supplier" title="حذف المورد" data-id="<?= (int)$s->id ?>"><i class="fa fa-trash-o"></i></button>
                        <?php endif ?>
                    </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
            <div class="st-add">
                <input type="text" id="sup-name" placeholder="اسم المورد *">
                <input type="text" id="sup-phone" placeholder="رقم الهاتف *" style="max-width:140px">
                <button class="st-add-btn" id="btn-add-supplier"><i class="fa fa-plus"></i> إضافة</button>
            </div>
        </div>

        <!-- ═══ المواقع ═══ -->
        <div class="st-card">
            <div class="st-card-head">
                <i class="fa fa-map-marker" style="color:#7c3aed"></i> مواقع التخزين
                <span class="st-count"><?= count($locations) ?></span>
            </div>
            <div class="st-card-body" id="locations-list">
                <?php if (empty($locations)): ?>
                    <div class="st-empty"><i class="fa fa-map-marker"></i><br>لا يوجد مواقع</div>
                <?php else: ?>
                    <?php foreach ($locations as $loc): ?>
                    <div class="st-row">
                        <div class="st-row-icon" style="background:#ede9fe;color:#7c3aed"><i class="fa fa-map-marker"></i></div>
                        <div>
                            <div class="st-row-name"><?= Html::encode($loc->locations_name) ?></div>
                        </div>
                    </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
            <div class="st-add">
                <input type="text" id="loc-name" placeholder="اسم الموقع *">
                <button class="st-add-btn" id="btn-add-location"><i class="fa fa-plus"></i> إضافة</button>
            </div>
        </div>

    </div>
</div>

<!-- مودال نقل بيانات المورد ثم الحذف (overflow:visible حتى يعمل الـ select داخل المودال) -->
<div id="modal-transfer-supplier" class="modal fade" tabindex="-1" role="dialog" data-backdrop="true" data-keyboard="true">
    <div class="modal-dialog modal-transfer-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">نقل بيانات المورد ثم الحذف</h4>
            </div>
            <div class="modal-body modal-transfer-body"
                 x-data="{ hiddenSupplierId: null, showNoOther: false }"
                 id="modal-transfer-alpine">
                <p id="transfer-msg" class="text-muted"></p>
                <div class="form-group" id="transfer-supplier-form-group">
                    <label>نقل الفواتير والأصناف والحركات إلى المورد:</label>
                    <div id="transfer-to-supplier-list" class="transfer-supplier-list">
                        <?php foreach ($suppliers as $s): ?>
                        <button type="button" class="btn btn-secondary btn-block transfer-supplier-option transfer-option-supplier"
                                data-id="<?= (int)$s->id ?>" data-name="<?= Html::encode($s->name) ?>"
                                style="text-align: right; margin-bottom: 6px;"
                                x-show="hiddenSupplierId != <?= (int)$s->id ?>"
                                x-transition.opacity.duration.200ms>
                            <?php if ($s->isSystemUser): ?>
                            <i class="fa fa-user-circle" style="margin-left:6px;color:#166534"></i><?= Html::encode($s->name) ?> <span class="st-badge st-badge-user" style="font-size:10px"><i class="fa fa-check-circle"></i> مستخدم</span>
                            <?php else: ?>
                            <i class="fa fa-truck" style="margin-left:6px;color:#0369a1"></i><?= Html::encode($s->name) ?> <span class="st-badge st-badge-ext" style="font-size:10px">خارجي</span>
                            <?php endif ?>
                        </button>
                        <?php endforeach ?>
                    </div>
                    <p id="transfer-no-other-supplier" class="text-warning" style="margin-top:8px;"
                       x-show="showNoOther" x-cloak
                       x-transition.opacity.duration.200ms>لا يوجد موردون آخرون. أضف مورداً من الأسفل ثم أعد محاولة النقل.</p>
                    <input type="hidden" id="transfer-to-supplier" value="">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="btn-transfer-then-delete">نقل ثم حذف</button>
            </div>
        </div>
    </div>
</div>

<?php
$addSupplierUrl = Url::to(['quick-add-supplier']);
$addLocationUrl = Url::to(['quick-add-location']);
$deleteSupplierUrl = Url::to(['delete-supplier']);
$transferSupplierUrl = Url::to(['transfer-supplier-data']);

$js = <<<JS
$('#btn-add-supplier').click(function(){
    var name = $('#sup-name').val().trim();
    var phone = $('#sup-phone').val().trim();
    if (!name) { alert('أدخل اسم المورد'); $('#sup-name').focus(); return; }
    if (!phone) { alert('أدخل رقم الهاتف'); $('#sup-phone').focus(); return; }
    $(this).prop('disabled', true);
    $.post('$addSupplierUrl', { name: name, phone: phone, _csrf: '$csrfToken' }, function(resp){
        if (resp.success) {
            location.reload();
        } else {
            alert(resp.message);
        }
    }, 'json').always(function(){ $('#btn-add-supplier').prop('disabled', false); });
});

$('#btn-add-location').click(function(){
    var name = $('#loc-name').val().trim();
    if (!name) { alert('أدخل اسم الموقع'); $('#loc-name').focus(); return; }
    $(this).prop('disabled', true);
    $.post('$addLocationUrl', { name: name, _csrf: '$csrfToken' }, function(resp){
        if (resp.success) {
            location.reload();
        } else {
            alert(resp.message);
        }
    }, 'json').always(function(){ $('#btn-add-location').prop('disabled', false); });
});

// Enter key support
$('#sup-name, #sup-phone').keypress(function(e){ if(e.which==13) $('#btn-add-supplier').click(); });
$('#loc-name').keypress(function(e){ if(e.which==13) $('#btn-add-location').click(); });

// حذف مورد خارجي
var deleteSupplierId = null;
$(document).on('click', '.st-btn-delete-supplier', function(){
    var id = $(this).data('id');
    deleteSupplierId = id;
    var _tData = Alpine.\$data(document.getElementById('modal-transfer-alpine'));
    $('.transfer-supplier-option').removeClass('selected').prop('disabled', false);
    /* OLD jQuery - replaced by Alpine.js
    $('.transfer-supplier-option').show().css({ display: 'block', visibility: 'visible' });
    $('.transfer-option-supplier[data-id="' + id + '"]').hide();
    $('#transfer-no-other-supplier').hide();
    */
    _tData.hiddenSupplierId = id;
    _tData.showNoOther = false;
    $('#transfer-to-supplier').val('');
    $('#transfer-to-user').val('');
    $.post('$deleteSupplierUrl', { id: id, _csrf: '$csrfToken' }, function(resp){
        if (resp.success) {
            location.reload();
        } else {
            $('#transfer-msg').text(resp.message || 'لا يمكن الحذف: توجد سجلات مرتبطة.');
            var totalOptions = $('#transfer-to-supplier-list .transfer-supplier-option').length;
            if (totalOptions <= 1) {
                /* OLD jQuery - replaced by Alpine.js
                $('#transfer-no-other-supplier').show();
                */
                _tData.showNoOther = true;
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-transfer-supplier')).show();
        }
    }, 'json');
});
$(document).on('click', '.transfer-supplier-option', function(){
    $('.transfer-supplier-option').removeClass('selected');
    $(this).addClass('selected');
    $('#transfer-to-supplier').val($(this).data('id'));
});
$('#btn-transfer-then-delete').on('click', function(){
    var toId = $('#transfer-to-supplier').val();
    if (!toId || !deleteSupplierId) { alert('اختر المورد المستهدف (انقر على اسم المورد)'); return; }
    if (parseInt(toId) === parseInt(deleteSupplierId)) { alert('اختر مورداً مختلفاً عن المورد المراد حذفه'); return; }
    var btn = $(this);
    btn.prop('disabled', true);
    $.post('$transferSupplierUrl', { from_id: deleteSupplierId, to_id: toId, _csrf: '$csrfToken' }, function(resp){
        if (resp.success) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-transfer-supplier')).hide();
            location.reload();
        } else {
            alert(resp.message || 'حدث خطأ');
        }
    }, 'json').always(function(){ btn.prop('disabled', false); });
});
JS;
$this->registerJs($js);
?>
