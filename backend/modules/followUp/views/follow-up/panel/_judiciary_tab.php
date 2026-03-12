<?php
/**
 * Judiciary Tab — Hierarchical Tree View
 * Displays: Request → Document → Status per customer
 */
use yii\helpers\Html;
use yii\helpers\Url;
use backend\helpers\NameHelper;

/**
 * @var string|int $contract_id
 * @var backend\modules\contracts\models\Contracts $contract
 * @var array $judiciaryData
 */

$judiciary = $judiciaryData['judiciary'] ?? null;
$actions = $judiciaryData['actions'] ?? [];
$lastAction = $judiciaryData['last_action'] ?? null;
$daysSinceLast = $judiciaryData['days_since_last'] ?? 999;
$stageLabel = $judiciaryData['stage_label'] ?? '';
$perParty = $judiciaryData['per_party'] ?? [];
$actionTree = $judiciaryData['action_tree'] ?? [];

// Request status styles
$reqStatusStyles = [
    'pending'  => ['icon' => 'fa-clock-o',     'color' => '#F59E0B', 'bg' => '#FFFBEB', 'label' => 'معلق'],
    'approved' => ['icon' => 'fa-check-circle', 'color' => '#10B981', 'bg' => '#ECFDF5', 'label' => 'موافقة'],
    'rejected' => ['icon' => 'fa-times-circle', 'color' => '#EF4444', 'bg' => '#FEF2F2', 'label' => 'مرفوض'],
];
?>

