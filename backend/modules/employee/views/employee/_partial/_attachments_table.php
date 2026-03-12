<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $employeeAttachments */
/* @var $form \yii\widgets\ActiveForm */
/* @var $model \backend\models\Employee */

?>
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?= Yii::t('app', 'Attachments') ?></h3>
                        <?= $form->field($model, 'profile_attachment_files')->fileInput(['multiple' => false])->label(false) ?>
                    </div><!-- /.box-header -->
                    <div class="box-body">
                        <table id="example2" class="table table-hover">
                            <tbody>
                            <?php if (count($employeeAttachments) == 0) { ?>
                                <tr>
                                    <td colspan="2"><i><?= Yii::t('app', 'No Data Found!') ?></i></td>
                                </tr>
                            <?php } ?>
                            <?php foreach ($employeeAttachments as $employeeImage) { ?>
                                <tr>
                                    <td style="width: 98%"><?= $employeeImage->file_name ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" data-id='<?= $employeeImage->id ?>'><i
                                                    style="margin-right: 0" class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div><!-- /.box-body -->
                </div>
            </div>
        </div>
    </section>
<?php
$this->registerJs(<<<SCRIPT
$(document).on('click','button',function(){
id=$(this).attr('data-id');
$.post('remove-file',{id:id},function(){});
});
SCRIPT
);
?>