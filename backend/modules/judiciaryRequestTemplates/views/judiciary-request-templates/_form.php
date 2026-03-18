<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\models\JudiciaryRequestTemplate;
use backend\services\JudiciaryRequestGenerator;

/* @var $this yii\web\View */
/* @var $model backend\models\JudiciaryRequestTemplate */
/* @var $form yii\widgets\ActiveForm */

$placeholders = JudiciaryRequestGenerator::getPlaceholderLabels();
?>

<style>
.tpl-ph-bar{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:12px 16px;margin-bottom:8px}
.tpl-ph-title{font-size:12px;font-weight:700;color:#64748B;margin-bottom:8px;display:flex;align-items:center;gap:6px}
.tpl-ph-title i{color:#800020;font-size:14px}
.tpl-ph-chips{display:flex;flex-wrap:wrap;gap:6px}
.tpl-ph-chip{display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;border:1px solid #E2E8F0;background:#fff;color:#334155;transition:all .15s;user-select:none}
.tpl-ph-chip:hover{background:#800020;color:#fff;border-color:#800020;transform:translateY(-1px);box-shadow:0 2px 6px rgba(128,0,32,.2)}
.tpl-ph-chip:active{transform:translateY(0)}
.tpl-ph-chip code{font-family:'Courier New',monospace;font-size:10px;color:#94A3B8;padding:1px 4px;background:#F1F5F9;border-radius:3px;transition:all .15s}
.tpl-ph-chip:hover code{color:rgba(255,255,255,.7);background:rgba(255,255,255,.15)}
.tpl-ph-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);background:#065F46;color:#fff;padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;z-index:9999;opacity:0;transition:all .3s;pointer-events:none;display:flex;align-items:center;gap:6px}
.tpl-ph-toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
</style>

<div class="judiciary-request-template-form">

    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'jadal-form'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{hint}\n{error}",
            'labelOptions' => ['class' => 'control-label'],
        ],
    ]); ?>

    <div class="box box-primary">
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'اسم القالب']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'template_type')->dropDownList(
                        JudiciaryRequestTemplate::getTypeLabels(),
                        ['prompt' => 'اختر نوع القالب...']
                    ) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="tpl-ph-bar">
                        <div class="tpl-ph-title"><i class="fa fa-code"></i> المتغيرات المتاحة <span style="font-weight:400;color:#94A3B8">(اضغط لإدراج في محتوى القالب)</span></div>
                        <div class="tpl-ph-chips">
                            <?php foreach ($placeholders as $key => $label): ?>
                                <span class="tpl-ph-chip" data-placeholder="<?= Html::encode($key) ?>" title="<?= Html::encode($key) ?>">
                                    <?= Html::encode($label) ?>
                                    <code><?= Html::encode($key) ?></code>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?= $form->field($model, 'template_content')->textarea([
                        'rows' => 15,
                        'placeholder' => 'محتوى القالب... اضغط على المتغيرات أعلاه لإدراجها تلقائياً',
                        'id' => 'template-content-textarea',
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'is_combinable')->checkbox() ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'sort_order')->input('number', ['min' => 0]) ?>
                </div>
            </div>
        </div>
        <div class="box-footer jadal-form-actions">
            <?= Html::submitButton(
                $model->isNewRecord ? '<i class="fa fa-save"></i> حفظ' : '<i class="fa fa-check"></i> تحديث',
                ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
            ) ?>
            <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<div class="tpl-ph-toast" id="tplToast"><i class="fa fa-check-circle"></i> <span id="tplToastText"></span></div>

<?php
$js = <<<'JS'
(function(){
    var $ta = document.getElementById('template-content-textarea');
    var $toast = document.getElementById('tplToast');
    var $toastText = document.getElementById('tplToastText');
    var toastTimer = null;

    function showToast(text) {
        $toastText.textContent = text;
        $toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function(){ $toast.classList.remove('show'); }, 1500);
    }

    document.querySelectorAll('.tpl-ph-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            var ph = this.getAttribute('data-placeholder');
            var label = this.childNodes[0].textContent.trim();
            if (!$ta) return;

            $ta.focus();
            var start = $ta.selectionStart;
            var end = $ta.selectionEnd;
            var val = $ta.value;
            $ta.value = val.substring(0, start) + ph + val.substring(end);
            var newPos = start + ph.length;
            $ta.selectionStart = newPos;
            $ta.selectionEnd = newPos;
            $ta.focus();

            // Trigger change for Yii2 validation
            var evt = new Event('input', { bubbles: true });
            $ta.dispatchEvent(evt);

            showToast('تم إدراج: ' + label);
        });
    });
})();
JS;
$this->registerJs($js);
?>
