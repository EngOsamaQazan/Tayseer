<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'ملف القضية #' . $model->judiciary_number;
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$court = $model->court;
$type = $model->type;
$lawyer = $model->lawyer;
$contract = $model->contract;
$customers = $model->customers;
$guarantors = $model->customersGuarantor;

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

    <?php if (isset($actionsDP)):
        \johnitvn\ajaxcrud\CrudAsset::register($this);
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
                    'role' => 'modal-remote',
                    'class' => 'btn btn-success',
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
                                <a href="<?= $editUrl ?>" role="modal-remote"><i class="fa fa-pencil text-primary"></i> تعديل</a>
                                <div class="jca-act-divider"></div>
                                <a href="<?= $delUrl ?>" role="modal-remote" data-request-method="post"
                                   data-confirm-title="تأكيد الحذف" data-confirm-message="هل أنت متأكد من حذف هذا الإجراء؟">
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

    <?php \yii\bootstrap\Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '', 'size' => \yii\bootstrap\Modal::SIZE_LARGE]) ?>
    <?php \yii\bootstrap\Modal::end() ?>

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
