<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\JudiciaryAuthority */

$this->title = 'إضافة جهة رسمية';
$this->params['breadcrumbs'][] = ['label' => 'الجهات الرسمية', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="judiciary-authorities-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
