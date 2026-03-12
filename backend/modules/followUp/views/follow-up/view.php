<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use johnitvn\ajaxcrud\CrudAsset;
use common\helper\Permissions;

/* @var $this yii\web\View */
/* @var $modelView common\models\FollowUp */
CrudAsset::register($this);
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
            'feeling',
            'created_by',
            'connection_goal',
        ],
    ]) ?>
</div>
<script>

        alert('اي تعديلات تتم بهذه الصفحه لن يتم حفظها');

</script>