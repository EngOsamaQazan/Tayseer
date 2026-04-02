<?php
use backend\modules\contracts\models\Contracts;
use backend\modules\customers\models\ContractsCustomers;
use backend\helpers\PhoneHelper;
use yii\helpers\Url;
use yii\helpers\Html;

$contractModel = Contracts::findOne($contract_id);
$allParties = $contractModel ? $contractModel->contractsCustomers : [];
?>

<style>
.pn-page{font-family:inherit}
.pn-section{margin-bottom:24px}
.pn-section-title{font-size:14px;font-weight:700;color:#1E293B;margin-bottom:12px;display:flex;align-items:center;gap:8px;padding-bottom:8px;border-bottom:2px solid #E2E8F0}
.pn-section-title i{color:var(--ocp-primary,#6B1D3D);font-size:16px}
.pn-section-title .pn-count{font-size:11px;font-weight:600;background:#F1F5F9;color:#64748B;padding:2px 8px;border-radius:10px}
.pn-party{background:#fff;border:1px solid #E2E8F0;border-radius:10px;margin-bottom:14px;overflow:hidden;transition:border-color .2s}
.pn-party:hover{border-color:#CBD5E1}
.pn-party-header{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#FAFBFC;border-bottom:1px solid #E2E8F0;flex-wrap:wrap}
.pn-party-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.pn-party-icon.client{background:#FDF2F8;color:#BE185D}
.pn-party-icon.guarantor{background:#EFF6FF;color:#2563EB}
.pn-party-name{font-weight:700;font-size:13px;color:#1E293B;cursor:pointer;text-decoration:none;border-bottom:1px dashed var(--ocp-primary,#6B1D3D)}
.pn-party-name:hover{color:var(--ocp-primary,#6B1D3D)}
.pn-party-type{font-size:10px;padding:2px 8px;border-radius:6px;font-weight:600}
.pn-party-type.client{background:#FDF2F8;color:#BE185D}
.pn-party-type.guarantor{background:#EFF6FF;color:#2563EB}
.pn-party-contracts{font-size:11px;color:#94A3B8;margin-right:auto}
.pn-party-actions{display:flex;gap:4px;margin-right:auto}
.pn-party-body{padding:12px 16px}
.pn-primary{display:flex;align-items:center;gap:10px;padding:10px 14px;background:#F8FAFC;border-radius:8px;margin-bottom:10px;flex-wrap:wrap}
.pn-primary-label{font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.3px}
.pn-primary-number{font-size:15px;font-weight:700;color:#1E293B;direction:ltr;font-family:'Courier New',monospace}
.pn-primary-social{display:flex;gap:4px;margin-right:auto}
.pn-contact-btn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;text-decoration:none;transition:all .15s;border:none;cursor:pointer;font-size:14px}
.pn-contact-btn.call{background:#EFF6FF;color:#2563EB;position:relative}
.pn-contact-btn.call:hover{background:#DBEAFE}
@keyframes pn-ring{0%,100%{transform:rotate(0)}10%{transform:rotate(14deg)}20%{transform:rotate(-14deg)}30%{transform:rotate(10deg)}40%{transform:rotate(-10deg)}50%{transform:rotate(6deg)}60%{transform:rotate(-6deg)}70%{transform:rotate(2deg)}80%{transform:rotate(0)}}
@keyframes pn-pulse{0%{box-shadow:0 0 0 0 rgba(37,99,235,.5)}70%{box-shadow:0 0 0 10px rgba(37,99,235,0)}100%{box-shadow:0 0 0 0 rgba(37,99,235,0)}}
.pn-contact-btn.call.pn-calling{background:#2563EB;color:#fff;animation:pn-ring .8s ease-in-out infinite,pn-pulse 1.2s ease-out infinite;pointer-events:none}
.pn-call-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);background:#1E293B;color:#fff;padding:10px 22px;border-radius:10px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;z-index:9999;opacity:0;transition:transform .35s cubic-bezier(.4,0,.2,1),opacity .35s;box-shadow:0 8px 24px rgba(0,0,0,.18);direction:rtl;pointer-events:none}
.pn-call-toast.pn-show{transform:translateX(-50%) translateY(0);opacity:1}
.pn-call-toast.pn-hide{transform:translateX(-50%) translateY(80px);opacity:0}
.pn-call-toast i{font-size:16px;color:#60A5FA;animation:pn-ring .8s ease-in-out infinite}
.pn-call-toast .pn-toast-num{direction:ltr;font-family:'Courier New',monospace;color:#93C5FD;font-weight:700}
.pn-call-toast.pn-success{background:#065F46}
.pn-call-toast.pn-success i{color:#34D399;animation:none}
.pn-call-toast.pn-error{background:#991B1B}
.pn-call-toast.pn-error i{color:#FCA5A5;animation:none}
.pn-contact-btn.whatsapp{background:#F0FDF4;color:#16A34A}
.pn-contact-btn.whatsapp:hover{background:#DCFCE7}
.pn-contact-btn.facebook{background:#EFF6FF;color:#1D4ED8}
.pn-contact-btn.facebook:hover{background:#DBEAFE}
.pn-contact-btn.facebook.empty{background:#F1F5F9;color:#CBD5E1;cursor:default}
.pn-contact-btn.sms{background:#FDF2F8;color:#BE185D}
.pn-contact-btn.sms:hover{background:#FCE7F3}
.pn-contact-btn.edit{background:#F1F5F9;color:#64748B}
.pn-contact-btn.edit:hover{background:#E2E8F0;color:#1E293B}
.pn-extra-phones{margin-top:8px}
.pn-extra-title{font-size:11px;font-weight:600;color:#94A3B8;margin-bottom:6px;display:flex;align-items:center;gap:4px}
.pn-extra-row{display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid #F1F5F9;border-radius:8px;margin-bottom:4px;transition:background .15s;flex-wrap:wrap}
.pn-extra-row:hover{background:#FAFBFC}
.pn-extra-number{font-size:13px;font-weight:600;color:#334155;direction:ltr;font-family:'Courier New',monospace;min-width:120px}
.pn-extra-owner{font-size:12px;color:#64748B}
.pn-extra-relation{font-size:10px;padding:1px 6px;border-radius:4px;background:#F5F3FF;color:#7C3AED;font-weight:500}
.pn-extra-actions{display:flex;gap:3px;margin-right:auto}
.pn-extra-actions .pn-contact-btn{width:28px;height:28px;font-size:12px}
.pn-empty{text-align:center;padding:20px;color:#94A3B8;font-size:13px}
.pn-add-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;background:var(--ocp-primary,#6B1D3D);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all .15s;border:none;cursor:pointer}
.pn-add-btn:hover{filter:brightness(.9);color:#fff;text-decoration:none}
</style>

<?php
$allPhonesList = [];
foreach ($allParties as $_cc) {
    $_cust = $_cc->customer;
    if (!$_cust) continue;
    $_partyType = $_cc->customer_type === 'client' ? 'مشتري' : 'كفيل';
    if ($_cust->primary_phone_number) {
        $allPhonesList[] = [
            'number' => PhoneHelper::toWhatsApp($_cust->primary_phone_number),
            'local'  => PhoneHelper::toLocal($_cust->primary_phone_number),
            'name'   => $_cust->name,
            'label'  => $_partyType . ' — الرقم الرئيسي',
            'primary' => true,
        ];
    }
    foreach ($_cust->phoneNumbers ?? [] as $_pn) {
        $_rel = \backend\modules\cousins\models\Cousins::findOne(['id' => $_pn->phone_number_owner]);
        $allPhonesList[] = [
            'number' => PhoneHelper::toWhatsApp($_pn->phone_number),
            'local'  => PhoneHelper::toLocal($_pn->phone_number),
            'name'   => $_pn->owner_name ?: $_cust->name,
            'label'  => $_rel ? $_rel->name : ($_partyType),
            'primary' => false,
        ];
    }
}
?>

<div class="pn-page">
    <div class="pn-section">
        <div class="pn-section-title">
            <i class="fa fa-users"></i> أطراف العقد وأرقام الهواتف
            <span class="pn-count"><?= count($allParties) ?> طرف</span>
            <?php if (count($allPhonesList) > 0): ?>
            <button type="button" class="pn-add-btn" style="margin-right:auto;background:#BE185D" onclick="BulkSms.open()" title="إرسال رسالة جماعية لجميع الأرقام">
                <i class="fa fa-paper-plane"></i> رسالة SMS للكل (<?= count($allPhonesList) ?>)
            </button>
            <?php endif; ?>
        </div>

        <?php if (empty($allParties)): ?>
            <div class="pn-empty"><i class="fa fa-info-circle"></i> لا يوجد أطراف مسجلة لهذا العقد</div>
        <?php endif; ?>

        <?php foreach ($allParties as $cc):
            $cust = $cc->customer;
            if (!$cust) continue;
            $type = $cc->customer_type === 'client' ? 'client' : 'guarantor';
            $typeLabel = $type === 'client' ? 'مشتري' : 'كفيل';
            $phones = $cust->phoneNumbers ?? [];

            $activeContracts = 0;
            $custContracts = ContractsCustomers::find()->where(['customer_id' => $cust->id])->all();
            foreach ($custContracts as $ctc) {
                $ct = Contracts::findOne($ctc->contract_id);
                if ($ct && !in_array($ct->status, ['finished', 'canceled'])) $activeContracts++;
            }
        ?>
        <div class="pn-party">
            <div class="pn-party-header">
                <div class="pn-party-icon <?= $type ?>">
                    <i class="fa <?= $type === 'client' ? 'fa-user' : 'fa-shield' ?>"></i>
                </div>
                <div>
                    <a href="javascript:void(0)" class="pn-party-name custmer-popup" data-bs-target="#customerInfoModal" data-bs-toggle="modal" customer-id="<?= $cust->id ?>">
                        <?= Html::encode($cust->name) ?>
                    </a>
                    <div style="margin-top:2px">
                        <span class="pn-party-type <?= $type ?>"><?= $typeLabel ?></span>
                        <?php if ($activeContracts > 0): ?>
                        <span style="font-size:10px;color:#94A3B8;margin-right:6px"><i class="fa fa-file-text-o"></i> <?= $activeContracts ?> عقد نشط</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="pn-party-actions">
                    <?= Html::a('<i class="fa fa-plus"></i> إضافة رقم', ['/phoneNumbers/phone-numbers/create', 'contract_id' => $cust->name, 'customers_id' => $cust->id], ['role' => 'modal-remote', 'class' => 'pn-add-btn', 'title' => 'إضافة رقم جديد']) ?>
                </div>
            </div>
            <div class="pn-party-body">
                <?php if ($cust->primary_phone_number): ?>
                <?php $ppLocal = PhoneHelper::toLocal($cust->primary_phone_number); $ppWa = PhoneHelper::toWhatsApp($cust->primary_phone_number); $ppTel = PhoneHelper::toTel($cust->primary_phone_number); ?>
                <div class="pn-primary">
                    <div>
                        <div class="pn-primary-label"><i class="fa fa-phone"></i> الرقم الرئيسي</div>
                        <div class="pn-primary-number"><?= Html::encode($ppLocal) ?></div>
                    </div>
                    <div class="pn-primary-social">
                        <a class="pn-contact-btn call" href="javascript:void(0)" onclick="makeCall('<?= Html::encode($ppTel) ?>', this)" title="اتصال"><i class="fa fa-phone"></i></a>
                        <a class="pn-contact-btn whatsapp pn-wa-btn" href="javascript:void(0)" data-wa-phone="<?= Html::encode($ppWa) ?>"><i class="fa fa-whatsapp"></i></a>
                        <?php if (!empty($cust->facebook_account)): ?>
                        <a class="pn-contact-btn facebook" href="https://m.me/<?= Html::encode($cust->facebook_account) ?>" target="_blank" title="فيسبوك"><i class="fa fa-facebook"></i></a>
                        <?php else: ?>
                        <span class="pn-contact-btn facebook empty" title="لا يوجد حساب فيسبوك"><i class="fa fa-facebook"></i></span>
                        <?php endif; ?>
                        <button type="button" class="pn-contact-btn sms" onclick="setPhoneNumebr('<?= Html::encode($ppWa) ?>')" data-bs-toggle="modal" data-bs-target="#smsModal" title="إرسال رسالة"><i class="fa fa-comment"></i></button>
                        <?= Html::a('<i class="fa fa-pencil"></i>', ['/customers/update-contact', 'id' => $cust->id], ['role' => 'modal-remote', 'class' => 'pn-contact-btn edit', 'title' => 'تعديل بيانات الاتصال']) ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($phones)): ?>
                <div class="pn-extra-phones">
                    <div class="pn-extra-title"><i class="fa fa-phone-square"></i> أرقام إضافية (<?= count($phones) ?>)</div>
                    <?php foreach ($phones as $pn):
                        $relation = \backend\modules\cousins\models\Cousins::findOne(['id' => $pn->phone_number_owner]);
                        $pnLocal = PhoneHelper::toLocal($pn->phone_number);
                        $pnWa = PhoneHelper::toWhatsApp($pn->phone_number);
                        $pnTel = PhoneHelper::toTel($pn->phone_number);
                    ?>
                    <div class="pn-extra-row">
                        <span class="pn-extra-number"><?= Html::encode($pnLocal) ?></span>
                        <?php if ($pn->owner_name): ?>
                        <span class="pn-extra-owner"><?= Html::encode($pn->owner_name) ?></span>
                        <?php endif; ?>
                        <?php if ($relation): ?>
                        <span class="pn-extra-relation"><?= Html::encode($relation->name) ?></span>
                        <?php endif; ?>
                        <div class="pn-extra-actions">
                            <a class="pn-contact-btn call" href="javascript:void(0)" onclick="makeCall('<?= Html::encode($pnTel) ?>', this)" title="اتصال"><i class="fa fa-phone"></i></a>
                            <a class="pn-contact-btn whatsapp pn-wa-btn" href="javascript:void(0)" data-wa-phone="<?= Html::encode($pnWa) ?>"><i class="fa fa-whatsapp"></i></a>
                            <?php if (!empty($pn->fb_account)): ?>
                            <a class="pn-contact-btn facebook" href="https://m.me/<?= Html::encode($pn->fb_account) ?>" target="_blank" title="فيسبوك"><i class="fa fa-facebook"></i></a>
                            <?php else: ?>
                            <span class="pn-contact-btn facebook empty"><i class="fa fa-facebook"></i></span>
                            <?php endif; ?>
                            <button type="button" class="pn-contact-btn sms" onclick="setPhoneNumebr('<?= Html::encode($pnWa) ?>')" data-bs-toggle="modal" data-bs-target="#smsModal" title="رسالة"><i class="fa fa-comment"></i></button>
                            <?= Html::a('<i class="fa fa-pencil"></i>', ['/phoneNumbers/phone-numbers/update', 'id' => $pn->id], ['role' => 'modal-remote', 'class' => 'pn-contact-btn edit', 'title' => 'تعديل']) ?>
                            <?= Html::a('<i class="fa fa-trash-o"></i>', ['/phoneNumbers/phone-numbers/delete', 'id' => $pn->id], ['class' => 'pn-contact-btn edit', 'style' => 'color:#EF4444', 'title' => 'حذف', 'data-request-method' => 'post', 'data-confirm-message' => 'هل أنت متأكد من حذف هذا الرقم؟']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php elseif (!$cust->primary_phone_number): ?>
                <div class="pn-empty"><i class="fa fa-phone-square"></i> لا توجد أرقام هواتف مسجلة</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="pn-call-toast" id="pnCallToast">
    <i class="fa fa-phone" id="pnCallToastIcon"></i>
    <span id="pnCallToastMsg"></span>
</div>

<style>
.wdp-popover{width:280px;direction:rtl;font-family:inherit}
.wdp-hdr{background:linear-gradient(135deg,#128C7E,#25D366);padding:10px 14px;border-radius:10px 10px 0 0;display:flex;align-items:center;gap:8px}
.wdp-hdr-icon{width:30px;height:30px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;color:#fff;flex-shrink:0}
.wdp-hdr-text{flex:1;min-width:0}
.wdp-hdr-title{font-size:12px;font-weight:700;color:#fff}
.wdp-hdr-phone{font-size:10px;color:rgba(255,255,255,.75);direction:ltr;font-family:'Courier New',monospace}
.wdp-body{padding:6px}
.wdp-direct{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:8px;border:none;background:#F0FDF4;cursor:pointer;width:100%;text-align:right;transition:all .12s;font-family:inherit;margin-bottom:4px}
.wdp-direct:hover{background:#DCFCE7;transform:translateX(-2px)}
.wdp-direct i{color:#16A34A;font-size:14px;flex-shrink:0}
.wdp-direct span{font-size:12px;font-weight:600;color:#166534;flex:1}
.wdp-direct .wdp-arrow{color:#86EFAC;font-size:11px;flex-shrink:0}
.wdp-divider{font-size:9px;font-weight:700;color:#94A3B8;padding:6px 10px 4px;display:flex;align-items:center;gap:6px;letter-spacing:.3px}
.wdp-divider::after{content:'';flex:1;height:1px;background:#E2E8F0}
.wdp-drafts{max-height:200px;overflow-y:auto;padding:0 2px}
.wdp-draft{display:flex;align-items:flex-start;gap:8px;padding:8px 10px;border-radius:8px;border:1px solid transparent;background:#fff;cursor:pointer;width:100%;text-align:right;transition:all .12s;font-family:inherit;margin-bottom:2px}
.wdp-draft:hover{border-color:#25D366;background:#F0FDF4;transform:translateX(-2px)}
.wdp-draft-icon{width:26px;height:26px;background:#F0FDF4;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;color:#128C7E;flex-shrink:0;margin-top:1px}
.wdp-draft-info{flex:1;min-width:0}
.wdp-draft-name{font-size:11px;font-weight:700;color:#1E293B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wdp-draft-preview{font-size:9px;color:#94A3B8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px;direction:rtl}
.wdp-empty{text-align:center;padding:16px 10px;color:#94A3B8;font-size:11px;font-weight:600}
.wdp-empty i{font-size:18px;display:block;margin-bottom:4px;color:#CBD5E1}
.wdp-loading{text-align:center;padding:20px;color:#64748B;font-size:12px}
.wdp-loading i{animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.tippy-box[data-theme~='wdp']{background:#fff;color:#1E293B;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.15);padding:0;border:1px solid #E2E8F0;overflow:hidden}
.tippy-box[data-theme~='wdp'] .tippy-content{padding:0}
.tippy-box[data-theme~='wdp'] .tippy-arrow{color:#fff}
</style>

<script>
window._bulkSmsPhones = <?= json_encode($allPhonesList, JSON_UNESCAPED_UNICODE) ?>;

var WaDraftPicker = (function() {
    var _cache = null;

    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function _resolvePreview(text) {
        if (typeof SmsDrafts !== 'undefined' && SmsDrafts.resolveVars) {
            return SmsDrafts.resolveVars(text);
        }
        return text;
    }

    function _openWa(phone, text) {
        var url = 'whatsapp://send?phone=' + encodeURIComponent(phone);
        if (text) url += '&text=' + encodeURIComponent(text);
        window.location.href = url;
    }

    function _buildContent(phone, drafts) {
        var html = '<div class="wdp-popover">';
        html += '<div class="wdp-hdr"><div class="wdp-hdr-icon"><i class="fa fa-whatsapp"></i></div>';
        html += '<div class="wdp-hdr-text"><div class="wdp-hdr-title">\u0648\u0627\u062A\u0633\u0627\u0628 \u2014 \u0627\u062E\u062A\u0631 \u0631\u0633\u0627\u0644\u0629</div>';
        html += '<div class="wdp-hdr-phone">' + _esc(phone) + '</div></div></div>';
        html += '<div class="wdp-body">';

        html += '<button type="button" class="wdp-direct" data-wdp-action="direct" data-wdp-phone="' + _esc(phone) + '">';
        html += '<i class="fa fa-paper-plane"></i><span>\u0641\u062A\u062D \u0628\u062F\u0648\u0646 \u0631\u0633\u0627\u0644\u0629</span><span class="wdp-arrow"><i class="fa fa-arrow-left"></i></span></button>';

        if (!drafts || !drafts.length) {
            html += '<div class="wdp-divider">\u0627\u0644\u0645\u0633\u0648\u062F\u0627\u062A</div>';
            html += '<div class="wdp-empty"><i class="fa fa-inbox"></i>\u0644\u0627 \u062A\u0648\u062C\u062F \u0645\u0633\u0648\u062F\u0627\u062A<br>\u0623\u0646\u0634\u0626 \u0645\u0633\u0648\u062F\u0629 \u0645\u0646 \u0646\u0627\u0641\u0630\u0629 SMS</div>';
        } else {
            html += '<div class="wdp-divider">\u0627\u0644\u0645\u0633\u0648\u062F\u0627\u062A (' + drafts.length + ')</div>';
            html += '<div class="wdp-drafts">';
            for (var i = 0; i < drafts.length; i++) {
                var d = drafts[i];
                var resolved = _resolvePreview(d.text);
                var preview = resolved.length > 45 ? resolved.substring(0, 45) + '...' : resolved;
                html += '<button type="button" class="wdp-draft" data-wdp-action="draft" data-wdp-phone="' + _esc(phone) + '" data-wdp-text="' + _esc(d.text) + '">';
                html += '<div class="wdp-draft-icon"><i class="fa fa-file-text-o"></i></div>';
                html += '<div class="wdp-draft-info"><div class="wdp-draft-name">' + _esc(d.name) + '</div>';
                html += '<div class="wdp-draft-preview">' + _esc(preview) + '</div></div></button>';
            }
            html += '</div>';
        }

        html += '</div></div>';
        return html;
    }

    function _buildLoading(phone) {
        var html = '<div class="wdp-popover">';
        html += '<div class="wdp-hdr"><div class="wdp-hdr-icon"><i class="fa fa-whatsapp"></i></div>';
        html += '<div class="wdp-hdr-text"><div class="wdp-hdr-title">\u0648\u0627\u062A\u0633\u0627\u0628</div>';
        html += '<div class="wdp-hdr-phone">' + _esc(phone) + '</div></div></div>';
        html += '<div class="wdp-body"><div class="wdp-loading"><i class="fa fa-spinner"></i> \u062C\u0627\u0631\u064A \u0627\u0644\u062A\u062D\u0645\u064A\u0644...</div></div></div>';
        return html;
    }

    function _handleClick(e) {
        var btn = e.target.closest('[data-wdp-action]');
        if (!btn) return;
        var phone = btn.getAttribute('data-wdp-phone');
        var action = btn.getAttribute('data-wdp-action');

        if (action === 'direct') {
            _openWa(phone, '');
        } else if (action === 'draft') {
            var rawText = btn.getAttribute('data-wdp-text');
            var resolved = _resolvePreview(rawText);
            _openWa(phone, resolved);
        }

        document.querySelectorAll('.pn-wa-btn').forEach(function(b) {
            if (b._tippy) b._tippy.hide();
        });
    }

    function _fetchAndRender(instance, phone) {
        var urls = (typeof OCP_CONFIG !== 'undefined' && OCP_CONFIG.urls) ? OCP_CONFIG.urls : {};
        var listUrl = urls.smsDraftList || '';
        if (!listUrl) {
            instance.setContent(_buildContent(phone, []));
            return;
        }

        if (_cache !== null) {
            instance.setContent(_buildContent(phone, _cache));
            return;
        }

        $.get(listUrl, function(res) {
            _cache = (res && res.drafts) ? res.drafts : [];
            instance.setContent(_buildContent(phone, _cache));
        }).fail(function() {
            instance.setContent(_buildContent(phone, []));
        });
    }

    function init() {
        document.querySelectorAll('.pn-wa-btn').forEach(function(btn) {
            if (btn._tippy) btn._tippy.destroy();
            var phone = btn.getAttribute('data-wa-phone');

            tippy(btn, {
                content: _buildLoading(phone),
                allowHTML: true,
                interactive: true,
                trigger: 'click',
                placement: 'bottom',
                appendTo: document.body,
                maxWidth: 300,
                animation: 'shift-away',
                theme: 'wdp',
                onShow: function(instance) {
                    _fetchAndRender(instance, phone);
                },
                onClickOutside: function(instance) {
                    instance.hide();
                },
                onMount: function(instance) {
                    instance.popper.addEventListener('click', _handleClick);
                },
                onHide: function(instance) {
                    instance.popper.removeEventListener('click', _handleClick);
                }
            });
        });
    }

    function invalidateCache() { _cache = null; }

    if (typeof $ !== 'undefined') {
        $(function() { init(); });
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }

    return { init: init, invalidateCache: invalidateCache };
})();

var _callToastTimer = null;
function _showToast(msg, phone, state) {
    var toast = document.getElementById('pnCallToast');
    var icon = document.getElementById('pnCallToastIcon');
    var msgEl = document.getElementById('pnCallToastMsg');

    toast.className = 'pn-call-toast';
    if (state === 'success') toast.classList.add('pn-success');
    else if (state === 'error') toast.classList.add('pn-error');

    icon.className = state === 'success' ? 'fa fa-check-circle'
                   : state === 'error'   ? 'fa fa-exclamation-circle'
                   : 'fa fa-phone';
    msgEl.innerHTML = msg + (phone ? '<span class="pn-toast-num">' + phone + '</span>' : '');
    toast.classList.add('pn-show');

    if (_callToastTimer) clearTimeout(_callToastTimer);
    _callToastTimer = setTimeout(function() {
        toast.classList.remove('pn-show');
        toast.classList.add('pn-hide');
    }, state ? 4000 : 8000);
}

function makeCall(phone, btn) {
    if (btn.classList.contains('pn-calling')) return;
    btn.classList.add('pn-calling');
    _showToast('جاري الاتصال بـ ', phone, null);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'http://localhost:9876/call');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.timeout = 5000;

    xhr.onload = function() {
        btn.classList.remove('pn-calling');
        try {
            var res = JSON.parse(xhr.responseText);
            if (res.ok) {
                _showToast('تم بدء الاتصال بـ ', phone, 'success');
            } else {
                _showToast(res.error || 'فشل الاتصال عبر USB', '', 'error');
            }
        } catch(e) {
            _showToast('خطأ في الاستجابة', '', 'error');
        }
    };

    xhr.onerror = xhr.ontimeout = function() {
        btn.classList.remove('pn-calling');
        _showToast('خدمة ADB غير متاحة — يتم استخدام Phone Link...', '', 'error');
        window.location.href = 'tel:' + encodeURIComponent(phone);
    };

    xhr.send('phone=' + encodeURIComponent(phone));
}
</script>
