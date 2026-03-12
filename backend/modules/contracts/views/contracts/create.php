<?php
/**
 * إنشاء عقد جديد
 */
use yii\helpers\Html;

$this->title = 'إنشاء عقد جديد';
$this->params['breadcrumbs'][] = ['label' => 'العقود', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="contracts-create">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-file-text-o"></i> <?= $this->title ?></h3>
            <div class="box-tools pull-left">
                <?= Html::a('<i class="fa fa-arrow-right"></i> العقود', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
            </div>
        </div>
        <div class="box-body">
            <?= $this->render('_form', [
                'model'              => $model,
                'companies'          => $companies,
                'inventoryItems'     => $inventoryItems,
                'scannedSerials'     => $scannedSerials,
                'existingCustomers'  => $existingCustomers ?? [],
                'existingGuarantors' => $existingGuarantors ?? [],
            ]) ?>
        </div>
    </div>
</div>
