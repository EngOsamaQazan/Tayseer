<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\InventoryStockLocations */
$this->title = Yii::t('app', 'Create New Location');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Inventory Stock Location'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="inventory-stock-locations-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
