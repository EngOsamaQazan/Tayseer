<?php
use yii\helpers\Html;

$this->title = 'تعديل القضية #' . $model->judiciary_number;
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.ju-page{direction:rtl;font-family:'Tajawal','Segoe UI',sans-serif}
.ju-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.ju-title{font-size:22px;font-weight:700;color:#1E293B;display:flex;align-items:center;gap:10px}
.ju-title i{color:#F59E0B}
.ju-nav{display:flex;gap:8px;flex-wrap:wrap}
.ju-nav .btn{border-radius:8px;font-size:13px;font-weight:600;padding:8px 18px}
@media(max-width:768px){
    .ju-header{flex-direction:column;align-items:flex-start}
}
</style>

<div class="ju-page">
    <div class="ju-header">
        <div class="ju-title">
            <i class="fa fa-pencil-square-o"></i>
            <?= $this->title ?>
        </div>
        <div class="ju-nav">
            <?= Html::a('<i class="fa fa-eye"></i> عرض القضية', ['view', 'id' => $model->id], ['class' => 'btn btn-info']) ?>
            <?= Html::a('<i class="fa fa-arrow-right"></i> القضايا', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <?= $this->render('_form', ['model' => $model, 'modelCustomerAction' => $modelCustomerAction, 'contract_id' => $model->contract_id]) ?>
</div>
