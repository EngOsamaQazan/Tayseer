<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\helpers\FlatpickrWidget;
use backend\modules\judiciaryType\models\JudiciaryType;
use backend\modules\court\models\Court;
use backend\modules\lawyers\models\Lawyers;
use backend\modules\JudiciaryInformAddress\model\JudiciaryInformAddress;
use backend\modules\companies\models\Companies;

$courts = ArrayHelper::map(Court::find()->asArray()->all(), 'id', 'name');
$types = ArrayHelper::map(JudiciaryType::find()->asArray()->all(), 'id', 'name');
$lawyers = ArrayHelper::map(Lawyers::find()->asArray()->all(), 'id', 'name');
$addresses = ArrayHelper::map(JudiciaryInformAddress::find()->asArray()->all(), 'id', 'address');
$companies = ArrayHelper::map(Companies::find()->asArray()->all(), 'id', 'name');
$isNew = $model->isNewRecord;

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
.jf-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;margin-bottom:20px}
.jf-card-head{padding:14px 20px;background:#FAFBFC;border-bottom:1px solid #E2E8F0;display:flex;align-items:center;gap:8px;font-size:14px;font-weight:700;color:#334155}
.jf-card-head i{color:#3B82F6;font-size:15px}
.jf-card-body{padding:20px}
.jf-card-body .form-group{margin-bottom:16px}
.jf-card-body .form-group label{font-weight:600;font-size:12px;color:#64748B;margin-bottom:6px}
.jf-card-body .form-control{border-radius:8px;border:1px solid #E2E8F0;font-size:13px;padding:8px 12px;transition:border-color .2s,box-shadow .2s}
.jf-card-body .form-control:focus{border-color:#3B82F6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.jf-card-body .select2-container--krajee-bs3 .select2-selection{border-radius:8px !important;border:1px solid #E2E8F0 !important;min-height:36px}
.jf-card-body .select2-container--krajee-bs3 .select2-selection:focus,.jf-card-body .select2-container--krajee-bs3.select2-container--focus .select2-selection{border-color:#3B82F6 !important;box-shadow:0 0 0 3px rgba(59,130,246,.1) !important}

.jf-grid{display:grid;gap:16px}
.jf-grid-3{grid-template-columns:repeat(3,1fr)}
.jf-grid-4{grid-template-columns:repeat(4,1fr)}
.jf-grid-2{grid-template-columns:repeat(2,1fr)}

.jf-save-bar{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px 20px;display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap;margin-bottom:20px;position:sticky;bottom:10px;z-index:10;box-shadow:0 -4px 12px rgba(0,0,0,.04)}
.jf-save-bar .btn{border-radius:8px;font-size:14px;font-weight:600;padding:10px 24px}

/* ═══ جدول الإجراءات ═══ */
.jf-actions-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;margin-bottom:20px}
.jf-actions-header{padding:16px 20px;border-bottom:1px solid #E2E8F0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;background:#FAFBFC}
.jf-actions-header .jf-section-title{font-size:15px;font-weight:700;color:#334155;display:flex;align-items:center;gap:8px;margin:0}
.jf-actions-header .jf-section-title i{color:#8B5CF6}

.jf-action-row{display:grid;grid-template-columns:40px 1fr auto;border-bottom:1px solid #F1F5F9;transition:background .15s}
.jf-action-row:last-child{border-bottom:none}
.jf-action-row:hover{background:#F8FAFC}
.jf-action-num{display:flex;align-items:center;justify-content:center;padding:16px 8px;color:#CBD5E1;font-size:12px;font-weight:600;border-left:1px solid #F1F5F9}
.jf-action-body{padding:14px 16px;display:flex;flex-direction:column;gap:6px;min-width:0}
.jf-action-top{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.jf-action-name{font-weight:700;font-size:13px;color:#1E293B;display:flex;align-items:center;gap:6px}
.jf-action-name i{font-size:13px}
.jf-action-badge{padding:2px 10px;border-radius:6px;font-size:10px;font-weight:600;white-space:nowrap}
.jf-action-meta{display:flex;align-items:center;gap:16px;flex-wrap:wrap;font-size:12px;color:#94A3B8}
.jf-action-meta i{margin-left:4px;font-size:11px}
.jf-action-note{font-size:12px;color:#64748B;background:#F8FAFC;padding:6px 10px;border-radius:6px;margin-top:4px;line-height:1.5;word-wrap:break-word}
.jf-action-tools{display:flex;align-items:center;padding:14px 12px}
.jf-action-empty{text-align:center;padding:50px 20px;color:#94A3B8}
.jf-action-empty i{font-size:40px;display:block;margin-bottom:12px;color:#E2E8F0}
.jf-pager{padding:12px 20px;border-top:1px solid #F1F5F9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:12px;color:#94A3B8}

.jca-act-wrap{position:relative;display:inline-block}
.jca-act-trigger{background:none;border:1px solid #E2E8F0;border-radius:8px;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;font-size:14px;transition:all .15s;padding:0}
.jca-act-trigger:hover{background:#F1F5F9;color:#1E293B;border-color:#CBD5E1}
.jca-act-menu{display:none;position:fixed;min-width:160px;background:#fff;border:1px solid #E2E8F0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:99999;padding:4px 0;direction:rtl;font-size:12px}
.jca-act-wrap.open .jca-act-menu{display:block}
.jca-act-menu a{display:flex;align-items:center;gap:8px;padding:7px 14px;color:#334155;text-decoration:none;white-space:nowrap;transition:background .12s}
.jca-act-menu a:hover{background:#F1F5F9;color:#1D4ED8}
.jca-act-menu a i{width:16px;text-align:center}
.jca-act-divider{height:1px;background:#E2E8F0;margin:4px 0}

/* Approve / Reject inline */
.jf-req-actions{display:flex;gap:6px;align-items:center;margin-top:6px}
.jf-req-btn{display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:6px;font-size:11px;
  font-weight:600;border:1px solid;cursor:pointer;transition:all .15s;white-space:nowrap;background:none}
.jf-req-btn.approve{background:#D1FAE5;color:#065F46;border-color:#A7F3D0}
.jf-req-btn.approve:hover{background:#065F46;color:#fff}
.jf-req-btn.reject{background:#FEE2E2;color:#991B1B;border-color:#FECACA}
.jf-req-btn.reject:hover{background:#991B1B;color:#fff}
.jf-req-btn:disabled{opacity:.5;cursor:not-allowed}
.jf-decision-form{display:none;margin-top:8px;padding:10px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px}
.jf-decision-form.open{display:block}
.jf-decision-input{width:100%;padding:6px 10px;border:1px solid #E2E8F0;border-radius:6px;font-size:12px;
  resize:vertical;min-height:36px;font-family:inherit;margin-bottom:6px}
.jf-decision-input:focus{border-color:#800020;outline:none;box-shadow:0 0 0 2px rgba(128,0,32,.1)}
.jf-decision-btns{display:flex;gap:6px;justify-content:flex-end}
.jf-decision-confirm{padding:4px 14px;border-radius:6px;font-size:11px;font-weight:600;border:none;cursor:pointer;color:#fff}
.jf-decision-confirm.do-approve{background:#065F46}
.jf-decision-confirm.do-reject{background:#991B1B}
.jf-decision-confirm:hover{opacity:.85}
.jf-decision-cancel{padding:4px 14px;border-radius:6px;font-size:11px;font-weight:600;border:1px solid #E2E8F0;
  background:#fff;color:#64748B;cursor:pointer}

@media(max-width:992px){
    .jf-grid-3{grid-template-columns:repeat(2,1fr)}
    .jf-grid-4{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:768px){
    .jf-grid-3,.jf-grid-4,.jf-grid-2{grid-template-columns:1fr}
    .jf-action-row{grid-template-columns:1fr}
    .jf-action-num{display:none}
    .jf-action-tools{justify-content:flex-end;padding:0 16px 12px}
    .jf-save-bar{position:static;box-shadow:none}
}
</style>

<?php $form = ActiveForm::begin(['options' => ['class' => 'judiciary-form-modern']]); ?>

<div class="jf-card">
    <div class="jf-card-head"><i class="fa fa-gavel"></i> بيانات القضية الأساسية</div>
    <div class="jf-card-body">
        <div class="jf-grid jf-grid-3">
            <div>
                <?= $form->field($model, 'court_id')->widget(Select2::class, [
                    'data' => $courts,
                    'options' => ['placeholder' => 'اختر المحكمة'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المحكمة') ?>
            </div>
            <div>
                <?= $form->field($model, 'type_id')->widget(Select2::class, [
                    'data' => $types,
                    'options' => ['placeholder' => 'نوع القضية'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('نوع القضية') ?>
            </div>
            <div>
                <?= $form->field($model, 'company_id')->widget(Select2::class, [
                    'data' => $companies,
                    'options' => ['placeholder' => 'الشركة'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('الشركة') ?>
            </div>
        </div>
    </div>
</div>

<div class="jf-card">
    <div class="jf-card-head"><i class="fa fa-university"></i> المحامي والتكاليف</div>
    <div class="jf-card-body">
        <div class="jf-grid jf-grid-3">
            <div>
                <?= $form->field($model, 'lawyer_id')->widget(Select2::class, [
                    'data' => $lawyers,
                    'options' => ['placeholder' => 'اختر المحامي'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المحامي') ?>
            </div>
            <div>
                <?= $form->field($model, 'lawyer_cost')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])->label('أتعاب المحامي') ?>
            </div>
            <div>
                <?= $form->field($model, 'case_cost')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])->label('رسوم القضية') ?>
            </div>
        </div>
    </div>
</div>

<div class="jf-card">
    <div class="jf-card-head"><i class="fa fa-info-circle"></i> تفاصيل القضية</div>
    <div class="jf-card-body">
        <div class="jf-grid jf-grid-4">
            <div>
                <?= $form->field($model, 'judiciary_number')->textInput(['placeholder' => 'رقم القضية'])->label('رقم القضية') ?>
            </div>
            <div>
                <?= $form->field($model, 'year')->widget(Select2::class, [
                    'data' => $model->year(),
                    'options' => ['placeholder' => 'السنة'],
                    'pluginOptions' => ['allowClear' => true],
                ])->label('السنة') ?>
            </div>
            <div>
                <?= $form->field($model, 'income_date')->widget(FlatpickrWidget::class, [
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label('تاريخ الورود') ?>
            </div>
            <div>
                <?= $form->field($model, 'input_method')->dropDownList(['الادخال اليدوي', 'نسبه مؤيه'], ['class' => 'form-control'])->label('طريقة الإدخال') ?>
            </div>
        </div>
        <div class="jf-grid jf-grid-2">
            <div>
                <?= $form->field($model, 'judiciary_inform_address_id')->widget(Select2::class, [
                    'data' => $addresses,
                    'options' => ['placeholder' => 'الموطن المختار'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('الموطن المختار') ?>
            </div>
        </div>
    </div>
</div>

<?php if (!Yii::$app->request->isAjax): ?>
<div class="jf-save-bar">
    <?php if ($isNew): ?>
        <?= Html::submitButton('<i class="fa fa-print"></i> إنشاء وطباعة', ['name' => 'print', 'class' => 'btn btn-success']) ?>
        <?= Html::submitButton('<i class="fa fa-plus"></i> إنشاء', ['class' => 'btn btn-success']) ?>
    <?php else: ?>
        <?= Html::submitButton('<i class="fa fa-save"></i> حفظ التعديلات', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="fa fa-print"></i> طباعة سندات التنفيذ', ['/judiciary/judiciary/print-case', 'id' => $model->id], ['class' => 'btn btn-default']) ?>
    <?php endif ?>
</div>
<?php endif ?>

<?php ActiveForm::end() ?>

<?php if (!$isNew):
    \johnitvn\ajaxcrud\CrudAsset::register($this);
    $contractIdForGrid = $model->contract_id;
    $actionsDP = new yii\data\ActiveDataProvider([
        'query' => \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::find()
            ->where(['judiciary_id' => $model->id]),
        'sort' => ['defaultOrder' => ['action_date' => SORT_DESC]],
        'pagination' => ['pageSize' => 20],
    ]);
    $actions = $actionsDP->getModels();
    $totalCount = $actionsDP->getTotalCount();
?>

<div class="jf-actions-card" id="jf-actions-container">
    <div class="jf-actions-header">
        <div class="jf-section-title">
            <i class="fa fa-list-ul"></i> إجراءات الأطراف
            <span style="background:#F1F5F9;color:#64748B;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600"><?= $totalCount ?></span>
        </div>
        <?= Html::a(
            '<i class="fa fa-plus"></i> إضافة إجراء',
            ['/judiciaryCustomersActions/judiciary-customers-actions/create-followup-judicary-custamer-action', 'contractID' => $contractIdForGrid],
            ['role' => 'modal-remote', 'class' => 'btn btn-success', 'style' => 'border-radius:8px;font-size:13px;padding:8px 18px;font-weight:600']
        ) ?>
    </div>

    <div>
        <?php if (empty($actions)): ?>
            <div class="jf-action-empty">
                <i class="fa fa-inbox"></i>
                <p>لا توجد إجراءات مسجلة على هذه القضية</p>
            </div>
        <?php else: ?>
            <?php foreach ($actions as $i => $m):
                $def = $m->judiciaryActions;
                $nature = $def ? ($def->action_nature ?: 'process') : 'process';
                $ns = $natureStyles[$nature] ?? $natureStyles['process'];
                $reqStatus = $m->request_status;
                $editUrl = Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'contractID' => $contractIdForGrid, 'id' => $m->id]);
                $delUrl = Url::to(['/judiciary/judiciary/delete-customer-action', 'id' => $m->id, 'judiciary' => $m->judiciary_id]);
            ?>
            <div class="jf-action-row">
                <div class="jf-action-num"><?= $i + 1 ?></div>
                <div class="jf-action-body">
                    <div class="jf-action-top">
                        <span class="jf-action-name">
                            <i class="fa <?= $ns['icon'] ?>" style="color:<?= $ns['color'] ?>"></i>
                            <?= Html::encode($def ? $def->name : '#' . $m->judiciary_actions_id) ?>
                        </span>
                        <span class="jf-action-badge" style="background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>"><?= $ns['label'] ?></span>
                        <?php if ($reqStatus): ?>
                            <?php $rc = $statusColors[$reqStatus] ?? '#6B7280'; $rl = $statusLabels[$reqStatus] ?? $reqStatus; ?>
                            <span class="jf-action-badge jf-status-badge-<?= $m->id ?>" style="background:<?= $rc ?>20;color:<?= $rc ?>"><?= $rl ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="jf-action-meta">
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
                    <?php if (!empty($m->decision_text)): ?>
                        <div class="jf-action-note" style="color:#1E293B;font-weight:600"><i class="fa fa-gavel" style="color:#F59E0B;margin-left:4px"></i> <?= Html::encode($m->decision_text) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($m->note)): ?>
                        <div class="jf-action-note"><?= Html::encode($m->note) ?></div>
                    <?php endif; ?>
                    <?php if ($reqStatus === 'pending'): ?>
                    <div class="jf-req-actions" id="jf-ra-<?= $m->id ?>">
                        <button type="button" class="jf-req-btn approve" onclick="JCA.startDecision(<?= $m->id ?>, 'approved')"><i class="fa fa-check"></i> موافقة</button>
                        <button type="button" class="jf-req-btn reject" onclick="JCA.startDecision(<?= $m->id ?>, 'rejected')"><i class="fa fa-times"></i> رفض</button>
                    </div>
                    <div class="jf-decision-form" id="jf-df-<?= $m->id ?>">
                        <textarea class="jf-decision-input" id="jf-dt-<?= $m->id ?>" placeholder="نص القرار أو سبب الرفض (اختياري)..." rows="2"></textarea>
                        <div class="jf-decision-btns">
                            <button type="button" class="jf-decision-cancel" onclick="JCA.cancelDecision(<?= $m->id ?>)">إلغاء</button>
                            <button type="button" class="jf-decision-confirm" onclick="JCA.confirmDecision(<?= $m->id ?>)">تأكيد</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="jf-action-tools">
                    <div class="jca-act-wrap">
                        <button type="button" class="jca-act-trigger"><i class="fa fa-ellipsis-v"></i></button>
                        <div class="jca-act-menu">
                            <a href="<?= $editUrl ?>" role="modal-remote"><i class="fa fa-pencil text-primary"></i> تعديل</a>
                            <div class="jca-act-divider"></div>
                            <a href="<?= $delUrl ?>" data-request-method="post" data-confirm-message="هل أنت متأكد من حذف هذا الإجراء؟">
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
    <div class="jf-pager">
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
$updateReqUrl = Url::to(['/judiciary/judiciary/update-request-status']);
$reqUrlJs = json_encode($updateReqUrl);

$jcaJs = <<<JS
window.JCA = (function(){
    var reqUrl = {$reqUrlJs};
    var pendingDecision = {};

    function getCsrfParam() {
        var m = document.querySelector('meta[name="csrf-param"]');
        return m ? m.getAttribute('content') : '_csrf-backend';
    }
    function getCsrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function startDecision(id, status) {
        pendingDecision = {id: id, status: status};
        var df = document.getElementById('jf-df-' + id);
        if (!df) return;
        df.classList.add('open');
        var cfm = df.querySelector('.jf-decision-confirm');
        if (cfm) {
            cfm.className = 'jf-decision-confirm ' + (status === 'approved' ? 'do-approve' : 'do-reject');
            cfm.textContent = status === 'approved' ? 'تأكيد الموافقة' : 'تأكيد الرفض';
        }
        var ta = document.getElementById('jf-dt-' + id);
        if (ta) { ta.value = ''; ta.focus(); }
    }

    function cancelDecision(id) {
        var df = document.getElementById('jf-df-' + id);
        if (df) df.classList.remove('open');
        pendingDecision = {};
    }

    function confirmDecision(id) {
        if (!pendingDecision.id) return;
        var ta = document.getElementById('jf-dt-' + id);
        var dt = ta ? ta.value.trim() : '';
        var df = document.getElementById('jf-df-' + id);
        var btn = df ? df.querySelector('.jf-decision-confirm') : null;
        if (btn) { btn.disabled = true; btn.textContent = 'جاري الحفظ...'; }
        var postData = {
            id: pendingDecision.id,
            status: pendingDecision.status,
            decision_text: dt
        };
        postData[getCsrfParam()] = getCsrfToken();
        $.post(reqUrl, postData).done(function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message || 'حدث خطأ');
                if (btn) btn.disabled = false;
            }
        }).fail(function() {
            alert('حدث خطأ أثناء الحفظ');
            if (btn) { btn.disabled = false; btn.textContent = 'تأكيد'; }
        }).always(function() { pendingDecision = {}; });
    }

    $(document).on('click', '.jca-act-trigger', function(e) {
        e.stopPropagation();
        var wrap = this.closest('.jca-act-wrap');
        var menu = wrap.querySelector('.jca-act-menu');
        var wasOpen = wrap.classList.contains('open');
        document.querySelectorAll('.jca-act-wrap.open').forEach(function(w){ w.classList.remove('open'); });
        if (!wasOpen) {
            wrap.classList.add('open');
            var r = this.getBoundingClientRect();
            menu.style.left = r.left + 'px';
            menu.style.top = (r.bottom + 4) + 'px';
        }
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.jca-act-wrap').length) {
            document.querySelectorAll('.jca-act-wrap.open').forEach(function(w){ w.classList.remove('open'); });
        }
    });
    $(document).on('click', '.jca-act-menu a', function() {
        document.querySelectorAll('.jca-act-wrap.open').forEach(function(w){ w.classList.remove('open'); });
    });

    return {
        startDecision: startDecision,
        cancelDecision: cancelDecision,
        confirmDecision: confirmDecision
    };
})();
JS;
$this->registerJs($jcaJs);
?>
<?php endif ?>
