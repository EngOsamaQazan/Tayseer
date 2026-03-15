<?php

use yii\helpers\Html;

$this->title = 'إضافة مفوض / وكيل';
$this->params['breadcrumbs'][] = ['label' => 'المفوضين والوكلاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lawyers-create">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
