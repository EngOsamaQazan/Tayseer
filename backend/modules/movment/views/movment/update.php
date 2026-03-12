<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Movment */
$this->title = Yii::t('app', 'Update Movment');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Movments'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="movment-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
