<?php
/**
 * عرض تفاصيل حركة مالية
 */
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'حركة مالية #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'الحركات المالية', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="financial-transaction-view">
    <?= DetailView::widget([
        'model' => $model,
        'options' => ['class' => 'table table-striped table-bordered detail-view'],
        'attributes' => [
            ['attribute' => 'id', 'label' => '#'],
            ['attribute' => 'description', 'label' => 'الوصف'],
            ['attribute' => 'amount', 'label' => 'المبلغ', 'format' => ['decimal', 2]],
            [
                'attribute' => 'type', 'label' => 'النوع',
                'value' => $model->type == 1 ? 'دائنة (دخل)' : 'مدينة (مصاريف)',
            ],
            ['attribute' => 'date', 'label' => 'التاريخ'],
            ['attribute' => 'document_number', 'label' => 'رقم المستند'],
            ['label' => 'الشركة', 'value' => $model->company->name ?? '—'],
            ['attribute' => 'contract_id', 'label' => 'العقد'],
            ['attribute' => 'bank_description', 'label' => 'وصف البنك'],
            ['attribute' => 'notes', 'label' => 'ملاحظات'],
        ],
    ]) ?>
</div>
