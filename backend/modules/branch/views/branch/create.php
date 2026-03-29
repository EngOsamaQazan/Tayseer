<?php

use yii\helpers\Html;

$this->title = 'إضافة فرع جديد';
$this->params['breadcrumbs'][] = ['label' => 'الفروع', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="branch-create">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
