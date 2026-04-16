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
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="fa-solid fa-file-lines me-2"></i><?= $this->title ?></h5>
            <?= Html::a('<i class="fa-solid fa-arrow-right me-1"></i> العقود', ['index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
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
