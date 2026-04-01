<?
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
/* @var $model */
$users =  Yii::$app->cache->getOrSet(Yii::$app->params["key_users"], function () {
    return Yii::$app->db->createCommand(Yii::$app->params['users_query'])->queryAll();
}, Yii::$app->params['time_duration']);
$_by  =   Yii::$app->cache->getOrSet(Yii::$app->params["key_income_by"], function () {
    return Yii::$app->db->createCommand(Yii::$app->params['income_by_query'])->queryAll();
}, Yii::$app->params['time_duration']);
?>
    <div class="questions-bank card card-body">

        <?php
        $form = yii\widgets\ActiveForm::begin([
            'id' => '_search',
            'method' => 'get',
            'action' => ['reports/total-judiciary-customer-payments-index']
        ]);
        ?>
        <div class ="row">
            <div class="col-lg-6">
                <?=
                $form->field($model, 'created_by')->dropDownList(
                    yii\helpers\ArrayHelper::map($users, 'id', 'username'),
                    ['prompt' => '-- اختر الموظف --', 'class' => 'form-control']
                );
                ?>
            </div>
            <div class="col-lg-6">
                <?= $form->field($model, '_by')->dropDownList(
                    yii\helpers\ArrayHelper::map(array_filter($_by, fn($row) => $row['_by'] !== null), '_by', '_by'),
                    ['prompt' => '-- اختر العميل --', 'class' => 'form-control']
                );
                ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?=
                $form->field($model, 'date_from')->widget(\backend\helpers\FlatpickrWidget::class, ['pluginOptions' => [
                    'dateFormat' => 'Y-m-d'
                ]]);
                ?>
            </div>
            <div class="col-lg-6">
                <?=
                $form->field($model, 'date_to')->widget(\backend\helpers\FlatpickrWidget::class, ['pluginOptions' => [
                    'dateFormat' => 'Y-m-d'
                ]]);
                ?>
            </div>

        </div>
        <div class="row">
            <div class="col-lg-6">
                <?=
                $form->field($model, 'followed_by')->dropDownList(
                    yii\helpers\ArrayHelper::map($users, 'id', 'username'),
                    ['prompt' => '-- اختر المتابع --', 'class' => 'form-control']
                );
                ?>
            </div>

        </div>
      <div class="form-group">
            <?= yii\helpers\Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
<?php yii\widgets\ActiveForm::end() ?>