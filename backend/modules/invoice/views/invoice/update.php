<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Invoice */
?>
<div class="invoice-update">

    <?= $this->render('_form', [
        'model' => $model,
        'modelsItems' => $modelsAddress,
    ]) ?>

</div>
