<?php
/**
 * Tabler — Content Area (page-header + page-body + footer)
 */

use yii\helpers\Html;
use yii\widgets\Breadcrumbs;
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title" style="font-family:var(--font-family, 'Cairo', sans-serif);font-weight:700;font-size:20px">
                    <?php
                    if (isset($this->blocks['content-header'])) {
                        echo $this->blocks['content-header'];
                    } elseif ($this->title !== null) {
                        echo Html::encode($this->title);
                    }
                    ?>
                </h2>
            </div>
            <div class="col-auto">
                <?= Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    'options' => ['class' => 'breadcrumb mb-0', 'style' => 'background:transparent;padding:0;font-size:13px'],
                    'itemTemplate' => "<li class='breadcrumb-item'>{link}</li>\n",
                    'activeItemTemplate' => "<li class='breadcrumb-item active'>{link}</li>\n",
                ]) ?>
            </div>
        </div>
    </div>
</div>

<!-- Page Body -->
<div class="page-body">
    <div class="container-xl">
        <?= $content ?>
    </div>
</div>

<!-- Footer -->
<footer class="footer footer-transparent d-print-none">
    <div class="container-xl text-center">
        <span class="text-muted" style="font-size:11px">نظام تيسير لإدارة شركات التقسيط</span>
    </div>
</footer>
