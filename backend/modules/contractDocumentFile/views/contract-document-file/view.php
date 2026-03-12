<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\ContractDocumentFile */
?>
<div class="contract-document-file-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'document_type',
            'contract_id',
        ],
    ]) ?>

</div>
