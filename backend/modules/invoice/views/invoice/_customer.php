<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\customers */
?>
<div class="customers-create">
    <?=
    $this->render('customers/create', [
        'model' => $model,
        'modelsAddress' => $modelsAddress,
        'modelsPhoneNumbers' => $modelsPhoneNumbers
            
    ])
    ?>
</div>
