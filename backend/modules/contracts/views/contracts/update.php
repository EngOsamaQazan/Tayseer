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
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="card-title mb-0"><i class="fa-solid fa-pen-to-square me-2"></i><?= $this->title ?></h5>
            <div class="d-flex gap-2">
                <?= Html::a('<i class="fa-solid fa-eye me-1"></i> عرض', ['view', 'id' => $model->id], ['class' => 'btn btn-outline-info btn-sm']) ?>
                <?= Html::a('<i class="fa-solid fa-arrow-right me-1"></i> العقود', ['index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            </div>
        </div>
        <div class="card-body">
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
