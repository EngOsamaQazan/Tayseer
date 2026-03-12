<?php
/**
 * شاشة عرض تفاصيل المصروف
 * 
 * @var yii\web\View $this
 * @var backend\modules\expenses\models\Expenses $model
 */

use yii\widgets\DetailView;

$this->title = Yii::t('app', 'تفاصيل المصروف');
?>

<div class="expenses-view">
    <?= DetailView::widget([
        'model' => $model,
        'options' => ['class' => 'table table-striped table-bordered detail-view'],
        'attributes' => [
            ['attribute' => 'id', 'label' => Yii::t('app', '#')],
            ['attribute' => 'category_id', 'label' => Yii::t('app', 'التصنيف'), 'value' => $model->category->name ?? '—'],
            ['attribute' => 'description', 'label' => Yii::t('app', 'الوصف'), 'format' => 'ntext'],
            ['attribute' => 'amount', 'label' => Yii::t('app', 'المبلغ'), 'format' => 'decimal'],
            ['attribute' => 'receiver_number', 'label' => Yii::t('app', 'رقم المستلم')],
            ['attribute' => 'document_number', 'label' => Yii::t('app', 'رقم المستند')],
            ['attribute' => 'contract_id', 'label' => Yii::t('app', 'رقم العقد')],
            ['attribute' => 'created_by', 'label' => Yii::t('app', 'أنشئ بواسطة')],
            ['attribute' => 'created_at', 'label' => Yii::t('app', 'تاريخ الإنشاء')],
            ['attribute' => 'updated_at', 'label' => Yii::t('app', 'آخر تحديث')],
        ],
    ]) ?>
</div>
