<?php
/**
 * تعديل العقد
 */
use yii\helpers\Html;

$this->title = 'تعديل العقد #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'العقود', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="contracts-update">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-pencil"></i> <?= $this->title ?></h3>
            <div class="box-tools pull-left">
                <?= Html::a('<i class="fa fa-eye"></i> عرض', ['view', 'id' => $model->id], ['class' => 'btn btn-info btn-sm']) ?>
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
