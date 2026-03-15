<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\judiciaryRequestTemplates\models\JudiciaryRequestTemplateSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'قوالب الطلبات';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="judiciary-request-template-index">

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
            <div class="box-tools pull-left">
                <?= Html::a('<i class="fa fa-plus"></i> إضافة قالب', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
            </div>
        </div>
        <div class="box-body">
            <?php Pjax::begin(['id' => 'grid-pjax']); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'layout' => "{items}\n{summary}\n{pager}",
                'summary' => '<span class="text-muted">عرض {begin}-{end} من {totalCount}</span>',
                'pager' => [
                    'firstPageLabel' => 'الأولى',
                    'lastPageLabel' => 'الأخيرة',
                    'prevPageLabel' => 'السابق',
                    'nextPageLabel' => 'التالي',
                    'maxButtonCount' => 5,
                ],
                'columns' => require(__DIR__ . '/_columns.php'),
            ]); ?>
            <?php Pjax::end(); ?>
        </div>
    </div>

</div>
