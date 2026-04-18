<?php

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Customer Wizard V2 — shell view.
 *
 * @var \yii\web\View          $this
 * @var \common\models\WizardDraft|null $draft
 * @var array                  $payload     decoded JSON payload (or empty array)
 * @var int                    $currentStep
 * @var int                    $totalSteps
 */

$this->title = 'إضافة عميل جديد';
$this->params['breadcrumbs'] = [
    ['label' => 'العملاء', 'url' => ['/customers/customers/index']],
    $this->title,
];

$baseUrl = Yii::$app->request->baseUrl;
$this->registerCssFile($baseUrl . '/css/customer-wizard/core.css', [
    'depends' => [\yii\web\YiiAsset::class],
]);
$this->registerJsFile($baseUrl . '/js/customer-wizard/core.js', [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);

$urls = [
    'start'    => Url::to(['/customers/wizard/start']),
    'step'     => Url::to(['/customers/wizard/step']),
    'save'     => Url::to(['/customers/wizard/save']),
    'validate' => Url::to(['/customers/wizard/validate']),
    'finish'   => Url::to(['/customers/wizard/finish']),
    'discard'  => Url::to(['/customers/wizard/discard']),
    'drafts'   => Url::to(['/customers/wizard/drafts']),
    'scan'     => Url::to(['/customers/wizard/scan']),
];

$steps = [
    1 => ['label' => 'التعريف بالعميل',     'icon' => 'fa-user'],
    2 => ['label' => 'العمل والدخل',         'icon' => 'fa-briefcase'],
    3 => ['label' => 'المعرّفون والعقارات',  'icon' => 'fa-users'],
    4 => ['label' => 'المراجعة والاعتماد',   'icon' => 'fa-check-circle'],
];
?>

<div id="cw-shell" class="cw-shell" data-cw-current-step="<?= $currentStep ?>">
    <div class="cw-container">

        <header class="cw-header">
            <h1 class="cw-header__title">
                <i class="fa fa-user-plus" aria-hidden="true"></i>
                <?= Html::encode($this->title) ?>
                <span class="cw-pill" data-cw-status>
                    <i class="fa fa-cloud" aria-hidden="true"></i>
                    <span>جاهز</span>
                </span>
            </h1>
            <div class="cw-header__actions">
                <button type="button" class="cw-btn cw-btn--ghost cw-btn--sm" data-cw-action="save-draft">
                    <i class="fa fa-floppy-o" aria-hidden="true"></i>
                    <span>حفظ كمسودة</span>
                </button>
                <button type="button" class="cw-btn cw-btn--outline cw-btn--sm" data-cw-action="discard">
                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                    <span>إلغاء وبدء جديد</span>
                </button>
                <a href="<?= Url::to(['/customers/customers/index']) ?>" class="cw-btn cw-btn--ghost cw-btn--sm">
                    <i class="fa fa-arrow-right" aria-hidden="true"></i>
                    <span>العودة للقائمة</span>
                </a>
            </div>
        </header>

        <nav class="cw-stepper" data-cw-stepper aria-label="خطوات إنشاء العميل">
            <?php foreach ($steps as $n => $meta): ?>
                <div class="cw-step <?= $n === $currentStep ? 'cw-step--current' : '' ?>"
                     data-cw-step="<?= $n ?>"
                     role="button"
                     tabindex="0"
                     aria-current="<?= $n === $currentStep ? 'step' : 'false' ?>"
                     aria-label="الخطوة <?= $n ?> من <?= $totalSteps ?>: <?= Html::encode($meta['label']) ?>">
                    <span class="cw-step__circle">
                        <span class="cw-step__num"><?= $n ?></span>
                    </span>
                    <span class="cw-step__label"><?= Html::encode($meta['label']) ?></span>
                </div>
            <?php endforeach ?>
        </nav>

        <main>
            <?php for ($i = 1; $i <= $totalSteps; $i++): ?>
                <section class="cw-section <?= $i === $currentStep ? 'cw-section--active' : '' ?>"
                         data-cw-section="<?= $i ?>"
                         aria-hidden="<?= $i === $currentStep ? 'false' : 'true' ?>"
                         aria-label="<?= Html::encode($steps[$i]['label']) ?>">
                    <?php
                    $partial = [
                        1 => '_step_1_identity',
                        2 => '_step_2_employment',
                        3 => '_step_3_guarantors',
                        4 => '_step_4_review',
                    ][$i];
                    echo $this->render($partial, [
                        'payload' => $payload,
                        'step'    => $i,
                    ]);
                    ?>
                </section>
            <?php endfor ?>
        </main>

        <footer class="cw-nav" role="navigation" aria-label="التنقّل بين الخطوات">
            <div class="cw-nav__group">
                <button type="button" class="cw-btn cw-btn--outline" data-cw-action="prev">
                    <i class="fa fa-arrow-right" aria-hidden="true"></i>
                    <span>السابق</span>
                </button>
            </div>
            <div class="cw-nav__group">
                <button type="button" class="cw-btn cw-btn--ghost" data-cw-action="save-draft">
                    <i class="fa fa-floppy-o" aria-hidden="true"></i>
                    <span>حفظ كمسودة</span>
                </button>
                <button type="button" class="cw-btn cw-btn--primary" data-cw-action="next">
                    <span>التالي</span>
                    <i class="fa fa-arrow-left" aria-hidden="true"></i>
                </button>
            </div>
        </footer>

    </div>
</div>

<?php
$urlsJson = json_encode($urls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$this->registerJs(<<<JS
jQuery(function () {
    if (window.CW && typeof CW.init === 'function') {
        CW.init({
            shellSelector: '#cw-shell',
            urls: {$urlsJson},
            totalSteps: {$totalSteps},
            currentStep: {$currentStep}
        });
    }
});
JS, \yii\web\View::POS_END);
?>