<style>
/* ═══ Judiciary Tree Styles ═══ */
.jud-tree-customer { margin-bottom:20px; }
.jud-tree-customer-header {
    display:flex;align-items:center;gap:10px;padding:10px 14px;
    background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-radius:10px;
    margin-bottom:12px;border:1px solid #e2e8f0;
}
.jud-tree-customer-avatar {
    width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.jud-tree-request {
    position:relative;margin-bottom:14px;padding-right:0;
}
.jud-tree-request-card {
    display:flex;align-items:flex-start;gap:10px;padding:12px 14px;
    background:#fff;border-radius:10px;border:1px solid #BFDBFE;
    border-right:4px solid #3B82F6;transition:box-shadow .2s;
}
.jud-tree-request-card:hover { box-shadow:0 2px 8px rgba(59,130,246,.12); }
.jud-tree-doc-wrap {
    margin-right:24px;padding-right:20px;
    border-right:2px dashed #C4B5FD;
}
.jud-tree-doc-card {
    display:flex;align-items:flex-start;gap:10px;padding:10px 12px;
    background:#FDFCFF;border-radius:8px;border:1px solid #DDD6FE;
    border-right:3px solid #8B5CF6;margin-bottom:8px;transition:box-shadow .2s;
}
.jud-tree-doc-card:hover { box-shadow:0 2px 6px rgba(139,92,246,.1); }
.jud-tree-status-wrap {
    margin-right:20px;padding-right:16px;
    border-right:2px dotted #FDBA74;
}
.jud-tree-status-card {
    display:flex;align-items:center;gap:8px;padding:6px 10px;
    background:#FFFBF5;border-radius:6px;border:1px solid #FED7AA;
    margin-bottom:4px;font-size:12px;
}
.jud-tree-status-card.is-old { opacity:.55;border-style:dashed; }
.jud-tree-icon {
    width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;
}
.jud-tree-process-card {
    display:flex;align-items:center;gap:10px;padding:8px 12px;
    background:#F8FAFC;border-radius:8px;border:1px solid #E2E8F0;
    margin-bottom:6px;
}
.jud-tree-orphan-section {
    margin-right:24px;padding-right:20px;margin-top:8px;
    border-right:2px dashed #E5E7EB;
}
.jud-badge {
    font-size:10px;padding:1px 8px;border-radius:10px;white-space:nowrap;display:inline-flex;align-items:center;gap:3px;
}
.jud-tree-edit {
    width:26px;height:26px;display:flex;align-items:center;justify-content:center;
    border-radius:6px;color:var(--ocp-primary,#3B82F6);background:transparent;border:none;cursor:pointer;flex-shrink:0;
}
.jud-tree-edit:hover { background:#EFF6FF; }
.jud-meta { font-size:11px;color:#94A3B8;display:flex;gap:8px;flex-wrap:wrap;margin-top:2px; }
</style>

<?php if (!$judiciary): ?>
    <!-- No judiciary case -->
    <div class="ocp-card" style="padding:var(--ocp-space-xl);text-align:center">
        <i class="fa fa-gavel" style="font-size:48px;color:var(--ocp-text-muted);margin-bottom:16px"></i>
        <h4 style="color:var(--ocp-text-secondary);margin-bottom:8px">لا يوجد ملف قضائي مسجل</h4>
        <p style="color:var(--ocp-text-muted);margin-bottom:16px">لم يتم تسجيل أي قضية على هذا العقد بعد</p>
        <a href="<?= Url::to(['/judiciary/judiciary/create', 'contract_id' => $contract_id]) ?>" class="ocp-btn-primary" style="display:inline-flex;align-items:center;gap:6px;padding:10px 24px;background:var(--ocp-primary);color:#fff;border-radius:8px;text-decoration:none;font-weight:600">
            <i class="fa fa-plus"></i> تسجيل قضية جديدة
        </a>
    </div>
<?php else: ?>

    <!-- ═══ Case Header Card ═══ -->
    <div class="ocp-card" style="padding:var(--ocp-space-lg);margin-bottom:var(--ocp-space-md)">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:48px;height:48px;border-radius:12px;background:#FFEBEE;display:flex;align-items:center;justify-content:center">
                    <i class="fa fa-gavel" style="font-size:20px;color:var(--ocp-danger)"></i>
                </div>
                <div>
                    <div style="font-size:var(--ocp-font-size-lg);font-weight:700;color:var(--ocp-text-primary)">
                        قضية <?= Html::encode(($judiciary->judiciary_number ?: '-') . '/' . ($judiciary->year ?: '-')) ?>
                        <?php if ($judiciary->case_status): ?>
                        <span class="jud-badge" style="background:#EDE7F6;color:#4527A0"><?= Html::encode($judiciary->case_status) ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:var(--ocp-font-size-sm);color:var(--ocp-text-muted);margin-top:2px">
                        <?= $judiciary->court ? Html::encode($judiciary->court->name) : 'محكمة غير محددة' ?>
                        <?php if ($judiciary->type): ?> · <?= Html::encode($judiciary->type->name) ?><?php endif; ?>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="<?= Url::to(['/judiciary/judiciary/update', 'id' => $judiciary->id, 'contract_id' => $contract_id]) ?>" class="ocp-action-btn" style="text-decoration:none;padding:6px 14px;min-height:auto;height:auto">
                    <i class="fa fa-external-link" style="margin-left:4px"></i> فتح القضية
                </a>
                <a href="<?= Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/create-followup-judicary-custamer-action', 'contractID' => $contract_id]) ?>" role="modal-remote" class="ocp-action-btn" style="text-decoration:none;padding:6px 14px;min-height:auto;height:auto;background:var(--ocp-primary);color:#fff">
                    <i class="fa fa-plus" style="margin-left:4px"></i> إضافة إجراء
                </a>
            </div>
        </div>

        <!-- Quick stats -->
        <div style="display:flex;gap:24px;margin-top:16px;flex-wrap:wrap">
            <div>
                <span style="font-size:var(--ocp-font-size-xs);color:var(--ocp-text-muted)">المحامي</span>
                <div style="font-weight:600;color:var(--ocp-text-primary)"><?= $judiciary->lawyer ? Html::encode($judiciary->lawyer->name) : 'غير محدد' ?></div>
            </div>
            <div>
                <span style="font-size:var(--ocp-font-size-xs);color:var(--ocp-text-muted)">إجمالي الإجراءات</span>
                <div style="font-weight:700;color:var(--ocp-primary);font-size:var(--ocp-font-size-lg)"><?= count($actions) ?></div>
            </div>
            <div>
                <span style="font-size:var(--ocp-font-size-xs);color:var(--ocp-text-muted)">المرحلة الحالية</span>
                <div style="font-weight:600;color:var(--ocp-text-primary)"><?= Html::encode($stageLabel) ?></div>
            </div>
            <div>
                <span style="font-size:var(--ocp-font-size-xs);color:var(--ocp-text-muted)">آخر إجراء منذ</span>
                <div style="font-weight:600;color:<?= $daysSinceLast > 30 ? 'var(--ocp-danger)' : ($daysSinceLast > 14 ? 'var(--ocp-warning)' : 'var(--ocp-success)') ?>">
                    <?= $daysSinceLast < 999 ? $daysSinceLast . ' يوم' : 'لا يوجد' ?>
                </div>
            </div>
        </div>

        <!-- Last check date -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding-top:12px;border-top:1px solid var(--ocp-border-light)">
            <div style="font-size:var(--ocp-font-size-xs);color:var(--ocp-text-muted)">
                <i class="fa fa-calendar-check-o"></i>
                آخر تشييك: <strong style="color:var(--ocp-text-primary)"><?= $judiciary->last_check_date ? date('Y/m/d', strtotime($judiciary->last_check_date)) : 'لم يتم بعد' ?></strong>
            </div>
            <button class="btn btn-xs btn-default" onclick="OCP.updateJudiciaryCheck(<?= $judiciary->id ?>)" style="border-radius:6px;font-size:12px">
                <i class="fa fa-check"></i> تحديث تاريخ التشييك
            </button>
        </div>
    </div>

    <!-- ═══ Per-Party Status Card ═══ -->
    <?php if (!empty($perParty)): ?>
    <div class="ocp-card" style="padding:var(--ocp-space-lg);margin-bottom:var(--ocp-space-md)">
        <h4 style="margin:0 0 12px;font-size:var(--ocp-font-size-base);font-weight:700;color:var(--ocp-text-primary)">
            <i class="fa fa-users"></i> آخر إجراء لكل طرف
            <span style="font-size:var(--ocp-font-size-xs);color:var(--ocp-text-muted);font-weight:400;margin-right:8px">(<?= count($perParty) ?> أطراف)</span>
        </h4>
        <?php foreach ($perParty as $pi => $party): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;<?= $pi < count($perParty) - 1 ? 'border-bottom:1px solid var(--ocp-border-light);' : '' ?>">
            <div style="width:36px;height:36px;border-radius:50%;background:<?= $party['customer_type'] === 'client' ? '#E3F2FD' : '#FFF3E0' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="fa <?= $party['customer_type'] === 'client' ? 'fa-user' : 'fa-user-o' ?>" style="color:<?= $party['customer_type'] === 'client' ? '#1565C0' : '#E65100' ?>;font-size:14px"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-weight:600;color:var(--ocp-text-primary);font-size:var(--ocp-font-size-sm)" title="<?= Html::encode($party['customer_name']) ?>"><?= Html::encode(NameHelper::short($party['customer_name'])) ?></span>
                    <span class="jud-badge" style="background:<?= $party['customer_type'] === 'client' ? '#E3F2FD' : '#FFF3E0' ?>;color:<?= $party['customer_type'] === 'client' ? '#1565C0' : '#E65100' ?>"><?= $party['customer_type_label'] ?></span>
                </div>
                <div style="font-size:var(--ocp-font-size-xs);color:var(--ocp-text-muted);margin-top:2px">
                    <?php if (!empty($party['last_request_name'])): ?>
                        <span style="font-weight:500">آخر طلب:</span> <?= Html::encode($party['last_request_name']) ?>
                        <?php if ($party['last_request_status']): $rs = $reqStatusStyles[$party['last_request_status']] ?? $reqStatusStyles['pending']; ?>
                            · <span class="jud-badge" style="background:<?= $rs['bg'] ?>;color:<?= $rs['color'] ?>"><?= $rs['label'] ?></span>
                        <?php endif; ?> ·
                    <?php endif; ?>
                    <?= Html::encode($party['last_action_name']) ?>
                    · <?= $party['last_action_date'] ? date('Y/m/d', strtotime($party['last_action_date'])) : '-' ?>
                </div>
            </div>
            <div style="text-align:center;flex-shrink:0;min-width:60px">
                <?php $dColor = $party['days_since_last_action'] > 30 ? 'var(--ocp-danger)' : ($party['days_since_last_action'] > 14 ? 'var(--ocp-warning)' : 'var(--ocp-success)'); ?>
                <div style="font-size:var(--ocp-font-size-lg);font-weight:700;color:<?= $dColor ?>;font-family:var(--ocp-font-mono)"><?= $party['days_since_last_action'] < 999 ? $party['days_since_last_action'] : '—' ?></div>
                <div style="font-size:10px;color:var(--ocp-text-muted)">يوم</div>
            </div>
            <div style="text-align:center;flex-shrink:0;min-width:40px">
                <div style="font-size:var(--ocp-font-size-sm);font-weight:600;color:var(--ocp-text-secondary)"><?= $party['total_actions'] ?></div>
                <div style="font-size:10px;color:var(--ocp-text-muted)">إجراء</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- ═══ HIERARCHICAL ACTION TREE ═══ -->
    <!-- ═══════════════════════════════════════════════════ -->
    <?php if (empty($actionTree)): ?>
        <div class="ocp-card" style="padding:var(--ocp-space-xl);text-align:center">
            <p style="color:var(--ocp-text-muted)">لم يُسجل أي إجراء على هذه القضية بعد</p>
        </div>
    <?php else: ?>
        <div class="ocp-card" style="padding:var(--ocp-space-lg)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <h4 style="margin:0;font-size:var(--ocp-font-size-base);font-weight:700;color:var(--ocp-text-primary)">
                    <i class="fa fa-sitemap"></i> شجرة الإجراءات القضائية
                </h4>
                <span style="font-size:var(--ocp-font-size-xs);color:var(--ocp-text-muted)"><?= count($actions) ?> إجراء</span>
            </div>

            <?php foreach ($actionTree as $customerGroup): ?>
            <div class="jud-tree-customer">
                <!-- ─── Customer Header ─── -->
                <div class="jud-tree-customer-header">
                    <div class="jud-tree-customer-avatar" style="background:<?= $customerGroup['customer_type'] === 'client' ? '#E3F2FD' : '#FFF3E0' ?>">
                        <i class="fa <?= $customerGroup['customer_type'] === 'client' ? 'fa-user' : 'fa-user-o' ?>" style="color:<?= $customerGroup['customer_type'] === 'client' ? '#1565C0' : '#E65100' ?>;font-size:15px"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:13px;color:#1E293B" title="<?= Html::encode($customerGroup['customer_name'] ?: '') ?>"><?= Html::encode(NameHelper::short($customerGroup['customer_name'] ?: 'غير محدد')) ?></div>
                        <div style="font-size:11px;color:#94A3B8">
                            <?= $customerGroup['customer_type'] === 'client' ? 'مدين' : ($customerGroup['customer_type'] === 'guarantor' ? 'كفيل' : 'طرف') ?>
                            · <?= count($customerGroup['requests']) ?> طلبات
                        </div>
                    </div>
                </div>

                <!-- ─── Requests Tree ─── -->
                <?php foreach ($customerGroup['requests'] as $reqNode): ?>
                    <?php
                    $req = $reqNode['action'];
                    $reqStatus = $reqNode['request_status'] ?: 'approved';
                    $rs = $reqStatusStyles[$reqStatus] ?? $reqStatusStyles['pending'];
                    ?>
                    <div class="jud-tree-request">
                        <!-- REQUEST CARD -->
                        <div class="jud-tree-request-card">
                            <div class="jud-tree-icon" style="background:#EFF6FF;color:#3B82F6">
                                <i class="fa fa-file-text-o"></i>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                                    <span style="font-weight:700;color:#1E293B;font-size:13px"><?= Html::encode($reqNode['name']) ?></span>
                                    <span class="jud-badge" style="background:<?= $rs['bg'] ?>;color:<?= $rs['color'] ?>"><i class="fa <?= $rs['icon'] ?>" style="margin-left:3px;font-size:9px"></i> <?= $rs['label'] ?></span>
                                    <span class="jud-badge" style="background:#EFF6FF;color:#3B82F6">طلب</span>
                                </div>
                                <div class="jud-meta">
                                    <?php if ($req->action_date): ?>
                                    <span><i class="fa fa-calendar"></i> <?= date('Y/m/d', strtotime($req->action_date)) ?></span>
                                    <?php endif; ?>
                                    <?php if ($req->createdBy): ?>
                                    <span><i class="fa fa-id-badge"></i> <?= Html::encode($req->createdBy->username) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($reqNode['documents'])): ?>
                                    <span style="color:#8B5CF6"><i class="fa fa-paperclip"></i> <?= count($reqNode['documents']) ?> كتاب</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($req->note): ?>
                                <div style="margin-top:4px;padding:4px 8px;background:#F1F5F9;border-radius:4px;font-size:11px;color:#64748B"><?= Html::encode($req->note) ?></div>
                                <?php endif; ?>
                            </div>
                            <a href="<?= Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'id' => $req->id, 'contractID' => $contract_id]) ?>" role="modal-remote" class="jud-tree-edit" title="تعديل"><i class="fa fa-pencil"></i></a>
                        </div>

                        <!-- DOCUMENTS under this request -->
                        <?php if (!empty($reqNode['documents'])): ?>
                        <div class="jud-tree-doc-wrap">
                            <?php foreach ($reqNode['documents'] as $docNode): ?>
                                <?php $doc = $docNode['action']; ?>
                                <div class="jud-tree-doc-card">
                                    <div class="jud-tree-icon" style="background:#F5F3FF;color:#8B5CF6">
                                        <i class="fa fa-file-o"></i>
                                    </div>
                                    <div style="flex:1;min-width:0">
                                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                                            <span style="font-weight:600;color:#334155;font-size:12px"><?= Html::encode($docNode['name']) ?></span>
                                            <span class="jud-badge" style="background:#F5F3FF;color:#8B5CF6">كتاب</span>
                                        </div>
                                        <div class="jud-meta">
                                            <?php if ($doc->action_date): ?>
                                            <span><i class="fa fa-calendar"></i> <?= date('Y/m/d', strtotime($doc->action_date)) ?></span>
                                            <?php endif; ?>
                                            <?php if ($doc->createdBy): ?>
                                            <span><i class="fa fa-id-badge"></i> <?= Html::encode($doc->createdBy->username) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($docNode['statuses'])): ?>
                                            <span style="color:#EA580C"><i class="fa fa-exchange"></i> <?= count($docNode['statuses']) ?> حالة</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($doc->note): ?>
                                        <div style="margin-top:3px;padding:3px 6px;background:#FAFAFA;border-radius:4px;font-size:11px;color:#64748B"><?= Html::encode($doc->note) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?= Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'id' => $doc->id, 'contractID' => $contract_id]) ?>" role="modal-remote" class="jud-tree-edit" title="تعديل"><i class="fa fa-pencil"></i></a>
                                </div>

                                <!-- STATUSES under this document -->
                                <?php if (!empty($docNode['statuses'])): ?>
                                <div class="jud-tree-status-wrap">
                                    <?php foreach ($docNode['statuses'] as $stNode): ?>
                                        <?php $st = $stNode['action']; ?>
                                        <div class="jud-tree-status-card <?= !$stNode['is_current'] ? 'is-old' : '' ?>">
                                            <div style="width:22px;height:22px;border-radius:6px;background:<?= $stNode['is_current'] ? '#FFF7ED' : '#F9FAFB' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                                <i class="fa <?= $stNode['is_current'] ? 'fa-circle' : 'fa-circle-o' ?>" style="font-size:8px;color:<?= $stNode['is_current'] ? '#EA580C' : '#CBD5E1' ?>"></i>
                                            </div>
                                            <div style="flex:1;min-width:0">
                                                <span style="font-weight:500;color:<?= $stNode['is_current'] ? '#92400E' : '#94A3B8' ?>"><?= Html::encode($stNode['name']) ?></span>
                                                <?php if ($stNode['is_current']): ?>
                                                <span class="jud-badge" style="background:#ECFDF5;color:#059669;margin-right:4px">● حالية</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($st->action_date): ?>
                                            <span style="font-size:11px;color:#94A3B8;font-family:var(--ocp-font-mono);white-space:nowrap"><?= date('Y/m/d', strtotime($st->action_date)) ?></span>
                                            <?php endif; ?>
                                            <?php if ($st->amount): ?>
                                            <span style="font-size:11px;color:#059669;font-weight:600"><?= number_format($st->amount, 2) ?> د.أ</span>
                                            <?php endif; ?>
                                            <a href="<?= Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'id' => $st->id, 'contractID' => $contract_id]) ?>" role="modal-remote" class="jud-tree-edit" title="تعديل" style="width:22px;height:22px"><i class="fa fa-pencil" style="font-size:10px"></i></a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <!-- ─── Orphan Documents (not linked to any request) ─── -->
                <?php if (!empty($customerGroup['orphan_documents'])): ?>
                <div class="jud-tree-orphan-section">
                    <div style="font-size:11px;color:#94A3B8;margin-bottom:6px;font-weight:600"><i class="fa fa-unlink"></i> كتب بدون طلب مرتبط</div>
                    <?php foreach ($customerGroup['orphan_documents'] as $oDoc): ?>
                        <?php $doc = $oDoc['action']; ?>
                        <div class="jud-tree-doc-card" style="border-right-color:#CBD5E1;background:#FAFAFA">
                            <div class="jud-tree-icon" style="background:#F1F5F9;color:#94A3B8">
                                <i class="fa fa-file-o"></i>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                                    <span style="font-weight:600;color:#64748B;font-size:12px"><?= Html::encode($oDoc['name']) ?></span>
                                    <span class="jud-badge" style="background:#F1F5F9;color:#94A3B8">كتاب</span>
                                </div>
                                <div class="jud-meta">
                                    <?php if ($doc->action_date): ?>
                                    <span><i class="fa fa-calendar"></i> <?= date('Y/m/d', strtotime($doc->action_date)) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="<?= Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'id' => $doc->id, 'contractID' => $contract_id]) ?>" role="modal-remote" class="jud-tree-edit" title="تعديل"><i class="fa fa-pencil"></i></a>
                        </div>
                        <?php if (!empty($oDoc['statuses'])): ?>
                        <div class="jud-tree-status-wrap">
                            <?php foreach ($oDoc['statuses'] as $stNode): ?>
                                <?php $st = $stNode['action']; ?>
                                <div class="jud-tree-status-card <?= !$stNode['is_current'] ? 'is-old' : '' ?>">
                                    <div style="width:22px;height:22px;border-radius:6px;background:<?= $stNode['is_current'] ? '#FFF7ED' : '#F9FAFB' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                        <i class="fa <?= $stNode['is_current'] ? 'fa-circle' : 'fa-circle-o' ?>" style="font-size:8px;color:<?= $stNode['is_current'] ? '#EA580C' : '#CBD5E1' ?>"></i>
                                    </div>
                                    <span style="flex:1;font-weight:500;color:<?= $stNode['is_current'] ? '#92400E' : '#94A3B8' ?>;font-size:12px"><?= Html::encode($stNode['name']) ?></span>
                                    <?php if ($st->action_date): ?>
                                    <span style="font-size:11px;color:#94A3B8;font-family:var(--ocp-font-mono)"><?= date('Y/m/d', strtotime($st->action_date)) ?></span>
                                    <?php endif; ?>
                                    <a href="<?= Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'id' => $st->id, 'contractID' => $contract_id]) ?>" role="modal-remote" class="jud-tree-edit" title="تعديل" style="width:22px;height:22px"><i class="fa fa-pencil" style="font-size:10px"></i></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- ─── Orphan Statuses ─── -->
                <?php if (!empty($customerGroup['orphan_statuses'])): ?>
                <div class="jud-tree-orphan-section">
                    <div style="font-size:11px;color:#94A3B8;margin-bottom:6px;font-weight:600"><i class="fa fa-unlink"></i> حالات غير مربوطة</div>
                    <?php foreach ($customerGroup['orphan_statuses'] as $oSt): ?>
                        <?php $st = $oSt['action']; ?>
                        <div class="jud-tree-status-card <?= !$oSt['is_current'] ? 'is-old' : '' ?>">
                            <div style="width:22px;height:22px;border-radius:6px;background:#F9FAFB;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="fa fa-circle-o" style="font-size:8px;color:#CBD5E1"></i>
                            </div>
                            <span style="flex:1;font-weight:500;color:#94A3B8;font-size:12px"><?= Html::encode($oSt['name']) ?></span>
                            <?php if ($st->action_date): ?>
                            <span style="font-size:11px;color:#94A3B8;font-family:var(--ocp-font-mono)"><?= date('Y/m/d', strtotime($st->action_date)) ?></span>
                            <?php endif; ?>
                            <a href="<?= Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'id' => $st->id, 'contractID' => $contract_id]) ?>" role="modal-remote" class="jud-tree-edit" title="تعديل" style="width:22px;height:22px"><i class="fa fa-pencil" style="font-size:10px"></i></a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- ─── Process Actions ─── -->
                <?php if (!empty($customerGroup['processes'])): ?>
                <div style="margin-top:8px">
                    <?php foreach ($customerGroup['processes'] as $proc): ?>
                        <?php $procDef = $proc->judiciaryActions; ?>
                        <div class="jud-tree-process-card">
                            <div class="jud-tree-icon" style="background:#F1F5F9;color:#64748B">
                                <i class="fa fa-cog"></i>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div style="display:flex;align-items:center;gap:6px">
                                    <span style="font-weight:500;color:#475569;font-size:12px"><?= Html::encode($procDef ? $procDef->name : 'إجراء') ?></span>
                                    <span class="jud-badge" style="background:#F1F5F9;color:#64748B">إداري</span>
                                </div>
                                <div class="jud-meta">
                                    <?php if ($proc->action_date): ?>
                                    <span><i class="fa fa-calendar"></i> <?= date('Y/m/d', strtotime($proc->action_date)) ?></span>
                                    <?php endif; ?>
                                    <?php if ($proc->createdBy): ?>
                                    <span><i class="fa fa-id-badge"></i> <?= Html::encode($proc->createdBy->username) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="<?= Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'id' => $proc->id, 'contractID' => $contract_id]) ?>" role="modal-remote" class="jud-tree-edit" title="تعديل"><i class="fa fa-pencil"></i></a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>
