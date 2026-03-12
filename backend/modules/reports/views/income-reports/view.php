<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\reports\models\IncomeReports */
?>
<div class="income-reports-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'contract_id',
            'date',
            'amount',
            'created_by',
            'payment_type',
            '_by',
            'receipt_bank',
            'payment_purpose',
            'financial_transaction_id',
            'type',
            'notes:ntext',
            'document_number',
        ],
    ]) ?>

</div>
