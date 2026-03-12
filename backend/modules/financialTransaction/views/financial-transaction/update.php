<?php
/**
 * تعديل حركة مالية
 */
use yii\helpers\Html;

$this->title = 'تعديل حركة مالية #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'الحركات المالية', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="financial-transaction-update">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-pencil"></i> <?= $this->title ?></h3>
            <div class="box-tools pull-left">
                <?= Html::a('<i class="fa fa-arrow-right"></i> الحركات المالية', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
            </div>
        </div>
        <div class="box-body">
            <?= $this->render('_form', ['model' => $model]) ?>
        </div>
    </div>
</div>
