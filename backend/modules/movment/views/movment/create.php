<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\Movment */

$this->title = Yii::t('app', 'Create New Movment');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Movments'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="movment-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
