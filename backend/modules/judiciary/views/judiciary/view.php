<?php
use yii\helpers\Html;
use yii\helpers\Url;
use backend\modules\judiciary\models\Judiciary;
use backend\models\JudiciarySeizedAsset;
use backend\models\JudiciaryDeadline;
use backend\modules\diwan\models\DiwanCorrespondence;

$this->title = 'ملف القضية #' . $model->judiciary_number;
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/judiciary-v2.css?v=' . time());

$court = $model->court;
$type = $model->type;
$lawyer = $model->lawyer;
$contract = $model->contract;
$customers = $model->customers;
$guarantors = $model->customersGuarantor;

$stageList = Judiciary::getStageList();
$stageOrder = Judiciary::STAGE_ORDER;
$furthestRank = Judiciary::getStageRank($model->furthest_stage);
$bottleneckRank = Judiciary::getStageRank($model->bottleneck_stage);

$statusMap = [
    'open' => ['label' => 'مفتوحة', 'color' => '#2563EB', 'bg' => '#EFF6FF', 'icon' => 'fa-folder-open'],
    'closed' => ['label' => 'مغلقة', 'color' => '#16A34A', 'bg' => '#F0FDF4', 'icon' => 'fa-check-circle'],
    'suspended' => ['label' => 'معلقة', 'color' => '#F59E0B', 'bg' => '#FFFBEB', 'icon' => 'fa-pause-circle'],
    'archived' => ['label' => 'مؤرشفة', 'color' => '#64748B', 'bg' => '#F8FAFC', 'icon' => 'fa-archive'],
];
$cs = $statusMap[$model->case_status] ?? $statusMap['open'];

$contractTypes = ['normal' => 'عادي', 'solidarity' => 'تضامني'];
$contractStatuses = [
    'active' => ['label' => 'نشط', 'color' => '#16A34A', 'bg' => '#F0FDF4'],
    'pending' => ['label' => 'معلق', 'color' => '#F59E0B', 'bg' => '#FFFBEB'],
    'finished' => ['label' => 'منتهي', 'color' => '#64748B', 'bg' => '#F8FAFC'],
    'canceled' => ['label' => 'ملغي', 'color' => '#EF4444', 'bg' => '#FEF2F2'],
    'legal_department' => ['label' => 'الشؤون القانونية', 'color' => '#8B5CF6', 'bg' => '#F5F3FF'],
    'judiciary' => ['label' => 'قضائي', 'color' => '#2563EB', 'bg' => '#EFF6FF'],
    'settlement' => ['label' => 'تسوية', 'color' => '#0D9488', 'bg' => '#F0FDFA'],
    'refused' => ['label' => 'مرفوض', 'color' => '#DC2626', 'bg' => '#FEF2F2'],
];

