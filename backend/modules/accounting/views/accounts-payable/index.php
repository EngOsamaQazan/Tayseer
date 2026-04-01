<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use kartik\grid\SerialColumn;
use yii\widgets\Pjax;
use backend\modules\accounting\models\Payable;

$this->title = 'الذمم الدائنة';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row" style="margin-bottom:20px;">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-value" style="color:var(--clr-primary, #800020);"><?= number_format($stats['total'], 2) ?></div>
            <div class="stat-label">إجمالي الذمم</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-value" style="color:#28a745;"><?= number_format($stats['paid'], 2) ?></div>
            <div class="stat-label">المسدّد</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-value" style="color:#ffc107;"><?= number_format($stats['balance'], 2) ?></div>
            <div class="stat-label">المتبقي للسداد</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-value" style="color:#dc3545;"><?= number_format($stats['overdue'], 2) ?></div>
            <div class="stat-label">متأخرة السداد</div>
        </div>
    </div>
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-arrow-circle-up"></i> <?= $this->title ?> <span class="badge"><?= $stats['count_open'] ?> مفتوحة</span></h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-clock-o"></i> تقرير أعمار الذمم', ['aging-report'], ['class' => 'btn btn-info btn-sm']) ?>
            <?= Html::a('<i class="fa fa-plus"></i> ذمة جديدة', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
    </div>
    <div class="box-body">
        <?php Pjax::begin(['id' => 'payables-pjax']); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'id' => 'payables-grid',
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'hover' => true,
            'toggleData' => false,
            'showPageSummary' => true,
            'summary' => '<span class="text-muted">عرض {begin}-{end} من {totalCount}</span>',
            'pager' => [
                'firstPageLabel' => 'الأولى',
                'lastPageLabel' => 'الأخيرة',
                'prevPageLabel' => 'السابق',
                'nextPageLabel' => 'التالي',
            ],
            'columns' => [
                ['class' => SerialColumn::class, 'header' => '#'],
                [
                    'attribute' => 'vendor_name',
                    'label' => 'المورد/الجهة',
                    'contentOptions' => ['style' => 'font-weight:600;'],
                ],
                [
                    'attribute' => 'category',
                    'label' => 'التصنيف',
                    'filter' => Payable::getCategories(),
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'amount',
                    'label' => 'المبلغ',
                    'format' => ['decimal', 2],
                    'contentOptions' => ['class' => 'text-left', 'style' => 'font-weight:600;'],
                    'pageSummary' => true,
                    'pageSummaryFunc' => GridView::F_SUM,
                ],
                [
                    'attribute' => 'paid_amount',
                    'label' => 'المدفوع',
                    'format' => ['decimal', 2],
                    'contentOptions' => ['class' => 'text-left', 'style' => 'color:#28a745; font-weight:600;'],
                    'pageSummary' => true,
                    'pageSummaryFunc' => GridView::F_SUM,
                ],
                [
                    'attribute' => 'balance',
                    'label' => 'المتبقي',
                    'format' => ['decimal', 2],
                    'contentOptions' => ['class' => 'text-left', 'style' => 'color:#dc3545; font-weight:700;'],
                    'pageSummary' => true,
                    'pageSummaryFunc' => GridView::F_SUM,
                ],
                [
                    'attribute' => 'due_date',
                    'label' => 'الاستحقاق',
                    'contentOptions' => function ($model) {
                        $overdue = $model->due_date && $model->due_date < date('Y-m-d') && $model->status !== 'paid';
                        return ['class' => 'text-center', 'style' => $overdue ? 'color:#dc3545; font-weight:700;' : ''];
                    },
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'status',
                    'label' => 'الحالة',
                    'format' => 'raw',
                    'value' => function ($model) { return $model->getStatusBadge(); },
                    'filter' => Payable::getStatuses(),
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'header' => 'الإجراءات',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $btns = '';
                        if ($model->status !== 'paid') {
                            $btns .= '<button type="button" class="btn btn-xs btn-success pay-btn" data-id="' . $model->id . '" data-balance="' . $model->balance . '" title="تسجيل دفعة"><i class="fa fa-money"></i></button> ';
                        }
                        $btns .= Html::a('<i class="fa fa-edit"></i>', ['update', 'id' => $model->id], ['class' => 'btn btn-xs btn-primary', 'title' => 'تعديل']);
                        return $btns;
                    },
                    'contentOptions' => ['class' => 'text-center', 'style' => 'white-space:nowrap;'],
                ],
            ],
        ]) ?>
        <?php Pjax::end(); ?>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="post" id="payment-form">
                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                <div class="modal-header">
                    <h4 class="modal-title"><i class="fa fa-money"></i> تسجيل دفعة</h4>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>الرصيد المتبقي</label>
                        <input type="text" class="form-control" id="modal-balance" readonly style="font-weight:700; font-size:18px;">
                    </div>
                    <div class="form-group">
                        <label>مبلغ الدفعة <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="payment_amount" class="form-control" id="modal-payment" required placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> تسجيل</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$this->registerJs("
$(document).on('click', '.pay-btn', function() {
    var id = $(this).data('id');
    var balance = $(this).data('balance');
    $('#modal-balance').val(parseFloat(balance).toFixed(2));
    $('#modal-payment').attr('max', balance).val('');
    $('#payment-form').attr('action', 'record-payment?id=' + id);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentModal')).show();
});
");
?>
