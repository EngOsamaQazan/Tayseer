<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\InventoryStockLocations */

$this->title = Yii::t('app', 'Update Location');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Inventory Stock Location'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="inventory-stock-locations-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
