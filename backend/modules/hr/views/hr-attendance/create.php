<?php
/**
 * إنشاء سجل حضور يدوي — Create Attendance Record
 *
 * @var $model \backend\modules\hr\models\HrAttendance
 * @var $employees array [id => name]
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'إدخال حضور يدوي';
?>

<style>
.hr-page { padding: 20px; }
.hr-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.hr-page-header h1 {
    font-size: 22px; font-weight: 700; color: var(--clr-primary, #800020); margin: 0;
}

.hr-form-card {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm);
    padding: 28px; max-width: 600px;
}
</style>

<div class="hr-page">
    <!-- Header -->
    <div class="hr-page-header">
        <h1><i class="fa fa-plus-circle"></i> <?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="fa fa-arrow-right"></i> لوحة الحضور', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <!-- Form Card -->
    <div class="hr-form-card">
        <?= $this->render('_form', [
            'model' => $model,
            'employees' => $employees,
        ]) ?>
    </div>
</div>
