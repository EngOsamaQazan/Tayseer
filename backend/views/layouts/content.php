<?php
/**
 * Vuexy — Content Area (breadcrumbs + content + footer)
 */

use yii\helpers\Html;
use yii\widgets\Breadcrumbs;
?>

<main id="main-content" role="main">
<div class="container-xxl flex-grow-1 container-p-y">

    <?php if ($this->title !== null || isset($this->params['breadcrumbs'])): ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1 class="fw-bold py-1 mb-0" style="font-size:1.375rem">
            <?php
            if (isset($this->blocks['content-header'])) {
                echo $this->blocks['content-header'];
            } elseif ($this->title !== null) {
                echo Html::encode($this->title);
            }
            ?>
        </h1>
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            'options' => ['class' => 'breadcrumb mb-0'],
            'itemTemplate' => "<li class='breadcrumb-item'>{link}</li>\n",
            'activeItemTemplate' => "<li class='breadcrumb-item active'>{link}</li>\n",
        ]) ?>
    </div>
    <?php endif ?>

    <?= $content ?>

</div>

</main>

<footer class="content-footer footer bg-footer-theme">
    <div class="container-xxl">
        <div class="footer-container d-flex align-items-center justify-content-center py-3">
            <span class="text-body-secondary" style="font-size:12px">نظام تيسير لإدارة شركات التقسيط</span>
        </div>
    </div>
</footer>
