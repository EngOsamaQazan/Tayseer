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
.pn-contact-btn.call{background:#EFF6FF;color:#2563EB}
.pn-contact-btn.call:hover{background:#DBEAFE}
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
                        <a class="pn-contact-btn call" href="tel:<?= Html::encode($ppTel) ?>" title="اتصال"><i class="fa fa-phone"></i></a>
                        <a class="pn-contact-btn whatsapp" href="https://wa.me/<?= Html::encode($ppWa) ?>" target="_blank" title="واتساب"><i class="fa fa-whatsapp"></i></a>
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
                            <a class="pn-contact-btn call" href="tel:<?= Html::encode($pnTel) ?>" title="اتصال"><i class="fa fa-phone"></i></a>
                            <a class="pn-contact-btn whatsapp" href="https://wa.me/<?= Html::encode($pnWa) ?>" target="_blank" title="واتساب"><i class="fa fa-whatsapp"></i></a>
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

<script>
window._bulkSmsPhones = <?= json_encode($allPhonesList, JSON_UNESCAPED_UNICODE) ?>;
</script>
