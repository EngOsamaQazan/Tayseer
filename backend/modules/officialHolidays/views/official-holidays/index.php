<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\officialHolidays\models\HolidaySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'العطل الرسمية';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="official-holidays-index">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-calendar"></i> <?= Html::encode($this->title) ?></h3>
            <div class="box-tools">
                <?= Html::a('<i class="fa fa-plus"></i> إضافة عطلة', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
            </div>
        </div>
        <div class="box-body">
            <?php Pjax::begin(['id' => 'official-holidays-pjax']); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'id' => 'official-holidays-grid',
                'tableOptions' => ['class' => 'table table-striped table-hover table-bordered'],
                'summary' => '<span class="text-muted">عرض {begin}-{end} من {totalCount}</span>',
                'pager' => [
                    'firstPageLabel' => 'الأولى',
                    'lastPageLabel' => 'الأخيرة',
                    'prevPageLabel' => 'السابق',
                    'nextPageLabel' => 'التالي',
                    'maxButtonCount' => 5,
                ],
                'columns' => require __DIR__ . '/_columns.php',
            ]); ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>
