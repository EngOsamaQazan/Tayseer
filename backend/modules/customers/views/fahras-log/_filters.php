<?php

use yii\helpers\Html;

/* @var $this           yii\web\View                          */
/* @var $searchModel    \common\models\FahrasCheckLogSearch   */
/* @var $verdictOptions array                                 */
/* @var $sourceOptions  array                                 */
?>
<form method="get" class="fahras-log-filters" id="fahras-log-filters">
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label">بحث (الاسم / الرقم الوطني / الهاتف)</label>
            <?= Html::activeTextInput($searchModel, 'q', [
                'class'       => 'form-control',
                'placeholder' => 'ابحث…',
                'autocomplete'=> 'off',
            ]) ?>
        </div>
        <div class="col-md-2">
            <label class="form-label">من تاريخ</label>
            <?= Html::activeInput('date', $searchModel, 'dateFrom', ['class' => 'form-control']) ?>
        </div>
        <div class="col-md-2">
            <label class="form-label">إلى تاريخ</label>
            <?= Html::activeInput('date', $searchModel, 'dateTo', ['class' => 'form-control']) ?>
        </div>
        <div class="col-md-2">
            <label class="form-label">القرار</label>
            <?= Html::activeDropDownList($searchModel, 'verdict', $verdictOptions, ['class' => 'form-control']) ?>
        </div>
        <div class="col-md-2">
            <label class="form-label">المصدر</label>
            <?= Html::activeDropDownList($searchModel, 'source', $sourceOptions, ['class' => 'form-control']) ?>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fa fa-filter"></i>
            </button>
        </div>
    </div>
</form>
