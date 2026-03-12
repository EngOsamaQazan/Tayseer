<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\modules\companies\models\Companies */
$this->title = 'إضافة مُستثمر جديد';
$this->params['breadcrumbs'][] = ['label' => 'المُستثمرين', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="companies-create">
    <?= $this->render('_form', [
        'model' => $model,
        'modelsCompanieBanks'=>$modelsCompanieBanks
    ]) ?>
</div>
