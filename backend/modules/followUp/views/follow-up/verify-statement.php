<?php
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $status string valid|expired|invalid */
/* @var $label string */
/* @var $message string */
/* @var $contract_id int|null */

$this->title = 'تحقق من كشف الحساب';
$this->registerCssFile(Yii::getAlias('@web') . '/css/follow-up-statement.css', ['depends' => ['yii\web\YiiAsset']]);

$statusClass = [
    'valid'   => 'jadal-verify--valid',
    'expired' => 'jadal-verify--expired',
    'invalid' => 'jadal-verify--invalid',
][$status] ?? 'jadal-verify--invalid';
?>
<div class="jadal-statement jadal-verify <?= $statusClass ?>">
    <div class="jadal-verify__box">
        <h1 class="jadal-verify__title">تحقق من كشف الحساب</h1>
        <p class="jadal-verify__label"><?= Html::encode($label) ?></p>
        <p class="jadal-verify__message"><?= Html::encode($message) ?></p>
        <?php if (isset($contract_id)): ?>
            <p class="jadal-verify__meta">رقم العقد: <?= (int) $contract_id ?></p>
        <?php endif; ?>
    </div>
</div>
