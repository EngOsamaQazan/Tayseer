<?php
use common\components\GeneralHelpers;
use yii\helpers\Url;
?>
<?php
            echo $form->field($model, "speakers")->textInput([
                'placeholder' => Yii::t('app', "Select $type"),
                'class' => 'form-control',
            ])->label(false);?>
                <p class="notic"><?=yii::t('app','You Can Search by Full-Name, User-Name, Mobile Or Email')?></p>


