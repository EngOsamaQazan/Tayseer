<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use common\helper\Permissions;
use backend\helpers\NameHelper;
use backend\helpers\PhoneInputAsset;

PhoneInputAsset::register($this);
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

/**
 * @var yii\web\View $this
 * @var backend\modules\contracts\models\Contracts $contract
 * @var array $riskData
 * @var array $aiData
 * @var array $kanbanData
 * @var array $timeline
 * @var array $financials
 * @var array $alerts
 * @var backend\modules\customers\models\Customers|null $customer
 * @var backend\modules\followUp\helper\ContractCalculations $contractCalculations
 * @var string|int $contract_id
 * @var backend\modules\followUp\models\FollowUp $model
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $modelsPhoneNumbersFollwUps
 * @var array $judiciaryData
 * @var array $allJudiciaryData
 */

$isJudiciaryPaid = $contract->isJudiciaryPaid();
$isLegal = in_array($contract->status, ['judiciary', 'legal_department']) && !$isJudiciaryPaid;
$isClosed = in_array($contract->status, ['finished', 'canceled']) || $isJudiciaryPaid;
$hasCase = in_array($contract->status, ['judiciary', 'legal_department']) && !empty($allJudiciaryData);

$this->title = 'لوحة تحكم العقد #' . $contract->id;
$this->params['breadcrumbs'][] = ['label' => 'تقارير المتابعة', 'url' => ['/followUpReport']];
$this->params['breadcrumbs'][] = $this->title;

// Register OCP assets
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/ocp.css?v=20260408', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/ocp.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => \yii\web\View::POS_END]);

// Pass data to JS
$contractId = $contract->id;
$this->registerJs("window.OCP_CONFIG = " . Json::encode([
    'contractId' => $contractId,
    'urls' => [
        'createTask' => Url::to(['/followUp/follow-up/create-task']),
        'moveTask' => Url::to(['/followUp/follow-up/move-task']),
        'saveFollowUp' => Url::to(['/followUp/follow-up/save-follow-up']),
        'savePromise' => Url::to(['/followUp/follow-up/save-promise']),
        'aiFeedback' => Url::to(['/followUp/follow-up/ai-feedback']),
        'getTimeline' => Url::to(['/followUp/follow-up/get-timeline', 'contract_id' => $contractId]),
        'sendSms' => Url::to(['/followUp/follow-up/send-sms']),
        'bulkSendSms' => Url::to(['/followUp/follow-up/bulk-send-sms']),
        'smsDraftList' => Url::to(['/followUp/follow-up/sms-draft-list']),
        'smsDraftSave' => Url::to(['/followUp/follow-up/sms-draft-save']),
        'smsDraftDelete' => Url::to(['/followUp/follow-up/sms-draft-delete']),
        'changeStatus' => Url::to(['/followUp/follow-up/change-status']),
        'customerInfo' => Url::to(['/followUp/follow-up/custamer-info']),
        'updateJudiciaryCheck' => Url::to(['/followUp/follow-up/update-judiciary-check']),
        'addNewLoan' => Url::to(['/followUp/follow-up/add-new-loan']),
        'createJudiciary' => Url::to(['/judiciary/judiciary/create', 'contract_id' => $contractId]),
    ],
]) . ";", \yii\web\View::POS_HEAD);

// JS vars for old modals compatibility
$this->registerJsVar('is_loan', $contractCalculations->contract_model->is_loan ?? 0, \yii\web\View::POS_HEAD);
$this->registerJsVar('change_status_url', Url::to(['/followUp/follow-up/change-status']), \yii\web\View::POS_HEAD);
$this->registerJsVar('send_sms', Url::to(['/followUp/follow-up/send-sms']), \yii\web\View::POS_HEAD);
$this->registerJsVar('bulk_send_sms', Url::to(['/followUp/follow-up/bulk-send-sms']), \yii\web\View::POS_HEAD);
$this->registerJsVar('customer_info_url', Url::to(['/followUp/follow-up/custamer-info']), \yii\web\View::POS_HEAD);
$this->registerJsVar('quick_update_customer_url', Url::to(['/followUp/follow-up/quick-update-customer']), \yii\web\View::POS_HEAD);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/follow-up.js?v=20260408c', ['depends' => [\yii\web\JqueryAsset::class]]);

$_lastPay = $riskData['last_payment'] ?? ['date' => '-', 'amount' => 0];
$_custShort = $customer ? NameHelper::short($customer->name) : 'غير محدد';
$_statusLbl = ($contractCalculations->contract_model->is_loan ?? 0)
    ? 'قضائي مسدد'
    : \backend\modules\followUp\helper\RiskEngine::statusLabel($contract->status);
