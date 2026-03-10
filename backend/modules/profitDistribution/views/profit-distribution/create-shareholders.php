<?php

use yii\helpers\Url;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\modules\profitDistribution\models\ProfitDistributionModel $model */
/** @var backend\modules\shareholders\models\Shareholders[] $shareholders */
/** @var int $totalShares */

$this->title = 'توزيع أرباح على المساهمين';
$this->params['breadcrumbs'][] = ['label' => 'توزيع الأرباح', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCss('.content-header,.page-header { display: none !important; }');
?>

<style>
:root {
    --pd-primary: #059669;
    --pd-primary-light: #d1fae5;
    --pd-border: #e2e8f0;
    --pd-bg: #f8fafc;
    --pd-r: 12px;
    --pd-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.pd-create { max-width: 1000px; margin: 0 auto; padding: 24px; font-family: 'Segoe UI', Tahoma, sans-serif; }

.pd-page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
.pd-page-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0; }
.pd-page-header i { color: #7c3aed; font-size: 20px; }
.pd-back { color: #64748b; text-decoration: none; font-size: 13px; margin-right: auto; display: flex; align-items: center; gap: 4px; }
.pd-back:hover { color: #1e293b; }

.pd-card { background: #fff; border-radius: var(--pd-r); box-shadow: var(--pd-shadow); border: 1px solid var(--pd-border); margin-bottom: 20px; overflow: hidden; }
.pd-card-head { background: var(--pd-bg); padding: 14px 20px; border-bottom: 1px solid var(--pd-border); font-size: 14px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.pd-card-head i { color: var(--pd-primary); }
.pd-card-body { padding: 20px; }

.pd-form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
.pd-form-group { display: flex; flex-direction: column; gap: 4px; }
.pd-form-group.full { grid-column: 1 / -1; }
.pd-form-label { font-size: 13px; font-weight: 600; color: #475569; }
.pd-form-control { border: 1px solid var(--pd-border); border-radius: 8px; padding: 9px 14px; font-size: 14px; color: #1e293b; background: #fff; outline: none; transition: border .2s; }
.pd-form-control:focus { border-color: var(--pd-primary); box-shadow: 0 0 0 3px rgba(5,150,105,.1); }
textarea.pd-form-control { resize: vertical; min-height: 80px; }

.pd-sh-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.pd-sh-table thead th { background: var(--pd-bg); padding: 12px 16px; font-weight: 600; color: #475569; border-bottom: 2px solid var(--pd-border); text-align: right; font-size: 12px; text-transform: uppercase; }
.pd-sh-table tbody tr { border-bottom: 1px solid #f1f5f9; }
.pd-sh-table tbody td { padding: 12px 16px; color: #334155; vertical-align: middle; }
.pd-sh-name { font-weight: 600; color: #1e293b; }
.pd-sh-pct { color: #7c3aed; font-weight: 600; }
.pd-sh-amount { color: var(--pd-primary); font-weight: 700; font-size: 14px; }
.pd-sh-total { background: #f0fdf4; font-weight: 700; }
.pd-sh-total td { padding: 14px 16px; color: #1e293b; font-size: 14px; }

.pd-btn-row { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
.pd-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; transition: all .2s; text-decoration: none !important; }
.pd-btn-primary { background: var(--pd-primary); color: #fff !important; }
.pd-btn-primary:hover { background: #047857; }
.pd-btn-outline { background: #fff; color: #475569 !important; border: 1px solid var(--pd-border); }
.pd-btn-outline:hover { background: var(--pd-bg); }

.pd-alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
.pd-alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.pd-alert-info { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }

@media (max-width: 768px) {
    .pd-create { padding: 12px; }
    .pd-form-row { grid-template-columns: 1fr; }
    .pd-sh-table { font-size: 12px; }
}
</style>

<div class="pd-create">

    <div class="pd-page-header">
        <i class="fa fa-users"></i>
        <h1><?= $this->title ?></h1>
        <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['index'], ['class' => 'pd-back']) ?>
    </div>

    <?php if ($model->hasErrors()): ?>
        <div class="pd-alert pd-alert-error">
            <?php foreach ($model->getFirstErrors() as $err): ?>
                <div><?= Html::encode($err) ?></div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <?php if (empty($shareholders)): ?>
        <div class="pd-alert pd-alert-info">
            <i class="fa fa-info-circle"></i> لا يوجد مساهمين نشطين. يرجى إضافة مساهمين أولاً.
        </div>
    <?php endif ?>

    <?php $form = \yii\widgets\ActiveForm::begin(['id' => 'shareholders-form']); ?>

        <div class="pd-card">
            <div class="pd-card-head"><i class="fa fa-money"></i> بيانات التوزيع</div>
            <div class="pd-card-body">
                <div class="pd-form-row">
                    <div class="pd-form-group">
                        <label class="pd-form-label">المبلغ المقرر توزيعه <span style="color:#dc2626">*</span></label>
                        <?= Html::activeInput('number', $model, 'distribution_amount', [
                            'class' => 'pd-form-control',
                            'id' => 'distribution-amount',
                            'placeholder' => '0.00',
                            'step' => '0.01',
                            'min' => '0',
                        ]) ?>
                    </div>
                    <div class="pd-form-group">
                        <label class="pd-form-label">الفترة من <span style="color:#dc2626">*</span></label>
                        <?= Html::activeInput('date', $model, 'period_from', ['class' => 'pd-form-control']) ?>
                    </div>
                    <div class="pd-form-group">
                        <label class="pd-form-label">الفترة إلى <span style="color:#dc2626">*</span></label>
                        <?= Html::activeInput('date', $model, 'period_to', ['class' => 'pd-form-control']) ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($shareholders)): ?>
            <div class="pd-card">
                <div class="pd-card-head"><i class="fa fa-pie-chart"></i> التوزيع على المساهمين (إجمالي الأسهم: <?= number_format($totalShares) ?>)</div>
                <div style="overflow-x:auto">
                    <table class="pd-sh-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم المساهم</th>
                                <th>عدد الأسهم</th>
                                <th>النسبة %</th>
                                <th>المبلغ المستحق</th>
                            </tr>
                        </thead>
                        <tbody id="shareholders-tbody">
                            <?php foreach ($shareholders as $i => $sh):
                                $pct = $totalShares > 0 ? round(($sh->share_count / $totalShares) * 100, 4) : 0;
                            ?>
                                <tr data-shares="<?= $sh->share_count ?>">
                                    <td style="color:#94a3b8;font-size:12px"><?= $i + 1 ?></td>
                                    <td><span class="pd-sh-name"><?= Html::encode($sh->name) ?></span></td>
                                    <td><?= number_format($sh->share_count) ?></td>
                                    <td><span class="pd-sh-pct"><?= $pct ?>%</span></td>
                                    <td><span class="pd-sh-amount sh-calc-amount">0.00</span></td>
                                </tr>
                            <?php endforeach ?>
                            <tr class="pd-sh-total">
                                <td colspan="3"></td>
                                <td>الإجمالي</td>
                                <td><span class="pd-sh-amount" id="total-distribution">0.00</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pd-card">
                <div class="pd-card-head"><i class="fa fa-sticky-note-o"></i> ملاحظات</div>
                <div class="pd-card-body">
                    <?= Html::activeTextarea($model, 'notes', ['class' => 'pd-form-control', 'style' => 'width:100%', 'rows' => 3, 'placeholder' => 'أضف ملاحظات...']) ?>
                </div>
            </div>

            <div class="pd-btn-row">
                <?= Html::a('<i class="fa fa-arrow-right"></i> إلغاء', ['index'], ['class' => 'pd-btn pd-btn-outline']) ?>
                <button type="submit" name="save_distribution" value="1" class="pd-btn pd-btn-primary">
                    <i class="fa fa-save"></i> حفظ التوزيع
                </button>
            </div>
        <?php endif ?>

    <?php \yii\widgets\ActiveForm::end(); ?>

</div>

<?php
$totalSharesJs = $totalShares;
$js = <<<JS
var totalShares = {$totalSharesJs};
$('#distribution-amount').on('input change', function() {
    var amount = parseFloat($(this).val()) || 0;
    var sum = 0;
    $('#shareholders-tbody tr[data-shares]').each(function() {
        var shares = parseInt($(this).data('shares')) || 0;
        var lineAmount = totalShares > 0 ? (shares / totalShares) * amount : 0;
        lineAmount = Math.round(lineAmount * 100) / 100;
        sum += lineAmount;
        $(this).find('.sh-calc-amount').text(lineAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    });
    $('#total-distribution').text(sum.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
});
$('#distribution-amount').trigger('input');
JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>
