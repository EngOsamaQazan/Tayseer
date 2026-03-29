<?php

use yii\helpers\Html;

$this->title = 'تعديل: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'الفروع', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="branch-update">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
