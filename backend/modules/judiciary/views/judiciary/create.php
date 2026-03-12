<?php
use yii\helpers\Html;
use backend\modules\followUp\helper\ContractCalculations;

$this->title = 'إنشاء قضية - عقد #' . $contract_id;
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$calc = new ContractCalculations($contract_id);
?>

<style>
.jc-page{direction:rtl;font-family:'Tajawal','Segoe UI',sans-serif}
.jc-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.jc-title{font-size:22px;font-weight:700;color:#1E293B;display:flex;align-items:center;gap:10px}
.jc-title i{color:#16A34A}
.jc-nav .btn{border-radius:8px;font-size:13px;font-weight:600;padding:8px 18px}

.jc-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
.jc-stat{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:14px;transition:box-shadow .2s}
.jc-stat:hover{box-shadow:0 4px 12px rgba(0,0,0,.06)}
.jc-stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.jc-stat-value{font-size:18px;font-weight:700;color:#1E293B;direction:ltr;text-align:left}
.jc-stat-label{font-size:12px;color:#94A3B8;font-weight:500;margin-top:2px}

@media(max-width:992px){.jc-stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:576px){.jc-stats{grid-template-columns:1fr}.jc-header{flex-direction:column;align-items:flex-start}}
</style>

<div class="jc-page">
    <div class="jc-header">
        <div class="jc-title">
            <i class="fa fa-plus-circle"></i>
            <?= $this->title ?>
        </div>
        <div class="jc-nav">
            <?= Html::a('<i class="fa fa-arrow-right"></i> القضايا', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <div class="jc-stats">
        <div class="jc-stat">
            <div class="jc-stat-icon" style="background:#EFF6FF;color:#2563EB"><i class="fa fa-money"></i></div>
            <div>
                <div class="jc-stat-value"><?= number_format($calc->getContractTotal() ?? 0, 0) ?></div>
                <div class="jc-stat-label">إجمالي العقد</div>
            </div>
        </div>
        <div class="jc-stat">
            <div class="jc-stat-icon" style="background:#F0FDF4;color:#16A34A"><i class="fa fa-check"></i></div>
            <div>
                <div class="jc-stat-value"><?= number_format($calc->paidAmount() ?? 0, 0) ?></div>
                <div class="jc-stat-label">المدفوع</div>
            </div>
        </div>
        <div class="jc-stat">
            <div class="jc-stat-icon" style="background:#FFFBEB;color:#F59E0B"><i class="fa fa-clock-o"></i></div>
            <div>
                <div class="jc-stat-value"><?= number_format($calc->deservedAmount() ?? 0, 0) ?></div>
                <div class="jc-stat-label">المستحق</div>
            </div>
        </div>
        <div class="jc-stat">
            <div class="jc-stat-icon" style="background:#FEF2F2;color:#EF4444"><i class="fa fa-exclamation"></i></div>
            <div>
                <div class="jc-stat-value"><?= number_format($calc->remainingAmount() ?? 0, 0) ?></div>
                <div class="jc-stat-label">المتبقي</div>
            </div>
        </div>
    </div>

    <?= $this->render('_form', ['model' => $model, 'modelCustomerAction' => $modelCustomerAction, 'contract_id' => $contract_id]) ?>
</div>