$natureStyles = [
    'request'    => ['icon' => 'fa-file-text-o', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'طلب إجرائي'],
    'document'   => ['icon' => 'fa-file-o',      'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'label' => 'كتاب / مذكرة'],
    'doc_status' => ['icon' => 'fa-exchange',     'color' => '#EA580C', 'bg' => '#FFF7ED', 'label' => 'حالة كتاب'],
    'process'    => ['icon' => 'fa-cog',          'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'إجراء إداري'],
];
$statusColors = ['pending' => '#F59E0B', 'approved' => '#10B981', 'rejected' => '#EF4444'];
$statusLabels = ['pending' => 'معلق', 'approved' => 'موافقة', 'rejected' => 'مرفوض'];

$assetTypeLabels = JudiciarySeizedAsset::getAssetTypeLabels();
$assetStatusLabels = JudiciarySeizedAsset::getStatusLabels();
$deadlineTypeLabels = JudiciaryDeadline::getTypeLabels();
$deadlineStatusLabels = JudiciaryDeadline::getStatusLabels();
$corrTypeLabels = DiwanCorrespondence::getCommunicationTypeLabels();
$corrStatusLabels = DiwanCorrespondence::getStatusLabels();
?>

<style>
.jv-page{direction:rtl;font-family:'Tajawal','Segoe UI',sans-serif}
.jv-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.jv-title{font-size:22px;font-weight:700;color:#1E293B;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.jv-title i{color:#3B82F6}
.jv-status{padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px}
.jv-actions{display:flex;gap:8px;flex-wrap:wrap}
.jv-actions .btn{border-radius:8px;font-size:13px;font-weight:600;padding:8px 18px}

.jv-info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:24px}
.jv-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:20px;transition:box-shadow .2s}
.jv-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.06)}
.jv-card-title{font-size:13px;font-weight:700;color:#64748B;margin-bottom:14px;display:flex;align-items:center;gap:8px;border-bottom:1px solid #F1F5F9;padding-bottom:10px}
.jv-card-title i{color:#3B82F6;font-size:15px}
.jv-field{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed #F1F5F9}
.jv-field:last-child{border-bottom:none}
.jv-label{color:#94A3B8;font-size:12px;font-weight:500}
.jv-value{color:#1E293B;font-size:13px;font-weight:600;text-align:left;direction:ltr}

.jv-parties{margin-bottom:24px}
.jv-party-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-top:12px}
.jv-party-chip{background:#fff;border:1px solid #E2E8F0;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;transition:all .2s}
.jv-party-chip:hover{border-color:#3B82F6;box-shadow:0 2px 8px rgba(59,130,246,.1)}
.jv-party-chip .jv-party-icon{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0}
.jv-party-chip .jv-party-name{font-weight:600;font-size:13px;color:#1E293B}
.jv-party-chip .jv-party-role{font-size:11px;color:#94A3B8}

.jv-section-title{font-size:16px;font-weight:700;color:#1E293B;display:flex;align-items:center;gap:8px;margin-bottom:4px}

/* ═══ جدول الإجراءات الجديد ═══ */
.jv-actions-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden}
.jv-actions-header{padding:16px 20px;border-bottom:1px solid #E2E8F0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;background:#FAFBFC}
.jv-actions-list{padding:0}
.jv-action-row{display:grid;grid-template-columns:40px 1fr auto;gap:0;border-bottom:1px solid #F1F5F9;transition:background .15s}
.jv-action-row:last-child{border-bottom:none}
.jv-action-row:hover{background:#F8FAFC}

.jv-action-num{display:flex;align-items:center;justify-content:center;padding:16px 8px;color:#CBD5E1;font-size:12px;font-weight:600;border-left:1px solid #F1F5F9}
.jv-action-body{padding:14px 16px;display:flex;flex-direction:column;gap:6px;min-width:0}
.jv-action-top{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.jv-action-name{font-weight:700;font-size:13px;color:#1E293B;display:flex;align-items:center;gap:6px}
.jv-action-name i{font-size:13px}
.jv-action-badge{padding:2px 10px;border-radius:6px;font-size:10px;font-weight:600;white-space:nowrap}
.jv-action-meta{display:flex;align-items:center;gap:16px;flex-wrap:wrap;font-size:12px;color:#94A3B8}
.jv-action-meta i{margin-left:4px;font-size:11px}
.jv-action-note{font-size:12px;color:#64748B;background:#F8FAFC;padding:6px 10px;border-radius:6px;margin-top:4px;line-height:1.5;max-width:100%;word-wrap:break-word}
.jv-action-tools{display:flex;align-items:center;padding:14px 12px}

.jv-action-empty{text-align:center;padding:50px 20px;color:#94A3B8}
.jv-action-empty i{font-size:40px;display:block;margin-bottom:12px;color:#E2E8F0}

.jca-act-wrap{position:relative;display:inline-block}
.jca-act-trigger{background:none;border:1px solid #E2E8F0;border-radius:8px;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;font-size:14px;transition:all .15s;padding:0}
.jca-act-trigger:hover{background:#F1F5F9;color:#1E293B;border-color:#CBD5E1}
.jca-act-menu{display:none;position:fixed;min-width:160px;background:#fff;border:1px solid #E2E8F0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:99999;padding:4px 0;direction:rtl;font-size:12px}
.jca-act-wrap.open .jca-act-menu{display:block}
.jca-act-menu a{display:flex;align-items:center;gap:8px;padding:7px 14px;color:#334155;text-decoration:none;white-space:nowrap;transition:background .12s}
.jca-act-menu a:hover{background:#F1F5F9;color:#1D4ED8}
.jca-act-menu a i{width:16px;text-align:center}
.jca-act-divider{height:1px;background:#E2E8F0;margin:4px 0}

.jv-pager{padding:12px 20px;border-top:1px solid #F1F5F9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:12px;color:#94A3B8}

@media(max-width:768px){
    .jv-header{flex-direction:column;align-items:flex-start}
    .jv-info-grid{grid-template-columns:1fr}
    .jv-action-row{grid-template-columns:1fr;gap:0}
    .jv-action-num{display:none}
    .jv-action-tools{justify-content:flex-end;padding:0 16px 12px}
    .jv-party-grid{grid-template-columns:1fr}
    .jv-action-meta{gap:10px}
}
</style>

<div class="jv-page">

    <div class="jv-header">
        <div>
            <div class="jv-title">
                <i class="fa fa-gavel"></i>
                <?= $this->title ?>
                <span class="jv-status" style="background:<?= $cs['bg'] ?>;color:<?= $cs['color'] ?>">
                    <i class="fa <?= $cs['icon'] ?>"></i> <?= $cs['label'] ?>
                </span>
            </div>
            <?php if ($model->year): ?>
                <span style="color:#94A3B8;font-size:13px;margin-right:32px">السنة: <?= Html::encode($model->year) ?></span>
            <?php endif; ?>
        </div>
        <div class="jv-actions">
            <?= Html::a('<i class="fa fa-pencil"></i> تعديل القضية', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('<i class="fa fa-arrow-right"></i> القضايا', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <div class="jv-info-grid">
        <div class="jv-card">
            <div class="jv-card-title"><i class="fa fa-info-circle"></i> معلومات القضية</div>
            <div class="jv-field">
                <span class="jv-label">رقم القضية</span>
                <span class="jv-value"><?= Html::encode($model->judiciary_number ?: '—') ?></span>
            </div>
            <div class="jv-field">
                <span class="jv-label">نوع القضية</span>
                <span class="jv-value"><?= Html::encode($type ? $type->name : '—') ?></span>
            </div>
            <div class="jv-field">
                <span class="jv-label">تاريخ الورود</span>
                <span class="jv-value"><?= Html::encode($model->income_date ?: '—') ?></span>
            </div>
            <div class="jv-field">
                <span class="jv-label">آخر طلب إجرائي</span>
                <span class="jv-value"><?= Html::encode(($lastRequestDate ?? null) ?: '—') ?></span>
            </div>
        </div>

        <div class="jv-card">
            <div class="jv-card-title"><i class="fa fa-university"></i> المحكمة والمحامي</div>
            <div class="jv-field">
                <span class="jv-label">المحكمة</span>
                <span class="jv-value"><?= Html::encode($court ? $court->name : '—') ?></span>
            </div>
            <div class="jv-field">
                <span class="jv-label">المحامي</span>
                <span class="jv-value"><?= Html::encode($lawyer ? $lawyer->name : '—') ?></span>
            </div>
            <div class="jv-field">
                <span class="jv-label">أتعاب المحامي</span>
                <span class="jv-value"><?= number_format($model->lawyer_cost ?? 0, 2) ?></span>
            </div>
            <div class="jv-field">
                <span class="jv-label">رسوم القضية</span>
                <span class="jv-value"><?= number_format($model->case_cost ?? 0, 2) ?></span>
            </div>
        </div>

        <div class="jv-card">
            <div class="jv-card-title"><i class="fa fa-file-text-o"></i> العقد</div>
            <?php if ($contract): ?>
                <div class="jv-field">
                    <span class="jv-label">رقم العقد</span>
                    <span class="jv-value">#<?= $contract->id ?></span>
                </div>
                <div class="jv-field">
                    <span class="jv-label">نوع العقد</span>
                    <span class="jv-value"><?= $contractTypes[$contract->type] ?? Html::encode($contract->type ?? '—') ?></span>
                </div>
                <div class="jv-field">
                    <span class="jv-label">قيمة العقد</span>
                    <span class="jv-value"><?= number_format($contract->total_value ?? 0, 2) ?></span>
                </div>
                <div class="jv-field">
                    <span class="jv-label">حالة العقد</span>
                    <?php $cst = $contractStatuses[$contract->status] ?? null; ?>
                    <span class="jv-value">
                        <?php if ($cst): ?>
                            <span style="padding:2px 10px;border-radius:6px;font-size:11px;font-weight:600;background:<?= $cst['bg'] ?>;color:<?= $cst['color'] ?>"><?= $cst['label'] ?></span>
                        <?php else: ?>
                            <?= Html::encode($contract->status ?? '—') ?>
                        <?php endif; ?>
                    </span>
                </div>
            <?php else: ?>
                <div style="text-align:center;color:#94A3B8;padding:20px">
                    <i class="fa fa-inbox" style="font-size:24px;display:block;margin-bottom:8px"></i>
                    لا يوجد عقد مرتبط
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($customers) || !empty($guarantors)): ?>
    <div class="jv-parties">
        <div class="jv-section-title"><i class="fa fa-users" style="color:#3B82F6"></i> أطراف القضية</div>

        <?php if (!empty($customers)): ?>
            <p style="font-size:12px;color:#64748B;margin:8px 0 4px;font-weight:600">العملاء</p>
            <div class="jv-party-grid">
                <?php foreach ($customers as $c): ?>
                    <div class="jv-party-chip">
                        <div class="jv-party-icon" style="background:#EFF6FF;color:#2563EB">
                            <?= mb_substr($c->name ?? '?', 0, 1) ?>
                        </div>
                        <div>
                            <div class="jv-party-name"><?= Html::encode($c->name) ?></div>
                            <div class="jv-party-role">عميل</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($guarantors)): ?>
            <p style="font-size:12px;color:#64748B;margin:14px 0 4px;font-weight:600">الكفلاء</p>
            <div class="jv-party-grid">
                <?php foreach ($guarantors as $g): ?>
                    <div class="jv-party-chip">
                        <div class="jv-party-icon" style="background:#FFF7ED;color:#EA580C">
                            <?= mb_substr($g->name ?? '?', 0, 1) ?>
                        </div>
                        <div>
                            <div class="jv-party-name"><?= Html::encode($g->name) ?></div>
                            <div class="jv-party-role">كفيل</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ═══ Workflow Stage Progress Bar ═══ -->
    <?php if ($model->furthest_stage || !empty($defendantStages)): ?>
    <div class="jv-card" style="margin-bottom:24px">
        <div class="jv-card-title"><i class="fa fa-tasks"></i> مراحل سير القضية</div>
        <div class="jv-stage-bar">
            <?php foreach ($stageOrder as $i => $stageKey):
                $label = $stageList[$stageKey] ?? $stageKey;
                $rank = $i;
                $isCurrent = ($stageKey === $model->furthest_stage);
                $isCompleted = ($furthestRank >= 0 && $rank < $furthestRank);
                $isBottleneck = ($stageKey === $model->bottleneck_stage && $model->furthest_stage !== $model->bottleneck_stage);
                $stepClass = 'jv-stage-step';
                if ($isCurrent) $stepClass .= ' current';
                elseif ($isCompleted) $stepClass .= ' completed';
                if ($isBottleneck) $stepClass .= ' bottleneck';
            ?>
            <div class="<?= $stepClass ?>">
                <div class="jv-stage-dot">
                    <?php if ($isCompleted): ?>
                        <i class="fa fa-check"></i>
                    <?php elseif ($isCurrent): ?>
                        <i class="fa fa-circle"></i>
                    <?php elseif ($isBottleneck): ?>
                        <i class="fa fa-exclamation"></i>
                    <?php else: ?>
                        <span><?= $i + 1 ?></span>
                    <?php endif; ?>
                </div>
                <div class="jv-stage-label"><?= $label ?></div>
                <?php if ($isBottleneck): ?>
                    <div class="jv-stage-hint">عنق الزجاجة</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($model->furthest_stage && $model->bottleneck_stage): ?>
        <div style="display:flex;gap:20px;margin-top:16px;flex-wrap:wrap;font-size:12px;padding:12px 16px;background:#F8FAFC;border-radius:8px">
            <div style="display:flex;align-items:center;gap:6px">
                <span style="width:10px;height:10px;border-radius:50%;background:#2563EB;display:inline-block"></span>
                <span style="color:#64748B">أبعد مرحلة:</span>
                <strong style="color:#1E293B"><?= Judiciary::getStageLabel($model->furthest_stage) ?></strong>
            </div>
            <?php if ($model->furthest_stage !== $model->bottleneck_stage): ?>
            <div style="display:flex;align-items:center;gap:6px">
                <span style="width:10px;height:10px;border-radius:50%;background:#F59E0B;display:inline-block"></span>
                <span style="color:#64748B">عنق الزجاجة:</span>
                <strong style="color:#92400E"><?= Judiciary::getStageLabel($model->bottleneck_stage) ?></strong>
            </div>
            <?php endif; ?>
            <div style="display:flex;align-items:center;gap:6px">
                <span style="color:#64748B">الحالة:</span>
                <strong style="color:#1E293B"><?= $model->getOverallStatus() ?></strong>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ═══ Per-Defendant Stage Cards ═══ -->
    <?php if (!empty($defendantStages)): ?>
    <div style="margin-bottom:24px">
        <div class="jv-section-title"><i class="fa fa-user-circle" style="color:#8B5CF6"></i> مراحل المدعى عليهم</div>
        <div class="jv-party-grid" style="margin-top:12px">
            <?php foreach ($defendantStages as $ds):
                $custName = ($ds->customer) ? $ds->customer->name : ('عميل #' . $ds->customer_id);
                $stageLabel = Judiciary::getStageLabel($ds->current_stage);
                $dsRank = Judiciary::getStageRank($ds->current_stage);
                if ($dsRank >= 8) { $badgeBg = '#D1FAE5'; $badgeColor = '#065F46'; }
                elseif ($dsRank >= 5) { $badgeBg = '#DBEAFE'; $badgeColor = '#1E40AF'; }
                else { $badgeBg = '#FEF3C7'; $badgeColor = '#92400E'; }
            ?>
            <div class="jv-party-chip" style="flex-direction:column;align-items:flex-start;gap:8px;padding:14px 16px">
                <div style="display:flex;align-items:center;gap:8px;width:100%">
                    <div class="jv-party-icon" style="background:#F5F3FF;color:#7C3AED;width:32px;height:32px;font-size:12px">
                        <?= mb_substr($custName, 0, 1) ?>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div class="jv-party-name" style="font-size:12px"><?= Html::encode($custName) ?></div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:6px;width:100%">
                    <span style="padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600;background:<?= $badgeBg ?>;color:<?= $badgeColor ?>"><?= $stageLabel ?></span>
                    <?php if ($ds->stage_updated_at): ?>
                        <span style="font-size:10px;color:#94A3B8"><i class="fa fa-clock-o"></i> <?= Yii::$app->formatter->asRelativeTime($ds->stage_updated_at) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ Active Deadlines ═══ -->
    <?php if (!empty($activeDeadlines)): ?>
    <div class="jv-card" style="margin-bottom:24px">
        <div class="jv-card-title"><i class="fa fa-clock-o" style="color:#DC2626"></i> المواعيد النهائية النشطة
            <span style="background:#FEE2E2;color:#991B1B;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;margin-right:8px"><?= count($activeDeadlines) ?></span>
        </div>
        <div class="jv-deadline-grid">
            <?php foreach ($activeDeadlines as $dl):
                $dlStatus = $dl->status ?? 'pending';
                $dlColors = [
                    'expired' => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'color' => '#991B1B', 'icon' => 'fa-exclamation-circle'],
                    'approaching' => ['bg' => '#FFFBEB', 'border' => '#FDE68A', 'color' => '#92400E', 'icon' => 'fa-warning'],
                    'pending' => ['bg' => '#F8FAFC', 'border' => '#E2E8F0', 'color' => '#64748B', 'icon' => 'fa-hourglass-half'],
                    'completed' => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'color' => '#166534', 'icon' => 'fa-check-circle'],
                ];
                $dc = $dlColors[$dlStatus] ?? $dlColors['pending'];
                $typeLabel = $deadlineTypeLabels[$dl->deadline_type] ?? $dl->deadline_type;
                $statusLabel = $deadlineStatusLabels[$dlStatus] ?? $dlStatus;
                $daysRemaining = $dl->deadline_date ? (int)((strtotime($dl->deadline_date) - time()) / 86400) : null;
            ?>
            <div class="jv-deadline-card" style="background:<?= $dc['bg'] ?>;border:1px solid <?= $dc['border'] ?>">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                    <div style="display:flex;align-items:center;gap:6px">
                        <i class="fa <?= $dc['icon'] ?>" style="color:<?= $dc['color'] ?>;font-size:14px"></i>
                        <span style="font-weight:700;font-size:12px;color:<?= $dc['color'] ?>"><?= Html::encode($typeLabel) ?></span>
                    </div>
                    <span style="padding:2px 8px;border-radius:4px;font-size:10px;font-weight:600;background:<?= $dc['color'] ?>15;color:<?= $dc['color'] ?>"><?= $statusLabel ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px">
                    <span style="color:#64748B"><i class="fa fa-calendar"></i> <?= Html::encode($dl->deadline_date ?: '—') ?></span>
                    <?php if ($daysRemaining !== null): ?>
                        <span style="font-weight:700;color:<?= $dc['color'] ?>">
                            <?php if ($daysRemaining < 0): ?>
                                متأخر <?= abs($daysRemaining) ?> يوم
                            <?php elseif ($daysRemaining === 0): ?>
                                اليوم!
                            <?php else: ?>
                                باقي <?= $daysRemaining ?> يوم
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($dl->notes)): ?>
                    <div style="font-size:11px;color:#64748B;margin-top:6px;background:rgba(255,255,255,.6);padding:4px 8px;border-radius:4px"><?= Html::encode($dl->notes) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ Seized Assets ═══ -->
    <?php if (!empty($seizedAssets)): ?>
    <div class="jv-card" style="margin-bottom:24px">
        <div class="jv-card-title"><i class="fa fa-lock" style="color:#7C3AED"></i> الأصول المحجوزة
            <span style="background:#F5F3FF;color:#7C3AED;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;margin-right:8px"><?= count($seizedAssets) ?></span>
        </div>
        <div class="jv-asset-list">
            <?php foreach ($seizedAssets as $asset):
                $atLabel = $assetTypeLabels[$asset->asset_type] ?? $asset->asset_type;
                $asLabel = $assetStatusLabels[$asset->status] ?? $asset->status;
                $assetStatusColors = [
                    'seizure_requested' => ['bg' => '#FEF3C7', 'color' => '#92400E'],
                    'seized' => ['bg' => '#FEE2E2', 'color' => '#991B1B'],
                    'valued' => ['bg' => '#EDE9FE', 'color' => '#6D28D9'],
                    'auction_requested' => ['bg' => '#FFF7ED', 'color' => '#C2410C'],
                    'auctioned' => ['bg' => '#DBEAFE', 'color' => '#1E40AF'],
                    'released' => ['bg' => '#D1FAE5', 'color' => '#065F46'],
                ];
                $asc = $assetStatusColors[$asset->status] ?? ['bg' => '#F1F5F9', 'color' => '#64748B'];
            ?>
            <div class="jv-asset-row">
                <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0">
                    <div style="width:36px;height:36px;border-radius:8px;background:#F5F3FF;color:#7C3AED;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0">
                        <i class="fa <?= ($asset->asset_type === 'vehicle') ? 'fa-car' : (($asset->asset_type === 'real_estate') ? 'fa-building' : (($asset->asset_type === 'bank_account') ? 'fa-bank' : 'fa-cube')) ?>"></i>
                    </div>
                    <div style="min-width:0;flex:1">
                        <div style="font-weight:600;font-size:12px;color:#1E293B"><?= Html::encode($atLabel) ?></div>
                        <?php if ($asset->description): ?>
                            <div style="font-size:11px;color:#64748B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= Html::encode($asset->description) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
                    <?php if ($asset->amount): ?>
                        <span style="font-weight:700;font-size:12px;color:#1E293B;direction:ltr"><?= number_format($asset->amount, 2) ?></span>
                    <?php endif; ?>
                    <span style="padding:3px 10px;border-radius:6px;font-size:10px;font-weight:600;background:<?= $asc['bg'] ?>;color:<?= $asc['color'] ?>"><?= $asLabel ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ Correspondence ═══ -->
    <?php if (!empty($correspondences)): ?>
    <div class="jv-card" style="margin-bottom:24px">
        <div class="jv-card-title"><i class="fa fa-envelope" style="color:#0D9488"></i> المراسلات
            <span style="background:#F0FDFA;color:#0D9488;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;margin-right:8px"><?= count($correspondences) ?></span>
        </div>
        <?php
        $corrByType = ['all' => $correspondences, 'notification' => [], 'outgoing_letter' => [], 'incoming_response' => []];
        foreach ($correspondences as $c) {
            $ct = $c->communication_type;
            if (isset($corrByType[$ct])) $corrByType[$ct][] = $c;
        }
        $filterTabs = [
            'all' => ['label' => 'الكل', 'icon' => 'fa-list', 'count' => count($correspondences)],
            'notification' => ['label' => 'تبليغات', 'icon' => 'fa-bell', 'count' => count($corrByType['notification'])],
            'outgoing_letter' => ['label' => 'كتب صادرة', 'icon' => 'fa-paper-plane', 'count' => count($corrByType['outgoing_letter'])],
            'incoming_response' => ['label' => 'ردود واردة', 'icon' => 'fa-reply', 'count' => count($corrByType['incoming_response'])],
        ];
        ?>
        <div style="display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap">
            <?php foreach ($filterTabs as $fKey => $fTab): ?>
                <button type="button" class="jv-corr-filter <?= $fKey === 'all' ? 'active' : '' ?>" data-filter="<?= $fKey ?>"
                        style="border:1px solid #E2E8F0;background:<?= $fKey === 'all' ? '#0D9488' : '#fff' ?>;color:<?= $fKey === 'all' ? '#fff' : '#64748B' ?>;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:4px;transition:all .2s">
                    <i class="fa <?= $fTab['icon'] ?>"></i> <?= $fTab['label'] ?>
                    <?php if ($fTab['count'] > 0): ?>
                        <span style="background:rgba(255,255,255,.2);padding:0 6px;border-radius:10px;font-size:10px"><?= $fTab['count'] ?></span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="jv-corr-list">
            <?php foreach ($correspondences as $corr):
                $ctLabel = $corrTypeLabels[$corr->communication_type] ?? $corr->communication_type;
                $csLabel = $corrStatusLabels[$corr->status] ?? $corr->status;
                $corrStatusClr = [
                    'draft' => '#94A3B8', 'sent' => '#2563EB', 'delivered' => '#16A34A',
                    'responded' => '#0D9488', 'closed' => '#64748B',
                ];
                $cClr = $corrStatusClr[$corr->status] ?? '#64748B';
                $corrIcons = ['notification' => 'fa-bell', 'outgoing_letter' => 'fa-paper-plane', 'incoming_response' => 'fa-reply'];
            ?>
            <div class="jv-corr-item" data-corr-type="<?= Html::encode($corr->communication_type) ?>">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
                    <i class="fa <?= $corrIcons[$corr->communication_type] ?? 'fa-envelope' ?>" style="color:#0D9488;font-size:12px"></i>
                    <span style="font-weight:600;font-size:12px;color:#1E293B"><?= Html::encode($corr->purpose ?: $ctLabel) ?></span>
                    <span style="padding:2px 8px;border-radius:4px;font-size:10px;font-weight:600;background:<?= $cClr ?>15;color:<?= $cClr ?>"><?= $csLabel ?></span>
                </div>
                <div style="display:flex;gap:16px;font-size:11px;color:#94A3B8">
                    <?php if ($corr->correspondence_date): ?>
                        <span><i class="fa fa-calendar"></i> <?= Html::encode($corr->correspondence_date) ?></span>
                    <?php endif; ?>
                    <?php if ($corr->recipient_type): ?>
                        <?php
                        $recipientLabels = [
                            'defendant' => 'المدعى عليه', 'bank' => 'البنك',
                            'employer' => 'جهة العمل', 'administrative' => 'جهة إدارية',
                        ];
                        ?>
                        <span><i class="fa fa-user"></i> <?= $recipientLabels[$corr->recipient_type] ?? Html::encode($corr->recipient_type) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?= Html::a('<i class="fa fa-list"></i> عرض جميع المراسلات', ['/diwan/diwan/correspondence-index', 'DiwanCorrespondenceSearch[related_record_id]' => $model->id, 'DiwanCorrespondenceSearch[related_module]' => 'judiciary'], [
            'class' => 'btn btn-sm btn-default',
            'style' => 'margin-top:12px;border-radius:8px;font-size:12px;font-weight:600',
        ]) ?>
    </div>
    <?php $this->registerJs("
        document.querySelectorAll('.jv-corr-filter').forEach(function(btn){
            btn.addEventListener('click', function(){
                document.querySelectorAll('.jv-corr-filter').forEach(function(b){ b.style.background='#fff'; b.style.color='#64748B'; b.classList.remove('active'); });
                this.style.background='#0D9488'; this.style.color='#fff'; this.classList.add('active');
                var f = this.getAttribute('data-filter');
                document.querySelectorAll('.jv-corr-item').forEach(function(item){
                    item.style.display = (f === 'all' || item.getAttribute('data-corr-type') === f) ? '' : 'none';
                });
            });
        });
    "); ?>
    <?php endif; ?>

    <!-- ═══ Action Buttons ═══ -->
    <div style="margin-bottom:24px;display:flex;gap:8px;flex-wrap:wrap">
        <?= Html::a('<i class="fa fa-file-text"></i> إنشاء طلب إجرائي', ['generate-request', 'id' => $model->id], [
            'class' => 'btn btn-primary',
            'style' => 'border-radius:8px;font-size:13px;font-weight:600;padding:10px 24px',
        ]) ?>
        <?= Html::a('<i class="fa fa-clock-o"></i> لوحة المواعيد', ['deadline-dashboard-view'], [
            'class' => 'btn btn-warning',
            'style' => 'border-radius:8px;font-size:13px;font-weight:600;padding:10px 24px;color:#fff',
        ]) ?>
        <button type="button" class="btn btn-info jv-timeline-trigger"
                data-url="<?= Url::to(['case-timeline', 'id' => $model->id]) ?>"
                data-case-label="<?= Html::encode($model->judiciary_number ?: '#' . $model->id) ?>"
                style="border-radius:8px;font-size:13px;font-weight:600;padding:10px 24px;color:#fff">
            <i class="fa fa-history"></i> الجدول الزمني
        </button>
    </div>

    <?php if (isset($actionsDP)):
        $actions = $actionsDP->getModels();
        $totalCount = $actionsDP->getTotalCount();
    ?>
    <div class="jv-actions-card">
        <div class="jv-actions-header">
            <div class="jv-section-title" style="margin:0">
                <i class="fa fa-list-ul" style="color:#8B5CF6"></i> إجراءات الأطراف
                <span style="background:#F1F5F9;color:#64748B;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600"><?= $totalCount ?></span>
            </div>
            <?= Html::a(
                '<i class="fa fa-plus"></i> إضافة إجراء',
                ['/judiciaryCustomersActions/judiciary-customers-actions/create-followup-judicary-custamer-action', 'contractID' => $model->contract_id],
                [
                    'class' => 'btn btn-success jv-modal-remote',
                    'style' => 'border-radius:8px;font-size:13px;padding:8px 18px;font-weight:600',
                ]
            ) ?>
        </div>

        <div class="jv-actions-list">
            <?php if (empty($actions)): ?>
                <div class="jv-action-empty">
                    <i class="fa fa-inbox"></i>
                    <p>لا توجد إجراءات مسجلة على هذه القضية</p>
                </div>
            <?php else: ?>
                <?php foreach ($actions as $i => $m):
                    $def = $m->judiciaryActions;
                    $nature = $def ? ($def->action_nature ?: 'process') : 'process';
                    $ns = $natureStyles[$nature] ?? $natureStyles['process'];
                    $reqStatus = $m->request_status;
                    $editUrl = Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'contractID' => $model->contract_id, 'id' => $m->id]);
                    $delUrl = Url::to(['/judiciary/judiciary/delete-customer-action', 'id' => $m->id, 'judiciary' => $m->judiciary_id]);
                ?>
                <div class="jv-action-row">
                    <div class="jv-action-num"><?= $i + 1 ?></div>
                    <div class="jv-action-body">
                        <div class="jv-action-top">
                            <span class="jv-action-name">
                                <i class="fa <?= $ns['icon'] ?>" style="color:<?= $ns['color'] ?>"></i>
                                <?= Html::encode($def ? $def->name : '#' . $m->judiciary_actions_id) ?>
                            </span>
                            <span class="jv-action-badge" style="background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>"><?= $ns['label'] ?></span>
                            <?php if ($reqStatus): ?>
                                <?php $rc = $statusColors[$reqStatus] ?? '#6B7280'; $rl = $statusLabels[$reqStatus] ?? $reqStatus; ?>
                                <span class="jv-action-badge" style="background:<?= $rc ?>20;color:<?= $rc ?>"><?= $rl ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="jv-action-meta">
                            <?php if ($m->customers): ?>
                                <span><i class="fa fa-user"></i> <?= Html::encode($m->customers->name) ?></span>
                            <?php endif; ?>
                            <?php if ($m->action_date): ?>
                                <span><i class="fa fa-calendar"></i> <?= Html::encode($m->action_date) ?></span>
                            <?php endif; ?>
                            <?php if ($m->createdBy): ?>
                                <span><i class="fa fa-user-circle-o"></i> <?= Html::encode($m->createdBy->username) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($m->note)): ?>
                            <div class="jv-action-note"><?= Html::encode($m->note) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="jv-action-tools">
                        <div class="jca-act-wrap">
                            <button type="button" class="jca-act-trigger"><i class="fa fa-ellipsis-v"></i></button>
                            <div class="jca-act-menu">
                                <a href="<?= $editUrl ?>" class="jv-modal-remote"><i class="fa fa-pencil text-primary"></i> تعديل</a>
                                <div class="jca-act-divider"></div>
                                <a href="<?= $delUrl ?>" class="jv-delete-action"
                                   data-confirm="هل أنت متأكد من حذف هذا الإجراء؟">
                                    <i class="fa fa-trash text-danger"></i> حذف
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($totalCount > 0): ?>
        <div class="jv-pager">
            <span>إجمالي <?= number_format($totalCount) ?> إجراء</span>
            <?php
            $pagination = $actionsDP->getPagination();
            if ($pagination && $pagination->getPageCount() > 1) {
                echo \yii\widgets\LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination pagination-sm', 'style' => 'margin:0'],
                ]);
            }
            ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content"><div class="modal-body" id="ajaxCrudModalBody"><div class="text-center p-5"><i class="fa fa-spinner fa-spin fa-2x"></i></div></div></div>
        </div>
    </div>

    <?php
    $jcaJs = <<<'JS'
    $(document).on('click', '.jca-act-trigger', function(e) {
        e.stopPropagation();
        var $wrap = $(this).closest('.jca-act-wrap');
        var $menu = $wrap.find('.jca-act-menu');
        var wasOpen = $wrap.hasClass('open');
        $('.jca-act-wrap.open').removeClass('open');
        if (!wasOpen) {
            $wrap.addClass('open');
            var r = this.getBoundingClientRect();
            $menu.css({ left: r.left + 'px', top: (r.bottom + 4) + 'px' });
        }
    });
    $(document).on('click', function() { $('.jca-act-wrap.open').removeClass('open'); });
    $(document).on('click', '.jca-act-menu a', function() { $('.jca-act-wrap.open').removeClass('open'); });
JS;
    $this->registerJs($jcaJs);
    ?>
    <?php endif ?>

</div>

<!-- Timeline Side Panel -->
<div class="ctl-overlay" id="ctlOverlayView"></div>
<div class="ctl-panel" id="ctlPanelView">
    <div class="ctl-hdr">
        <h3><i class="fa fa-history"></i> <span id="ctlTitleView">الجدول الزمني</span></h3>
        <button class="ctl-close" id="ctlCloseView">&times;</button>
    </div>
    <div class="ctl-case-info" id="ctlCaseInfoView"></div>
    <div class="ctl-toolbar">
        <div class="ctl-filter-chips" id="ctlFilterChipsView">
            <span class="ctl-chip active" data-filter="all">الكل</span>
        </div>
    </div>
    <div class="ctl-body" id="ctlBodyView">
        <div class="ctl-loading"><i class="fa fa-spinner"></i><div>جاري التحميل...</div></div>
    </div>
</div>

<?php
$timelineJs = <<<'JS'
(function(){
    var $overlay = $('#ctlOverlayView'),
        $panel   = $('#ctlPanelView'),
        $body    = $('#ctlBodyView'),
        $info    = $('#ctlCaseInfoView'),
        $title   = $('#ctlTitleView'),
        $chips   = $('#ctlFilterChipsView'),
        allData  = [],
        activeFilter = 'all';

    function open(url, label) {
        $title.text('الجدول الزمني — ' + label);
        $body.html('<div class="ctl-loading"><i class="fa fa-spinner"></i><div>جاري التحميل...</div></div>');
        $info.empty();
        $overlay.addClass('open');
        $panel.addClass('open');

        $.getJSON(url, function(res) {
            if (!res.success) {
                $body.html('<div class="ctl-empty"><i class="fa fa-exclamation-circle"></i><div>' + (res.message || 'خطأ') + '</div></div>');
                return;
            }
            var c = res['case'] || {};
            $info.html(
                '<div class="ctl-info-item"><b>' + (c.judiciary_number || '#' + c.id) + '</b></div>' +
                (c.court ? '<div class="ctl-info-item">المحكمة: <b>' + c.court + '</b></div>' : '') +
                (c.lawyer ? '<div class="ctl-info-item">المحامي: <b>' + c.lawyer + '</b></div>' : '')
            );

            var parties = res.parties || [];
            var chipHtml = '<span class="ctl-chip active" data-filter="all">الكل</span>';
            parties.forEach(function(p) {
                chipHtml += '<span class="ctl-chip" data-filter="' + p.id + '">' + p.name.split(' ').slice(0,2).join(' ') + '</span>';
            });
            $chips.html(chipHtml);
            activeFilter = 'all';

            allData = res.timeline || [];
            renderTimeline();
        }).fail(function() {
            $body.html('<div class="ctl-empty"><i class="fa fa-exclamation-circle"></i><div>حدث خطأ في التحميل</div></div>');
        });
    }

    function renderTimeline() {
        var items = allData;
        if (activeFilter !== 'all') {
            items = items.filter(function(it) { return String(it.customer_id) === String(activeFilter); });
        }
        if (!items.length) {
            $body.html('<div class="ctl-empty"><i class="fa fa-inbox"></i><div>لا توجد إجراءات</div></div>');
            return;
        }
        var html = '', lastDate = '';
        var natureColors = { request:'#3B82F6', document:'#8B5CF6', doc_status:'#F59E0B', process:'#10B981', correspondence:'#0D9488', deadline:'#DC2626' };
        var sourceIcons = { correspondence:'fa-envelope', deadline:'fa-clock-o' };
        items.forEach(function(it) {
            var d = (it.action_date || '').substring(0, 10);
            if (d && d !== lastDate) {
                html += '<div class="ctl-date-sep"><span>' + d + '</span></div>';
                lastDate = d;
            }
            var nat = it.action_nature || 'process';
            var src = it.source || 'action';
            var icon = it.icon || (src === 'correspondence' ? 'fa-envelope' : (src === 'deadline' ? 'fa-clock-o' : ''));
            var borderColor = natureColors[nat] || natureColors[src] || '#10B981';
            html += '<div class="ctl-item" data-nature="' + nat + '" style="border-right-color:' + borderColor + '">';
            html += '<div class="ctl-item-hdr">';
            if (icon) html += '<i class="fa ' + icon + '" style="color:' + borderColor + ';margin-left:6px"></i>';
            html += '<span class="ctl-item-action">' + (it.action_name || '') + '</span>';
            if (it.status) html += '<span style="margin-right:auto;padding:1px 8px;border-radius:4px;font-size:10px;background:' + borderColor + '15;color:' + borderColor + '">' + it.status + '</span>';
            html += '<span class="ctl-item-date">' + (it.action_date || '') + '</span></div>';
            if (it.customer_name) {
                html += '<div class="ctl-item-party"><i class="fa fa-user"></i> ' + it.customer_name + '</div>';
            }
            if (it.note) {
                html += '<div class="ctl-item-note">' + it.note + '</div>';
            }
            html += '<div class="ctl-item-meta">';
            if (it.created_by) html += '<span><i class="fa fa-user-circle-o"></i> ' + it.created_by + '</span>';
            html += '</div></div>';
        });
        $body.html(html);
    }

    function close() {
        $overlay.removeClass('open');
        $panel.removeClass('open');
    }

    $(document).on('click', '.jv-timeline-trigger', function() {
        open($(this).data('url'), $(this).data('case-label'));
    });
    $('#ctlCloseView, #ctlOverlayView').on('click', close);
    $chips.on('click', '.ctl-chip', function() {
        $chips.find('.ctl-chip').removeClass('active');
        $(this).addClass('active');
        activeFilter = $(this).data('filter');
        renderTimeline();
    });
})();
JS;
$this->registerJs($timelineJs);

$modalJs = <<<'JS'
(function(){
    var $modal = document.getElementById('ajaxCrudModal');
    var $body  = document.getElementById('ajaxCrudModalBody');
    var bsModal = null;

    function getModal(){
        if (!bsModal && typeof bootstrap !== 'undefined') {
            bsModal = new bootstrap.Modal($modal);
        }
        return bsModal;
    }

    function openRemote(url){
        $body.innerHTML = '<div class="text-center p-5"><i class="fa fa-spinner fa-spin fa-2x"></i></div>';
        var m = getModal();
        if(m) m.show(); else $($modal).modal('show');

        fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(function(r){ return r.text(); })
            .then(function(html){ $body.innerHTML = html; })
            .catch(function(){ $body.innerHTML = '<div class="alert alert-danger m-3">حدث خطأ في التحميل</div>'; });
    }

    document.addEventListener('click', function(e){
        var link = e.target.closest('.jv-modal-remote');
        if(link){
            e.preventDefault();
            openRemote(link.href);
        }
        var del = e.target.closest('.jv-delete-action');
        if(del){
            e.preventDefault();
            var msg = del.getAttribute('data-confirm') || 'هل أنت متأكد؟';
            if(confirm(msg)){
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = del.href;
                var csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = document.querySelector('meta[name=csrf-param]').getAttribute('content');
                csrf.value = document.querySelector('meta[name=csrf-token]').getAttribute('content');
                form.appendChild(csrf);
                document.body.appendChild(form);
                form.submit();
            }
        }
    });
})();
JS;
$this->registerJs($modalJs);
?>
