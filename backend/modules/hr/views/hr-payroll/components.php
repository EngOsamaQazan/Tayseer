<?php
/**
 * مكونات الراتب — Salary Components Management
 */

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;

$this->title = 'مكونات الراتب';

$componentTypeMap = [
    'earning'   => ['label' => 'بدل',      'color' => '#28a745', 'icon' => 'fa-plus-circle'],
    'deduction' => ['label' => 'استقطاع',  'color' => '#dc3545', 'icon' => 'fa-minus-circle'],
];
?>

<style>
.hr-page { padding: 20px; }
.hr-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.hr-page-header h1 {
    font-size: 22px; font-weight: 700; color: var(--clr-primary, #800020); margin: 0;
}

.comp-type-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; color: #fff;
}

.comp-calc-badge {
    display: inline-block; padding: 2px 8px; border-radius: 4px;
    font-size: 11px; font-weight: 600;
    background: #f0f0f0; color: var(--clr-text, #212529);
}

.comp-active { color: #28a745; }
.comp-inactive { color: #dc3545; }

.hr-grid-card {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm); overflow: hidden;
}
.hr-grid-card .kv-grid-table th {
    background: #f8f9fa; font-size: 12px; font-weight: 700;
    color: var(--clr-text-muted, #6c757d); text-align: right;
}
.hr-grid-card .kv-grid-table td { font-size: 13px; vertical-align: middle; }

/* Info cards */
.comp-info-bar {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 14px; margin-bottom: 24px;
}
.comp-info-card {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm); padding: 16px; text-align: center;
    border-top: 4px solid var(--card-color, #800020);
}
.comp-info-card .card-icon {
    font-size: 24px; margin-bottom: 6px;
}
.comp-info-card .card-value {
    font-size: 24px; font-weight: 800; color: var(--clr-text, #212529);
}
.comp-info-card .card-label {
    font-size: 12px; color: var(--clr-text-muted, #6c757d);
}
</style>

<div class="hr-page">
    <!-- Header -->
    <div class="hr-page-header">
        <h1><i class="fa fa-puzzle-piece"></i> <?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="fa fa-plus"></i> مكون جديد', ['component-create'], [
                'class' => 'btn btn-primary btn-sm',
            ]) ?>
        </div>
    </div>

    <!-- Info Cards -->
    <?php
    // Count earnings and deductions
    $earningCount = 0;
    $deductionCount = 0;
    $models = $dataProvider->getModels();
    foreach ($models as $m) {
        if ($m->component_type === 'earning') {
            $earningCount++;
        } else {
            $deductionCount++;
        }
    }
    ?>
    <div class="comp-info-bar">
        <div class="comp-info-card" style="--card-color:#800020">
            <div class="card-icon"><i class="fa fa-puzzle-piece" style="color:#800020"></i></div>
            <div class="card-value"><?= $dataProvider->getTotalCount() ?></div>
            <div class="card-label">إجمالي المكونات</div>
        </div>
        <div class="comp-info-card" style="--card-color:#28a745">
            <div class="card-icon"><i class="fa fa-plus-circle" style="color:#28a745"></i></div>
            <div class="card-value"><?= $earningCount ?></div>
            <div class="card-label">بدلات</div>
        </div>
        <div class="comp-info-card" style="--card-color:#dc3545">
            <div class="card-icon"><i class="fa fa-minus-circle" style="color:#dc3545"></i></div>
            <div class="card-value"><?= $deductionCount ?></div>
            <div class="card-label">استقطاعات</div>
        </div>
    </div>

    <!-- Components GridView -->
    <div class="hr-grid-card">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'responsive' => true,
            'hover' => true,
            'striped' => false,
            'condensed' => true,
            'summary' => '<div class="text-muted" style="padding:10px 16px;font-size:12px">عرض {begin}-{end} من {totalCount} مكون</div>',
            'tableOptions' => ['class' => 'kv-grid-table table table-bordered'],
            'columns' => [
                [
                    'attribute' => 'code',
                    'header' => 'الرمز',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return '<code style="font-size:13px;font-weight:700;color:var(--clr-primary,#800020)">' . Html::encode($model->code) . '</code>';
                    },
                ],
                [
                    'attribute' => 'name',
                    'header' => 'الاسم',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return '<strong>' . Html::encode($model->name) . '</strong>';
                    },
                ],
                [
                    'attribute' => 'component_type',
                    'header' => 'النوع',
                    'format' => 'raw',
                    'value' => function ($model) use ($componentTypeMap) {
                        $type = $model->component_type ?? 'earning';
                        $info = $componentTypeMap[$type] ?? ['label' => $type, 'color' => '#999', 'icon' => 'fa-circle'];
                        return '<span class="comp-type-badge" style="background:' . $info['color'] . '"><i class="fa ' . $info['icon'] . '"></i> ' . $info['label'] . '</span>';
                    },
                ],
                [
                    'attribute' => 'is_fixed',
                    'header' => 'طريقة الحساب',
                    'format' => 'raw',
                    'value' => function ($model) {
                        if ($model->is_fixed) {
                            return '<span class="comp-calc-badge"><i class="fa fa-lock"></i> ثابت</span>';
                        }
                        $formula = $model->calculation_formula;
                        if ($formula) {
                            return '<span class="comp-calc-badge" title="' . Html::encode($formula) . '"><i class="fa fa-calculator"></i> معادلة</span>';
                        }
                        return '<span class="comp-calc-badge"><i class="fa fa-pencil"></i> متغير</span>';
                    },
                ],
                [
                    'attribute' => 'is_taxable',
                    'header' => 'خاضع للضريبة',
                    'format' => 'raw',
                    'value' => function ($model) {
                        if ($model->is_taxable) {
                            return '<i class="fa fa-check-circle" style="color:#28a745;font-size:16px" title="نعم"></i>';
                        }
                        return '<i class="fa fa-times-circle" style="color:#adb5bd;font-size:16px" title="لا"></i>';
                    },
                    'contentOptions' => ['style' => 'text-align:center'],
                ],
                [
                    'attribute' => 'sort_order',
                    'header' => 'الترتيب',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return '<span style="color:var(--clr-text-muted);font-weight:600">' . ((int) ($model->sort_order ?? 0)) . '</span>';
                    },
                    'contentOptions' => ['style' => 'text-align:center'],
                ],
                [
                    'class' => 'kartik\grid\ActionColumn',
                    'header' => 'إجراءات',
                    'template' => '{update}',
                    'buttons' => [
                        'update' => function ($url, $model) {
                            return Html::a(
                                '<i class="fa fa-pencil"></i> تعديل',
                                ['component-update', 'id' => $model->id],
                                ['class' => 'btn btn-xs btn-default']
                            );
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>
</div>
