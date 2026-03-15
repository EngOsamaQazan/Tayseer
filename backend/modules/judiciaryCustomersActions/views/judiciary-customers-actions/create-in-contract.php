<?php
/**
 * إضافة/تعديل إجراء قضائي — تصميم جديد كلياً
 * يدعم: العرض الهرمي للإجراءات + Smart Media Upload + اختيار مباشر للأطراف
 */
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\customers\models\Customers;
use backend\modules\judiciaryActions\models\JudiciaryActions;
use yii\helpers\ArrayHelper;
use backend\modules\customers\models\ContractsCustomers;

/* @var $model backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions */
/* @var $contractID int */

// ─── Load judiciary for this contract ───
$judiciary = Judiciary::find()->where(['contract_id' => $contractID])->one();
$judiciaryLabel = $judiciary ? ($judiciary->judiciary_number . '/' . ($judiciary->year ?: '-')) : '-';

// ─── Load contract parties with photos ───
$partyRows = (new \yii\db\Query())
    ->select(['cc.customer_id', 'c.name', 'cc.customer_type'])
    ->from('os_contracts_customers cc')
    ->innerJoin('os_customers c', 'c.id = cc.customer_id')
    ->where(['cc.contract_id' => $contractID])
    ->all();

$partyPhotos = [];
foreach ($partyRows as $p) {
    $custModel = Customers::findOne($p['customer_id']);
    $photoPath = $custModel ? $custModel->getSelectedImagePath() : null;
    if ($photoPath && is_file(Yii::getAlias('@webroot') . $photoPath)) {
        $partyPhotos[$p['customer_id']] = $photoPath;
    } else {
        $partyPhotos[$p['customer_id']] = null;
    }
}

// ─── Load all active actions grouped by nature ───
$allActions = JudiciaryActions::find()
    ->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]])
    ->orderBy(['name' => SORT_ASC])
    ->all();

$natureLabels = [
    'request'    => 'طلبات إجرائية',
    'document'   => 'كتب ومذكرات',
    'doc_status' => 'حالات كتب',
    'process'    => 'إجراءات إدارية',
];
$natureIcons = [
    'request'    => 'fa-file-text-o',
    'document'   => 'fa-file-o',
    'doc_status' => 'fa-exchange',
    'process'    => 'fa-cog',
];
$natureColors = [
    'request'    => '#3B82F6',
    'document'   => '#8B5CF6',
    'doc_status' => '#EA580C',
    'process'    => '#64748B',
];

$actionNatureMap = [];
$groupedById = [];
foreach ($allActions as $a) {
    $n = $a->action_nature ?: 'process';
    $actionNatureMap[$a->id] = $n;
    $groupedById[$a->id] = ['name' => $a->name, 'nature' => $n];
}

// ─── Request-type actions for quick-add ───
$requestActions = [];
foreach ($allActions as $a) {
    if (($a->action_nature ?: 'process') === 'request') {
        $requestActions[$a->id] = $a->name;
    }
}

// ─── All requests (approved + pending) + existing documents for linking ───
$approvedRequests = [];
$existingDocuments = [];
if ($judiciary) {
    $reqRows = (new \yii\db\Query())
        ->select(['jca.id', 'jca.action_date', 'jca.customers_id', 'jca.request_status', 'ja.name as aname', 'c.name as cname'])
        ->from('os_judiciary_customers_actions jca')
        ->innerJoin('os_judiciary_actions ja', 'ja.id = jca.judiciary_actions_id')
        ->leftJoin('os_customers c', 'c.id = jca.customers_id')
        ->where(['jca.judiciary_id' => $judiciary->id, 'jca.is_deleted' => 0, 'ja.action_nature' => 'request'])
        ->andWhere(['in', 'jca.request_status', ['approved', 'pending']])
        ->all();
    foreach ($reqRows as $r) {
        $statusBadge = ($r['request_status'] === 'pending') ? ' [قيد الانتظار]' : '';
        $approvedRequests[$r['id']] = $r['aname'] . ($r['action_date'] ? ' · ' . substr($r['action_date'], 0, 10) : '') . ($r['cname'] ? ' — ' . $r['cname'] : '') . $statusBadge;
    }

    $docRows = (new \yii\db\Query())
        ->select(['jca.id', 'jca.action_date', 'jca.customers_id', 'ja.name as aname', 'c.name as cname'])
        ->from('os_judiciary_customers_actions jca')
        ->innerJoin('os_judiciary_actions ja', 'ja.id = jca.judiciary_actions_id')
        ->leftJoin('os_customers c', 'c.id = jca.customers_id')
        ->where(['jca.judiciary_id' => $judiciary->id, 'jca.is_deleted' => 0, 'ja.action_nature' => 'document'])
        ->all();
    foreach ($docRows as $r) {
        $existingDocuments[$r['id']] = $r['aname'] . ($r['action_date'] ? ' · ' . substr($r['action_date'], 0, 10) : '') . ($r['cname'] ? ' — ' . $r['cname'] : '');
    }
}

$isNew = $model->isNewRecord;
$existingCustomerId = $model->customers_id ?: '';
?>

