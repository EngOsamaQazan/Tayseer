<?php
/**
 * شاشة عرض تفاصيل الدفعة
 * =========================
 * 
 * @var yii\web\View $this
 * @var backend\modules\contractInstallment\models\ContractInstallment $model
 * @var int $contract_id رقم العقد
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = Yii::t('app', 'تفاصيل الدفعة رقم') . ': ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'الأقساط'), 'url' => ['index', 'contract_id' => $contract_id]];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="contract-installment-view box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-eye"></i> <?= Html::encode($this->title) ?>
        </h3>
        <div class="box-tools pull-left">
            <?= Html::a(
                '<i class="fa fa-pencil"></i> ' . Yii::t('app', 'تعديل'),
                ['update', 'id' => $model->id],
                ['class' => 'btn btn-primary btn-sm']
            ) ?>
            <?= Html::a(
                '<i class="fa fa-trash"></i> ' . Yii::t('app', 'حذف'),
                ['delete', 'id' => $model->id],
                [
                    'class' => 'btn btn-danger btn-sm',
                    'data' => [
                        'confirm' => Yii::t('app', 'هل أنت متأكد من حذف هذه الدفعة؟'),
                        'method' => 'post',
                    ],
                ]
            ) ?>
        </div>
    </div>
    <div class="box-body">
        <?= DetailView::widget([
            'model' => $model,
            'options' => ['class' => 'table table-striped table-bordered detail-view'],
            'attributes' => [
                ['attribute' => 'id', 'label' => Yii::t('app', 'رقم الدفعة')],
                ['attribute' => 'contract_id', 'label' => Yii::t('app', 'رقم العقد')],
                ['attribute' => 'date', 'label' => Yii::t('app', 'التاريخ')],
                ['attribute' => 'amount', 'label' => Yii::t('app', 'المبلغ'), 'format' => 'decimal'],
                ['attribute' => 'created_by', 'label' => Yii::t('app', 'أُنشئ بواسطة')],
                ['attribute' => 'payment_type', 'label' => Yii::t('app', 'نوع الدفع')],
                ['attribute' => '_by', 'label' => Yii::t('app', 'بواسطة')],
                ['attribute' => 'receipt_bank', 'label' => Yii::t('app', 'بنك الإيصال')],
                ['attribute' => 'payment_purpose', 'label' => Yii::t('app', 'الغرض')],
            ],
        ]) ?>
    </div>
</div>
