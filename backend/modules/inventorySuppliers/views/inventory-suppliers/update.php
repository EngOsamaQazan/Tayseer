<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\InventorySuppliers */

$this->title = Yii::t('app', 'Update Supplier');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Inventory Suppliers'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="inventory-suppliers-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
