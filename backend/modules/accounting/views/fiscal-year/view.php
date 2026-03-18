<?php

use yii\helpers\Html;
use backend\modules\accounting\models\FiscalYear;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'السنوات المالية', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-calendar"></i> <?= Html::encode($model->name) ?></h3>
                <div class="box-tools">
                    <?php if ($model->status === FiscalYear::STATUS_OPEN): ?>
                        <?= Html::a('<i class="fa fa-edit"></i>', ['update', 'id' => $model->id], ['class' => 'btn btn-primary btn-xs']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-condensed">
                    <tr><th>من</th><td><?= $model->start_date ?></td></tr>
                    <tr><th>إلى</th><td><?= $model->end_date ?></td></tr>
                    <tr><th>الحالة</th><td><?= $model->getStatusBadge() ?></td></tr>
                    <tr>
                        <th>السنة الحالية</th>
                        <td><?= $model->is_current ? '<span class="label label-primary">نعم</span>' : 'لا' ?></td>
                    </tr>
                </table>

                <?php if ($model->status === FiscalYear::STATUS_OPEN): ?>
                <div style="margin-top:15px;">
                    <?= Html::a('<i class="fa fa-lock"></i> إغلاق السنة المالية', ['close-year', 'id' => $model->id], [
                        'class' => 'btn btn-danger btn-block',
                        'data' => [
                            'confirm' => 'هل أنت متأكد من إغلاق السنة المالية؟ لن تتمكن من تسجيل قيود جديدة عليها.',
                            'method' => 'post',
                        ],
                    ]) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-list-ol"></i> الفترات المحاسبية</h3>
            </div>
            <div class="box-body no-padding">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>الفترة</th>
                            <th class="text-center">من</th>
                            <th class="text-center">إلى</th>
                            <th class="text-center">الحالة</th>
                            <th class="text-center">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($model->periods as $period): ?>
                        <tr>
                            <td class="text-center"><?= $period->period_number ?></td>
                            <td><strong><?= Html::encode($period->name) ?></strong></td>
                            <td class="text-center"><?= $period->start_date ?></td>
                            <td class="text-center"><?= $period->end_date ?></td>
                            <td class="text-center"><?= $period->getStatusBadge() ?></td>
                            <td class="text-center">
                                <?php if ($period->status === 'open' && $model->status === FiscalYear::STATUS_OPEN): ?>
                                    <?= Html::a('<i class="fa fa-lock"></i> إغلاق', ['close-period', 'id' => $period->id], [
                                        'class' => 'btn btn-xs btn-warning',
                                        'data' => [
                                            'confirm' => 'هل أنت متأكد من إغلاق فترة ' . $period->name . '؟',
                                            'method' => 'post',
                                        ],
                                    ]) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
