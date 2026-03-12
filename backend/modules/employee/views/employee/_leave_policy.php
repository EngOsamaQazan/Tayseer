<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var backend\models\Employee $model */

$employeeId = Yii::$app->getRequest()->getQueryParam('id');
$leavePolicies = $model->getLeavePolicy();
$userLeavePolicy = \common\models\UserLeavePolicy::find()
    ->select('leave_policy_id')
    ->where(['user_id' => $employeeId])
    ->asArray()
    ->all();
$selectedIds = array_column($userLeavePolicy, 'leave_policy_id');
?>

<div class="emp-leave-policy-wrapper">
    <?php $form = ActiveForm::begin([
        'action' => ['employee/employee-leave-policy', 'id' => $employeeId],
    ]); ?>

    <div class="emp-section">
        <div class="emp-section-header">
            <i class="fa fa-calendar-check-o"></i>
            <span><?= Yii::t('app', 'leave policy') ?></span>
        </div>
        <div class="emp-section-body">
            <?php if (!empty($leavePolicies)): ?>
                <p class="emp-leave-hint">
                    <i class="fa fa-info-circle"></i>
                    <?= Yii::t('app', 'Select the leave policies applicable to this employee') ?>
                </p>
                <div class="emp-leave-grid">
                    <?php foreach ($leavePolicies as $policy): ?>
                        <?php $isChecked = in_array($policy->id, $selectedIds); ?>
                        <label class="emp-leave-card <?= $isChecked ? 'selected' : '' ?>">
                            <input type="checkbox"
                                   name="Employee[leavePolicy][]"
                                   value="<?= $policy->id ?>"
                                   class="emp-leave-check"
                                   <?= $isChecked ? 'checked' : '' ?>
                                   style="display:none">
                            <div class="emp-leave-card-icon">
                                <i class="fa fa-calendar"></i>
                            </div>
                            <div class="emp-leave-card-info">
                                <span class="emp-leave-card-title"><?= Html::encode($policy->title) ?></span>
                                <?php if (!empty($policy->total_days)): ?>
                                    <span class="emp-leave-card-days"><?= $policy->total_days ?> <?= Yii::t('app', 'days') ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="emp-leave-card-check"><i class="fa fa-check-circle"></i></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="emp-no-attachments">
                    <i class="fa fa-calendar-times-o"></i>
                    <span><?= Yii::t('app', 'No Data Found!') ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!Yii::$app->request->isAjax): ?>
        <div class="emp-form-actions">
            <?= Html::submitButton(
                '<i class="fa fa-check-circle"></i> ' . Yii::t('app', 'Update'),
                ['class' => 'btn emp-btn-primary']
            ) ?>
        </div>
    <?php endif; ?>

    <?php ActiveForm::end(); ?>
</div>

<?php
$css = <<<CSS

/* ─── Leave Policy Hint ─── */
.emp-leave-hint {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.emp-leave-hint i {
    color: #94a3b8;
}

/* ─── Leave Policy Grid ─── */
.emp-leave-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 10px;
}

/* ─── Leave Policy Card ─── */
.emp-leave-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    background: #fff;
    position: relative;
    margin: 0;
}
.emp-leave-card:hover {
    border-color: #94a3b8;
    background: #f8fafc;
}
.emp-leave-card.selected {
    border-color: #800020;
    background: #fdf2f4;
    box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.08);
}
.emp-leave-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #eff6ff;
    color: #3b82f6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}
.emp-leave-card.selected .emp-leave-card-icon {
    background: rgba(128, 0, 32, 0.1);
    color: #800020;
}
.emp-leave-card-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.emp-leave-card-title {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
}
.emp-leave-card-days {
    font-size: 11px;
    color: #94a3b8;
}
.emp-leave-card-check {
    display: none;
    color: #800020;
    font-size: 18px;
}
.emp-leave-card.selected .emp-leave-card-check {
    display: flex;
}

@media (max-width: 768px) {
    .emp-leave-grid {
        grid-template-columns: 1fr;
    }
}

CSS;

$js = <<<JS

// Leave policy card toggle
document.querySelectorAll('.emp-leave-card').forEach(function(card) {
    card.addEventListener('click', function(e) {
        e.preventDefault();
        var cb = this.querySelector('.emp-leave-check');
        cb.checked = !cb.checked;
        this.classList.toggle('selected', cb.checked);
    });
});

JS;

$this->registerCss($css);
$this->registerJs($js, \yii\web\View::POS_END);
?>
