<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Shares */

$this->title = Yii::t('app', 'Create Shares');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Shares'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="shares-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
