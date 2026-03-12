<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  تعديل بيانات الموظف الموسعة
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\modules\hr\models\HrEmployeeExtended $model */

/* ─── اسم الموظف ─── */
$employeeName = '';
if ($model->user) {
    $employeeName = $model->user->name ?: $model->user->username;
}

$this->title = 'تعديل بيانات الموظف' . ($employeeName ? ' — ' . $employeeName : '');

/* ─── تسجيل CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);
?>

<div class="hr-page">

    <div style="margin-bottom:16px;">
        <?= Html::a('<i class="fa fa-arrow-right"></i> العودة إلى سجل الموظفين', ['index'], [
            'class' => 'btn btn-default btn-sm',
            'style' => 'border-radius:8px',
        ]) ?>
        <?php if ($model->user_id): ?>
            <?= Html::a('<i class="fa fa-eye"></i> عرض ملف الموظف', ['view', 'id' => $model->user_id], [
                'class' => 'btn btn-default btn-sm',
                'style' => 'border-radius:8px;margin-right:6px',
            ]) ?>
        <?php endif ?>
    </div>

    <div class="hr-page-header" style="margin-bottom:24px;">
        <div class="hr-page-header-right">
            <h1 class="hr-page-title">
                <i class="fa fa-pencil-square-o"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <span class="hr-page-subtitle">
                تحديث البيانات الموسعة للموظف
                <?php if ($model->employee_code): ?>
                    — <strong style="color:#800020"><?= Html::encode($model->employee_code) ?></strong>
                <?php endif ?>
            </span>
        </div>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