$_smsParties = [];
foreach ($contract->contractsCustomers ?? [] as $_cc) {
    $_ccCust = $_cc->customer ?? null;
    if ($_ccCust) $_smsParties[] = $_ccCust->name;
}
$_judiciaryModel = $judiciaryData['judiciary'] ?? null;
$_courtName = ($_judiciaryModel && $_judiciaryModel->court) ? $_judiciaryModel->court->name : '';
$_caseNum = $_judiciaryModel ? (($_judiciaryModel->judiciary_number ?: '') . ($_judiciaryModel->year ? '/' . $_judiciaryModel->year : '')) : '';
$_smsVars = [
    'اسم_العميل'   => $_custShort,
    'أطراف_العقد'   => implode(' و ', $_smsParties) ?: 'غير محدد',
    'رقم_العقد'     => (string)$contract->id,
    'حالة_العقد'    => $_statusLbl,
    'المبلغ_الإجمالي' => number_format($financials['total'] ?? 0, 2),
    'المدفوع'       => number_format($financials['paid'] ?? 0, 2),
    'المتبقي'       => number_format($financials['remaining'] ?? 0, 2),
    'المستحق'       => number_format($financials['should_paid'] ?? 0, 2),
    'المتأخر'       => number_format($financials['overdue'] ?? 0, 2),
    'القسط_الشهري'  => number_format($financials['monthly_installment'] ?? 0, 2),
    'أقساط_متأخرة'  => (string)($financials['overdue_installments'] ?? 0),
    'أقساط_متبقية'  => (string)($financials['remaining_installments'] ?? 0),
    'أيام_التأخير'  => (string)($riskData['dpd'] ?? 0),
    'آخر_دفعة'      => number_format($_lastPay['amount'] ?? 0, 2),
    'تاريخ_آخر_دفعة' => $_lastPay['date'] ?? '-',
    'أتعاب_المحاماة' => number_format($financials['lawyer_costs'] ?? 0, 2),
    'تاريخ_الشراء' => $contract->Date_of_sale ?? '-',
];
if ($_judiciaryModel) {
    $_smsVars['اسم_المحكمة'] = $_courtName;
    $_smsVars['رقم_القضية'] = $_caseNum;
}
$this->registerJs("window.SMS_VARS=" . \yii\helpers\Json::encode($_smsVars) . ";", \yii\web\View::POS_HEAD);

$dpd = $riskData['dpd'] ?? 0;
$dpdClass = $dpd <= 0 ? 'ok' : ($dpd <= 7 ? 'warning' : ($dpd <= 30 ? 'danger' : 'critical'));
$riskLevel = $riskData['level'] ?? 'low';
$showWarningStrip = in_array($riskLevel, ['high', 'critical']) || $isLegal;
$isFinishedPaid = ($contract->status === 'finished');
$statusBadge = ($isJudiciaryPaid || $isFinishedPaid) ? 'settled' : \backend\modules\followUp\helper\RiskEngine::statusBadgeClass($contract->status);
$statusLabel = $isJudiciaryPaid ? 'قضائي مسدد' : ($isFinishedPaid ? 'مسدد' : \backend\modules\followUp\helper\RiskEngine::statusLabel($contract->status));
$customerName = $customer ? NameHelper::short($customer->name) : 'غير محدد';
$lastPayment = $riskData['last_payment'] ?? ['date' => '-', 'amount' => 0];

$purchaseDateRaw = $contract->Date_of_sale ?? null;
$purchaseDateLabel = '—';
if ($purchaseDateRaw !== null && $purchaseDateRaw !== '') {
    $ts = strtotime((string) $purchaseDateRaw);
    if ($ts !== false) {
        // صريح بأرقام لاتينية — بدون علامات اتجاه ICU التي تفسد العرض في RTL
        $purchaseDateLabel = date('d/m/Y', $ts);
    }
}

$firstInstDateRaw = $contract->first_installment_date ?? null;
$firstInstDateLabel = '—';
if ($firstInstDateRaw !== null && $firstInstDateRaw !== '') {
    $ts2 = strtotime((string) $firstInstDateRaw);
    if ($ts2 !== false) {
        $firstInstDateLabel = date('d/m/Y', $ts2);
    }
}

$riskLevelArabic = ['low' => 'منخفض', 'med' => 'متوسط', 'high' => 'مرتفع', 'critical' => 'حرج'];
?>

