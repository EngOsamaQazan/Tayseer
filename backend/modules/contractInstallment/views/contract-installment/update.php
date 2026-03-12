<?php
/**
 * شاشة تعديل الدفعة
 * ====================
 * 
 * @var yii\web\View $this
 * @var backend\modules\contractInstallment\models\ContractInstallment $model
 * @var int $contract_id رقم العقد
 * @var backend\modules\contracts\models\Contracts $contract_model
 */

use yii\helpers\Html;

$this->title = Yii::t('app', 'تعديل الدفعة رقم') . ': ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'الأقساط'), 'url' => ['index', 'contract_id' => $model->contract_id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="contract-installment-update box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-pencil"></i> <?= Html::encode($this->title) ?>
        </h3>
    </div>
    <div class="box-body">
        <?= $this->render('_form', [
            'model' => $model,
            'contract_id' => $contract_id,
            'contract_model' => $contract_model,
        ]) ?>
    </div>
</div>
