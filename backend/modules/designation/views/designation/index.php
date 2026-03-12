<?php
/**
 * المسميات الوظيفية والأقسام — شاشة إدارة موحدة
 */
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'المسميات الوظيفية والأقسام';
$csrfToken = Yii::$app->request->csrfToken;

// تجميع المسميات حسب القسم
$grouped = [];
$noDept = [];
foreach ($designations as $d) {
    if ($d['department_name']) {
        $grouped[$d['department_name']][] = $d;
    } else {
        $noDept[] = $d;
    }
}
?>

<style>
.org-page { max-width:1100px; font-family:'Cairo','Segoe UI',Tahoma,sans-serif; }
.org-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.org-header h1 { font-size:22px; font-weight:800; color:#1e293b; margin:0; }
.org-header h1 i { color:#800020; margin-left:8px; }
.org-stats { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px; }
.org-stat { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 20px; display:flex; align-items:center; gap:10px; min-width:160px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
.org-stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; }
.org-stat-num { font-size:22px; font-weight:800; color:#1e293b; line-height:1; }
.org-stat-label { font-size:11px; color:#94a3b8; }

.org-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
@media(max-width:900px) { .org-grid { grid-template-columns:1fr; } }

.org-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; }
.org-card-head { padding:14px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:10px; font-weight:700; font-size:14px; color:#1e293b; }
.org-card-head i { color:#800020; }
.org-card-count { background:#e2e8f0; color:#475569; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:700; margin-right:auto; }
.org-card-body { padding:0; max-height:500px; overflow-y:auto; }

.org-row { display:flex; align-items:center; padding:10px 20px; border-bottom:1px solid #f1f5f9; gap:12px; }
.org-row:last-child { border-bottom:none; }
.org-row:hover { background:#f8fafc; }
.org-row-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; }
.org-row-name { font-weight:600; font-size:13.5px; color:#1e293b; flex:1; }
.org-row-meta { font-size:11px; color:#94a3b8; }
.org-row-badge { font-size:10px; padding:2px 8px; border-radius:8px; font-weight:600; }
.org-row-badge--dept { background:#f0fdf4; color:#166534; }
.org-row-badge--count { background:#eff6ff; color:#1d4ed8; }

.org-section-label { padding:8px 20px; background:#f1f5f9; border-bottom:1px solid #e2e8f0; font-size:11.5px; font-weight:700; color:#64748b; display:flex; align-items:center; gap:6px; }

.org-add { padding:12px 20px; border-top:1px solid #e2e8f0; background:#fafbfc; display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.org-add input, .org-add select { flex:1; min-width:120px; padding:7px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; }
.org-add input:focus, .org-add select:focus { border-color:#800020; outline:none; box-shadow:0 0 0 3px rgba(128,0,32,.08); }
.org-add-btn { padding:7px 16px; background:#800020; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; white-space:nowrap; }
.org-add-btn:hover { background:#6b001a; }

.org-seed-btn { padding:10px 20px; background:#800020; color:#fff; border:none; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
.org-seed-btn:hover { background:#6b001a; }
.org-empty { padding:30px; text-align:center; color:#94a3b8; font-size:13px; }
.org-del-btn { background:none; border:none; color:#cbd5e1; cursor:pointer; padding:4px 8px; border-radius:6px; font-size:13px; transition:all .15s; flex-shrink:0; }
.org-del-btn:hover { background:#fee2e2; color:#dc2626; }
.org-bulk-bar { padding:8px 20px; background:#fef3c7; border-top:1px solid #fde68a; display:none; align-items:center; gap:8px; font-size:12px; color:#92400e; font-weight:600; }
.org-bulk-bar button { padding:4px 12px; border:none; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; }
.org-bulk-bar .org-bulk-del { background:#dc2626; color:#fff; }
.org-bulk-bar .org-bulk-del:hover { background:#b91c1c; }
.org-bulk-bar .org-bulk-sel { background:#e2e8f0; color:#475569; }
.org-check { cursor:pointer; width:16px; height:16px; accent-color:#800020; }
</style>

<div class="org-page">

    <div class="org-header">
        <h1><i class="fa fa-sitemap"></i> المسميات الوظيفية والأقسام</h1>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="org-seed-btn" id="btn-seed">
                <i class="fa fa-magic"></i> إنشاء الافتراضية
            </button>
            <button class="org-seed-btn" id="btn-auto-link" style="background:#0369a1">
                <i class="fa fa-link"></i> ربط تلقائي
            </button>
            <button class="org-seed-btn" id="btn-reset" style="background:#dc2626">
                <i class="fa fa-trash"></i> إعادة تعيين
            </button>
        </div>
    </div>

    <div class="org-stats">
        <div class="org-stat">
            <div class="org-stat-icon" style="background:#eff6ff;color:#1d4ed8"><i class="fa fa-building"></i></div>
            <div>
                <div class="org-stat-num"><?= count($departments) ?></div>
                <div class="org-stat-label">الأقسام</div>
            </div>
        </div>
        <div class="org-stat">
            <div class="org-stat-icon" style="background:#fef3c7;color:#92400e"><i class="fa fa-briefcase"></i></div>
            <div>
                <div class="org-stat-num"><?= count($designations) ?></div>
                <div class="org-stat-label">المسميات الوظيفية</div>
            </div>
        </div>
        <div class="org-stat">
            <div class="org-stat-icon" style="background:#fdf2f8;color:#800020"><i class="fa fa-users"></i></div>
            <div>
                <div class="org-stat-num"><?= array_sum($desigCounts) ?></div>
                <div class="org-stat-label">موظفين مرتبطين</div>
            </div>
        </div>
    </div>

    <div class="org-grid">

        <!-- ═══ الأقسام ═══ -->
        <div class="org-card">
            <div class="org-card-head">
                <i class="fa fa-building"></i> الأقسام
                <span class="org-card-count"><?= count($departments) ?></span>
            </div>
            <div class="org-bulk-bar" id="bulk-dept">
                <button class="org-bulk-sel" id="btn-sel-all-dept"><i class="fa fa-check-square-o"></i> تحديد الكل</button>
                <button class="org-bulk-del" id="btn-del-sel-dept"><i class="fa fa-trash"></i> حذف المحدد</button>
                <span id="bulk-dept-count">0 محدد</span>
            </div>
            <div class="org-card-body">
                <?php if (empty($departments)): ?>
                    <div class="org-empty"><i class="fa fa-building"></i><br>لا يوجد أقسام — اضغط "إنشاء الافتراضية"</div>
                <?php else: ?>
                    <?php foreach ($departments as $dept): ?>
                    <div class="org-row" data-type="dept" data-id="<?= $dept->id ?>">
                        <input type="checkbox" class="org-check org-check-dept" value="<?= $dept->id ?>" style="margin-left:4px">
                        <div class="org-row-icon" style="background:#f0fdf4;color:#166534"><i class="fa fa-building"></i></div>
                        <div class="org-row-name"><?= Html::encode($dept->title) ?></div>
                        <?php if ($dept->description): ?>
                        <div class="org-row-meta"><?= Html::encode($dept->description) ?></div>
                        <?php endif; ?>
                        <button class="org-del-btn" data-type="dept" data-id="<?= $dept->id ?>" title="حذف"><i class="fa fa-times"></i></button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="org-add">
                <input type="text" id="dept-name" placeholder="اسم القسم الجديد *">
                <button class="org-add-btn" id="btn-add-dept"><i class="fa fa-plus"></i> إضافة</button>
            </div>
        </div>

        <!-- ═══ المسميات الوظيفية ═══ -->
        <div class="org-card">
            <div class="org-card-head">
                <i class="fa fa-briefcase"></i> المسميات الوظيفية
                <span class="org-card-count"><?= count($designations) ?></span>
            </div>
            <div class="org-bulk-bar" id="bulk-desig">
                <button class="org-bulk-sel" id="btn-sel-all-desig"><i class="fa fa-check-square-o"></i> تحديد الكل</button>
                <button class="org-bulk-del" id="btn-del-sel-desig"><i class="fa fa-trash"></i> حذف المحدد</button>
                <span id="bulk-desig-count">0 محدد</span>
            </div>
            <div class="org-card-body">
                <?php if (empty($designations)): ?>
                    <div class="org-empty"><i class="fa fa-briefcase"></i><br>لا يوجد مسميات — اضغط "إنشاء الافتراضية"</div>
                <?php else: ?>

                    <?php foreach ($grouped as $deptName => $desigs): ?>
                    <div class="org-section-label">
                        <i class="fa fa-building"></i> <?= Html::encode($deptName) ?>
                        <span style="margin-right:auto;font-size:10px;color:#94a3b8"><?= count($desigs) ?> مسمى</span>
                    </div>
                    <?php foreach ($desigs as $d): ?>
                    <div class="org-row" data-type="desig" data-id="<?= $d['id'] ?>">
                        <input type="checkbox" class="org-check org-check-desig" value="<?= $d['id'] ?>" style="margin-left:4px">
                        <div class="org-row-icon" style="background:#fef3c7;color:#92400e"><i class="fa fa-briefcase"></i></div>
                        <div class="org-row-name"><?= Html::encode($d['title']) ?></div>
                        <?php $cnt = $desigCounts[$d['id']] ?? 0; if ($cnt > 0): ?>
                        <span class="org-row-badge org-row-badge--count"><?= $cnt ?> موظف</span>
                        <?php endif; ?>
                        <button class="org-del-btn" data-type="desig" data-id="<?= $d['id'] ?>" title="حذف"><i class="fa fa-times"></i></button>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>

                    <?php if (!empty($noDept)): ?>
                    <div class="org-section-label">
                        <i class="fa fa-question-circle"></i> بدون قسم
                    </div>
                    <?php foreach ($noDept as $d): ?>
                    <div class="org-row" data-type="desig" data-id="<?= $d['id'] ?>">
                        <input type="checkbox" class="org-check org-check-desig" value="<?= $d['id'] ?>" style="margin-left:4px">
                        <div class="org-row-icon" style="background:#f1f5f9;color:#64748b"><i class="fa fa-briefcase"></i></div>
                        <div class="org-row-name"><?= Html::encode($d['title']) ?></div>
                        <?php $cnt = $desigCounts[$d['id']] ?? 0; if ($cnt > 0): ?>
                        <span class="org-row-badge org-row-badge--count"><?= $cnt ?> موظف</span>
                        <?php endif; ?>
                        <button class="org-del-btn" data-type="desig" data-id="<?= $d['id'] ?>" title="حذف"><i class="fa fa-times"></i></button>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
            <div class="org-add">
                <input type="text" id="desig-name" placeholder="اسم المسمى الوظيفي *" style="min-width:140px">
                <select id="desig-dept">
                    <option value="">— القسم —</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept->id ?>"><?= Html::encode($dept->title) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="org-add-btn" id="btn-add-desig"><i class="fa fa-plus"></i> إضافة</button>
            </div>
        </div>

    </div>
</div>

<?php
$seedUrl = Url::to(['seed-defaults']);
$addDeptUrl = Url::to(['quick-add-department']);
$addDesigUrl = Url::to(['quick-add-designation']);
$deleteUrl = Url::to(['ajax-delete']);
$resetUrl = Url::to(['reset-all']);
$autoLinkUrl = Url::to(['auto-link']);

$js = <<<JS
var csrf = '{$csrfToken}';

// ═══ إنشاء الافتراضيات ═══
$('#btn-seed').on('click', function() {
    if (!confirm('سيتم إنشاء الأقسام والمسميات الوظيفية الافتراضية. هل تريد المتابعة؟')) return;
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جارٍ...');
    $.post('{$seedUrl}', {_csrf: csrf}, function(res) {
        if (res.success) { alert(res.message); location.reload(); }
        else { alert(res.message || 'خطأ'); btn.prop('disabled', false).html('<i class="fa fa-magic"></i> إنشاء الافتراضية'); }
    }, 'json').fail(function(){ btn.prop('disabled', false).html('<i class="fa fa-magic"></i> إنشاء الافتراضية'); alert('خطأ اتصال'); });
});

// ═══ ربط تلقائي ═══
$('#btn-auto-link').on('click', function() {
    if (!confirm('سيتم ربط المسميات الوظيفية بالأقسام المناسبة تلقائياً. المتابعة؟')) return;
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جارٍ...');
    $.post('{$autoLinkUrl}', {_csrf: csrf}, function(res) {
        if (res.success) { alert(res.message); location.reload(); }
        else { alert(res.message || 'خطأ'); }
        btn.prop('disabled', false).html('<i class="fa fa-link"></i> ربط تلقائي');
    }, 'json').fail(function(){ btn.prop('disabled', false).html('<i class="fa fa-link"></i> ربط تلقائي'); alert('خطأ اتصال'); });
});

// ═══ إعادة تعيين (تنظيف) ═══
$('#btn-reset').on('click', function() {
    if (!confirm('تحذير: سيتم حذف جميع الأقسام والمسميات الوظيفية! هل أنت متأكد؟')) return;
    if (!confirm('هذا الإجراء لا يمكن التراجع عنه. متأكد؟')) return;
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جارٍ...');
    $.post('{$resetUrl}', {_csrf: csrf}, function(res) {
        if (res.success) { alert(res.message); location.reload(); }
        else { alert(res.message || 'خطأ'); }
        btn.prop('disabled', false).html('<i class="fa fa-trash"></i> إعادة تعيين');
    }, 'json').fail(function(){ btn.prop('disabled', false).html('<i class="fa fa-trash"></i> إعادة تعيين'); alert('خطأ اتصال'); });
});

// ═══ حذف فردي ═══
$(document).on('click', '.org-del-btn', function(e) {
    e.stopPropagation();
    var type = $(this).data('type'), id = $(this).data('id');
    var label = type === 'dept' ? 'القسم' : 'المسمى';
    if (!confirm('حذف ' + label + '؟')) return;
    $.post('{$deleteUrl}', {type: type, ids: [id], _csrf: csrf}, function(res) {
        if (res.success) { location.reload(); } else { alert(res.message); }
    }, 'json');
});

// ═══ إضافة قسم ═══
$('#btn-add-dept').click(function(){
    var name = $('#dept-name').val().trim();
    if (!name) { alert('أدخل اسم القسم'); $('#dept-name').focus(); return; }
    $(this).prop('disabled', true);
    $.post('{$addDeptUrl}', {title: name, _csrf: csrf}, function(resp){
        if (resp.success) { location.reload(); } else { alert(resp.message); }
    }, 'json').always(function(){ $('#btn-add-dept').prop('disabled', false); });
});

// ═══ إضافة مسمى وظيفي ═══
$('#btn-add-desig').click(function(){
    var name = $('#desig-name').val().trim();
    if (!name) { alert('أدخل اسم المسمى'); $('#desig-name').focus(); return; }
    var deptId = $('#desig-dept').val();
    $(this).prop('disabled', true);
    $.post('{$addDesigUrl}', {title: name, department_id: deptId, _csrf: csrf}, function(resp){
        if (resp.success) { location.reload(); } else { alert(resp.message); }
    }, 'json').always(function(){ $('#btn-add-desig').prop('disabled', false); });
});

// ═══ Checkbox bulk logic ═══
function updateBulk(type) {
    var cnt = $('.org-check-' + type + ':checked').length;
    $('#bulk-' + type).css('display', cnt > 0 ? 'flex' : 'none');
    $('#bulk-' + type + '-count').text(cnt + ' محدد');
}
$(document).on('change', '.org-check-dept', function(){ updateBulk('dept'); });
$(document).on('change', '.org-check-desig', function(){ updateBulk('desig'); });

$('#btn-sel-all-dept').click(function(){ var all = $('.org-check-dept'); var check = all.filter(':checked').length < all.length; all.prop('checked', check); updateBulk('dept'); });
$('#btn-sel-all-desig').click(function(){ var all = $('.org-check-desig'); var check = all.filter(':checked').length < all.length; all.prop('checked', check); updateBulk('desig'); });

$('#btn-del-sel-dept').click(function(){
    var ids = []; $('.org-check-dept:checked').each(function(){ ids.push($(this).val()); });
    if (!ids.length) return;
    if (!confirm('حذف ' + ids.length + ' قسم؟')) return;
    $.post('{$deleteUrl}', {type: 'dept', ids: ids, _csrf: csrf}, function(res) {
        if (res.success) { location.reload(); } else { alert(res.message); }
    }, 'json');
});
$('#btn-del-sel-desig').click(function(){
    var ids = []; $('.org-check-desig:checked').each(function(){ ids.push($(this).val()); });
    if (!ids.length) return;
    if (!confirm('حذف ' + ids.length + ' مسمى وظيفي؟')) return;
    $.post('{$deleteUrl}', {type: 'desig', ids: ids, _csrf: csrf}, function(res) {
        if (res.success) { location.reload(); } else { alert(res.message); }
    }, 'json');
});

// Enter key
$('#dept-name').keypress(function(e){ if(e.which==13) $('#btn-add-dept').click(); });
$('#desig-name').keypress(function(e){ if(e.which==13) $('#btn-add-desig').click(); });
JS;
$this->registerJs($js);
?>
