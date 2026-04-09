<?php
/**
 * @var yii\web\View $this
 * @var backend\modules\companyManagement\models\Company $model
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'تعديل: ' . $model->name_ar;
?>

<div class="company-update">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fa fa-edit"></i> <?= Html::encode($this->title) ?></h5>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'name_ar') ?>
                    <?= $form->field($model, 'name_en') ?>
                    <?= $form->field($model, 'domain')->textInput(['dir' => 'ltr']) ?>
                    <?= $form->field($model, 'server_ip')->textInput(['dir' => 'ltr']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'og_title') ?>
                    <?= $form->field($model, 'og_description')->textarea(['rows' => 2]) ?>
                    <?= $form->field($model, 'sms_sender')->textInput(['dir' => 'ltr']) ?>
                    <?= $form->field($model, 'status')->dropDownList([
                        'pending' => 'قيد الانتظار',
                        'dns_ready' => 'DNS جاهز',
                        'provisioned' => 'تم التجهيز',
                        'active' => 'نشط',
                        'disabled' => 'معطّل',
                    ]) ?>
                </div>
            </div>

            <hr>
            <div class="d-flex justify-content-between">
                <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
                <?= Html::submitButton('<i class="fa fa-save"></i> حفظ التعديلات', ['class' => 'btn btn-primary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
