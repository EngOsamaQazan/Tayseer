<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Items */

$this->title = Yii::t('app', 'Create Item');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Items'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="items-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
