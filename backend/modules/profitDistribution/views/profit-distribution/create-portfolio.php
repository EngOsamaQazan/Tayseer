<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var backend\modules\profitDistribution\models\ProfitDistributionModel $model */
/** @var backend\modules\companies\models\Companies[] $companies */
/** @var array|null $calcResult */

$this->title = 'احتساب أرباح محفظة';
$this->params['breadcrumbs'][] = ['label' => 'توزيع الأرباح', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCss('.content-header,.page-header { display: none !important; }');

$companyOptions = ArrayHelper::map($companies, 'id', 'name');
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
.pd-create { max-width: 900px; margin: 0 auto; padding: 24px; font-family: 'Segoe UI', Tahoma, sans-serif; }

.pd-page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
.pd-page-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0; }
.pd-page-header i { color: var(--pd-primary); font-size: 20px; }
.pd-back { color: #64748b; text-decoration: none; font-size: 13px; margin-right: auto; display: flex; align-items: center; gap: 4px; }
.pd-back:hover { color: #1e293b; }

.pd-card { background: #fff; border-radius: var(--pd-r); box-shadow: var(--pd-shadow); border: 1px solid var(--pd-border); margin-bottom: 20px; overflow: hidden; }
.pd-card-head { background: var(--pd-bg); padding: 14px 20px; border-bottom: 1px solid var(--pd-border); font-size: 14px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.pd-card-head i { color: var(--pd-primary); }
.pd-card-body { padding: 20px; }

.pd-form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
.pd-form-row.two-col { grid-template-columns: 1fr 1fr; }
.pd-form-group { display: flex; flex-direction: column; gap: 4px; }
.pd-form-group.full { grid-column: 1 / -1; }
.pd-form-label { font-size: 13px; font-weight: 600; color: #475569; }
.pd-form-control { border: 1px solid var(--pd-border); border-radius: 8px; padding: 9px 14px; font-size: 14px; color: #1e293b; background: #fff; outline: none; transition: border .2s; }
.pd-form-control:focus { border-color: var(--pd-primary); box-shadow: 0 0 0 3px rgba(5,150,105,.1); }
select.pd-form-control { appearance: auto; }
textarea.pd-form-control { resize: vertical; min-height: 80px; }

.pd-pnl { padding: 0; }
.pd-pnl-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
.pd-pnl-row:last-child { border-bottom: none; }
.pd-pnl-label { font-size: 14px; color: #475569; display: flex; align-items: center; gap: 8px; }
.pd-pnl-value { font-size: 16px; font-weight: 700; }
.pd-pnl-green { color: #059669; }
.pd-pnl-red { color: #dc2626; }
.pd-pnl-bold { background: #f0fdf4; font-size: 16px; }
.pd-pnl-bold .pd-pnl-label { font-weight: 700; color: #1e293b; font-size: 15px; }
.pd-pnl-bold .pd-pnl-value { font-size: 20px; }
.pd-pnl-split { background: var(--pd-bg); }
.pd-pnl-split .pd-pnl-label { font-size: 13px; }

.pd-btn-row { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
.pd-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; transition: all .2s; text-decoration: none !important; }
.pd-btn-primary { background: var(--pd-primary); color: #fff !important; }
.pd-btn-primary:hover { background: #047857; }
.pd-btn-calculate { background: #2563eb; color: #fff !important; }
.pd-btn-calculate:hover { background: #1d4ed8; }
.pd-btn-outline { background: #fff; color: #475569 !important; border: 1px solid var(--pd-border); }
.pd-btn-outline:hover { background: var(--pd-bg); }

.pd-alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
.pd-alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

@media (max-width: 768px) {
    .pd-create { padding: 12px; }
    .pd-form-row { grid-template-columns: 1fr; }
}
</style>

<div class="pd-create">

    <div class="pd-page-header">
        <i class="fa fa-calculator"></i>
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

    <?php $form = \yii\widgets\ActiveForm::begin(['id' => 'portfolio-form']); ?>

        <div class="pd-card">
            <div class="pd-card-head"><i class="fa fa-filter"></i> معايير الاحتساب</div>
            <div class="pd-card-body">
                <div class="pd-form-row">
                    <div class="pd-form-group">
                        <label class="pd-form-label">المحفظة / المُستثمر <span style="color:#dc2626">*</span></label>
                        <?= Html::activeDropDownList($model, 'company_id', $companyOptions, [
                            'class' => 'pd-form-control',
                            'prompt' => '— اختر المحفظة —',
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
                <div class="pd-btn-row" style="margin-top:16px">
                    <button type="submit" class="pd-btn pd-btn-calculate"><i class="fa fa-calculator"></i> احتساب</button>
                </div>
            </div>
        </div>

        <?php if ($calcResult !== null): ?>
            <div class="pd-card">
                <div class="pd-card-head"><i class="fa fa-bar-chart"></i> نتائج الاحتساب (قائمة الأرباح والخسائر)</div>
                <div class="pd-pnl">
                    <div class="pd-pnl-row">
                        <span class="pd-pnl-label"><i class="fa fa-arrow-down" style="color:#059669"></i> الإيرادات</span>
                        <span class="pd-pnl-value pd-pnl-green"><?= number_format($calcResult['total_revenue'], 2) ?></span>
                    </div>
                    <div class="pd-pnl-row">
                        <span class="pd-pnl-label"><i class="fa fa-arrow-up" style="color:#dc2626"></i> المصاريف المباشرة</span>
                        <span class="pd-pnl-value pd-pnl-red">(<?= number_format($calcResult['direct_expenses'], 2) ?>)</span>
                    </div>
                    <div class="pd-pnl-row">
                        <span class="pd-pnl-label"><i class="fa fa-arrow-up" style="color:#dc2626"></i> حصة المصاريف المشتركة</span>
                        <span class="pd-pnl-value pd-pnl-red">(<?= number_format($calcResult['shared_expenses'], 2) ?>)</span>
                    </div>
                    <div class="pd-pnl-row pd-pnl-bold">
                        <span class="pd-pnl-label"><i class="fa fa-line-chart"></i> صافي الربح</span>
                        <span class="pd-pnl-value <?= $calcResult['net_profit'] >= 0 ? 'pd-pnl-green' : 'pd-pnl-red' ?>">
                            <?= number_format($calcResult['net_profit'], 2) ?>
                        </span>
                    </div>
                    <div class="pd-pnl-row pd-pnl-split">
                        <span class="pd-pnl-label"><i class="fa fa-user" style="color:#2563eb"></i> نسبة المُستثمر <?= $calcResult['investor_pct'] ?>%</span>
                        <span class="pd-pnl-value" style="color:#2563eb"><?= number_format($calcResult['investor_amount'], 2) ?></span>
                    </div>
                    <div class="pd-pnl-row pd-pnl-split">
                        <span class="pd-pnl-label"><i class="fa fa-building" style="color:#7c3aed"></i> نسبة الشركة الأم <?= $calcResult['parent_pct'] ?>%</span>
                        <span class="pd-pnl-value" style="color:#7c3aed"><?= number_format($calcResult['parent_amount'], 2) ?></span>
                    </div>
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
                <button type="submit" name="save_draft" value="1" class="pd-btn pd-btn-primary">
                    <i class="fa fa-save"></i> حفظ كمسودة
                </button>
            </div>
        <?php endif ?>

    <?php \yii\widgets\ActiveForm::end(); ?>

</div>
