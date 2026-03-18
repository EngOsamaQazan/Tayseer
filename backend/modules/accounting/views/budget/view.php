<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\modules\accounting\models\Account;
use backend\modules\accounting\models\CostCenter;
use backend\modules\accounting\models\BudgetLine;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'الموازنات', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$accounts = Account::getLeafAccounts();
$costCenters = ArrayHelper::map(CostCenter::find()->where(['is_active' => 1])->all(), 'id', 'name');
$months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
?>

<!-- Budget Header -->
<div class="row" style="margin-bottom:20px;">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-value" style="color:var(--clr-primary, #800020);"><?= number_format($model->total_amount, 2) ?></div>
            <div class="stat-label">إجمالي الموازنة</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-label">السنة المالية</div>
            <div class="stat-value" style="font-size:16px;"><?= $model->fiscalYear ? Html::encode($model->fiscalYear->name) : '—' ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-label">الحالة</div>
            <div style="margin-top:5px;"><?= $model->getStatusBadge() ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-label">عدد البنود</div>
            <div class="stat-value" style="font-size:20px;"><?= count($lines) ?></div>
        </div>
    </div>
</div>

<!-- Budget Lines Table -->
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-list"></i> بنود الموازنة</h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-bar-chart"></i> تقرير الانحراف', ['variance', 'id' => $model->id], ['class' => 'btn btn-info btn-sm']) ?>
            <?php if ($model->status === 'draft'): ?>
                <?= Html::a('<i class="fa fa-check-circle"></i> اعتماد الموازنة', ['approve', 'id' => $model->id], [
                    'class' => 'btn btn-success btn-sm',
                    'data-method' => 'post',
                    'data-confirm' => 'هل أنت متأكد من اعتماد هذه الموازنة؟',
                ]) ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="box-body" style="overflow-x:auto;">
        <?php if (empty($lines)): ?>
            <div class="text-center text-muted" style="padding:30px;">
                <i class="fa fa-folder-open-o fa-3x"></i>
                <p style="margin-top:10px;">لا توجد بنود في هذه الموازنة. أضف بنوداً أدناه.</p>
            </div>
        <?php else: ?>
        <table class="table table-bordered table-condensed table-striped" style="min-width:1100px;">
            <thead>
                <tr style="background:#f5f6f8;">
                    <th style="width:30px;" class="text-center">#</th>
                    <th>الحساب</th>
                    <th>مركز التكلفة</th>
                    <?php for ($i = 0; $i < 12; $i++): ?>
                    <th class="text-center" style="width:80px; font-size:11px;"><?= $months[$i] ?></th>
                    <?php endfor; ?>
                    <th class="text-center" style="width:100px; font-weight:800;">السنوي</th>
                    <th class="text-center" style="width:100px;">الفعلي</th>
                    <th style="width:50px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php $grandTotal = 0; $grandActual = 0; ?>
                <?php foreach ($lines as $idx => $line):
                    $actual = $actuals[$line->id] ?? 0;
                    $grandTotal += $line->annual_total;
                    $grandActual += $actual;
                    $variance = $line->annual_total - $actual;
                ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td style="font-size:12px;"><?= $line->account ? Html::encode($line->account->code . ' - ' . $line->account->name_ar) : '' ?></td>
                    <td style="font-size:12px;"><?= $line->costCenter ? Html::encode($line->costCenter->name) : '—' ?></td>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                    <td class="text-left" style="font-size:11px;"><?= number_format($line->{"period_{$i}"}, 0) ?></td>
                    <?php endfor; ?>
                    <td class="text-left" style="font-weight:700;"><?= number_format($line->annual_total, 2) ?></td>
                    <td class="text-left" style="font-weight:600; color:<?= $actual > $line->annual_total ? '#dc3545' : '#28a745' ?>;"><?= number_format($actual, 2) ?></td>
                    <td class="text-center">
                        <?php if ($model->status === 'draft'): ?>
                        <?= Html::a('<i class="fa fa-trash"></i>', ['remove-line', 'id' => $model->id, 'line_id' => $line->id], [
                            'class' => 'btn btn-xs btn-danger',
                            'data-method' => 'post',
                            'data-confirm' => 'حذف هذا البند؟',
                        ]) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f5f6f8; font-weight:800;">
                    <td colspan="15" class="text-left">المجموع</td>
                    <td class="text-left"><?= number_format($grandTotal, 2) ?></td>
                    <td class="text-left" style="color:<?= $grandActual > $grandTotal ? '#dc3545' : '#28a745' ?>;"><?= number_format($grandActual, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add Line Form -->
<?php if ($model->status === 'draft'): ?>
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-plus"></i> إضافة بند جديد</h3>
    </div>
    <div class="box-body">
        <?php
        $newLine = new BudgetLine();
        $newLine->budget_id = $model->id;
        $form = ActiveForm::begin(['action' => ['add-line', 'id' => $model->id], 'id' => 'add-line-form']);
        ?>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($newLine, 'account_id')->widget(Select2::class, [
                    'data' => $accounts,
                    'options' => ['placeholder' => 'اختر الحساب...'],
                    'pluginOptions' => ['allowClear' => false, 'dir' => 'rtl'],
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($newLine, 'cost_center_id')->widget(Select2::class, [
                    'data' => $costCenters,
                    'options' => ['placeholder' => 'مركز تكلفة (اختياري)...'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ]) ?>
            </div>
            <div class="col-md-3">
                <label>الإجمالي السنوي</label>
                <input type="number" step="0.01" min="0" class="form-control" id="annual-total-input" placeholder="أدخل الإجمالي ليتم توزيعه تلقائياً">
            </div>
            <div class="col-md-2" style="padding-top:24px;">
                <button type="button" class="btn btn-info btn-block" id="distribute-btn"><i class="fa fa-columns"></i> وزّع</button>
            </div>
        </div>
        <div class="row" style="margin-top:10px;">
            <?php for ($i = 1; $i <= 12; $i++): ?>
            <div class="col-md-1" style="padding:0 5px;">
                <label style="font-size:10px;"><?= $months[$i - 1] ?></label>
                <?= $form->field($newLine, "period_{$i}")->textInput(['type' => 'number', 'step' => '0.01', 'min' => '0', 'class' => 'form-control input-sm period-input', 'placeholder' => '0'])->label(false) ?>
            </div>
            <?php endfor; ?>
        </div>
        <div class="row" style="margin-top:10px;">
            <div class="col-md-8">
                <?= $form->field($newLine, 'notes')->textInput(['placeholder' => 'ملاحظات (اختياري)']) ?>
            </div>
            <div class="col-md-4" style="padding-top:24px;">
                <?= Html::submitButton('<i class="fa fa-plus"></i> إضافة البند', ['class' => 'btn btn-success btn-block']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
<?php endif; ?>

<?php
$this->registerJs("
$('#distribute-btn').on('click', function() {
    var total = parseFloat($('#annual-total-input').val()) || 0;
    var monthly = Math.round(total / 12 * 100) / 100;
    var remainder = Math.round((total - monthly * 12) * 100) / 100;
    
    $('.period-input').each(function(i) {
        var val = monthly;
        if (i === 11) val = monthly + remainder;
        $(this).val(val.toFixed(2));
    });
});
");
?>
