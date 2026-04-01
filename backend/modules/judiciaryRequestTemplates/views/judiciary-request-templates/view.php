<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use backend\models\JudiciaryRequestTemplate;

/* @var $this yii\web\View */
/* @var $model backend\models\JudiciaryRequestTemplate */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'قوالب الطلبات', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="judiciary-request-template-view">

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
            <div class="box-tools pull-left">
                <?= Html::a('<i class="fa fa-edit"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn btn-primary btn-sm']) ?>
                <?= Html::a('<i class="fa fa-list"></i> القائمة', ['index'], ['class' => 'btn btn-secondary btn-sm']) ?>
            </div>
        </div>
        <div class="box-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'name',
                    [
                        'attribute' => 'template_type',
                        'value' => function ($model) {
                            $labels = JudiciaryRequestTemplate::getTypeLabels();
                            return $labels[$model->template_type] ?? $model->template_type;
                        },
                    ],
                    [
                        'attribute' => 'template_content',
                        'format' => 'raw',
                        'value' => $model->template_content ?: null,
                    ],
                    [
                        'attribute' => 'is_combinable',
                        'value' => $model->is_combinable ? 'نعم' : 'لا',
                    ],
                    'sort_order',
                    [
                        'attribute' => 'created_at',
                        'format' => ['date', 'php:Y-m-d H:i'],
                    ],
                    [
                        'attribute' => 'updated_at',
                        'format' => ['date', 'php:Y-m-d H:i'],
                    ],
                ],
            ]) ?>
        </div>
    </div>

</div>
