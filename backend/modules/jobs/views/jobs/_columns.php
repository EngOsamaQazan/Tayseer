<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use backend\modules\jobs\models\JobsType;

return [
    [
        'class' => 'kartik\grid\SerialColumn',
        'width' => '40px',
        'contentOptions' => ['data-label' => '#'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'name',
        'format' => 'raw',
        'contentOptions' => ['data-label' => 'الاسم'],
        'value' => function ($model) {
            return Html::a(
                '<i class="fa fa-building-o"></i> ' . Html::encode($model->name),
                ['view', 'id' => $model->id],
                ['class' => 'text-primary', 'style' => 'font-weight:600']
            );
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'job_type',
        'format' => 'raw',
        'contentOptions' => ['data-label' => 'نوع العمل'],
        'value' => function ($model) {
            return $model->jobType ? Html::encode($model->jobType->name) : '-';
        },
        'filter' => ArrayHelper::map(JobsType::find()->all(), 'id', 'name'),
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'address_city',
        'label' => 'المدينة',
        'contentOptions' => ['data-label' => 'المدينة'],
        'value' => function ($model) {
            return $model->address_city ?: '-';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'أرقام الهواتف',
        'format' => 'raw',
        'contentOptions' => ['data-label' => 'الهواتف'],
        'value' => function ($model) {
            $phones = $model->getPhones()->limit(2)->all();
            if (empty($phones)) {
                return '<span class="text-muted">-</span>';
            }
            $html = '';
            foreach ($phones as $phone) {
                $icon = $phone->phone_type === 'mobile' ? 'fa-mobile' : 'fa-phone';
                $html .= '<div><i class="fa ' . $icon . '"></i> ' . Html::encode($phone->phone_number) . '</div>';
            }
            $total = $model->getPhones()->count();
            if ($total > 2) {
                $html .= '<small class="text-muted">+' . ($total - 2) . ' أرقام أخرى</small>';
            }
            return $html;
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'العملاء',
        'format' => 'raw',
        'contentOptions' => ['data-label' => 'العملاء', 'class' => 'text-center'],
        'value' => function ($model) {
            $count = $model->getCustomersCount();
            if ($count > 0) {
                return '<span class="badge bg-blue">' . $count . '</span>';
            }
            return '<span class="text-muted">0</span>';
        },
        'headerOptions' => ['class' => 'text-center'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'التقييم',
        'format' => 'raw',
        'contentOptions' => ['data-label' => 'التقييم', 'class' => 'text-center', 'style' => 'white-space:nowrap'],
        'value' => function ($model) {
            $avg = $model->getAverageRating();
            if ($avg === null) {
                return '<span class="text-muted">لا يوجد</span>';
            }
            $html = '';
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= round($avg)) {
                    $html .= '<i class="fa fa-star text-warning"></i>';
                } else {
                    $html .= '<i class="fa fa-star-o text-muted"></i>';
                }
            }
            $html .= ' <small class="text-muted">(' . number_format($avg, 1) . ')</small>';
            return $html;
        },
        'headerOptions' => ['class' => 'text-center'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'status',
        'label' => 'الحالة',
        'format' => 'raw',
        'contentOptions' => ['data-label' => 'الحالة', 'class' => 'text-center'],
        'value' => function ($model) {
            return $model->getStatusBadge();
        },
        'filter' => [1 => 'فعال', 0 => 'غير فعال'],
        'headerOptions' => ['class' => 'text-center'],
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'header' => 'إجراءات',
        'dropdown' => false,
        'width' => '120px',
        'template' => '{view} {update} {delete}',
        'vAlign' => 'middle',
        'contentOptions' => ['data-label' => ''],
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'buttons' => [
            'view' => function ($url, $model) {
                return Html::a('<i class="fa fa-eye"></i>', $url, [
                    'class' => 'btn btn-xs btn-info',
                    'title' => 'عرض',
                    'data-bs-toggle' => 'tooltip',
                ]);
            },
            'update' => function ($url, $model) {
                return Html::a('<i class="fa fa-pencil"></i>', $url, [
                    'class' => 'btn btn-xs btn-primary',
                    'title' => 'تعديل',
                    'data-bs-toggle' => 'tooltip',
                ]);
            },
            'delete' => function ($url, $model) {
                return Html::a('<i class="fa fa-trash"></i>', $url, [
                    'class' => 'btn btn-xs btn-danger',
                    'title' => 'حذف',
                    'data-confirm' => 'هل أنت متأكد من الحذف؟',
                    'data-method' => 'post',
                    'data-bs-toggle' => 'tooltip',
                ]);
            },
        ],
    ],
];
