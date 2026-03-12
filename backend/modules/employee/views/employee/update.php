<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var backend\models\Employee $model */
/** @var int $id */
/** @var array $employeeAttachments */

$fullName = trim(implode(' ', array_filter([$model->name ?? '', $model->middle_name ?? '', $model->last_name ?? ''])));
$this->title = 'ملفي الشخصي - ' . Html::encode($model->name);
?>

<div class="employee-update-page">

    <!-- Page Header -->
    <div class="emp-page-header">
        <div class="emp-page-header-right">
            <h1 class="emp-page-title">
                <i class="fa fa-user-circle-o"></i>
                ملفي الشخصي
            </h1>
            <div class="emp-page-breadcrumb">
                <?= Html::a('الموظفين', ['index'], ['class' => 'emp-breadcrumb-link']) ?>
                <i class="fa fa-angle-left"></i>
                <span><?= Html::encode($fullName) ?></span>
            </div>
        </div>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'employeeAttachments' => $employeeAttachments,
        'id' => $id,
    ]) ?>

</div>

<?php
$css = <<<CSS

/* ─── Page Layout ─── */
.employee-update-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px 0;
}

/* ─── Page Header ─── */
.emp-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}
.emp-page-title {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.emp-page-title i {
    color: #800020;
    font-size: 24px;
}
.emp-page-breadcrumb {
    font-size: 13px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 6px;
}
.emp-breadcrumb-link {
    color: #800020;
    font-weight: 600;
    text-decoration: none;
}
.emp-breadcrumb-link:hover {
    color: #6b001a;
    text-decoration: underline;
}

/* ─── Responsive ─── */
@media (max-width: 768px) {
    .employee-update-page {
        padding: 12px;
    }
    .emp-page-title {
        font-size: 18px;
    }
}

CSS;

$this->registerCss($css);
?>
