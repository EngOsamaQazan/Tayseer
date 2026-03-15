<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

$this->title = 'توليد طلب إجرائي — القضية #' . $model->judiciary_number;
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'القضية #' . $model->judiciary_number, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'توليد طلب';

$templateTypes = \backend\models\JudiciaryRequestTemplate::getTypeLabels();
?>

<style>
.req-page{direction:rtl;font-family:'Tajawal','Segoe UI',sans-serif;max-width:1100px;margin:0 auto}
.req-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:24px;margin-bottom:20px}
.req-card h3{font-size:16px;font-weight:700;color:#1E293B;margin:0 0 16px;display:flex;align-items:center;gap:8px}
.req-card h3 i{color:#3B82F6}
.req-form-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;margin-bottom:16px}
.req-form-group label{display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:6px}
.req-form-group select,.req-form-group input{width:100%;padding:8px 12px;border:1px solid #CBD5E1;border-radius:8px;font-size:13px}
.req-template-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
.req-template-item{border:1px solid #E2E8F0;border-radius:10px;padding:12px 16px;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:10px}
.req-template-item:hover{border-color:#3B82F6;background:#EFF6FF}
.req-template-item.selected{border-color:#3B82F6;background:#DBEAFE}
.req-template-item input[type=checkbox]{margin:0;flex-shrink:0}
.req-generate-btn{background:#3B82F6;color:#fff;border:none;padding:12px 32px;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;margin-top:16px}
.req-generate-btn:hover{background:#2563EB}
.req-editor-wrap{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:20px;margin-top:20px;display:none}
.req-editor-toolbar{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.req-editor-toolbar .btn{border-radius:8px;font-size:13px;font-weight:600}
#request-editor{min-height:500px;border:1px solid #E2E8F0;border-radius:8px;padding:20px;font-size:14px;line-height:1.8;direction:rtl}
@media print{
    body *{visibility:hidden}
    #request-editor,#request-editor *{visibility:visible}
    #request-editor{position:absolute;left:0;top:0;width:210mm;min-height:297mm;padding:20mm;border:none;font-size:12pt}
}
</style>

<div class="req-page">
    <div class="req-card">
        <h3><i class="fa fa-magic"></i> معالج توليد الطلب الإجرائي</h3>

        <div class="req-form-row">
            <div class="req-form-group">
                <label>المحكوم عليه</label>
                <select id="defendant-select">
                    <option value="">— اختر —</option>
                    <?php foreach ($defendants as $d): ?>
                        <option value="<?= $d->id ?>"><?= Html::encode($d->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="req-form-group">
                <label>مقدم الطلب (المفوض/الوكيل)</label>
                <select id="representative-select">
                    <?php foreach ($lawyers as $l): ?>
                        <option value="<?= $l->id ?>" <?= $l->id == $model->lawyer_id ? 'selected' : '' ?>>
                            <?= Html::encode($l->name) ?>
                            (<?= ($l->representative_type ?? 'delegate') === 'lawyer' ? 'وكيل' : 'مفوض' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h3><i class="fa fa-list-ul"></i> اختر بنود الطلب</h3>
        <div class="req-template-grid">
            <?php foreach ($templates as $t): ?>
                <label class="req-template-item" data-id="<?= $t->id ?>">
                    <input type="checkbox" name="template_ids[]" value="<?= $t->id ?>">
                    <span><?= Html::encode($t->name) ?></span>
                </label>
            <?php endforeach; ?>
            <?php if (empty($templates)): ?>
                <p style="color:#94A3B8;font-size:13px">لا توجد قوالب بعد. أضف قوالب من إعدادات النظام.</p>
            <?php endif; ?>
        </div>

        <div class="req-form-row" style="margin-top:16px">
            <div class="req-form-group">
                <label>جهة التوظيف (إن وجدت)</label>
                <input type="text" id="employer-name" placeholder="اسم جهة العمل">
            </div>
            <div class="req-form-group">
                <label>البنك (إن وجد)</label>
                <input type="text" id="bank-name" placeholder="اسم البنك">
            </div>
            <div class="req-form-group">
                <label>المبلغ</label>
                <input type="number" id="amount-field" step="0.01" placeholder="0.00">
            </div>
        </div>

        <button type="button" class="req-generate-btn" id="btn-generate">
            <i class="fa fa-file-text-o"></i> توليد الطلب
        </button>
    </div>

    <div class="req-editor-wrap" id="editor-section">
        <div class="req-editor-toolbar">
            <button class="btn btn-success" onclick="window.print()"><i class="fa fa-print"></i> طباعة</button>
            <button class="btn btn-primary" id="btn-save-request"><i class="fa fa-save"></i> حفظ كإجراء</button>
        </div>
        <div id="request-editor" contenteditable="true"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.req-template-item').forEach(function(el){
        el.addEventListener('click', function(e){
            if(e.target.tagName !== 'INPUT'){
                var cb = el.querySelector('input[type=checkbox]');
                cb.checked = !cb.checked;
            }
            el.classList.toggle('selected', el.querySelector('input').checked);
        });
    });

    document.getElementById('btn-generate').addEventListener('click', function(){
        var ids = [];
        document.querySelectorAll('.req-template-item input:checked').forEach(function(cb){ ids.push(cb.value); });
        var defSel = document.getElementById('defendant-select');
        var repSel = document.getElementById('representative-select');

        fetch('<?= Url::to(['generate-request', 'id' => $model->id]) ?>', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= Yii::$app->request->csrfToken ?>'},
            body: new URLSearchParams({
                template_ids: JSON.stringify(ids),
                defendant_name: defSel.options[defSel.selectedIndex]?.text || '',
                representative_id: repSel.value,
                employer_name: document.getElementById('employer-name').value,
                bank_name: document.getElementById('bank-name').value,
                amount: document.getElementById('amount-field').value,
            })
        })
        .then(r=>r.json())
        .then(function(res){
            if(res.success){
                document.getElementById('request-editor').innerHTML = res.html;
                document.getElementById('editor-section').style.display = 'block';
                document.getElementById('editor-section').scrollIntoView({behavior:'smooth'});
            } else {
                alert(res.message || 'حدث خطأ');
            }
        });
    });
});
</script>
