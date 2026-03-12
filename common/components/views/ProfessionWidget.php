<?php
use kartik\select2\Select2;
use yii\web\JsExpression;
use common\components\GeneralHelpers;
use common\models\Profession;
use yii\helpers\Url;
/** @var $this \yii\web\View */
/** @var $model \common\models\Course */
?>
<?php
            $professionName = !($model->isNewRecord) ? GeneralHelpers::getProfessionsName($model->audience) : [];
            ?>

          <div id='audience_selector'>     
      <?php      echo $form->field($model, "audience")->widget(Select2::classname(), [
                    'value' => $professionName,
                    'data' => Profession::getProfessionList(),
                    'maintainOrder' => true,
                    'options' => ['placeholder' => Yii::t('app',"Select Audience"),'multiple' => true],
                        'toggleAllSettings' => [
                            /*'selectLabel' => '', 
                            'unselectLabel' => '',*/
                           'selectOptions' => ['class' => 'text-success'],
                        ],
                    'pluginOptions' => [
                        'allowClear' => true,
                        'closeOnSelect' => false,
                        'tags' => true, 
                        //'minimumInputLength' => 3,
                        'language' => [
                            'errorLoading' => new JsExpression("function () { return 'Waiting for results...'; }"),
                        ]/*,
                        'ajax' => [
                            'url' => Url::to(['/dropdown/available-profession']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) { return {q:params.term}; }')
                        ],*/
                        /*'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                        'templateResult' => new JsExpression('function(city) { return city.text; }'),
                        'templateSelection' => new JsExpression('function (city) { return city.text; }'),*/
                    ],
                ])->label(false); ?>


                
                
</div>