<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Collection */
?>
<div class="collection-update">

    <?= $this->render('_form', [
        'model' => $model,
        'contract_id'=>$contract_id
    ]) ?>

</div>
