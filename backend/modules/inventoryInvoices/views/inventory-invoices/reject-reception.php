<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var $model \backend\modules\inventoryInvoices\models\InventoryInvoices */
$this->title = 'رفض استلام الفاتورة #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'أوامر الشراء', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'فاتورة #' . $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="inventory-invoices-reject-reception">
    <h2><?= Html::encode($this->title) ?></h2>
    <p class="text-muted">أدخل سبب رفض الاستلام. بعد الحفظ يمكن للمورد التعديل على الفاتورة ثم إعادة إرسالها، أو يمكنك الموافقة بعد التعديل.</p>
    <?= Html::beginForm(Url::to(['reject-reception', 'id' => $model->id]), 'post', ['class' => 'form-horizontal']) ?>
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
    <div class="form-group">
        <label class="control-label" for="rejection_reason">سبب الرفض</label>
        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="4" placeholder="اختياري"><?= Html::encode($model->rejection_reason) ?></textarea>
    </div>
    <div class="form-group">
        <?= Html::submitButton('تسجيل الرفض', ['class' => 'btn btn-warning']) ?>
        <?= Html::a('إلغاء', ['view', 'id' => $model->id], ['class' => 'btn btn-default']) ?>
    </div>
    <?= Html::endForm() ?>
</div>
