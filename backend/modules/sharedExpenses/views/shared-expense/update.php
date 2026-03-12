<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\modules\sharedExpenses\models\SharedExpenseAllocation $model */

$this->title = 'تعديل التوزيع: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'توزيع المصاريف المشتركة', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCss('.content-header,.page-header { display: none !important; }');
?>

<div class="shared-expense-update" style="padding:24px;">
    <div style="max-width:1000px;margin:0 auto;">
        <h1 style="font-size:22px;font-weight:700;color:#1e293b;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
            <i class="fa fa-pencil" style="color:#8b5cf6"></i> <?= Html::encode($this->title) ?>
        </h1>

        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>
