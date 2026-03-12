<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\modules\judiciary\models\JudiciaryType */

$this->title = Yii::t('app', 'Update Judiciary Type');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Judiciary Type'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="judiciary-type-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
