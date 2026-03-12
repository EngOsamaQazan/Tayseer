<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\modules\capitalTransactions\models\CapitalTransactions $model */
/** @var backend\modules\companies\models\Companies|null $company */

$this->title = 'تعديل حركة رأس مال #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'المحافظ', 'url' => ['/companies/companies/index']];
if (isset($company) && $company) {
    $this->params['breadcrumbs'][] = ['label' => $company->name, 'url' => ['/companies/companies/view', 'id' => $company->id]];
    $this->params['breadcrumbs'][] = ['label' => 'حركات رأس المال', 'url' => ['index', 'company_id' => $company->id]];
} else {
    $this->params['breadcrumbs'][] = ['label' => 'حركات رأس المال', 'url' => ['index']];
}
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="capital-transactions-update">
    <?= $this->render('_form', [
        'model' => $model,
        'company' => $company ?? null,
    ]) ?>
</div>
