<?php
/**
 * @var yii\web\View $this
 * @var backend\modules\companyManagement\models\Company $model
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

$this->title = $model->name_ar;
$canProvision = in_array($model->status, ['pending', 'dns_ready', 'provisioned']);
?>

<div class="company-view">
    <div class="row">
        <!-- Company Details -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fa fa-building"></i> <?= Html::encode($model->name_ar) ?></h5>
                    <span class="badge <?= $model->getStatusBadgeClass() ?>"><?= $model->getStatusLabel() ?></span>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'options' => ['class' => 'table table-striped detail-view'],
                        'attributes' => [
                            'slug',
                            'name_ar',
                            'name_en',
                            [
                                'attribute' => 'domain',
                                'format' => 'raw',
                                'value' => Html::a($model->domain, 'https://' . $model->domain, ['target' => '_blank']),
                            ],
                            'db_name',
                            'server_ip',
                            'sms_sender',
                            'og_title',
                            [
                                'attribute' => 'created_at',
                                'format' => ['date', 'php:Y-m-d H:i'],
                            ],
                        ],
                    ]) ?>

                    <div class="d-flex gap-2">
                        <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['index'], ['class' => 'btn btn-secondary']) ?>
                        <?= Html::a('<i class="fa fa-edit"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn btn-warning']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Provision Panel -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fa fa-rocket"></i> تجهيز المنشأة</h5>
                </div>
                <div class="card-body">
                    <?php if ($model->status === 'active'): ?>
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle"></i> المنشأة مُجهزة ونشطة بالكامل.
                        </div>
                    <?php elseif ($canProvision): ?>
                        <p class="text-muted">اضغط على كل خطوة بالترتيب لتجهيز المنشأة تلقائياً:</p>
                    <?php endif; ?>

                    <div id="provision-steps">
                        <?php
                        $steps = [
                            ['id' => 'dns',       'icon' => 'globe',     'label' => '1. إنشاء DNS (GoDaddy)',     'desc' => 'إضافة سجل A للنطاق الفرعي'],
                            ['id' => 'database',  'icon' => 'database',  'label' => '2. قاعدة البيانات',           'desc' => 'إنشاء قاعدة البيانات وصلاحيات المستخدم'],
                            ['id' => 'server',    'icon' => 'server',    'label' => '3. إعداد السيرفر',            'desc' => 'استنساخ الكود وتطبيق البيئة'],
                            ['id' => 'ssl',       'icon' => 'lock',      'label' => '4. Apache + SSL',             'desc' => 'إعداد VirtualHost وشهادة SSL'],
                            ['id' => 'migrate',   'icon' => 'cogs',      'label' => '5. التهجير والصلاحيات',       'desc' => 'تشغيل التهجير وإنشاء المدير'],
                            ['id' => 'deploy',    'icon' => 'github',    'label' => '6. تحديث سكريبتات النشر',     'desc' => 'تحديث deploy.yml و deploy-pull.sh'],
                        ];

                        foreach ($steps as $i => $step): ?>
                            <div class="provision-step mb-3 p-3 border rounded" id="step-<?= $step['id'] ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fa fa-<?= $step['icon'] ?> me-2"></i>
                                        <strong><?= $step['label'] ?></strong>
                                        <div class="text-muted small mt-1"><?= $step['desc'] ?></div>
                                    </div>
                                    <div>
                                        <span class="step-status badge bg-label-secondary">معلق</span>
                                        <?php if ($canProvision): ?>
                                        <button class="btn btn-sm btn-primary ms-2 btn-run-step"
                                                data-step="<?= $step['id'] ?>"
                                                data-company="<?= $model->id ?>">
                                            <i class="fa fa-play"></i> تشغيل
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="step-output mt-2" style="display:none;">
                                    <pre class="bg-dark text-light p-2 rounded small mb-0" style="max-height:200px;overflow-y:auto;direction:ltr;text-align:left;"></pre>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Log -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fa fa-list-alt"></i> سجل العمليات</h5>
                </div>
                <div class="card-body">
                    <pre id="provision-log" class="bg-light p-3 rounded small" style="max-height:300px;overflow-y:auto;white-space:pre-wrap;direction:ltr;text-align:left;"><?= Html::encode($model->provision_log ?: 'لا يوجد سجل بعد') ?></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$provisionUrl = Url::to(['/companyManagement/provision/run-step']);
$statusUrl    = Url::to(['/companyManagement/provision/status', 'id' => $model->id]);
$js = <<<JS
$(document).on('click', '.btn-run-step', function() {
    var btn = $(this);
    var step = btn.data('step');
    var companyId = btn.data('company');
    var stepEl = $('#step-' + step);
    var statusBadge = stepEl.find('.step-status');
    var outputDiv = stepEl.find('.step-output');
    var outputPre = outputDiv.find('pre');

    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري...');
    statusBadge.removeClass('bg-label-secondary bg-label-success bg-label-danger').addClass('bg-label-warning').text('جاري التنفيذ...');
    outputDiv.show();
    outputPre.text('جاري التنفيذ...');

    $.ajax({
        url: '{$provisionUrl}',
        method: 'POST',
        data: { company_id: companyId, step: step },
        dataType: 'json',
        timeout: 300000,
        success: function(res) {
            if (res.success) {
                statusBadge.removeClass('bg-label-warning').addClass('bg-label-success').text('تم بنجاح');
                btn.html('<i class="fa fa-check"></i> تم').removeClass('btn-primary').addClass('btn-success');
                outputPre.text(res.message || 'تمت العملية بنجاح');
            } else {
                statusBadge.removeClass('bg-label-warning').addClass('bg-label-danger').text('فشل');
                btn.html('<i class="fa fa-redo"></i> إعادة').prop('disabled', false);
                outputPre.text(res.message || 'حدث خطأ');
            }
        },
        error: function(xhr) {
            statusBadge.removeClass('bg-label-warning').addClass('bg-label-danger').text('خطأ');
            btn.html('<i class="fa fa-redo"></i> إعادة').prop('disabled', false);
            outputPre.text('خطأ في الاتصال: ' + (xhr.statusText || 'timeout'));
        },
        complete: function() {
            // Refresh log
            $.get('{$statusUrl}', function(data) {
                if (data.log) $('#provision-log').text(data.log);
            });
        }
    });
});
JS;
$this->registerJs($js);
?>
