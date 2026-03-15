<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\JudiciaryRequestTemplate */

$this->title = 'إضافة قالب طلب';
$this->params['breadcrumbs'][] = ['label' => 'قوالب الطلبات', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="judiciary-request-template-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
