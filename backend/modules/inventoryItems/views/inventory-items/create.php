<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\InventoryItems */

$this->title = Yii::t('app', 'Create New Item');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Inventory Item'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="inventory-items-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
