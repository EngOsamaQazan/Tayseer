<?php
/**
 * قسم الديوان — إنشاء معاملة جديدة
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\modules\diwan\models\DiwanTransaction;

$this->title = 'قسم الديوان';
?>

<?= $this->render('@app/views/layouts/_diwan-tabs', ['activeTab' => 'create']) ?>

<style>
.dw-form-card {
    background: #fff; border-radius: 10px; box-shadow: 0 1px 8px rgba(0,0,0,.05);
    padding: 28px; max-width: 800px;
}
.dw-form-title { font-size: 18px; font-weight: 800; color: #333; margin-bottom: 4px; }
.dw-form-sub   { font-size: 12px; color: #888; margin-bottom: 24px; }

.dw-type-sel { display: flex; gap: 14px; margin-bottom: 22px; }
.dw-type-sel label {
    flex: 1; text-align: center; padding: 14px; border-radius: 8px; cursor: pointer;
    border: 2px solid #e0e0e0; transition: all .2s; font-weight: 700; font-size: 14px;
}
.dw-type-sel label:hover { border-color: var(--fin-primary, #800020); }
.dw-type-sel input[type=radio] { display: none; }
.dw-type-sel input[type=radio]:checked + label.lbl-recv { border-color: #4CAF50; background: #e8f5e9; color: #2e7d32; }
.dw-type-sel input[type=radio]:checked + label.lbl-dlvr { border-color: #FF9800; background: #fff3e0; color: #e65100; }

.dw-emp-row { display: grid; grid-template-columns: 1fr 50px 1fr; gap: 10px; align-items: end; margin-bottom: 22px; }
.dw-emp-arrow { text-align: center; padding-bottom: 18px; font-size: 22px; color: var(--fin-primary, #800020); }

.dw-contracts textarea {
    border: 2px solid #e0e0e0; border-radius: 8px; padding: 12px; font-size: 13px;
    min-height: 100px; resize: vertical; transition: border-color .2s;
}
.dw-contracts textarea:focus { border-color: var(--fin-primary, #800020); outline: none; }

.dw-form-btns { display: flex; gap: 10px; margin-top: 24px; }
.dw-form-btns .btn { padding: 10px 28px; border-radius: 8px; font-weight: 700; font-size: 14px; }
.dw-btn-primary { background: var(--fin-primary, #800020); color: #fff; border: none; }
.dw-btn-primary:hover { background: #5e1430; color: #fff; }
</style>

<div class="dw-form-card">
    <div class="dw-form-title"><i class="fa fa-plus-circle"></i> معاملة جديدة</div>
    <div class="dw-form-sub">سجّل عملية استلام أو تسليم وثائق</div>

    <?php $form = ActiveForm::begin(['id' => 'diwan-create-form']); ?>

    <!-- نوع المعاملة -->
    <div class="form-group">
        <label style="font-weight:700; margin-bottom:6px; display:block;">نوع المعاملة</label>
        <div class="dw-type-sel">
            <input type="radio" name="DiwanTransaction[transaction_type]" value="استلام" id="type-receive"
                   <?= $model->transaction_type === 'استلام' ? 'checked' : '' ?>>
            <label for="type-receive" class="lbl-recv"><i class="fa fa-download"></i><br>استلام</label>

            <input type="radio" name="DiwanTransaction[transaction_type]" value="تسليم" id="type-deliver"
                   <?= $model->transaction_type === 'تسليم' ? 'checked' : '' ?>>
            <label for="type-deliver" class="lbl-dlvr"><i class="fa fa-upload"></i><br>تسليم</label>
        </div>
    </div>

    <!-- من / إلى -->
    <div class="dw-emp-row">
        <div class="form-group">
            <?= $form->field($model, 'from_employee_id')->dropDownList(
                $employees,
                ['prompt' => '— اختر الموظف —', 'class' => 'form-control', 'style' => 'border-radius:6px; padding:8px;']
            )->label('من موظف') ?>
        </div>
        <div class="dw-emp-arrow"><i class="fa fa-long-arrow-left"></i></div>
        <div class="form-group">
            <?= $form->field($model, 'to_employee_id')->dropDownList(
                $employees,
                ['prompt' => '— اختر الموظف —', 'class' => 'form-control', 'style' => 'border-radius:6px; padding:8px;']
            )->label('إلى موظف') ?>
        </div>
    </div>

    <!-- أرقام العقود -->
    <div class="dw-contracts form-group">
        <label style="font-weight:700; margin-bottom:6px;">أرقام العقود</label>
        <textarea name="contract_numbers" class="form-control" placeholder="أدخل رقم عقد واحد في كل سطر&#10;مثال:&#10;7001&#10;7002&#10;7003"><?= Html::encode(Yii::$app->request->post('contract_numbers', '')) ?></textarea>
        <p class="help-block" style="font-size:11px; color:#999; margin-top:4px;">
            <i class="fa fa-info-circle"></i> أدخل كل رقم عقد في سطر مستقل، أو افصل بينها بفواصل
        </p>
    </div>

    <!-- تاريخ المعاملة -->
    <?= $form->field($model, 'transaction_date')->textInput([
        'type' => 'datetime-local',
        'value' => date('Y-m-d\TH:i'),
        'class' => 'form-control',
        'style' => 'border-radius:6px; padding:8px; max-width:280px;'
    ])->label('تاريخ المعاملة') ?>

    <!-- ملاحظات -->
    <?= $form->field($model, 'notes')->textarea([
        'rows' => 3,
        'placeholder' => 'ملاحظات إضافية (اختياري)',
        'style' => 'border-radius:8px; padding:12px;'
    ])->label('ملاحظات') ?>

    <!-- أزرار -->
    <div class="dw-form-btns">
        <?= Html::submitButton('<i class="fa fa-save"></i> حفظ المعاملة', ['class' => 'btn dw-btn-primary']) ?>
        <?= Html::a('إلغاء', ['index'], ['class' => 'btn btn-default', 'style' => 'border-radius:8px; padding:10px 28px;']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
