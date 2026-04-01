<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\helper\Permissions;

/* @var $this yii\web\View */
/* @var $modelView common\models\FollowUp */
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
]);
?>
<div class="follow-up-view">
<?=
$this->render('partial/follow-up-view',[
    'model' => $model,
    'contract_id' => $contract_id,
    'searchModel' => $searchModel,
    'dataProvider' => $dataProvider,
    'contract_model' => $contract_model,
    'modelsPhoneNumbersFollwUps' =>  $modelsPhoneNumbersFollwUps,
])
?>
    <h1><?= Html::encode($this->title) ?></h1>

    <?= DetailView::widget([
        'model' =>  $modelView,
        'attributes' => [
            'id',
            'contract_id',
            'date_time',
            'connection_type',
            'clinet_response:ntext',
            'created_by',
        ],
    ]) ?>
</div>
<script>

        alert('اي تعديلات تتم بهذه الصفحه لن يتم حفظها');

</script>