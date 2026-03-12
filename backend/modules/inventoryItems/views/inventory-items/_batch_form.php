<?php
/**
 * نموذج إضافة مجموعة أصناف دفعة واحدة
 * الحقول: اسم الصنف (إلزامي)، الباركود، التصنيف (اختيار من القائمة + إضافة جديد)
 */
use yii\helpers\Html;
use backend\modules\inventoryItems\models\InventoryItems;

$existingCategories = InventoryItems::find()
    ->select('category')
    ->distinct()
    ->andWhere(['not', ['category' => null]])
    ->andWhere(['!=', 'category', ''])
    ->orderBy(['category' => SORT_ASC])
    ->column();
?>

<style>
.batch-form-wrap { padding: 10px; }
.batch-row { display: flex; gap: 8px; align-items: flex-end; margin-bottom: 8px; padding: 10px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
.batch-row .bf-col { flex: 1; min-width: 0; }
.batch-row .bf-col label { font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 3px; display: block; }
.batch-row .bf-col input, .batch-row .bf-col select { width: 100%; padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; }
.batch-row .bf-col input:focus, .batch-row .bf-col select:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 2px rgba(59,130,246,0.15); }
.bf-num { width: 30px !important; flex: none !important; text-align: center; font-weight: 700; color: #64748b; font-size: 14px; padding-top: 22px; }
.bf-remove { flex: none !important; width: 32px; }
.bf-remove button { background: #fee2e2; color: #dc2626; border: none; width: 30px; height: 30px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.bf-remove button:hover { background: #fecaca; }
.bf-actions { display: flex; gap: 8px; margin-top: 10px; }
.bf-add-btn { background: #0ea5e9; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; }
.bf-add-btn:hover { background: #0284c7; }
.bf-hint { color: #94a3b8; font-size: 12px; margin-top: 6px; }
</style>

<div class="batch-form-wrap">
    <form id="batchItemsForm" action="<?= \yii\helpers\Url::to(['batch-create']) ?>" method="post">
    <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
    <div id="batchRows">
        <?php for ($i = 0; $i < 3; $i++): ?>
        <div class="batch-row" data-idx="<?= $i ?>">
            <div class="bf-num"><?= $i + 1 ?></div>
            <div class="bf-col" style="flex:2">
                <?php if ($i === 0): ?><label>اسم الصنف *</label><?php endif ?>
                <input type="text" name="items[<?= $i ?>][item_name]" placeholder="اسم الصنف" required>
            </div>
            <div class="bf-col">
                <?php if ($i === 0): ?><label>الباركود</label><?php endif ?>
                <input type="text" name="items[<?= $i ?>][item_barcode]" placeholder="الباركود" style="direction:ltr;font-family:monospace">
            </div>
            <div class="bf-col">
                <?php if ($i === 0): ?><label>التصنيف</label><?php endif ?>
                <select name="items[<?= $i ?>][category]" class="batch-cat-select">
                    <option value="">— التصنيف —</option>
                    <?php foreach ($existingCategories as $cat): ?>
                        <option value="<?= Html::encode($cat) ?>"><?= Html::encode($cat) ?></option>
                    <?php endforeach ?>
                    <option value="__new__" style="font-weight:700; color:#0369a1;">＋ تصنيف جديد...</option>
                </select>
            </div>
            <div class="bf-remove">
                <?php if ($i > 0): ?>
                <button type="button" onclick="removeBatchRow(this)" title="حذف"><i class="fa fa-times"></i></button>
                <?php endif ?>
            </div>
        </div>
        <?php endfor ?>
    </div>

    <div class="bf-actions">
        <button type="button" class="bf-add-btn" onclick="addBatchRow()"><i class="fa fa-plus"></i> إضافة سطر</button>
    </div>
    <p class="bf-hint"><i class="fa fa-info-circle"></i> أضف أسطراً بحسب الحاجة — الحقل المطلوب هو "اسم الصنف" فقط.</p>
    </form>
</div>

<script>
var batchIdx = 3;
var categoriesHtml = <?= json_encode(
    '<option value="">— التصنيف —</option>' .
    implode('', array_map(function($cat) {
        return '<option value="' . Html::encode($cat) . '">' . Html::encode($cat) . '</option>';
    }, $existingCategories)) .
    '<option value="__new__" style="font-weight:700; color:#0369a1;">＋ تصنيف جديد...</option>'
) ?>;

function handleBatchCatChange(sel) {
    if (sel.value === '__new__') {
        var newCat = prompt('أدخل اسم التصنيف الجديد (مثال: أجهزة خلوية، أجهزة كهربائية، أثاث):');
        if (newCat && newCat.trim()) {
            newCat = newCat.trim();
            var opt = document.createElement('option');
            opt.value = newCat;
            opt.textContent = newCat;
            opt.selected = true;
            var newOpt = sel.querySelector('option[value="__new__"]');
            sel.insertBefore(opt, newOpt);
            categoriesHtml = categoriesHtml.replace(
                '<option value="__new__"',
                '<option value="' + newCat.replace(/"/g, '&quot;') + '">' + newCat.replace(/</g, '&lt;') + '</option><option value="__new__"'
            );
        } else {
            sel.value = '';
        }
    }
}

document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('batch-cat-select')) {
        handleBatchCatChange(e.target);
    }
});

function addBatchRow() {
    var i = batchIdx++;
    var html = '<div class="batch-row" data-idx="' + i + '">' +
        '<div class="bf-num">' + (i + 1) + '</div>' +
        '<div class="bf-col" style="flex:2"><input type="text" name="items[' + i + '][item_name]" placeholder="اسم الصنف" required></div>' +
        '<div class="bf-col"><input type="text" name="items[' + i + '][item_barcode]" placeholder="الباركود" style="direction:ltr;font-family:monospace"></div>' +
        '<div class="bf-col"><select name="items[' + i + '][category]" class="batch-cat-select">' + categoriesHtml + '</select></div>' +
        '<div class="bf-remove"><button type="button" onclick="removeBatchRow(this)" title="حذف"><i class="fa fa-times"></i></button></div>' +
    '</div>';
    document.getElementById('batchRows').insertAdjacentHTML('beforeend', html);
    renumberRows();
}

function removeBatchRow(btn) {
    btn.closest('.batch-row').remove();
    renumberRows();
}

function renumberRows() {
    var rows = document.querySelectorAll('#batchRows .batch-row');
    rows.forEach(function(row, idx) {
        row.querySelector('.bf-num').textContent = (idx + 1);
    });
}
</script>
