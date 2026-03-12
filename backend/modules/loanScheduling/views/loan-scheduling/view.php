<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\loanScheduling\models\LoanScheduling */
?>
<div class="loan-scheduling-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'contract_id',
            'new_installment_date',
            'monthly_installment',
            'new_amount',
            'first_installment_date',
            'is_approved',
            'approved_by',
            'created_at',
            'updated_at',
            'created_by',
            'last_update_by',
            'is_deleted',
        ],
    ]) ?>

</div>
