<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\InventoryItemQuantities */

$this->title = Yii::t('app', 'Update Item Quantities');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Item Quantities'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="inventory-item-quantities-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
