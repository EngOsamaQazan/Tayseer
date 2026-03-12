<?php
/**
 * Partial: Contract Adjustments (Discounts / Write-offs) Panel
 * Used inside the contract view or follow-up panel
 */

use yii\helpers\Html;
use yii\helpers\Url;
use backend\modules\contracts\models\ContractAdjustment;

$typeLabels = ContractAdjustment::typeLabels();
$typeColors = [
    'discount'      => '#1a7a35',
    'write_off'     => '#c62828',
    'waiver'        => '#0c5460',
    'free_discount' => '#856404',
];
?>

<div class="ca-panel" id="contractAdjustmentsPanel" x-data="{ showForm: false, hasItems: false }">
    <div class="ca-header">
        <h4><i class="fa fa-tags"></i> الخصومات والتسويات</h4>
        <button type="button" class="btn btn-sm btn-success" id="caAddBtn" @click="showForm = true">
            <i class="fa fa-plus"></i> إضافة خصم
        </button>
    </div>

    <!-- Add form (Alpine.js controlled) -->
    <div class="ca-form" id="caForm" x-show="showForm" x-transition:enter="alpine-enter" x-cloak>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>النوع</label>
                    <select class="form-control" id="caType">
                        <?php foreach ($typeLabels as $k => $v): ?>
                            <option value="<?= $k ?>"><?= $v ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>المبلغ</label>
                    <input type="number" class="form-control" id="caAmount" step="0.01" min="0.01" placeholder="0.00">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>السبب</label>
                    <input type="text" class="form-control" id="caReason" placeholder="سبب الخصم...">
                </div>
            </div>
        </div>
        <div class="text-left" style="margin-bottom:12px">
            <button type="button" class="btn btn-primary btn-sm" id="caSaveBtn">
                <i class="fa fa-check"></i> حفظ
            </button>
            <button type="button" class="btn btn-default btn-sm" id="caCancelBtn" @click="showForm = false">
                <i class="fa fa-times"></i> إلغاء
            </button>
        </div>
    </div>

    <!-- List -->
    <div class="ca-list" id="caList">
        <div class="text-center text-muted" style="padding:20px" id="caLoading">
            <i class="fa fa-spinner fa-spin"></i> جاري التحميل...
        </div>
    </div>

    <div class="ca-total" id="caTotal" x-show="hasItems" x-transition x-cloak>
        إجمالي الخصومات: <strong id="caTotalAmount">0</strong>
    </div>
</div>

<style>
.ca-panel { background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 16px; margin-bottom: 16px; }
.ca-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.ca-header h4 { margin: 0; font-size: 15px; color: #333; }
.ca-form { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 12px; margin-bottom: 12px; }
.ca-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
.ca-item:last-child { border-bottom: none; }
.ca-item-info { flex: 1; }
.ca-item-type { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; color: #fff; margin-left: 8px; }
.ca-item-amount { font-weight: 600; margin-left: 12px; }
.ca-item-reason { color: #888; font-size: 12px; margin-top: 2px; }
.ca-item-meta { color: #aaa; font-size: 11px; }
.ca-item-del { color: #c62828; cursor: pointer; padding: 4px; opacity: 0.6; }
.ca-item-del:hover { opacity: 1; }
.ca-total { text-align: left; padding: 8px 12px; background: #f8f9fa; border-radius: 4px; font-size: 14px; margin-top: 8px; }
.ca-empty { text-align: center; color: #aaa; padding: 16px; font-size: 13px; }
</style>

<?php
$addUrl = Url::to(['/contracts/contracts/add-adjustment']);
$deleteUrl = Url::to(['/contracts/contracts/delete-adjustment']);
$listUrl = Url::to(['/contracts/contracts/adjustments']);
$contractId = $contract_id ?? 0;

$js = <<<JS
(function(){
    var contractId = {$contractId};
    var addUrl = '{$addUrl}';
    var deleteUrl = '{$deleteUrl}';
    var listUrl = '{$listUrl}';
    var typeLabels = {
        discount: 'خصم تجاري',
        write_off: 'شطب',
        waiver: 'إعفاء',
        free_discount: 'خصم مجاني'
    };
    var typeColors = {
        discount: '#1a7a35',
        write_off: '#c62828',
        waiver: '#0c5460',
        free_discount: '#856404'
    };

    function loadAdjustments() {
        $.getJSON(listUrl, {contract_id: contractId}, function(data) {
            var html = '';
            var total = 0;
            var _caData = Alpine.$data(document.getElementById('contractAdjustmentsPanel'));
            if (!data || data.length === 0) {
                html = '<div class="ca-empty"><i class="fa fa-info-circle"></i> لا توجد خصومات مسجلة</div>';
                _caData.hasItems = false;
            } else {
                for (var i = 0; i < data.length; i++) {
                    var d = data[i];
                    total += parseFloat(d.amount);
                    html += '<div class="ca-item">';
                    html += '<div class="ca-item-info">';
                    html += '<span class="ca-item-type" style="background:' + (typeColors[d.type]||'#666') + '">' + (typeLabels[d.type]||d.type) + '</span>';
                    html += '<span class="ca-item-amount">' + parseFloat(d.amount).toLocaleString('en', {minimumFractionDigits:2}) + '</span>';
                    if (d.reason) html += '<div class="ca-item-reason">' + d.reason + '</div>';
                    html += '</div>';
                    html += '<span class="ca-item-meta">' + (d.created_at || '') + '</span>';
                    html += '<i class="fa fa-trash ca-item-del" data-id="' + d.id + '" title="حذف"></i>';
                    html += '</div>';
                }
                _caData.hasItems = true;
                $('#caTotalAmount').text(total.toLocaleString('en', {minimumFractionDigits:2}));
            }
            $('#caList').html(html);
        });
    }

    /* Alpine.js handles show/hide via x-show on #caForm */

    $('#caSaveBtn').on('click', function(){
        var amount = parseFloat($('#caAmount').val());
        if (!amount || amount <= 0) { alert('يرجى إدخال مبلغ صحيح'); return; }
        $.post(addUrl, {
            contract_id: contractId,
            type: $('#caType').val(),
            amount: amount,
            reason: $('#caReason').val()
        }, function(res) {
            if (res.success) {
                document.getElementById('caCancelBtn').click();
                $('#caAmount').val('');
                $('#caReason').val('');
                loadAdjustments();
            } else {
                alert(res.errors ? Object.values(res.errors).join(', ') : 'حدث خطأ');
            }
        }, 'json');
    });

    $(document).on('click', '.ca-item-del', function(){
        if (!confirm('هل أنت متأكد من حذف هذا الخصم؟')) return;
        var id = $(this).data('id');
        $.getJSON(deleteUrl, {id: id}, function(res) {
            loadAdjustments();
        });
    });

    loadAdjustments();
})();
JS;
$this->registerJs($js);
?>
