<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\modules\loanScheduling\models\LoanScheduling */

$this->title = 'إنشاء تسوية';
?>
<div class="loan-scheduling-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
