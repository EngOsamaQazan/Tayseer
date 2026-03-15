<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\judiciaryAuthorities\models\JudiciaryAuthoritySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'الجهات الرسمية';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="judiciary-authorities-index">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= Html::encode($this->title) ?></h5>
            <?= Html::a('<i class="fa fa-plus"></i> إضافة', ['create'], ['class' => 'btn btn-primary']) ?>
        </div>
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => require(__DIR__ . '/_columns.php'),
                'tableOptions' => ['class' => 'table table-striped table-bordered'],
            ]) ?>
        </div>
    </div>
</div>
