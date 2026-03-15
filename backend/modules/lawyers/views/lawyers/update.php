<?php

use yii\helpers\Html;

$this->title = 'تعديل: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'المفوضين والوكلاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lawyers-update">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
