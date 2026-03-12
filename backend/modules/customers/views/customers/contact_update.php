<?php
/**
 * شاشة تعديل معلومات الاتصال
 * ============================
 * غلاف بسيط لعرض نموذج تعديل معلومات الاتصال
 * 
 * @var yii\web\View $this
 * @var backend\modules\customers\models\Customers $model نموذج العميل
 * @var int $id معرّف العميل
 */
?>

<div class="box-body">
    <?= $this->render('contact_form', [
        'model' => $model,
        'id' => $id,
    ]) ?>
</div>
