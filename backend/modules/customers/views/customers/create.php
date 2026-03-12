<?php
/**
 * إضافة عميل جديد — Smart Onboarding
 */
$this->title = 'إضافة عميل جديد';
$this->params['breadcrumbs'][] = ['label' => 'العملاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

echo $this->render('_smart_form', [
    'model' => $model,
    'modelsAddress' => $modelsAddress,
    'modelsPhoneNumbers' => $modelsPhoneNumbers,
    'customerDocumentsModel' => $customerDocumentsModel,
    'modelRealEstate' => $modelRealEstate,
]);