<div class="ocp-page <?= $isClosed ? 'ocp-page--settled' : '' ?>">

    <?php // ═══ SETTLED STRIP ═══ ?>
    <?php if ($isClosed): ?>
    <div class="ocp-warning-strip ocp-warning-strip--settled">
        <i class="fa fa-check-circle"></i>
        <span>
            <?php if ($isJudiciaryPaid): ?>
                هذا العقد <strong>قضائي مسدد</strong> — تم تسديد كامل المبلغ المستحق. جميع الإجراءات متوقفة.
            <?php elseif ($contract->status === 'finished'): ?>
                هذا العقد <strong>مسدد بالكامل</strong> — لا يمكن تنفيذ إجراءات عليه.
            <?php else: ?>
                هذا العقد <strong>ملغي</strong> — لا يمكن تنفيذ إجراءات عليه.
            <?php endif; ?>
        </span>
    </div>
    <?php endif; ?>

    <?php // ═══ WARNING STRIP ═══ ?>
    <?php if ($showWarningStrip && !$isClosed): ?>
    <?php if (in_array($contract->status, ['judiciary', 'legal_department'])): ?>
    <a href="#" class="ocp-warning-strip ocp-warning-strip--legal ocp-warning-strip--clickable" onclick="OCP.switchTab('judiciary');return false;" role="alert" aria-label="اضغط للانتقال لتبويب القضائي">
        <i class="fa fa-exclamation-triangle"></i>
        <span>هذا العقد في المرحلة القضائية — تطبق قيود خاصة على الإجراءات المتاحة</span>
        <i class="fa fa-arrow-left ocp-warning-strip__arrow"></i>
    </a>
    <?php else: ?>
    <div class="ocp-warning-strip" role="alert">
        <i class="fa fa-exclamation-triangle"></i>
        <span>تنبيه: مستوى المخاطر <?= $riskLevelArabic[$riskLevel] ?? 'مرتفع' ?> — <?= Html::encode($riskData['primary_reason'] ?? '') ?></span>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php // ═══ STATUS BAR ═══ ?>
    <div class="ocp-status-bar">
        <div class="ocp-status-bar__inner">
            <?php // Contract ID ?>
            <div class="ocp-status-bar__contract">
                <span class="ocp-status-bar__contract-id">#<?= $contract->id ?></span>
                <button class="ocp-status-bar__copy-btn" onclick="OCP.copyToClipboard('<?= $contract->id ?>')" title="نسخ رقم العقد">
                    <i class="fa fa-copy"></i>
                </button>
            </div>

            <div class="ocp-status-bar__divider"></div>

            <?php // تاريخ البيع (حقول العقد: Date_of_sale) ?>
            <div class="ocp-status-bar__meta" title="تاريخ إبرام عقد البيع / الشراء في النظام">
                <i class="fa fa-calendar-check-o ocp-status-bar__meta-icon" aria-hidden="true"></i>
                <span class="ocp-status-bar__meta-label">تاريخ البيع</span>
                <span class="ocp-status-bar__meta-value" dir="ltr" lang="en" translate="no"><?= Html::encode($purchaseDateLabel) ?></span>
            </div>

            <div class="ocp-status-bar__divider"></div>

            <?php // تاريخ أول قسط (حقول العقد: first_installment_date) ?>
            <div class="ocp-status-bar__meta" title="تاريخ أول قسط مستحق عند إنشاء العقد">
                <i class="fa fa-calendar ocp-status-bar__meta-icon" aria-hidden="true"></i>
                <span class="ocp-status-bar__meta-label">تاريخ أول قسط</span>
                <span class="ocp-status-bar__meta-value" dir="ltr" lang="en" translate="no"><?= Html::encode($firstInstDateLabel) ?></span>
            </div>

            <div class="ocp-status-bar__divider"></div>

            <?php // All contract parties ?>
            <div class="ocp-status-bar__customer" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                <?php
                $allParties = $contract->contractsCustomers ?? [];
                if (!empty($allParties)):
                    foreach ($allParties as $pi => $ccEntry):
                        $partyCust = $ccEntry->customer ?? null;
                        if (!$partyCust) continue;
                        $isClient = $ccEntry->customer_type === 'client';
                ?>
                    <?php if ($pi > 0): ?><span style="color:#CBD5E1;font-size:10px">|</span><?php endif; ?>
                    <span style="display:inline-flex;align-items:center;gap:3px">
                        <i class="fa <?= $isClient ? 'fa-user' : 'fa-shield' ?>" style="font-size:9px;color:<?= $isClient ? '#BE185D' : '#2563EB' ?>"></i>
                        <a href="javascript:void(0)" class="custmer-popup ocp-status-bar__customer-name" data-bs-target="#customerInfoModal" data-bs-toggle="modal" customer-id="<?= $partyCust->id ?>" title="<?= Html::encode($partyCust->name) ?> (<?= $isClient ? 'مشتري' : 'كفيل' ?>)" style="cursor:pointer;font-size:12px"><?= Html::encode(NameHelper::short($partyCust->name)) ?></a>
                        <?php $_jobModel = $partyCust->jobs; if ($_jobModel): ?>
                        <span style="font-size:10px;color:#64748B;background:#F1F5F9;padding:1px 6px;border-radius:6px;white-space:nowrap"><?= Html::encode($_jobModel->name) ?></span>
                        <?php endif; ?>
                    </span>
                <?php
                    endforeach;
                else:
                ?>
                    <span class="ocp-status-bar__customer-name"><?= Html::encode($customerName) ?></span>
                <?php endif; ?>
            </div>

            <div class="ocp-status-bar__divider"></div>

            <?php // Status ?>
            <span class="ocp-badge ocp-badge--<?= $statusBadge ?>"><?= Html::encode($statusLabel) ?></span>

            <div class="ocp-status-bar__divider"></div>

            <?php // Risk Badge ?>
            <div class="ocp-risk-badge ocp-risk-badge--<?= $riskLevel ?>">
                <span class="ocp-risk-badge__dot"></span>
                <?= $riskLevelArabic[$riskLevel] ?? 'غير محدد' ?>
            </div>

            <div class="ocp-status-bar__divider"></div>

            <?php // Assignee ?>
            <div class="ocp-assignee">
                <div class="ocp-assignee__avatar">
                    <i class="fa fa-user"></i>
                </div>
                <span class="ocp-assignee__name"><?= Html::encode($contract->followedBy ? $contract->followedBy->username : 'غير محدد') ?></span>
            </div>

            <div class="ocp-status-bar__divider"></div>

            <?php // Next Contract Buttons ?>
            <?php
            $nextID = $model->getNextContractID($contract_id);
            $nextIDForManager = $model->getNextContractIDForManager($contract_id);
            $targetNextId = Yii::$app->user->can('Manger') ? $nextIDForManager : $nextID;

            $reportIds = Yii::$app->session->get('followup_report_ids', []);
            $reportNextId = null;
            $reportPrevId = null;
            $currentIndex = false;
            if (!empty($reportIds)) {
                $currentIndex = array_search((int)$contract_id, array_map('intval', $reportIds));
                if ($currentIndex !== false) {
                    if (isset($reportIds[$currentIndex + 1])) {
                        $reportNextId = $reportIds[$currentIndex + 1];
                    }
                    if ($currentIndex > 0) {
                        $reportPrevId = $reportIds[$currentIndex - 1];
                    }
                }
            }
            ?>
            <?php
            $reportTotal = count($reportIds);
            $reportPos = ($currentIndex !== false && $currentIndex !== null) ? $currentIndex + 1 : null;
            ?>
            <?php if ($reportPos !== null): ?>
            <div style="display:flex;gap:4px;align-items:center">
                <?php if ($reportPrevId): ?>
                <a href="<?= Url::to(['panel', 'contract_id' => $reportPrevId]) ?>" class="ocp-next-contract-btn" title="العقد السابق حسب التقرير" style="background:#EFF6FF;color:#2563EB;border-color:#BFDBFE;padding:4px 8px">
                    <i class="fa fa-arrow-right"></i>
                </a>
                <?php endif; ?>
                <span style="font-size:10px;color:#64748B;white-space:nowrap;font-weight:600" title="موقعك في تقرير المتابعة"><?= $reportPos ?>/<?= $reportTotal ?></span>
                <?php if ($reportNextId): ?>
                <a href="<?= Url::to(['panel', 'contract_id' => $reportNextId]) ?>" class="ocp-next-contract-btn" title="العقد التالي حسب التقرير" style="background:#EFF6FF;color:#2563EB;border-color:#BFDBFE;padding:4px 8px">
                    <i class="fa fa-arrow-left"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($targetNextId > 0): ?>
            <a href="<?= Url::to(['panel', 'contract_id' => $targetNextId]) ?>" class="ocp-next-contract-btn" title="الانتقال للعقد التالي تسلسلياً">
                <i class="fa fa-arrow-left"></i> العقد التالي
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php // ═══ MAIN CONTENT ═══ ?>
    <div class="ocp-container" style="padding-top: var(--ocp-space-xl);">

        <?php // ═══ TWO COLUMN LAYOUT ═══ ?>
        <div class="ocp-grid-2col">

            <?php // ═══ LEFT COLUMN (Main) ═══ ?>
            <div>
                <?php // 1) TABS + TAB CONTENT — التبويبات ومحتواها معاً في الأعلى ?>
                <div class="ocp-section">
                    <div class="ocp-tabs" role="tablist" aria-label="تبويبات لوحة التحكم" style="flex-wrap:wrap;gap:4px">
                        <button class="ocp-tab" data-tab="timeline" role="tab" aria-selected="false" aria-controls="tab-timeline" id="btn-tab-timeline" onclick="OCP.switchTab('timeline')">
                            <i class="fa fa-clock-o"></i> السجل الزمني
                            <span class="ocp-tab__count"><?= count($timeline) ?></span>
                        </button>
                        <button class="ocp-tab" data-tab="kanban" role="tab" aria-selected="false" aria-controls="tab-kanban" id="btn-tab-kanban" onclick="OCP.switchTab('kanban')">
                            <i class="fa fa-columns"></i> سير العمل
                            <?php $totalTasks = 0; foreach ($kanbanData as $col) $totalTasks += $col['total']; ?>
                            <span class="ocp-tab__count"><?= $totalTasks ?></span>
                        </button>
                        <button class="ocp-tab" data-tab="financial" role="tab" aria-selected="false" aria-controls="tab-financial" id="btn-tab-financial" onclick="OCP.switchTab('financial')">
                            <i class="fa fa-money"></i> اللقطة المالية
                        </button>
                        <button class="ocp-tab" data-tab="phones" role="tab" aria-selected="false" aria-controls="tab-phones" id="btn-tab-phones" onclick="OCP.switchTab('phones')">
                            <i class="fa fa-phone"></i> أرقام الهواتف
                        </button>
                        <button class="ocp-tab" data-tab="payments" role="tab" aria-selected="false" aria-controls="tab-payments" id="btn-tab-payments" onclick="OCP.switchTab('payments')">
                            <i class="fa fa-credit-card"></i> الدفعات
                        </button>
                        <button class="ocp-tab" data-tab="settlements" role="tab" aria-selected="false" aria-controls="tab-settlements" id="btn-tab-settlements" onclick="OCP.switchTab('settlements')">
                            <i class="fa fa-balance-scale"></i> التسويات
                        </button>
                        <?php if ($hasCase): ?>
                        <?php
                        $_totalJudActions = 0;
                        foreach ($allJudiciaryData as $_cData) {
                            $_totalJudActions += count($_cData['actions'] ?? []);
                        }
                        ?>
                        <button class="ocp-tab" data-tab="judiciary-actions" role="tab" aria-selected="false" aria-controls="tab-judiciary-actions" id="btn-tab-judiciary-actions" onclick="OCP.switchTab('judiciary-actions')">
                            <i class="fa fa-gavel"></i> إجراءات قضائية
                            <?php if (count($allJudiciaryData) > 1): ?>
                            <span class="ocp-tab__count"><?= count($allJudiciaryData) ?> قضايا</span>
                            <?php endif; ?>
                            <?php if ($_totalJudActions > 0): ?>
                            <span class="ocp-tab__count"><?= $_totalJudActions ?></span>
                            <?php endif; ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php // TIMELINE TAB ?>
                    <div class="ocp-tab-content ocp-hidden" id="tab-timeline" role="tabpanel" aria-labelledby="btn-tab-timeline">
                        <?= $this->render('panel/_timeline', ['timeline' => $timeline]) ?>
                    </div>

                    <?php // KANBAN TAB ?>
                    <div class="ocp-tab-content ocp-hidden" id="tab-kanban" role="tabpanel" aria-labelledby="btn-tab-kanban">
                        <?= $this->render('panel/_kanban', ['kanbanData' => $kanbanData, 'contract' => $contract]) ?>
                    </div>

                    <?php // FINANCIAL TAB ?>
                    <div class="ocp-tab-content ocp-hidden" id="tab-financial" role="tabpanel" aria-labelledby="btn-tab-financial">
                        <?= $this->render('panel/_financial', ['financials' => $financials, 'settlementFinancials' => $settlementFinancials ?? null]) ?>
                    </div>

                    <?php // PHONE NUMBERS TAB (from old index) ?>
                    <div class="ocp-tab-content ocp-hidden" id="tab-phones" role="tabpanel" aria-labelledby="btn-tab-phones">
                        <div class="ocp-card" style="padding:var(--ocp-space-lg)">
                            <?= $this->render('partial/tabs/phone_numbers.php', [
                                'contractCalculations' => $contractCalculations,
                                'contract_id' => $contract_id,
                                'model' => $model,
                            ]) ?>
                        </div>
                    </div>

                    <?php // PAYMENTS TAB ?>
                    <div class="ocp-tab-content ocp-hidden" id="tab-payments" role="tabpanel" aria-labelledby="btn-tab-payments">
                        <?= $this->render('partial/tabs/payments.php', [
                            'contract_id' => $contract_id,
                            'model' => $model,
                        ]) ?>
                    </div>

                    <?php // SETTLEMENTS TAB — Cards ?>
                    <div class="ocp-tab-content ocp-hidden" id="tab-settlements" role="tabpanel" aria-labelledby="btn-tab-settlements">
                        <?= $this->render('partial/tabs/loan_scheduling.php', [
                            'contract_id' => $contract_id,
                            'model' => $model,
                            'contractCalculations' => $contractCalculations,
                        ]) ?>
                    </div>

                    <?php // JUDICIARY ACTIONS TAB — يظهر فقط إذا العقد عليه قضية ?>
                    <?php if ($hasCase): ?>
                    <div class="ocp-tab-content ocp-hidden" id="tab-judiciary-actions" role="tabpanel" aria-labelledby="btn-tab-judiciary-actions">
                        <?= $this->render('panel/_judiciary_tab', [
                            'contract_id' => $contract_id,
                            'contract' => $contract,
                            'allJudiciaryData' => $allJudiciaryData,
                            'model' => $model,
                        ]) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php // 2) COMMAND BAR — شريط الأوامر ?>
                <?php if (Permissions::can(Permissions::FOLLOWUP_CREATE) || Permissions::can(Permissions::FOLLOWUP_UPDATE)): ?>
                <div class="ocp-section">
                    <div class="ocp-command-bar">
                        <?php if ($isClosed): ?>
                            <div class="ocp-cmd-closed">
                                <i class="fa fa-lock"></i>
                                <span>هذا العقد <?= $isJudiciaryPaid ? 'قضائي مسدد' : ($contract->status === 'finished' ? 'منتهي' : 'ملغي') ?></span>
                            </div>
                            <?php if ($contract->status !== 'canceled'): ?>
                            <a class="ocp-cmd-btn ocp-cmd-btn--secondary" href="<?= Url::to(['printer', 'contract_id' => $contract_id]) ?>" target="_blank">
                                <i class="fa fa-print"></i> كشف حساب
                            </a>
                            <a class="ocp-cmd-btn ocp-cmd-btn--secondary" href="<?= Url::to(['clearance', 'contract_id' => $contract_id]) ?>" target="_blank">
                                <i class="fa fa-file-text-o"></i> براءة الذمة
                            </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="ocp-cmd-btn ocp-cmd-btn--call" onclick="OCP.openPanel('call')" title="C">
                                <i class="fa fa-phone"></i> تسجيل اتصال
                            </button>
                            <button class="ocp-cmd-btn ocp-cmd-btn--promise" onclick="OCP.openPanel('promise')" title="P">
                                <i class="fa fa-handshake-o"></i> وعد دفع
                            </button>
                            <button class="ocp-cmd-btn ocp-cmd-btn--sms" onclick="OCP.openPanel('sms')" title="S">
                                <i class="fa fa-comment"></i> إرسال تذكير
                            </button>
                            <button class="ocp-cmd-btn ocp-cmd-btn--visit" onclick="OCP.openPanel('visit')" title="V">
                                <i class="fa fa-car"></i> تسجيل زيارة
                            </button>

                            <?php // إنهاء المتابعة — الانتقال للعقد التالي حسب تقرير المتابعة ?>
                            <?php if ($reportNextId): ?>
                            <div class="ocp-cmd-divider"></div>
                            <a id="ocp-finish-follow-up" href="<?= Url::to(['panel', 'contract_id' => $reportNextId]) ?>" class="ocp-cmd-btn" style="background:#059669;color:#fff;border-color:#059669;font-weight:700;gap:6px" title="إنهاء المتابعة لهذا العقد والانتقال للعقد التالي في تقرير المتابعة (N)">
                                <i class="fa fa-check-circle"></i> إنهاء المتابعة
                                <span style="font-size:10px;opacity:.85;background:rgba(255,255,255,.2);padding:1px 6px;border-radius:8px"><?= ($reportPos ?? 0) + 1 ?>/<?= $reportTotal ?></span>
                            </a>
                            <?php elseif ($reportPos !== null && $reportNextId === null): ?>
                            <div class="ocp-cmd-divider"></div>
                            <span class="ocp-cmd-btn" style="background:#F0FDF4;color:#059669;border-color:#A7F3D0;font-weight:600;cursor:default;gap:6px" title="آخر عقد في قائمة تقرير المتابعة">
                                <i class="fa fa-flag-checkered"></i> آخر عقد في القائمة
                                <span style="font-size:10px;opacity:.85"><?= $reportPos ?>/<?= $reportTotal ?></span>
                            </span>
                            <?php endif; ?>

                            <div class="ocp-cmd-divider"></div>

                            <?php // Dropdown: إجراءات ?>
                            <div class="ocp-cmd-dropdown">
                                <button class="ocp-cmd-btn ocp-cmd-btn--secondary" onclick="OCP.toggleDropdown('dd-actions')">
                                    <i class="fa fa-cog"></i> إجراءات <i class="fa fa-caret-down ocp-cmd-caret"></i>
                                </button>
                                <div class="ocp-cmd-dropdown__menu" id="dd-actions">
                                    <?php if ($isLegal): ?>
                                        <?php $judiciaryModel = $judiciaryData['judiciary'] ?? null; ?>
                                        <button class="ocp-cmd-dropdown__item" onclick="<?= $judiciaryModel ? "window.open('" . Url::to(['/judiciary/judiciary/update', 'id' => $judiciaryModel->id, 'contract_id' => $contract_id]) . "', '_blank')" : "OCP.toast('لا يوجد ملف قضائي مسجل — يجب إنشاء قضية أولاً', 'warning')" ?>">
                                            <i class="fa fa-gavel"></i> فتح ملف القضية
                                        </button>
                                    <?php else: ?>
                                        <button class="ocp-cmd-dropdown__item" onclick="OCP.openPanel('legal')">
                                            <i class="fa fa-gavel"></i> تحويل للقضائي
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($hasCase): ?>
                                        <?php $judiciaryModel = $judiciaryData['judiciary'] ?? null; ?>
                                        <a class="ocp-cmd-dropdown__item" href="<?= $judiciaryModel ? Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/create-followup-judicary-custamer-action', 'contractID' => $contract_id]) : '#' ?>" role="modal-remote" data-pjax="0">
                                            <i class="fa fa-plus-circle"></i> إضافة إجراء قضائي
                                        </a>
                                    <?php endif; ?>
                                    <button class="ocp-cmd-dropdown__item" onclick="OCP.openPanel('review')">
                                        <i class="fa fa-user-circle"></i> طلب مراجعة مدير
                                    </button>
                                    <button class="ocp-cmd-dropdown__item" onclick="OCP.openPanel('note')">
                                        <i class="fa fa-sticky-note"></i> إضافة ملاحظة
                                    </button>
                                    <button class="ocp-cmd-dropdown__item" onclick="OCP.openPanel('freeze')">
                                        <i class="fa fa-pause-circle"></i> تجميد المتابعة
                                    </button>
                                    <div class="ocp-cmd-dropdown__divider"></div>
                                    <button class="ocp-cmd-dropdown__item" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('changeStatusModal')).show()">
                                        <i class="fa fa-exchange"></i> تغيير حالة العقد
                                    </button>
                                    <button class="ocp-cmd-dropdown__item" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settlementModal')).show()">
                                        <i class="fa fa-balance-scale"></i> إضافة تسوية
                                    </button>
                                </div>
                            </div>

                            <?php // Dropdown: مستندات ?>
                            <div class="ocp-cmd-dropdown">
                                <button class="ocp-cmd-btn ocp-cmd-btn--secondary" onclick="OCP.toggleDropdown('dd-docs')">
                                    <i class="fa fa-folder-open"></i> مستندات <i class="fa fa-caret-down ocp-cmd-caret"></i>
                                </button>
                                <div class="ocp-cmd-dropdown__menu" id="dd-docs">
                                    <a class="ocp-cmd-dropdown__item" href="<?= Url::to(['printer', 'contract_id' => $contract_id]) ?>" target="_blank">
                                        <i class="fa fa-print"></i> كشف حساب
                                    </a>
                                    <a class="ocp-cmd-dropdown__item" href="<?= Url::to(['clearance', 'contract_id' => $contract_id]) ?>" target="_blank">
                                        <i class="fa fa-file-text-o"></i> براءة الذمة
                                    </a>
                                    <button class="ocp-cmd-dropdown__item" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('customerImagesModal')).show()">
                                        <i class="fa fa-image"></i> صور العملاء
                                    </button>
                                    <button class="ocp-cmd-dropdown__item" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('auditModal')).show()">
                                        <i class="fa fa-check-square-o"></i> للتدقيق
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif ?>

                <?php // 3) SMART ALERTS — التنبيهات (hidden for closed contracts) ?>
                <?php if (!empty($alerts) && !$isClosed): ?>
                <div class="ocp-section">
                    <div class="ocp-alerts">
                        <?php foreach ($alerts as $alert): ?>
                        <div class="ocp-alert ocp-alert--<?= $alert['severity'] ?>">
                            <div class="ocp-alert__icon">
                                <i class="fa <?= $alert['icon'] ?>"></i>
                            </div>
                            <div class="ocp-alert__body">
                                <div class="ocp-alert__title"><?= Html::encode($alert['title']) ?></div>
                                <div class="ocp-alert__desc"><?= Html::encode($alert['description']) ?></div>
                            </div>
                            <?php if (!empty($alert['cta'])): ?>
                            <div class="ocp-alert__cta">
                                <button class="ocp-alert__cta-btn" data-action="<?= $alert['cta']['action'] ?>" onclick="OCP.handleAlertCTA(this)">
                                    <?= Html::encode($alert['cta']['label']) ?>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php // ═══ RIGHT COLUMN (AI + Sidebar) ═══ ?>
            <div>
                <?php // AI SUGGESTION PANEL ?>
                <?= $this->render('panel/_ai_suggestions', ['aiData' => $aiData, 'contract' => $contract, 'isClosed' => $isClosed, 'isJudiciaryPaid' => $isJudiciaryPaid]) ?>

                <?php
                // ═══ SOCIAL SECURITY STATEMENT CARD ═══
                // Renders only when the principal customer has a stored
                // SS statement. Best-effort lookup — failures are silenced
                // because this is a supplementary widget, not core data.
                if ($customer && $customer->id):
                    try {
                        $ssStatement = \backend\modules\customers\models\CustomerSsStatement::findCurrentForCustomer((int)$customer->id);
                        if ($ssStatement !== null) {
                            $ssStatementCount = \backend\modules\customers\models\CustomerSsStatement::countForCustomer((int)$customer->id);
                            echo $this->render('panel/_ss_summary', [
                                'statement'      => $ssStatement,
                                'customerId'     => (int)$customer->id,
                                'statementCount' => $ssStatementCount,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Yii::warning('SS sidebar render failed: ' . $e->getMessage(), __METHOD__);
                    }
                endif;
                ?>
            </div>

        </div>
    </div>

    <?php // ═══ NEXT CONTRACT (from old index) ═══ ?>
    <?php if ($model->next ?? null): ?>
    <div class="ocp-section" style="margin-top:var(--ocp-space-lg)">
        <div class="ocp-card" style="padding:var(--ocp-space-lg)">
            <?= $this->render('partial/next_contract.php', ['model' => $model, 'contract_id' => $contract_id]) ?>
        </div>
    </div>
    <?php endif; ?>

    <?php // ═══ SIDE PANELS (Hidden by default) ═══ ?>
    <div class="ocp-side-panel__overlay" id="ocp-overlay" onclick="OCP.closePanel()"></div>
    <?= $this->render('panel/_side_panels', ['contract' => $contract, 'customer' => $customer]) ?>

    <?php // ═══ OLD MODALS (Customer Info, Customer Images, Audit, Settlement, Change Status, SMS) ═══ ?>
    <?= $this->render('modals.php', ['contractCalculations' => $contractCalculations, 'contract_id' => $contract_id]) ?>

    <?php // ═══ AJAX CRUD MODAL (for phone numbers, settlements etc.) ═══ ?>
    <div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div style="text-align:center;padding:40px">
                        <i class="fa fa-spinner fa-spin" style="font-size:24px;color:var(--ty-clr-primary,#800020)"></i>
                    </div>
                </div>
                <div class="modal-footer"></div>
            </div>
        </div>
    </div>

    <?php
    $this->registerJs(<<<'JS'
    OCP.restoreTab();

    // Keyboard shortcut: N → إنهاء المتابعة (next contract)
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT' || e.target.isContentEditable) return;
        if (e.key === 'n' || e.key === 'N') {
            var btn = document.getElementById('ocp-finish-follow-up');
            if (btn) { e.preventDefault(); btn.click(); }
        }
    });

    var _ocpRefreshPending = false;
    var _ocpRefreshTimer = null;

    window.ocpRefreshTabs = function() {
        if (_ocpRefreshTimer) clearTimeout(_ocpRefreshTimer);
        _ocpRefreshTimer = setTimeout(function() {
            _ocpRefreshTimer = null;
            if (_ocpRefreshPending) return;
            _ocpRefreshPending = true;
            var activeTab = OCP.getActiveTab();
            var xhr = new XMLHttpRequest();
            xhr.open('GET', location.href);
            xhr.onload = function() {
                _ocpRefreshPending = false;
                if (xhr.status !== 200) return;
                var doc = new DOMParser().parseFromString(xhr.responseText, 'text/html');

                var tabs = ['timeline','kanban','financial','phones','payments','settlements','judiciary-actions'];
                for (var i = 0; i < tabs.length; i++) {
                    var id = 'tab-' + tabs[i];
                    var newEl = doc.getElementById(id);
                    var curEl = document.getElementById(id);
                    if (newEl && curEl) curEl.innerHTML = newEl.innerHTML;
                }

                var newBtns = doc.querySelectorAll('.ocp-tabs .ocp-tab');
                var curBtns = document.querySelectorAll('.ocp-tabs .ocp-tab');
                for (var j = 0; j < newBtns.length && j < curBtns.length; j++) {
                    var nc = newBtns[j].querySelector('.ocp-tab__count');
                    var cc = curBtns[j].querySelector('.ocp-tab__count');
                    if (nc && cc) cc.textContent = nc.textContent;
                }

                if (activeTab) OCP.switchTab(activeTab);
            };
            xhr.onerror = function() { _ocpRefreshPending = false; };
            xhr.send();
        }, 150);
    };

JS
    , $this::POS_READY);
    ?>

    <?php // ═══ TOAST NOTIFICATION ═══ ?>
    <div class="ocp-toast" id="ocp-toast">
        <i class="fa" id="ocp-toast-icon"></i>
        <span id="ocp-toast-message"></span>
    </div>
</div>
