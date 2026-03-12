<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\modules\JudiciaryInformAddress\model\JudiciaryInformAddress $model */

$this->title = Yii::t('app', 'Create Judiciary Inform Address');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Judiciary Inform Addresses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="judiciary-inform-address-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
