<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Court */

$this->title = Yii::t('app', 'Update Court');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Court'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="court-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
