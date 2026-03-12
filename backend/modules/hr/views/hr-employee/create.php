<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  إنشاء ملف موظف موسع جديد
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\modules\hr\models\HrEmployeeExtended $model */
/** @var array $userList */

$this->title = 'إضافة بيانات موظف';

/* ─── تسجيل CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);
?>

<div class="hr-page">

    <div style="margin-bottom:16px;">
        <?= Html::a('<i class="fa fa-arrow-right"></i> العودة إلى سجل الموظفين', ['index'], [
            'class' => 'btn btn-default btn-sm',
            'style' => 'border-radius:8px',
        ]) ?>
    </div>

    <div class="hr-page-header" style="margin-bottom:24px;">
        <div class="hr-page-header-right">
            <h1 class="hr-page-title">
                <i class="fa fa-user-plus"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <span class="hr-page-subtitle">إنشاء ملف بيانات موسعة لموظف جديد</span>
        </div>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'userList' => $userList ?? [],
    ]) ?>

</div>
