<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\notification\models\NotificationSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'إدارة الإشعارات');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notification-index">
    <?= GridView::widget([
        'id' => 'crud-datatable',
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pjax' => true,
        'summary' => '<div class="text-muted py-2">إجمالي: {totalCount} إشعار</div>',
        'columns' => require(__DIR__ . '/_columns.php'),
        'striped' => true,
        'condensed' => true,
        'responsive' => true,
        'hover' => true,
        'panel' => [
            'type' => 'default',
            'heading' => '<i class="fa fa-bell"></i> ' . $this->title,
            'before' => Html::a(
                '<i class="fa fa-plus"></i> ' . Yii::t('app', 'إرسال إشعار جديد'),
                ['create'],
                ['class' => 'btn btn-success btn-sm']
            ),
        ],
    ]) ?>
</div>