<style>
/* ══════ Judiciary Action Form — OCP Design ══════ */
[x-cloak] { display: none !important; }
.jaf { font-family:var(--ocp-font-family,'Tajawal',sans-serif);direction:rtl;font-size:13px;color:#1E293B; }
.jaf *,.jaf *:before,.jaf *:after { box-sizing:border-box; }

/* Case header chip */
.jaf-case {
    display:flex;align-items:center;gap:10px;padding:12px 16px;
    background:linear-gradient(135deg,#EFF6FF,#F0F9FF);border-radius:10px;
    border:1px solid #BFDBFE;margin-bottom:14px;
}
.jaf-case-icon { width:40px;height:40px;border-radius:10px;background:#DBEAFE;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.jaf-case-number { font-size:16px;font-weight:800;color:#1E40AF;font-family:'Courier New',monospace; }
.jaf-case-court { font-size:11px;color:#64748B;margin-top:1px; }

/* Parties selector */
.jaf-parties { display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px; }
.jaf-party {
    display:flex;align-items:center;gap:8px;padding:8px 14px;
    border-radius:10px;border:2px solid #E2E8F0;background:#fff;
    cursor:pointer;transition:all .2s;font-size:12px;
}
.jaf-party:hover { border-color:#93C5FD;background:#F0F9FF; }
.jaf-party.selected { border-color:#3B82F6;background:#EFF6FF;box-shadow:0 0 0 3px rgba(59,130,246,.15); }
.jaf-party-avatar {
    width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;
}
.jaf-party-name { font-weight:600;color:#1E293B; }
.jaf-party-type { font-size:10px;padding:1px 6px;border-radius:8px;font-weight:500; }

/* Action tree selector */
.jaf-action-tree { margin-bottom:14px; }
.jaf-nature-group { margin-bottom:8px; }
.jaf-nature-header {
    display:flex;align-items:center;gap:6px;padding:6px 10px;
    background:#F8FAFC;border-radius:8px 8px 0 0;border:1px solid #E2E8F0;border-bottom:none;
    font-weight:700;font-size:12px;cursor:pointer;user-select:none;
}
.jaf-nature-header i.toggle { transition:transform .2s;margin-left:auto; }
.jaf-nature-header.collapsed i.toggle { transform:rotate(-90deg); }
.jaf-nature-list {
    display:flex;flex-direction:column;border:1px solid #E2E8F0;border-radius:0 0 8px 8px;
    max-height:200px;overflow-y:auto;
}
.jaf-nature-list.collapsed { display:none; }
.jaf-action-item {
    display:flex;align-items:center;gap:8px;padding:7px 12px;
    border-bottom:1px solid #F1F5F9;cursor:pointer;transition:all .15s;font-size:12px;
}
.jaf-action-item:last-child { border-bottom:none; }
.jaf-action-item:hover { background:#F8FAFC; }
.jaf-action-item.selected { background:#EFF6FF;font-weight:600;color:#1D4ED8; }
.jaf-action-item .bullet { width:8px;height:8px;border-radius:50%;flex-shrink:0; }

/* Contextual sections */
/* OLD jQuery - replaced by Alpine.js: .jaf-ctx display toggling now uses x-show */
.jaf-ctx { margin-bottom:14px;padding:12px 14px;border-radius:10px;border:1px solid #E2E8F0;background:#FAFAFA; }
.jaf-ctx-title { font-size:12px;font-weight:700;color:#475569;margin-bottom:8px;display:flex;align-items:center;gap:6px; }

/* Date field */
.jaf-date-wrap { margin-bottom:14px; }
.jaf-input {
    width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;
    font-size:13px;outline:none;transition:border-color .2s;background:#fff;
}
.jaf-input:focus { border-color:#3B82F6;box-shadow:0 0 0 3px rgba(59,130,246,.1); }
.jaf-label { font-size:11px;font-weight:600;color:#64748B;margin-bottom:4px;display:block; }

/* Note textarea */
.jaf-note textarea {
    width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;
    font-size:13px;resize:vertical;min-height:60px;outline:none;font-family:inherit;
}
.jaf-note textarea:focus { border-color:#3B82F6;box-shadow:0 0 0 3px rgba(59,130,246,.1); }

/* Upload zone (simplified smart-media) */
.jaf-upload-zone {
    border:2px dashed #CBD5E1;border-radius:10px;padding:20px;text-align:center;
    background:#FAFAFA;cursor:pointer;transition:all .2s;position:relative;
}
.jaf-upload-zone:hover,.jaf-upload-zone.dragover { border-color:#3B82F6;background:#F0F9FF; }
.jaf-upload-zone input[type="file"] { position:absolute;inset:0;opacity:0;cursor:pointer; }
.jaf-upload-icon { font-size:28px;color:#94A3B8;margin-bottom:6px; }
.jaf-upload-text { font-size:12px;color:#64748B; }
.jaf-upload-hint { font-size:10px;color:#94A3B8;margin-top:2px; }
.jaf-preview-list { display:flex;gap:8px;flex-wrap:wrap;margin-top:10px; }
.jaf-preview-item {
    position:relative;width:80px;height:80px;border-radius:8px;overflow:hidden;
    border:1px solid #E2E8F0;background:#F8FAFC;
}
.jaf-preview-item img { width:100%;height:100%;object-fit:cover; }
.jaf-preview-item .remove {
    position:absolute;top:2px;left:2px;width:18px;height:18px;border-radius:50%;
    background:rgba(239,68,68,.9);color:#fff;display:flex;align-items:center;justify-content:center;
    font-size:10px;cursor:pointer;border:none;line-height:1;
}

/* Inline select */
.jaf-select {
    width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;
    font-size:13px;outline:none;background:#fff;cursor:pointer;
    -webkit-appearance:none;appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394A3B8' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:left 12px center;
}
.jaf-select:focus { border-color:#3B82F6;box-shadow:0 0 0 3px rgba(59,130,246,.1); }

/* Request status hint */
.jaf-status-hint {
    display:inline-flex;align-items:center;gap:4px;padding:4px 10px;
    border-radius:8px;font-size:11px;font-weight:600;
}

/* Multi-select party checkbox */
.jaf-party-check {
    font-size:16px;color:#CBD5E1;transition:all .2s;flex-shrink:0;
}
.jaf-party.selected .jaf-party-check { color:#3B82F6; }
.jaf-party.selected .jaf-party-check i:before { content:"\f14a"; }

/* Select all button */
.jaf-select-all { cursor:pointer; }
.jaf-select-all.all-selected { border-color:#DC2626!important;background:#FEF2F2!important; }
.jaf-select-all.all-selected span { color:#DC2626!important; }
.jaf-select-all.all-selected i { color:#DC2626!important; }
</style>

<?php
$existingNature = '';
if ($model->judiciary_actions_id && isset($actionNatureMap[$model->judiciary_actions_id])) {
    $existingNature = $actionNatureMap[$model->judiciary_actions_id];
}
$showReqTarget = (!$isNew && (int)$model->judiciary_actions_id === 55) ? 'true' : 'false';
?>
<div class="jaf"
     x-data="{ activeContext: '<?= $existingNature ?>', showPartyError: false, showRequestTarget: <?= $showReqTarget ?> }"
     id="jaf-alpine-root">

<?php $form = ActiveForm::begin([
    'id' => 'jaf-form',
    'options' => ['enctype' => 'multipart/form-data'],
]); ?>

<!-- ═══ Hidden fields ═══ -->
<?= Html::activeHiddenInput($model, 'judiciary_id', ['id' => 'jaf-judiciary-id', 'value' => $judiciary ? $judiciary->id : '']) ?>
<?php if ($isNew): ?>
    <input type="hidden" name="multi_customers_ids" id="jaf-multi-customers" value="">
<?php else: ?>
    <?= Html::activeHiddenInput($model, 'customers_id', ['id' => 'jaf-customer-id']) ?>
<?php endif; ?>
<?= Html::activeHiddenInput($model, 'judiciary_actions_id', ['id' => 'jaf-action-id']) ?>
<?= Html::activeHiddenInput($model, 'parent_id', ['id' => 'jaf-parent-id']) ?>
<?= Html::activeHiddenInput($model, 'request_status', ['id' => 'jaf-request-status', 'value' => $isNew ? 'pending' : $model->request_status]) ?>
<?= Html::activeHiddenInput($model, 'is_current', ['id' => 'jaf-is-current', 'value' => $model->is_current ?: 1]) ?>
<?= Html::activeHiddenInput($model, 'amount', ['id' => 'jaf-amount']) ?>
<?= Html::activeHiddenInput($model, 'request_target', ['id' => 'jaf-request-target']) ?>

<!-- ═══ 1. Case Header ═══ -->
<div class="jaf-case">
    <div class="jaf-case-icon"><i class="fa fa-gavel" style="color:#2563EB;font-size:18px"></i></div>
    <div>
        <div class="jaf-case-number"><?= Html::encode($judiciaryLabel) ?></div>
        <div class="jaf-case-court"><?= $judiciary && $judiciary->court ? Html::encode($judiciary->court->name) : '' ?></div>
    </div>
</div>

<!-- ═══ 2. Party Selector ═══ -->
<div>
    <label class="jaf-label"><i class="fa fa-users"></i> اختر <?= $isNew ? 'الأطراف' : 'الطرف' ?> <span style="color:#DC2626">*</span></label>
    <?php if ($isNew && count($partyRows) > 1): ?>
    <div style="margin-bottom:6px">
        <label class="jaf-party jaf-select-all" style="padding:5px 12px;font-size:11px;border-color:#10B981;background:#F0FDF4" data-select-all="1">
            <i class="fa fa-check-double" style="color:#10B981"></i>
            <span style="font-weight:600;color:#10B981">تحديد الكل</span>
        </label>
    </div>
    <?php endif; ?>
    <div class="jaf-parties" id="jaf-parties-container">
        <?php foreach ($partyRows as $p):
            $isClient = $p['customer_type'] === 'client';
            $isSelected = ($existingCustomerId == $p['customer_id']);
        ?>
        <div class="jaf-party <?= $isSelected ? 'selected' : '' ?>" data-customer-id="<?= $p['customer_id'] ?>">
            <div class="jaf-party-check"><i class="fa fa-square-o"></i></div>
            <?php $photoUrl = $partyPhotos[$p['customer_id']] ?? null; ?>
            <div class="jaf-party-avatar" style="background:<?= $isClient ? '#DBEAFE' : '#FEF3C7' ?>;border:2px solid <?= $isClient ? '#93C5FD' : '#FCD34D' ?>">
                <?php if ($photoUrl): ?>
                <img src="<?= Html::encode($photoUrl) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%" onerror="this.style.display='none';this.nextElementSibling.style.display=''">
                <i class="fa <?= $isClient ? 'fa-user' : 'fa-user-o' ?>" style="color:<?= $isClient ? '#2563EB' : '#D97706' ?>;display:none"></i>
                <?php else: ?>
                <i class="fa <?= $isClient ? 'fa-user' : 'fa-user-o' ?>" style="color:<?= $isClient ? '#2563EB' : '#D97706' ?>"></i>
                <?php endif; ?>
            </div>
            <div>
                <div class="jaf-party-name"><?= Html::encode($p['name']) ?></div>
                <span class="jaf-party-type" style="background:<?= $isClient ? '#DBEAFE' : '#FEF3C7' ?>;color:<?= $isClient ? '#1D4ED8' : '#92400E' ?>"><?= $isClient ? 'مدين' : 'كفيل' ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div id="jaf-party-error" style="color:#DC2626;font-size:11px;margin-top:4px;font-weight:600"
         x-show="showPartyError" x-cloak
         x-transition.opacity.duration.200ms>
        <i class="fa fa-exclamation-triangle"></i> يجب اختيار طرف واحد على الأقل
    </div>
</div>

<!-- ═══ 3. Action Tree Selector ═══ -->
<div class="jaf-action-tree">
    <label class="jaf-label"><i class="fa fa-sitemap"></i> اختر الإجراء القضائي</label>
    <input type="text" class="jaf-input" id="jaf-action-search" placeholder="ابحث في الإجراءات..." style="margin-bottom:10px;background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2714%27 height=%2714%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%2394A3B8%27 stroke-width=%272%27%3E%3Ccircle cx=%2711%27 cy=%2711%27 r=%278%27/%3E%3Cline x1=%2721%27 y1=%2721%27 x2=%2716.65%27 y2=%2716.65%27/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:10px center;padding-right:32px">
    <?php
    // Group actions by nature
    $grouped = [];
    foreach ($allActions as $a) {
        $n = $a->action_nature ?: 'process';
        $grouped[$n][] = $a;
    }
    $natureOrder = ['request', 'document', 'doc_status', 'process'];
    foreach ($natureOrder as $nature):
        if (empty($grouped[$nature])) continue;
        $nColor = $natureColors[$nature];
        $nIcon = $natureIcons[$nature];
        $nLabel = $natureLabels[$nature];
    ?>
    <div class="jaf-nature-group">
        <div class="jaf-nature-header" data-nature="<?= $nature ?>" onclick="JAF.toggleNature(this)">
            <i class="fa <?= $nIcon ?>" style="color:<?= $nColor ?>"></i>
            <span style="color:<?= $nColor ?>"><?= $nLabel ?></span>
            <span style="font-weight:400;color:#94A3B8;font-size:11px">(<?= count($grouped[$nature]) ?>)</span>
            <i class="fa fa-chevron-down toggle" style="color:#94A3B8;font-size:10px"></i>
        </div>
        <div class="jaf-nature-list collapsed" id="nature-list-<?= $nature ?>">
            <?php foreach ($grouped[$nature] as $a): ?>
            <div class="jaf-action-item <?= $model->judiciary_actions_id == $a->id ? 'selected' : '' ?>"
                 data-action-id="<?= $a->id ?>" data-nature="<?= $nature ?>"
                 onclick="JAF.selectAction(this)">
                <span class="bullet" style="background:<?= $nColor ?>"></span>
                <?= Html::encode($a->name) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ═══ 4. Contextual: Request Status (for editing existing requests) ═══ -->
<div class="jaf-ctx" id="ctx-request"
     x-show="activeContext === 'request'" x-cloak
     x-transition.opacity.duration.200ms>
    <div class="jaf-ctx-title"><i class="fa fa-clock-o" style="color:#F59E0B"></i> حالة الطلب</div>
    <?php if ($isNew): ?>
        <div class="jaf-status-hint" style="background:#FFFBEB;color:#B45309">
            <i class="fa fa-clock-o"></i> سيُحفظ تلقائياً بحالة "معلق"
        </div>
    <?php else: ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
            <?php foreach (['pending' => ['معلق','#F59E0B','#FFFBEB'], 'approved' => ['موافقة','#10B981','#ECFDF5'], 'rejected' => ['مرفوض','#EF4444','#FEF2F2']] as $sk => $sv): ?>
            <label class="jaf-party" style="border-width:2px;padding:6px 12px;<?= $model->request_status === $sk ? 'border-color:'.$sv[1].';background:'.$sv[2] : '' ?>" data-status="<?= $sk ?>" onclick="JAF.setRequestStatus('<?= $sk ?>')">
                <i class="fa <?= $sk === 'pending' ? 'fa-clock-o' : ($sk === 'approved' ? 'fa-check-circle' : 'fa-times-circle') ?>" style="color:<?= $sv[1] ?>;font-size:14px"></i>
                <span style="font-weight:600;color:<?= $sv[1] ?>"><?= $sv[0] ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:8px">
            <div style="flex:1">
                <label class="jaf-label">نص القرار</label>
                <?= Html::activeTextarea($model, 'decision_text', ['class' => 'jaf-input', 'rows' => 2, 'placeholder' => 'نص قرار القاضي (اختياري)', 'style' => 'resize:vertical;min-height:50px']) ?>
            </div>
            <div style="flex:0 0 auto">
                <label class="jaf-label">مرفق القرار</label>
                <?= Html::activeFileInput($model, 'decision_file', ['accept' => 'image/*,.pdf', 'class' => 'jaf-input', 'style' => 'padding:4px 8px']) ?>
            </div>
        </div>
    <?php endif; ?>
    <!-- request_target section (for specific requests like refund) -->
    <div id="ctx-request-target" style="margin-top:8px"
         x-show="showRequestTarget" x-cloak
         x-transition.opacity.duration.200ms>
        <div style="display:flex;gap:8px">
            <div style="flex:1">
                <label class="jaf-label">جهة الطلب</label>
                <select class="jaf-select" id="jaf-req-target-select" onchange="$('#jaf-request-target').val(this.value)">
                    <option value="">— اختر —</option>
                    <option value="judge" <?= $model->request_target === 'judge' ? 'selected' : '' ?>>القاضي</option>
                    <option value="accounting" <?= $model->request_target === 'accounting' ? 'selected' : '' ?>>المحاسبة</option>
                    <option value="other" <?= $model->request_target === 'other' ? 'selected' : '' ?>>أخرى</option>
                </select>
            </div>
            <div style="flex:1">
                <label class="jaf-label">المبلغ</label>
                <input type="number" step="0.01" class="jaf-input" id="jaf-amount-input" value="<?= $model->amount ?>" placeholder="0.00" oninput="$('#jaf-amount').val(this.value)">
            </div>
        </div>
    </div>
</div>

<!-- ═══ 5. Contextual: Link to Parent (for documents) ═══ -->
<div class="jaf-ctx" id="ctx-document"
     x-show="activeContext === 'document'" x-cloak
     x-transition.opacity.duration.200ms>
    <div class="jaf-ctx-title"><i class="fa fa-link" style="color:#8B5CF6"></i> ربط بالطلب</div>
    <div id="jaf-approved-req-wrap">
    <?php if (!empty($approvedRequests)): ?>
        <select class="jaf-select" id="jaf-parent-req-select" onchange="$('#jaf-parent-id').val(this.value)">
            <option value="">— اختر الطلب —</option>
            <?php foreach ($approvedRequests as $rid => $rl): ?>
            <option value="<?= $rid ?>" <?= $model->parent_id == $rid ? 'selected' : '' ?>><?= Html::encode($rl) ?></option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <div id="jaf-no-requests-msg" style="font-size:12px;color:#9CA3AF;padding:6px 0">
            <i class="fa fa-info-circle"></i> لا توجد طلبات سابقة — أضف طلباً من الأسفل
        </div>
    <?php endif; ?>
    </div>

    <!-- Quick-add request inline -->
    <div id="jaf-quick-req" style="margin-top:8px">
        <button type="button" id="jaf-quick-req-toggle" class="btn btn-sm" style="background:#EFF6FF;color:#2563EB;border:1px dashed #93C5FD;border-radius:6px;font-size:12px;padding:4px 12px" onclick="$('#jaf-quick-req-form').slideToggle(200)">
            <i class="fa fa-plus-circle"></i> إضافة طلب سريع واعتماده
        </button>
        <div id="jaf-quick-req-form" style="display:none;margin-top:8px;padding:10px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px">
            <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
                <div style="flex:2;min-width:140px">
                    <label style="font-size:11px;font-weight:600;color:#64748B;display:block;margin-bottom:3px">نوع الطلب</label>
                    <select id="jaf-quick-req-action" class="jaf-select" style="font-size:12px;padding:6px 8px">
                        <?php foreach ($requestActions as $raId => $raName): ?>
                        <option value="<?= $raId ?>"><?= Html::encode($raName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex:1;min-width:120px">
                    <label style="font-size:11px;font-weight:600;color:#64748B;display:block;margin-bottom:3px">تاريخ الطلب</label>
                    <input type="date" id="jaf-quick-req-date" class="jaf-input" style="font-size:12px;padding:6px 8px" value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <button type="button" id="jaf-quick-req-save" class="btn btn-sm btn-primary" style="border-radius:6px;font-size:12px;padding:6px 14px" onclick="JAF.quickAddRequest()">
                        <i class="fa fa-check"></i> إضافة واعتماد
                    </button>
                </div>
            </div>
            <div id="jaf-quick-req-status" style="margin-top:6px;font-size:11px;display:none"></div>
        </div>
    </div>
</div>

<!-- ═══ 6. Contextual: Link to Document (for statuses) ═══ -->
<div class="jaf-ctx" id="ctx-doc-status"
     x-show="activeContext === 'doc_status'" x-cloak
     x-transition.opacity.duration.200ms>
    <div class="jaf-ctx-title"><i class="fa fa-exchange" style="color:#EA580C"></i> ربط بالكتاب</div>
    <?php if (empty($existingDocuments)): ?>
        <div style="font-size:12px;color:#DC2626;padding:6px 0">
            <i class="fa fa-exclamation-triangle"></i> لا توجد كتب مسجلة
        </div>
    <?php else: ?>
        <div style="display:flex;gap:8px">
            <div style="flex:2">
                <select class="jaf-select" id="jaf-parent-doc-select" onchange="$('#jaf-parent-id').val(this.value)">
                    <option value="">— اختر الكتاب —</option>
                    <?php foreach ($existingDocuments as $did => $dl): ?>
                    <option value="<?= $did ?>" <?= $model->parent_id == $did ? 'selected' : '' ?>><?= Html::encode($dl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1">
                <select class="jaf-select" id="jaf-current-select" onchange="$('#jaf-is-current').val(this.value)">
                    <option value="1" <?= $model->is_current ? 'selected' : '' ?>>حالة حالية</option>
                    <option value="0" <?= !$model->is_current ? 'selected' : '' ?>>حالة سابقة</option>
                </select>
            </div>
        </div>
        <div style="margin-top:8px">
            <label class="jaf-label">المبلغ (إن وجد)</label>
            <input type="number" step="0.01" class="jaf-input" style="max-width:200px" value="<?= $model->amount ?>" placeholder="0.00" oninput="$('#jaf-amount').val(this.value)">
        </div>
    <?php endif; ?>
</div>

<!-- ═══ 7. Date ═══ -->
<div class="jaf-date-wrap">
    <label class="jaf-label"><i class="fa fa-calendar"></i> تاريخ الإجراء <span style="color:#DC2626">*</span></label>
    <?= Html::activeInput('date', $model, 'action_date', [
        'class' => 'jaf-input',
        'id' => 'jaf-action-date',
        'style' => 'max-width:200px',
        'required' => true,
        'value' => $model->action_date ? substr($model->action_date, 0, 10) : '',
    ]) ?>
    <div id="jaf-date-error" style="color:#DC2626;font-size:11px;margin-top:4px;font-weight:600;display:none">
        <i class="fa fa-exclamation-triangle"></i> يجب تحديد تاريخ الإجراء
    </div>
</div>

<!-- ═══ 8. Note ═══ -->
<div class="jaf-note" style="margin-bottom:14px">
    <label class="jaf-label"><i class="fa fa-sticky-note-o"></i> ملاحظات</label>
    <?= Html::activeTextarea($model, 'note', ['rows' => 2, 'placeholder' => 'ملاحظات اختيارية...', 'style' => 'width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:13px;resize:vertical;min-height:50px;outline:none;font-family:inherit']) ?>
</div>

<!-- ═══ 9. Smart Upload Zone ═══ -->
<div style="margin-bottom:14px">
    <label class="jaf-label"><i class="fa fa-paperclip"></i> مرفقات</label>
    <div class="jaf-upload-zone" id="jaf-drop-zone">
        <input type="file" name="JudiciaryCustomersActions[image]" accept="image/*,.pdf" id="jaf-file-input">
        <div class="jaf-upload-icon"><i class="fa fa-cloud-upload"></i></div>
        <div class="jaf-upload-text">اسحب الملف هنا أو اضغط للاختيار</div>
        <div class="jaf-upload-hint">JPG, PNG, PDF — بحد أقصى 10MB</div>
    </div>
    <div class="jaf-preview-list" id="jaf-previews">
        <?php if ($model->image): ?>
        <div class="jaf-preview-item" id="jaf-existing-preview">
            <?php
            $ext = strtolower(pathinfo($model->image, PATHINFO_EXTENSION));
            $isPdf = ($ext === 'pdf');
            $imgUrl = \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::resolveImageUrl($model->image);
            ?>
            <?php if ($isPdf): ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#FEF2F2"><i class="fa fa-file-pdf-o" style="font-size:28px;color:#DC2626"></i></div>
            <?php else: ?>
                <img src="<?= $imgUrl ?>" alt="">
            <?php endif; ?>
            <button type="button" class="remove" onclick="JAF.removeExisting()">&times;</button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!Yii::$app->request->isAjax): ?>
<div style="padding-top:8px">
    <?= Html::submitButton(
        $isNew ? '<i class="fa fa-plus"></i> إضافة' : '<i class="fa fa-save"></i> حفظ',
        ['class' => 'btn btn-primary btn-lg', 'style' => 'border-radius:10px;font-size:14px;padding:10px 30px']
    ) ?>
</div>
<?php endif; ?>

<?php ActiveForm::end(); ?>
</div>

<script>
window.JAF = (function() {
    var natureMap = <?= Json::encode($actionNatureMap) ?>;
    var REFUND_ID = 55;
    var isNewRecord = <?= $isNew ? 'true' : 'false' ?>;

    function hidePartyError() {
        try {
            var el = document.getElementById('jaf-alpine-root');
            if (el && typeof Alpine !== 'undefined' && Alpine.$data) {
                Alpine.$data(el).showPartyError = false;
            }
        } catch (e) {
            var errEl = document.getElementById('jaf-party-error');
            if (errEl) errEl.style.display = 'none';
        }
    }

    function init() {
        var $container = $('#jaf-parties-container');
        if (isNewRecord) {
            $container.off('click.jaf').on('click.jaf', '.jaf-party', function(e) {
                e.stopPropagation();
                $(this).toggleClass('selected');
                syncMultiCustomers();
                hidePartyError();
            });

            $(document).off('click.jaf-select-all').on('click.jaf-select-all', '.jaf-select-all', function() {
                var $parties = $container.find('.jaf-party');
                var allSelected = $parties.filter('.selected').length === $parties.length;
                if (allSelected) {
                    $parties.removeClass('selected');
                    $(this).removeClass('all-selected');
                    $(this).find('span').text('تحديد الكل');
                } else {
                    $parties.addClass('selected');
                    $(this).addClass('all-selected');
                    $(this).find('span').text('إلغاء تحديد الكل');
                }
                syncMultiCustomers();
                hidePartyError();
            });
        } else {
            $container.off('click.jaf').on('click.jaf', '.jaf-party', function(e) {
                e.stopPropagation();
                $container.find('.jaf-party').removeClass('selected');
                $(this).addClass('selected');
                $('#jaf-customer-id').val($(this).data('customer-id'));
                hidePartyError();
            });
        }

        $('#jaf-form').on('beforeSubmit', function() {
            var valid = true;
            if (!$('#jaf-action-date').val()) {
                $('#jaf-date-error').show();
                valid = false;
            } else {
                $('#jaf-date-error').hide();
            }
            if (isNewRecord && $('#jaf-parties-container .jaf-party.selected').length === 0) {
                try {
                    var el = document.getElementById('jaf-alpine-root');
                    if (el && typeof Alpine !== 'undefined' && Alpine.$data) {
                        Alpine.$data(el).showPartyError = true;
                    }
                } catch (e) {
                    var errEl = document.getElementById('jaf-party-error');
                    if (errEl) errEl.style.display = '';
                }
                valid = false;
            }
            return valid;
        });
        $('#jaf-action-date').on('change', function() { if (this.value) $('#jaf-date-error').hide(); });

        // Drop zone
        var $zone = $('#jaf-drop-zone');
        $zone.on('dragover dragenter', function(e) { e.preventDefault(); $(this).addClass('dragover'); });
        $zone.on('dragleave drop', function(e) { e.preventDefault(); $(this).removeClass('dragover'); });
        $zone.on('drop', function(e) {
            var dt = e.originalEvent.dataTransfer;
            if (dt && dt.files.length) {
                $('#jaf-file-input')[0].files = dt.files;
                showPreview(dt.files[0]);
            }
        });
        $('#jaf-file-input').on('change', function() {
            if (this.files.length) showPreview(this.files[0]);
        });

        // Pre-expand the nature group of existing selection
        var existingAction = $('#jaf-action-id').val();
        if (existingAction && natureMap[existingAction]) {
            var n = natureMap[existingAction];
            var $header = $('.jaf-nature-header[data-nature="' + n + '"]');
            $header.removeClass('collapsed');
            $('#nature-list-' + n).removeClass('collapsed');
            setTimeout(function() { showContext(n); }, 50);
        }

        // Action search filter
        var searchTimer;
        var searchEl = document.getElementById('jaf-action-search');
        if (!searchEl) return;
        searchEl.addEventListener('input', function() {
            clearTimeout(searchTimer);
            var q = this.value.trim().toLowerCase();
            searchTimer = setTimeout(function() {
                // First expand all groups so items become visible for filtering
                document.querySelectorAll('.jaf-nature-list').forEach(function(list) {
                    list.classList.remove('collapsed');
                });
                document.querySelectorAll('.jaf-nature-header').forEach(function(h) {
                    h.classList.remove('collapsed');
                });

                if (!q) {
                    // Show all items, re-collapse groups, restore counts
                    document.querySelectorAll('.jaf-action-item').forEach(function(el) { el.style.display = ''; });
                    document.querySelectorAll('.jaf-nature-list').forEach(function(list) { list.classList.add('collapsed'); });
                    document.querySelectorAll('.jaf-nature-group').forEach(function(g) {
                        g.style.display = '';
                        var total = g.querySelectorAll('.jaf-action-item').length;
                        var countSpan = g.querySelector('.jaf-nature-header span[style*="94A3B8"]');
                        if (countSpan) countSpan.textContent = '(' + total + ')';
                    });
                    return;
                }

                // Filter items by name
                document.querySelectorAll('.jaf-action-item').forEach(function(el) {
                    var name = el.textContent.trim().toLowerCase();
                    el.style.display = name.indexOf(q) !== -1 ? '' : 'none';
                });

                // Hide empty groups, update counts
                document.querySelectorAll('.jaf-nature-group').forEach(function(g) {
                    var items = g.querySelectorAll('.jaf-action-item');
                    var visible = 0;
                    items.forEach(function(it) { if (it.style.display !== 'none') visible++; });
                    g.style.display = visible > 0 ? '' : 'none';
                    var countSpan = g.querySelector('.jaf-nature-header span[style*="94A3B8"]');
                    if (countSpan) countSpan.textContent = '(' + visible + ')';
                });
            }, 150);
        });
    }

    function getSelectedCustomerIds() {
        var ids = [];
        $('#jaf-parties-container .jaf-party.selected').each(function() {
            ids.push($(this).data('customer-id'));
        });
        return ids;
    }

    function syncMultiCustomers() {
        var ids = getSelectedCustomerIds();
        $('#jaf-multi-customers').val(ids.join(','));
    }

    function showPreview(file) {
        var $list = $('#jaf-previews');
        $list.find('.jaf-new-preview').remove();

        var html;
        if (file.type.indexOf('image/') === 0) {
            var url = URL.createObjectURL(file);
            html = '<div class="jaf-preview-item jaf-new-preview"><img src="' + url + '"><button type="button" class="remove" onclick="JAF.clearFile()">&times;</button></div>';
        } else {
            html = '<div class="jaf-preview-item jaf-new-preview"><div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#FEF2F2"><i class="fa fa-file-pdf-o" style="font-size:28px;color:#DC2626"></i></div><button type="button" class="remove" onclick="JAF.clearFile()">&times;</button></div>';
        }
        $list.append(html);
    }

    function toggleNature(el) {
        var $h = $(el);
        var $list = $h.next('.jaf-nature-list');
        $h.toggleClass('collapsed');
        $list.toggleClass('collapsed');
    }

    function selectAction(el) {
        var $el = $(el);
        $('.jaf-action-item').removeClass('selected');
        $el.addClass('selected');

        var actionId = $el.data('action-id');
        var nature = $el.data('nature');
        $('#jaf-action-id').val(actionId);

        $('#jaf-parent-id').val('');

        try {
            var rootEl = document.getElementById('jaf-alpine-root');
            if (rootEl && typeof Alpine !== 'undefined' && Alpine.$data) {
                var _jafData = Alpine.$data(rootEl);
                _jafData.activeContext = nature;
                _jafData.showRequestTarget = (parseInt(actionId) === REFUND_ID);
            } else {
                showContext(nature);
            }
        } catch (e) {
            showContext(nature);
        }
    }

    function showContext(nature) {
        var el = document.getElementById('jaf-alpine-root');
        if (el && typeof Alpine !== 'undefined' && Alpine.$data) {
            try {
                Alpine.$data(el).activeContext = nature;
            } catch(e) {
                el.setAttribute('x-data', el.getAttribute('x-data').replace(/activeContext:\s*'[^']*'/, "activeContext: '" + nature + "'"));
            }
        }
    }

    function setRequestStatus(status) {
        $('#jaf-request-status').val(status);
        var colors = { pending: ['#F59E0B','#FFFBEB'], approved: ['#10B981','#ECFDF5'], rejected: ['#EF4444','#FEF2F2'] };
        $('[data-status]').css({ borderColor: '#E2E8F0', background: '#fff' });
        $('[data-status="' + status + '"]').css({ borderColor: colors[status][0], background: colors[status][1] });
    }

    function clearFile() {
        $('#jaf-file-input').val('');
        $('#jaf-previews .jaf-new-preview').remove();
    }

    function removeExisting() {
        $('#jaf-existing-preview').remove();
        $('<input type="hidden" name="remove_image" value="1">').insertAfter('#jaf-file-input');
    }

    function quickAddRequest() {
        var actionId = $('#jaf-quick-req-action').val();
        var actionDate = $('#jaf-quick-req-date').val();
        var $status = $('#jaf-quick-req-status');
        var $btn = $('#jaf-quick-req-save');

        if (!actionId || !actionDate) {
            $status.show().html('<span style="color:#DC2626"><i class="fa fa-exclamation-triangle"></i> يرجى تعبئة جميع الحقول</span>');
            return;
        }

        var selectedParties = [];
        $('#jaf-parties-container .jaf-party.selected').each(function() {
            selectedParties.push($(this).data('customer-id'));
        });
        if (selectedParties.length === 0) {
            $status.show().html('<span style="color:#DC2626"><i class="fa fa-exclamation-triangle"></i> يجب اختيار طرف واحد على الأقل</span>');
            return;
        }

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الإضافة...');
        $status.show().html('<span style="color:#6B7280"><i class="fa fa-spinner fa-spin"></i> جاري إنشاء واعتماد الطلب...</span>');

        $.ajax({
            url: '<?= Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/quick-add-request']) ?>',
            type: 'POST',
            data: {
                judiciary_id: <?= $judiciary ? $judiciary->id : 0 ?>,
                contract_id: <?= (int)$contractID ?>,
                action_id: actionId,
                action_date: actionDate,
                customer_ids: selectedParties,
                _csrf: $('meta[name="csrf-token"]').attr('content') || $('input[name="_csrf"]').val()
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $status.html('<span style="color:#059669"><i class="fa fa-check-circle"></i> ' + res.message + '</span>');
                    var $wrap = $('#jaf-approved-req-wrap');
                    var $sel = $wrap.find('#jaf-parent-req-select');
                    if ($sel.length === 0) {
                        $wrap.find('#jaf-no-requests-msg').remove();
                        $wrap.html('<select class="jaf-select" id="jaf-parent-req-select" onchange="$(\'#jaf-parent-id\').val(this.value)"><option value="">— اختر الطلب الأصلي —</option></select>');
                        $sel = $wrap.find('#jaf-parent-req-select');
                    }
                    $.each(res.created, function(i, item) {
                        $sel.append('<option value="' + item.id + '">' + item.label + '</option>');
                    });
                    if (res.created.length === 1) {
                        $sel.val(res.created[0].id).trigger('change');
                    }
                    $('#jaf-quick-req-form').slideUp(200);
                    $btn.prop('disabled', false).html('<i class="fa fa-check"></i> إضافة واعتماد');
                } else {
                    $status.html('<span style="color:#DC2626"><i class="fa fa-exclamation-triangle"></i> ' + (res.message || 'حدث خطأ') + '</span>');
                    $btn.prop('disabled', false).html('<i class="fa fa-check"></i> إضافة واعتماد');
                }
            },
            error: function() {
                $status.html('<span style="color:#DC2626"><i class="fa fa-exclamation-triangle"></i> حدث خطأ في الاتصال</span>');
                $btn.prop('disabled', false).html('<i class="fa fa-check"></i> إضافة واعتماد');
            }
        });
    }

    // Call init immediately + delayed fallback for AJAX modal contexts
    try { init(); } catch (e) {}
    setTimeout(function() {
        if (!$('#jaf-parties-container').data('jaf-init')) {
            try { init(); } catch (e) {}
        }
        $('#jaf-parties-container').data('jaf-init', 1);
    }, 100);

    return {
        toggleNature: toggleNature,
        selectAction: selectAction,
        setRequestStatus: setRequestStatus,
        clearFile: clearFile,
        removeExisting: removeExisting,
        quickAddRequest: quickAddRequest
    };
})();
</script>
