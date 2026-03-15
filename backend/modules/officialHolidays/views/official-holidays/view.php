<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\Holiday */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'العطل الرسمية', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="official-holidays-view">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-eye"></i> <?= Html::encode($this->title) ?></h3>
            <div class="box-tools">
                <?= Html::a('<i class="fa fa-edit"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn btn-primary btn-sm']) ?>
                <?= Html::a('<i class="fa fa-list"></i> القائمة', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
            </div>
        </div>
        <div class="box-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    [
                        'attribute' => 'holiday_date',
                        'label' => 'تاريخ العطلة',
                        'format' => ['date', 'php:Y-m-d'],
                    ],
                    [
                        'attribute' => 'name',
                        'label' => 'اسم العطلة',
                    ],
                    [
                        'attribute' => 'year',
                        'label' => 'السنة',
                    ],
                    [
                        'attribute' => 'source',
                        'label' => 'المصدر',
                        'value' => $model->source === \backend\models\Holiday::SOURCE_MANUAL ? 'يدوي' : 'تلقائي (API)',
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'تاريخ الإنشاء',
                        'format' => ['date', 'php:Y-m-d H:i'],
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
