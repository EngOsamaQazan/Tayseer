<?php
use common\components\GeneralHelpers;
use common\models\Profession;
use yii\helpers\Url;
/** @var $this \yii\web\View */
/** @var $model \common\models\Course */
?>
<?php
            ?>

          <div id='audience_selector'>     
      <?php      echo $form->field($model, "audience")->dropDownList(
                    Profession::getProfessionList(),
                    ['prompt' => '-- اختر الجمهور --', 'class' => 'form-control', 'multiple' => true]
                )->label(false); ?>


                
                
</div>