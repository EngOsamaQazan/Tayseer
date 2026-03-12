<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\Collection */

?>
<div class="collection-create">
    <?= $this->render('_form', [
        'model' => $model,
        'contract_id'=>$contract_id,
        'totle_value_price'=>$totle_value_price
    ]) ?>
</div>
