<?php
/**
 * أعمدة جدول جهات العمل — تصميم 2026
 *
 *  - data-label لكل خلية ⇒ يدعم تحويل الجدول إلى بطاقات تلقائياً على الموبايل
 *  - رسوم حديثة: أفاتار ملوّن، شارات للحالة والتقييم، عداد دائري للعملاء
 *  - أزرار إجراءات بأيقونات + tooltips، يستحيل إخفاؤها (راجع tayseer-responsive.js)
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use backend\modules\jobs\models\JobsType;

$avatarFromName = static function (?string $name): string {
    $name = trim((string) $name);
    if ($name === '') {
        return '?';
    }
    $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $name);
    $parts = preg_split('/\s+/u', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (empty($parts)) {
        return mb_substr($name, 0, 1, 'UTF-8');
    }
    if (count($parts) === 1) {
        return mb_substr($parts[0], 0, 2, 'UTF-8');
    }
    return mb_substr($parts[0], 0, 1, 'UTF-8') . mb_substr($parts[1], 0, 1, 'UTF-8');
};

return [
    [
        'class'          => 'kartik\grid\SerialColumn',
        'header'         => '#',
        'width'          => '52px',
        'headerOptions'  => ['style' => 'text-align:center'],
        'contentOptions' => ['data-label' => '#', 'style' => 'text-align:center;color:var(--jp-text-soft);font-weight:600'],
    ],
    [
        'class'          => 'kartik\grid\DataColumn',
        'attribute'      => 'name',
        'label'          => 'جهة العمل',
        'format'         => 'raw',
        'contentOptions' => ['data-label' => 'جهة العمل'],
        'value'          => function ($model) use ($avatarFromName) {
            $url     = Url::to(['view', 'id' => $model->id]);
            $initials = Html::encode($avatarFromName($model->name));
            $name    = Html::encode($model->name);
            $type    = $model->jobType ? Html::encode($model->jobType->name) : '';
            $meta    = $type !== '' ? '<div class="jp-job-meta"><i class="fa fa-tag"></i> ' . $type . '</div>' : '';
            return '<div class="jp-job-cell">'
                .  '<span class="jp-job-avatar" aria-hidden="true">' . $initials . '</span>'
                .  '<div style="min-width:0;flex:1">'
                .    '<a href="' . $url . '" class="jp-job-name" title="عرض التفاصيل">' . $name . '</a>'
                .    $meta
                .  '</div>'
                . '</div>';
        },
    ],
    [
        'class'          => 'kartik\grid\DataColumn',
        'attribute'      => 'job_type',
        'label'          => 'النوع',
        'format'         => 'raw',
        'contentOptions' => ['data-label' => 'النوع'],
        'value'          => function ($model) {
            return $model->jobType
                ? '<span class="jp-tag"><i class="fa fa-tag"></i> ' . Html::encode($model->jobType->name) . '</span>'
                : '<span style="color:var(--jp-text-soft)">—</span>';
        },
    ],
    [
        'class'          => 'kartik\grid\DataColumn',
        'attribute'      => 'address_city',
        'label'          => 'المدينة',
        'format'         => 'raw',
        'contentOptions' => ['data-label' => 'المدينة'],
        'value'          => function ($model) {
            if (empty($model->address_city)) {
                return '<span style="color:var(--jp-text-soft)">—</span>';
            }
            return '<span class="jp-tag jp-tag--city"><i class="fa fa-map-marker"></i> ' . Html::encode($model->address_city) . '</span>';
        },
    ],
    [
        'class'          => 'kartik\grid\DataColumn',
        'label'          => 'الهواتف',
        'format'         => 'raw',
        'contentOptions' => ['data-label' => 'الهواتف'],
        'value'          => function ($model) {
            $phones = $model->getPhones()->limit(2)->all();
            if (empty($phones)) {
                return '<span style="color:var(--jp-text-soft)">—</span>';
            }
            $html = '';
            foreach ($phones as $phone) {
                $isMobile = ($phone->phone_type === 'mobile');
                $icon = $isMobile ? 'fa-mobile' : 'fa-phone';
                $html .= '<span class="jp-phone"><i class="fa ' . $icon . '"></i> '
                       . Html::encode($phone->phone_number) . '</span>';
            }
            $total = (int) $model->getPhones()->count();
            if ($total > 2) {
                $extra = $total - 2;
                $html .= '<div style="margin-top:4px"><small style="color:var(--jp-text-soft)">+' . $extra . ' أرقام أخرى</small></div>';
            }
            return $html;
        },
    ],
    [
        'class'          => 'kartik\grid\DataColumn',
        'label'          => 'العملاء',
        'format'         => 'raw',
        'headerOptions'  => ['style' => 'text-align:center'],
        'contentOptions' => ['data-label' => 'العملاء', 'style' => 'text-align:center'],
        'value'          => function ($model) {
            $count = (int) $model->getCustomersCount();
            $cls = $count > 0 ? 'jp-count' : 'jp-count jp-count--zero';
            return '<span class="' . $cls . '" aria-label="عدد العملاء المرتبطين">' . $count . '</span>';
        },
    ],
    [
        'class'          => 'kartik\grid\DataColumn',
        'label'          => 'التقييم',
        'format'         => 'raw',
        'headerOptions'  => ['style' => 'text-align:center'],
        'contentOptions' => ['data-label' => 'التقييم', 'style' => 'text-align:center;white-space:nowrap'],
        'value'          => function ($model) {
            $avg = $model->getAverageRating();
            if ($avg === null) {
                return '<span style="color:var(--jp-text-soft)">—</span>';
            }
            $rounded = (int) round($avg);
            $stars   = '';
            for ($i = 1; $i <= 5; $i++) {
                $on = $i <= $rounded ? ' is-on' : '';
                $stars .= '<i class="fa fa-star' . $on . '" aria-hidden="true"></i>';
            }
            return '<span class="jp-stars" aria-label="متوسط التقييم ' . number_format($avg, 1) . ' من 5">'
                 . $stars
                 . '<span class="jp-stars-num">' . number_format($avg, 1) . '</span>'
                 . '</span>';
        },
    ],
    [
        'class'          => 'kartik\grid\DataColumn',
        'attribute'      => 'status',
        'label'          => 'الحالة',
        'format'         => 'raw',
        'headerOptions'  => ['style' => 'text-align:center'],
        'contentOptions' => ['data-label' => 'الحالة', 'style' => 'text-align:center'],
        'value'          => function ($model) {
            if ((int) $model->status === 1) {
                return '<span class="jp-status jp-status--active">فعّال</span>';
            }
            return '<span class="jp-status jp-status--inactive">غير فعّال</span>';
        },
    ],
    [
        'class'          => 'kartik\grid\ActionColumn',
        'header'         => 'إجراءات',
        'dropdown'       => false,
        'template'       => '{view} {update} {delete}',
        'vAlign'         => 'middle',
        'width'          => '140px',
        'headerOptions'  => ['style' => 'text-align:center', 'class' => 'kv-action-column'],
        'contentOptions' => ['data-label' => 'إجراءات', 'class' => 'kv-action-column', 'style' => 'text-align:center;white-space:nowrap'],
        'urlCreator'     => function ($action, $model) {
            return Url::to([$action, 'id' => $model->id]);
        },
        'buttons'        => [
            'view' => function ($url) {
                return Html::a('<i class="fa fa-eye" aria-hidden="true"></i><span class="visually-hidden">عرض</span>', $url, [
                    'class' => 'jp-act jp-act--view',
                    'title' => 'عرض',
                    'aria-label' => 'عرض جهة العمل',
                    'data-bs-toggle' => 'tooltip',
                    'data-pjax' => 0,
                ]);
            },
            'update' => function ($url) {
                return Html::a('<i class="fa fa-pencil" aria-hidden="true"></i><span class="visually-hidden">تعديل</span>', $url, [
                    'class' => 'jp-act jp-act--edit',
                    'title' => 'تعديل',
                    'aria-label' => 'تعديل جهة العمل',
                    'data-bs-toggle' => 'tooltip',
                    'data-pjax' => 0,
                ]);
            },
            'delete' => function ($url) {
                return Html::a('<i class="fa fa-trash" aria-hidden="true"></i><span class="visually-hidden">حذف</span>', $url, [
                    'class' => 'jp-act jp-act--delete',
                    'title' => 'حذف',
                    'aria-label' => 'حذف جهة العمل',
                    'data-confirm' => 'هل أنت متأكد من حذف جهة العمل هذه؟',
                    'data-method' => 'post',
                    'data-bs-toggle' => 'tooltip',
                    'data-pjax' => 0,
                ]);
            },
        ],
        'visibleButtons' => [
            'view'   => true,
            'update' => true,
            'delete' => true,
        ],
    ],
];
