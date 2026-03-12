<?php
/**
 * تعديل بيانات العميل — Smart Form (موحّد مع شاشة الإضافة)
 */
$this->title = 'تعديل: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'العملاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

echo $this->render('_smart_form', [
    'model' => $model,
    'modelsAddress' => $modelsAddress,
    'modelsPhoneNumbers' => $modelsPhoneNumbers,
    'customerDocumentsModel' => $customerDocumentsModel,
    'modelRealEstate' => $modelRealEstate,
]);
