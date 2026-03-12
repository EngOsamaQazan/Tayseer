<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\modules\companies\models\Companies */

$this->title = 'تعديل بيانات مُستثمر';
$this->params['breadcrumbs'][] = ['label' => 'المُستثمرين', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="companies-update">

    <?= $this->render('_form', [
        'model' => $model,
        'modelsCompanieBanks'=>$modelsCompanieBanks
    ]) ?>

</div>
