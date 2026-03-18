<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

/** @var \backend\modules\judiciary\models\Judiciary $model */
/** @var \backend\models\JudiciaryRequestTemplate[] $templates */
/** @var \backend\modules\customers\models\Customers[] $defendants */
/** @var \backend\modules\lawyers\models\Lawyers[] $lawyers */
/** @var array $defendantProfiles */
/** @var array $templateMeta */
/** @var float $contractAmount */

$this->title = 'توليد طلب إجرائي — القضية #' . ($model->judiciary_number ?: $model->id);
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'القضية #' . ($model->judiciary_number ?: $model->id), 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'توليد طلب';

$typeLabels = \backend\models\JudiciaryRequestTemplate::getTypeLabels();
$profilesJson = json_encode($defendantProfiles, JSON_UNESCAPED_UNICODE);
$metaJson = json_encode($templateMeta, JSON_UNESCAPED_UNICODE);
$contextPlaceholders = ['employer_name', 'bank_name', 'authority_name', 'amount', 'notification_date'];
?>

<style>
:root{--rq-primary:#1D4ED8;--rq-primary-light:#EFF6FF;--rq-green:#059669;--rq-green-light:#ECFDF5;--rq-amber:#D97706;--rq-amber-light:#FFFBEB;--rq-red:#DC2626;--rq-border:#E2E8F0;--rq-text:#1E293B;--rq-muted:#64748B;--rq-surface:#F8FAFC}
.rq-page{direction:rtl;font-family:'Tajawal','Segoe UI',sans-serif;max-width:1100px;margin:0 auto;padding-bottom:40px}

/* Step indicators */
.rq-steps{display:flex;gap:0;margin-bottom:28px;position:relative}
.rq-step{flex:1;text-align:center;padding:14px 8px;background:var(--rq-surface);border:1px solid var(--rq-border);font-size:13px;font-weight:600;color:var(--rq-muted);position:relative;transition:all .3s}
.rq-step:first-child{border-radius:0 10px 10px 0}
.rq-step:last-child{border-radius:10px 0 0 10px}
.rq-step .rq-step-num{display:inline-flex;width:24px;height:24px;border-radius:50%;background:var(--rq-border);color:#fff;align-items:center;justify-content:center;font-size:12px;margin-left:6px;transition:all .3s}
.rq-step.active{background:var(--rq-primary-light);border-color:var(--rq-primary);color:var(--rq-primary)}
.rq-step.active .rq-step-num{background:var(--rq-primary)}
.rq-step.done{background:var(--rq-green-light);border-color:var(--rq-green);color:var(--rq-green)}
.rq-step.done .rq-step-num{background:var(--rq-green)}

/* Card */
.rq-card{background:#fff;border:1px solid var(--rq-border);border-radius:12px;padding:24px;margin-bottom:20px;transition:opacity .3s}
.rq-card-title{font-size:15px;font-weight:700;color:var(--rq-text);margin:0 0 16px;display:flex;align-items:center;gap:8px}
.rq-card-title i{color:var(--rq-primary);font-size:16px}

/* Profile card */
.rq-profile{display:none;background:linear-gradient(135deg,#F0F9FF 0%,#E0F2FE 100%);border:1px solid #BAE6FD;border-radius:12px;padding:20px;margin-bottom:20px;animation:rqSlideIn .3s ease}
@keyframes rqSlideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.rq-profile-header{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.rq-profile-avatar{width:44px;height:44px;border-radius:50%;background:var(--rq-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;flex-shrink:0}
.rq-profile-name{font-size:16px;font-weight:700;color:var(--rq-text)}
.rq-profile-id{font-size:12px;color:var(--rq-muted)}
.rq-profile-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px}
.rq-profile-item{display:flex;align-items:center;gap:8px;font-size:13px;padding:8px 12px;background:rgba(255,255,255,.7);border-radius:8px}
.rq-profile-item i{color:var(--rq-primary);width:16px;text-align:center;flex-shrink:0}
.rq-profile-item .rq-pi-label{color:var(--rq-muted);margin-left:4px}
.rq-profile-item .rq-pi-value{color:var(--rq-text);font-weight:600}
.rq-profile-item.rq-pi-missing .rq-pi-value{color:var(--rq-amber);font-weight:400;font-style:italic}

/* Form grid */
.rq-form-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:16px}
.rq-fg{margin-bottom:0}
.rq-fg label{display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:6px}
.rq-fg select,.rq-fg input[type=text],.rq-fg input[type=number],.rq-fg input[type=date]{width:100%;padding:10px 14px;border:1px solid var(--rq-border);border-radius:8px;font-size:13px;font-family:inherit;transition:border-color .2s,box-shadow .2s;background:#fff}
.rq-fg select:focus,.rq-fg input:focus{border-color:var(--rq-primary);outline:none;box-shadow:0 0 0 3px rgba(29,78,216,.1)}
.rq-fg .rq-autofilled{border-color:var(--rq-green);background:var(--rq-green-light)}
.rq-fg .rq-fg-hint{font-size:11px;color:var(--rq-muted);margin-top:4px}
.rq-fg .rq-fg-source{font-size:10px;color:var(--rq-green);font-weight:600;margin-top:2px;display:none}
.rq-fg .rq-fg-source.visible{display:block}

/* Template grid — organized by type */
.rq-tpl-section{margin-bottom:16px}
.rq-tpl-type-label{font-size:12px;font-weight:700;color:var(--rq-muted);text-transform:uppercase;margin-bottom:8px;padding-bottom:4px;border-bottom:1px dashed var(--rq-border)}
.rq-tpl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px}
.rq-tpl-item{border:1px solid var(--rq-border);border-radius:8px;padding:10px 14px;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:8px;font-size:13px;user-select:none}
.rq-tpl-item:hover{border-color:var(--rq-primary);background:var(--rq-primary-light)}
.rq-tpl-item.selected{border-color:var(--rq-primary);background:#DBEAFE;font-weight:600}
.rq-tpl-item input[type=checkbox]{margin:0;flex-shrink:0;accent-color:var(--rq-primary)}

/* Context fields — dynamic based on templates */
.rq-context{display:none;animation:rqSlideIn .3s ease}
.rq-context.visible{display:block}
.rq-context-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;padding:3px 10px;border-radius:20px;margin-left:8px}
.rq-context-badge.auto{background:var(--rq-green-light);color:var(--rq-green)}
.rq-context-badge.manual{background:var(--rq-amber-light);color:var(--rq-amber)}

/* Generate button */
.rq-btn-generate{background:var(--rq-primary);color:#fff;border:none;padding:14px 36px;border-radius:10px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;display:inline-flex;align-items:center;gap:10px;transition:all .2s;margin-top:8px}
.rq-btn-generate:hover{background:#1E40AF;transform:translateY(-1px);box-shadow:0 4px 12px rgba(29,78,216,.3)}
.rq-btn-generate:disabled{opacity:.6;cursor:not-allowed;transform:none;box-shadow:none}
.rq-btn-generate .fa-spin{animation:fa-spin 1s infinite linear}

/* Editor */
.rq-editor-wrap{background:#fff;border:1px solid var(--rq-border);border-radius:12px;padding:20px;margin-top:20px;display:none;animation:rqSlideIn .4s ease}
.rq-editor-toolbar{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.rq-editor-toolbar .btn{border-radius:8px;font-size:13px;font-weight:600}
#request-editor{min-height:500px;border:1px solid var(--rq-border);border-radius:8px;padding:20px;font-size:14px;line-height:1.8;direction:rtl}

/* Responsive */
@media(max-width:767px){
    .rq-steps{flex-direction:column;gap:4px}
    .rq-step{border-radius:8px!important}
    .rq-form-row{grid-template-columns:1fr}
    .rq-tpl-grid{grid-template-columns:1fr}
    .rq-profile-grid{grid-template-columns:1fr}
}
@media print{
    body *{visibility:hidden}
    #request-editor,#request-editor *{visibility:visible}
    #request-editor{position:absolute;left:0;top:0;width:210mm;min-height:297mm;padding:20mm;border:none;font-size:12pt}
}
</style>

<div class="rq-page">
    <!-- Step Indicators -->
    <div class="rq-steps">
        <div class="rq-step active" id="step-1"><span class="rq-step-num">1</span> اختيار الأطراف</div>
        <div class="rq-step" id="step-2"><span class="rq-step-num">2</span> اختيار البنود</div>
        <div class="rq-step" id="step-3"><span class="rq-step-num">3</span> مراجعة وتوليد</div>
    </div>

    <!-- Step 1: Parties -->
    <div class="rq-card">
        <div class="rq-card-title"><i class="fa fa-users"></i> الأطراف</div>
        <div class="rq-form-row">
            <div class="rq-fg">
                <label>المحكوم عليه</label>
                <select id="defendant-select">
                    <option value="">— اختر المحكوم عليه —</option>
                    <?php foreach ($defendants as $d): ?>
                        <option value="<?= $d->id ?>"><?= Html::encode($d->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rq-fg">
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
    </div>

    <!-- Defendant Profile (auto-populated) -->
    <div class="rq-profile" id="defendant-profile">
        <div class="rq-profile-header">
            <div class="rq-profile-avatar" id="profile-avatar"></div>
            <div>
                <div class="rq-profile-name" id="profile-name"></div>
                <div class="rq-profile-id" id="profile-id"></div>
            </div>
        </div>
        <div class="rq-profile-grid" id="profile-grid"></div>
    </div>

    <!-- Step 2: Templates -->
    <div class="rq-card">
        <div class="rq-card-title"><i class="fa fa-list-ul"></i> بنود الطلب</div>
        <?php
        $grouped = [];
        foreach ($templates as $t) {
            $type = $t->template_type ?: 'other';
            $grouped[$type][] = $t;
        }
        if (empty($templates)): ?>
            <p style="color:var(--rq-muted);font-size:13px">لا توجد قوالب بعد. <?= Html::a('أضف قوالب من هنا', ['/judiciaryRequestTemplates/judiciary-request-templates/index'], ['style' => 'color:var(--rq-primary);font-weight:600']) ?></p>
        <?php else: ?>
            <?php foreach ($grouped as $type => $items): ?>
                <div class="rq-tpl-section">
                    <div class="rq-tpl-type-label"><?= Html::encode($typeLabels[$type] ?? $type) ?></div>
                    <div class="rq-tpl-grid">
                        <?php foreach ($items as $t): ?>
                            <label class="rq-tpl-item" data-id="<?= $t->id ?>">
                                <input type="checkbox" name="tpl[]" value="<?= $t->id ?>">
                                <span><?= Html::encode($t->name) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Step 3: Context Fields (shown dynamically based on selected templates) -->
    <div class="rq-card rq-context" id="context-section">
        <div class="rq-card-title">
            <i class="fa fa-pencil-square-o"></i> بيانات الطلب
            <span class="rq-context-badge auto" id="badge-auto" style="display:none"><i class="fa fa-check-circle"></i> <span id="badge-auto-count"></span> مُعبّأة تلقائياً</span>
            <span class="rq-context-badge manual" id="badge-manual" style="display:none"><i class="fa fa-edit"></i> <span id="badge-manual-count"></span> تحتاج إدخال</span>
        </div>
        <div class="rq-form-row" id="context-fields">
            <div class="rq-fg" id="fg-employer_name" style="display:none">
                <label>جهة التوظيف</label>
                <input type="text" id="ctx-employer_name" placeholder="—">
                <div class="rq-fg-source" id="src-employer_name"><i class="fa fa-database"></i> من ملف العميل</div>
            </div>
            <div class="rq-fg" id="fg-bank_name" style="display:none">
                <label>البنك</label>
                <input type="text" id="ctx-bank_name" placeholder="—">
                <div class="rq-fg-source" id="src-bank_name"><i class="fa fa-database"></i> من ملف العميل</div>
            </div>
            <div class="rq-fg" id="fg-authority_name" style="display:none">
                <label>الجهة الإدارية</label>
                <input type="text" id="ctx-authority_name" placeholder="اسم الجهة">
            </div>
            <div class="rq-fg" id="fg-amount" style="display:none">
                <label>المبلغ</label>
                <input type="number" id="ctx-amount" step="0.01" value="<?= $contractAmount ?>" placeholder="0.00">
                <?php if ($contractAmount > 0): ?>
                    <div class="rq-fg-source visible"><i class="fa fa-database"></i> قيمة العقد: <?= number_format($contractAmount, 2) ?></div>
                <?php endif; ?>
            </div>
            <div class="rq-fg" id="fg-notification_date" style="display:none">
                <label>تاريخ التبليغ</label>
                <input type="date" id="ctx-notification_date">
            </div>
        </div>
    </div>

    <!-- Generate Button -->
    <div style="text-align:center">
        <button type="button" class="rq-btn-generate" id="btn-generate" disabled>
            <i class="fa fa-file-text-o"></i> توليد الطلب
        </button>
    </div>

    <!-- Editor -->
    <div class="rq-editor-wrap" id="editor-section">
        <div class="rq-editor-toolbar">
            <button class="btn btn-success" onclick="window.print()"><i class="fa fa-print"></i> طباعة</button>
            <button class="btn btn-primary" id="btn-save-request"><i class="fa fa-save"></i> حفظ كإجراء</button>
        </div>
        <div id="request-editor" contenteditable="true"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var PROFILES   = <?= $profilesJson ?>;
    var TPL_META   = <?= $metaJson ?>;
    var CONTRACT_AMOUNT = <?= $contractAmount ?: 0 ?>;

    var defSel   = document.getElementById('defendant-select');
    var repSel   = document.getElementById('representative-select');
    var btnGen   = document.getElementById('btn-generate');
    var profileEl  = document.getElementById('defendant-profile');
    var contextEl  = document.getElementById('context-section');

    var ctxFields = ['employer_name','bank_name','authority_name','amount','notification_date'];
    var currentProfile = null;

    function updateSteps(){
        var s1 = document.getElementById('step-1');
        var s2 = document.getElementById('step-2');
        var s3 = document.getElementById('step-3');
        var hasDef = !!defSel.value;
        var hasTpl = document.querySelectorAll('.rq-tpl-item input:checked').length > 0;

        s1.className = 'rq-step ' + (hasDef ? 'done' : 'active');
        s2.className = 'rq-step ' + (hasTpl ? 'done' : (hasDef ? 'active' : ''));
        s3.className = 'rq-step ' + (hasDef && hasTpl ? 'active' : '');
        btnGen.disabled = !(hasDef && hasTpl);
    }

    defSel.addEventListener('change', function(){
        var id = this.value;
        currentProfile = PROFILES[id] || null;

        if(!currentProfile){
            profileEl.style.display = 'none';
            clearContextAutofill();
            updateSteps();
            return;
        }

        var p = currentProfile;
        document.getElementById('profile-avatar').textContent = (p.name||'?').charAt(0);
        document.getElementById('profile-name').textContent = p.name;
        document.getElementById('profile-id').textContent = p.id_number ? ('الهوية: ' + p.id_number) : '';

        var grid = document.getElementById('profile-grid');
        grid.innerHTML = '';
        var items = [
            {icon:'fa-briefcase', label:'جهة العمل', value:p.employer, key:'employer'},
            {icon:'fa-university', label:'البنك', value:p.bank ? (p.bank + (p.bank_branch ? ' — '+p.bank_branch : '')) : '', key:'bank'},
            {icon:'fa-money', label:'الراتب', value:p.salary > 0 ? Number(p.salary).toLocaleString('ar-JO') + ' د.أ' : '', key:'salary'},
            {icon:'fa-phone', label:'الهاتف', value:p.phone, key:'phone'},
        ];
        items.forEach(function(it){
            var div = document.createElement('div');
            div.className = 'rq-profile-item' + (it.value ? '' : ' rq-pi-missing');
            div.innerHTML = '<i class="fa '+it.icon+'"></i><span class="rq-pi-label">'+it.label+':</span><span class="rq-pi-value">'+(it.value || 'غير مسجّل')+'</span>';
            grid.appendChild(div);
        });

        profileEl.style.display = 'block';

        autofillContext(p);
        updateSteps();
    });

    function autofillContext(p){
        var empField = document.getElementById('ctx-employer_name');
        var bankField = document.getElementById('ctx-bank_name');

        if(p.employer){
            empField.value = p.employer;
            empField.classList.add('rq-autofilled');
            var src = document.getElementById('src-employer_name');
            if(src) src.classList.add('visible');
        } else {
            empField.value = '';
            empField.classList.remove('rq-autofilled');
            var src = document.getElementById('src-employer_name');
            if(src) src.classList.remove('visible');
        }

        if(p.bank){
            var bankVal = p.bank + (p.bank_branch ? ' — ' + p.bank_branch : '');
            bankField.value = bankVal;
            bankField.classList.add('rq-autofilled');
            var src = document.getElementById('src-bank_name');
            if(src) src.classList.add('visible');
        } else {
            bankField.value = '';
            bankField.classList.remove('rq-autofilled');
            var src = document.getElementById('src-bank_name');
            if(src) src.classList.remove('visible');
        }

        updateContextBadges();
    }

    function clearContextAutofill(){
        ctxFields.forEach(function(f){
            var el = document.getElementById('ctx-'+f);
            if(el && f !== 'amount'){
                el.value = '';
                el.classList.remove('rq-autofilled');
            }
            var src = document.getElementById('src-'+f);
            if(src) src.classList.remove('visible');
        });
        updateContextBadges();
    }

    function updateContextBadges(){
        var autoCount = 0, manualCount = 0;
        ctxFields.forEach(function(f){
            var fg = document.getElementById('fg-'+f);
            if(!fg || fg.style.display === 'none') return;
            var el = document.getElementById('ctx-'+f);
            if(el && el.classList.contains('rq-autofilled')) autoCount++;
            else if(el && !el.value) manualCount++;
        });
        var ba = document.getElementById('badge-auto');
        var bm = document.getElementById('badge-manual');
        if(autoCount > 0){
            ba.style.display = 'inline-flex';
            document.getElementById('badge-auto-count').textContent = autoCount;
        } else { ba.style.display = 'none'; }
        if(manualCount > 0){
            bm.style.display = 'inline-flex';
            document.getElementById('badge-manual-count').textContent = manualCount;
        } else { bm.style.display = 'none'; }
    }

    function updateContextFields(){
        var needed = {};
        document.querySelectorAll('.rq-tpl-item input:checked').forEach(function(cb){
            var meta = TPL_META[cb.value];
            if(meta && meta.placeholders){
                meta.placeholders.forEach(function(ph){
                    if(ctxFields.indexOf(ph) !== -1) needed[ph] = true;
                });
            }
        });

        var anyVisible = false;
        ctxFields.forEach(function(f){
            var fg = document.getElementById('fg-'+f);
            if(!fg) return;
            if(needed[f]){
                fg.style.display = '';
                anyVisible = true;
            } else {
                fg.style.display = 'none';
            }
        });

        if(anyVisible){
            contextEl.classList.add('visible');
        } else {
            contextEl.classList.remove('visible');
        }

        updateContextBadges();
    }

    document.querySelectorAll('.rq-tpl-item').forEach(function(el){
        el.addEventListener('click', function(e){
            if(e.target.tagName !== 'INPUT'){
                var cb = el.querySelector('input[type=checkbox]');
                cb.checked = !cb.checked;
            }
            el.classList.toggle('selected', el.querySelector('input').checked);
            updateContextFields();
            updateSteps();
        });
    });

    btnGen.addEventListener('click', function(){
        var ids = [];
        document.querySelectorAll('.rq-tpl-item input:checked').forEach(function(cb){ ids.push(cb.value); });
        if(ids.length === 0) return;

        btnGen.disabled = true;
        btnGen.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري التوليد...';

        var params = new URLSearchParams({
            template_ids: JSON.stringify(ids),
            defendant_id: defSel.value,
            representative_id: repSel.value,
            employer_name_override: document.getElementById('ctx-employer_name').value,
            bank_name_override: document.getElementById('ctx-bank_name').value,
            authority_name: document.getElementById('ctx-authority_name').value,
            amount: document.getElementById('ctx-amount').value,
            notification_date: document.getElementById('ctx-notification_date').value,
        });

        fetch('<?= Url::to(['generate-request', 'id' => $model->id]) ?>', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= Yii::$app->request->csrfToken ?>'},
            body: params
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            btnGen.disabled = false;
            btnGen.innerHTML = '<i class="fa fa-file-text-o"></i> توليد الطلب';
            if(res.success){
                document.getElementById('request-editor').innerHTML = res.html;
                document.getElementById('editor-section').style.display = 'block';
                document.getElementById('editor-section').scrollIntoView({behavior:'smooth'});
            } else {
                alert(res.message || 'حدث خطأ');
            }
        })
        .catch(function(){
            btnGen.disabled = false;
            btnGen.innerHTML = '<i class="fa fa-file-text-o"></i> توليد الطلب';
            alert('حدث خطأ في الاتصال');
        });
    });

    document.getElementById('btn-save-request').addEventListener('click', function(){
        var editor = document.getElementById('request-editor');
        var html = editor.innerHTML;
        if(!html || html.trim().length < 10){
            alert('يرجى توليد الطلب أولاً');
            return;
        }
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...';

        var ids = [];
        document.querySelectorAll('.rq-tpl-item input:checked').forEach(function(cb){ ids.push(cb.value); });

        var params = new URLSearchParams({
            html: html,
            defendant_id: defSel.value,
            template_ids: ids.join(','),
        });

        fetch('<?= Url::to(['save-generated-request', 'id' => $model->id]) ?>', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= Yii::$app->request->csrfToken ?>'},
            body: params
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-save"></i> حفظ كإجراء';
            if(res.success){
                btn.innerHTML = '<i class="fa fa-check"></i> تم الحفظ';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');
                setTimeout(function(){ btn.innerHTML = '<i class="fa fa-save"></i> حفظ كإجراء'; btn.classList.remove('btn-success'); btn.classList.add('btn-primary'); }, 3000);
            } else {
                alert(res.message || 'حدث خطأ');
            }
        })
        .catch(function(){
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-save"></i> حفظ كإجراء';
            alert('حدث خطأ في الاتصال');
        });
    });

    updateSteps();
});
</script>
