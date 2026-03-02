<?php
use yii\helpers\Html;
use yii\helpers\Url;
?>

<form action="<?= Url::to(['confirm-delete', 'id' => $model->id]) ?>" method="post">
<?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

<style>
.cd-box { padding: 20px; }
.cd-warning {
    display: flex; align-items: center; gap: 14px; padding: 16px;
    background: #FEF3C7; border-radius: 10px; margin-bottom: 20px; border: 1px solid #FDE68A;
}
.cd-warning i { font-size: 32px; color: #D97706; }
.cd-warning-text h5 { margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #92400E; }
.cd-warning-text p { margin: 0; font-size: 13px; color: #A16207; }
.cd-safe {
    display: flex; align-items: center; gap: 14px; padding: 16px;
    background: #F0FDF4; border-radius: 10px; margin-bottom: 16px; border: 1px solid #BBF7D0;
}
.cd-safe i { font-size: 32px; color: #16A34A; }
.cd-safe-text h5 { margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #166534; }
.cd-safe-text p { margin: 0; font-size: 13px; color: #15803D; }
.cd-action-info {
    display: flex; align-items: center; gap: 10px; padding: 12px 16px;
    background: #F8FAFC; border-radius: 8px; margin-bottom: 20px; border: 1px solid #E2E8F0;
}
.cd-action-info i { font-size: 20px; color: #64748B; }
.cd-action-info span { font-size: 14px; font-weight: 600; color: #334155; }
.cd-action-info .cd-usage {
    margin-right: auto; font-size: 12px; color: #94A3B8; font-weight: 400;
}
.cd-option-group { margin-bottom: 16px; }
.cd-option {
    display: flex; align-items: flex-start; gap: 10px; padding: 14px 16px;
    border: 2px solid #E2E8F0; border-radius: 10px; margin-bottom: 10px;
    cursor: pointer; transition: all .2s;
}
.cd-option:hover { border-color: #93C5FD; background: #F8FAFC; }
.cd-option.selected { border-color: #3B82F6; background: #EFF6FF; }
.cd-option input[type="radio"] { margin-top: 3px; accent-color: #3B82F6; }
.cd-option-body { flex: 1; }
.cd-option-title { font-size: 14px; font-weight: 700; color: #1E293B; margin-bottom: 2px; }
.cd-option-desc { font-size: 12px; color: #64748B; }
.cd-migrate-section {
    padding: 16px; background: #EFF6FF; border-radius: 10px; border: 1px solid #BFDBFE;
    display: none;
}
.cd-migrate-section.visible { display: block; }
.cd-migrate-section label {
    display: block; font-size: 13px; font-weight: 700; color: #1E40AF; margin-bottom: 8px;
}
.cd-migrate-section select {
    width: 100%; padding: 10px 12px; border: 1px solid #93C5FD; border-radius: 8px;
    font-size: 14px; background: #fff;
}
.cd-migrate-hint {
    margin-top: 8px; font-size: 12px; color: #3B82F6;
}
.cd-danger-hint {
    display: flex; align-items: center; gap: 8px; padding: 10px 14px;
    background: #FEF2F2; border-radius: 8px; border: 1px solid #FECACA;
    font-size: 12px; color: #991B1B; margin-top: 12px; display: none;
}
.cd-danger-hint.visible { display: flex; }
.cd-danger-hint i { font-size: 14px; color: #EF4444; }
</style>

<div class="cd-box">
    <?php if (!empty($error)): ?>
    <div style="padding:10px 16px;background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;margin-bottom:16px;color:#991B1B;font-size:13px;font-weight:600">
        <i class="fa fa-exclamation-circle"></i> <?= Html::encode($error) ?>
    </div>
    <?php endif; ?>

    <div class="cd-action-info">
        <i class="fa fa-gavel"></i>
        <span><?= Html::encode($model->name) ?></span>
        <span class="cd-usage"><?= $usageCount ?> استخدام</span>
    </div>

    <?php if ($usageCount > 0): ?>
        <div class="cd-warning">
            <i class="fa fa-exclamation-triangle"></i>
            <div class="cd-warning-text">
                <h5>يوجد <?= number_format($usageCount) ?> سجل مرتبط بهذا الإجراء</h5>
                <p>اختر طريقة التعامل مع السجلات المرتبطة</p>
            </div>
        </div>

        <div class="cd-option-group">
            <label class="cd-option selected" id="cd-opt-migrate">
                <input type="radio" name="delete_mode" value="migrate" checked>
                <div class="cd-option-body">
                    <div class="cd-option-title"><i class="fa fa-exchange" style="color:#3B82F6;margin-left:4px"></i> نقل السجلات لإجراء بديل</div>
                    <div class="cd-option-desc">ترحيل <?= number_format($usageCount) ?> سجل إلى إجراء آخر قبل الحذف</div>
                </div>
            </label>
            <label class="cd-option" id="cd-opt-purge">
                <input type="radio" name="delete_mode" value="purge">
                <div class="cd-option-body">
                    <div class="cd-option-title"><i class="fa fa-trash" style="color:#EF4444;margin-left:4px"></i> حذف الإجراء مع جميع سجلاته</div>
                    <div class="cd-option-desc">سيتم حذف الإجراء وجميع السجلات المرتبطة به (<?= number_format($usageCount) ?> سجل) نهائياً</div>
                </div>
            </label>
        </div>

        <div class="cd-migrate-section visible" id="cd-migrate-panel">
            <label><i class="fa fa-exchange"></i> ترحيل السجلات إلى:</label>
            <?= Html::dropDownList('migrate_to_id', null, $otherActions, [
                'prompt' => '— اختر الإجراء البديل —',
                'class' => 'cd-migrate-select',
                'id' => 'migrate-to-select',
            ]) ?>
            <div class="cd-migrate-hint">
                <i class="fa fa-info-circle"></i>
                سيتم نقل جميع السجلات (<?= number_format($usageCount) ?>) من «<?= Html::encode($model->name) ?>» إلى الإجراء المحدد
            </div>
        </div>

        <div class="cd-danger-hint" id="cd-purge-warn">
            <i class="fa fa-exclamation-circle"></i>
            <span>تحذير: سيتم حذف <?= number_format($usageCount) ?> سجل بشكل نهائي ولا يمكن التراجع عن هذا الإجراء</span>
        </div>

    <?php else: ?>
        <div class="cd-safe">
            <i class="fa fa-check-circle"></i>
            <div class="cd-safe-text">
                <h5>لا توجد سجلات مرتبطة</h5>
                <p>يمكن حذف هذا الإجراء بأمان بدون تأثير على بيانات العملاء</p>
            </div>
        </div>
        <input type="hidden" name="delete_mode" value="purge">
    <?php endif; ?>
</div>
</form>

<script>
(function(){
    var opts = document.querySelectorAll('.cd-option');
    var migratePanel = document.getElementById('cd-migrate-panel');
    var purgeWarn = document.getElementById('cd-purge-warn');
    var migrateSelect = document.getElementById('migrate-to-select');

    opts.forEach(function(opt){
        opt.addEventListener('click', function(){
            opts.forEach(function(o){ o.classList.remove('selected'); });
            opt.classList.add('selected');
            var mode = opt.querySelector('input[type="radio"]').value;
            if (migratePanel) migratePanel.classList.toggle('visible', mode === 'migrate');
            if (purgeWarn) purgeWarn.classList.toggle('visible', mode === 'purge');
            if (migrateSelect && mode === 'purge') migrateSelect.removeAttribute('required');
            if (migrateSelect && mode === 'migrate') migrateSelect.setAttribute('required', 'required');
        });
    });
})();
</script>
