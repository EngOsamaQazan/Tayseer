<?php
/**
 * شاشة عرض أقساط العقد
 * =======================
 * تعرض جدول بجميع الأقساط المدفوعة لعقد محدد
 * تشمل: ملخص مالي (المبلغ المدفوع، المتبقي، المستحق)
 * 
 * @var yii\web\View $this
 * @var backend\modules\contractInstallment\models\ContractInstallmentSearch $searchModel نموذج البحث
 * @var yii\data\ActiveDataProvider $dataProvider مزود البيانات
 * @var int $contract_id رقم العقد
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\contracts\models\Contracts;
use common\helper\LoanContract;

/* === إعداد عنوان الصفحة ومسار التنقل === */
$this->title = Yii::t('app', 'أقساط العقد رقم') . ': ' . $contract_id;
$this->params['breadcrumbs'][] = $this->title;

/* === حسابات مالية — من vw_contract_balance === */
$contractHelper = new LoanContract();
$contract_model = $contractHelper->findContract($contract_id);

$vb = \backend\modules\followUp\helper\ContractCalculations::fromView($contract_id);
if ($vb) {
    $contract_model->total_value = $vb['totalDebt'];
    $paidAmount = $vb['paid'];
} else {
    $paidAmount = ContractInstallment::find()
        ->where(['contract_id' => $contract_model->id])
        ->sum('amount') ?? 0;
}

$startDate = new DateTime($contract_model->first_installment_date);
$today = new DateTime(date('Y-m-d'));
$interval = $today->diff($startDate);
$monthsDiff = ($interval->y * 12) + $interval->m;
$batchesShouldBePaid = $monthsDiff + 1;
$amountShouldBePaid = min(
    $batchesShouldBePaid * ($vb ? $vb['effectiveInstallment'] : $contract_model->monthly_installment_value),
    $contract_model->total_value
);
?>

<div class="contract-installment-index">

    <!-- === قسم البحث === -->
    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <!-- === ملخص مالي للعقد === -->
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-calculator"></i> <?= Yii::t('app', 'الملخص المالي') ?>
            </h3>
        </div>
        <div class="box-body">
            <div class="row">
                <!-- القيمة الإجمالية -->
                <div class="col-md-3 col-sm-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-aqua"><i class="fa fa-money"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text"><?= Yii::t('app', 'القيمة الإجمالية') ?></span>
                            <span class="info-box-number"><?= number_format($contract_model->total_value, 2) ?> <small>د.أ</small></span>
                        </div>
                    </div>
                </div>

                <!-- المبلغ المستحق -->
                <div class="col-md-3 col-sm-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text"><?= Yii::t('app', 'المستحق حتى الآن') ?></span>
                            <span class="info-box-number"><?= number_format($amountShouldBePaid, 2) ?> <small>د.أ</small></span>
                        </div>
                    </div>
                </div>

                <!-- المبلغ المدفوع -->
                <div class="col-md-3 col-sm-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text"><?= Yii::t('app', 'المدفوع') ?></span>
                            <span class="info-box-number"><?= number_format($paidAmount, 2) ?> <small>د.أ</small></span>
                        </div>
                    </div>
                </div>

                <!-- المتبقي -->
                <div class="col-md-3 col-sm-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-red"><i class="fa fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text"><?= Yii::t('app', 'المتبقي') ?></span>
                            <span class="info-box-number"><?= number_format($contract_model->total_value - $paidAmount, 2) ?> <small>د.أ</small></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- === جدول الأقساط === -->
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-list"></i> <?= Yii::t('app', 'سجل الأقساط') ?>
            </h3>
            <div class="box-tools pull-left">
                <?= Html::a(
                    '<i class="fa fa-plus"></i> ' . Yii::t('app', 'إضافة دفعة'),
                    ['create', 'contract_id' => $contract_id],
                    ['class' => 'btn btn-success btn-sm']
                ) ?>
            </div>
        </div>
        <div class="box-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'summary' => false,
                'rowOptions' => function ($model) {
                    $isPaid = $model->hasAttribute('is_made_payment') ? $model->is_made_payment : 1;
                    if ($isPaid == 1) {
                        return ['class' => 'success'];
                    } elseif ($model->date < date('Y-m-d') && $isPaid == 0) {
                        return ['class' => 'warning'];
                    }
                    return [];
                },
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    [
                        'attribute' => 'date',
                        'label' => Yii::t('app', 'التاريخ'),
                    ],
                    [
                        'attribute' => 'amount',
                        'label' => Yii::t('app', 'المبلغ'),
                        'format' => 'decimal',
                    ],
                    [
                        'attribute' => 'payment_type',
                        'label' => Yii::t('app', 'نوع الدفع'),
                        'value' => 'paymentType.name',
                    ],
                    [
                        'attribute' => '_by',
                        'label' => Yii::t('app', 'بواسطة'),
                    ],
                    [
                        'attribute' => 'receipt_bank',
                        'label' => Yii::t('app', 'بنك الإيصال'),
                    ],
                    [
                        'attribute' => 'payment_purpose',
                        'label' => Yii::t('app', 'الغرض'),
                    ],
                    [
                        'attribute' => 'created_by',
                        'label' => Yii::t('app', 'المنشئ'),
                        'value' => 'createdByUser.username',
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => Yii::t('app', 'الإجراءات'),
                        'template' => '{view} {update} {delete}',
                        'buttons' => [
                            'view' => function ($url, $model) use ($contract_id) {
                                return Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $model->id, 'contract_id' => $contract_id], ['class' => 'btn btn-info btn-xs', 'title' => Yii::t('app', 'عرض')]);
                            },
                            'update' => function ($url, $model) {
                                return Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $model->id], ['class' => 'btn btn-warning btn-xs', 'title' => Yii::t('app', 'تعديل')]);
                            },
                            'delete' => function ($url, $model) {
                                return Html::a('<i class="fa fa-trash"></i>', ['delete', 'id' => $model->id], [
                                    'class' => 'btn btn-danger btn-xs',
                                    'title' => Yii::t('app', 'حذف'),
                                    'data' => ['confirm' => Yii::t('app', 'هل أنت متأكد من الحذف؟'), 'method' => 'post'],
                                ]);
                            },
                        ],
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
