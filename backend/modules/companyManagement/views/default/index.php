<?php
/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

$this->title = 'إدارة المنشآت';
?>

<div class="company-management-index">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fa fa-building"></i> إدارة المنشآت</h5>
            <?= Html::a('<i class="fa fa-plus"></i> إضافة منشأة', ['create'], ['class' => 'btn btn-primary']) ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'tableOptions' => ['class' => 'table table-hover table-striped'],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'slug',
                        'name_ar',
                        [
                            'attribute' => 'domain',
                            'format' => 'raw',
                            'value' => function ($m) {
                                return Html::a($m->domain, 'https://' . $m->domain, ['target' => '_blank']);
                            },
                        ],
                        'db_name',
                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'value' => function ($m) {
                                return '<span class="badge ' . $m->getStatusBadgeClass() . '">' . $m->getStatusLabel() . '</span>';
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view}',
                            'buttons' => [
                                'view' => function ($url) {
                                    return Html::a('<i class="fa fa-eye"></i>', $url, [
                                        'class' => 'btn btn-sm btn-outline-primary',
                                        'title' => 'عرض',
                                    ]);
                                },
                            ],
                        ],
                    ],
                ]) ?>
            </div>
        </div>
    </div>
</div>
