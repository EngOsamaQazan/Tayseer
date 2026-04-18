<?php

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Customer Wizard V2 — shell view.
 *
 * Accessibility goals (WCAG 2.2 AA):
 *   • Single h1 (avoid duplication with breadcrumb h1).
 *   • Native <button> for stepper items (no role="button" on divs).
 *   • Hidden step sections use the `hidden` attribute (removes from a11y tree
 *     and tab order in one go) — supplemented by aria-hidden for legacy SR.
 *   • Status pill is a polite live region.
 *   • Bottom toolbar is <div role="toolbar"> (not <footer>).
 *   • Single instructions live region for SR-only step announcements.
 *
 * @var \yii\web\View                   $this
 * @var \common\models\WizardDraft|null $draft
 * @var array                           $payload
 * @var int                             $currentStep
 * @var int                             $totalSteps
 * @var array                           $lookups   ['cities'=>[], 'citizens'=>[], 'hearAboutUs'=>[]]
 */
$lookups = $lookups ?? ['cities' => [], 'citizens' => [], 'hearAboutUs' => []];

$this->title = 'إضافة عميل جديد';
$this->params['breadcrumbs'] = [
    ['label' => 'العملاء', 'url' => ['/customers/customers/index']],
    $this->title,
];

$baseUrl = Yii::$app->request->baseUrl;

// Cache-busting helper: appends ?v=<filemtime> so browsers reload the
// asset whenever we deploy a new version. This prevents the silent
// "I deployed a fix but users still see old behaviour" failure mode.
$webRoot = Yii::getAlias('@webroot');
$ver = static function ($relPath) use ($webRoot, $baseUrl) {
    $absolute = $webRoot . $relPath;
    $stamp = @filemtime($absolute) ?: 1;
    return $baseUrl . $relPath . '?v=' . $stamp;
};

$this->registerCssFile($ver('/css/customer-wizard/core.css'), [
    'depends' => [\yii\web\YiiAsset::class],
]);
$this->registerCssFile($ver('/css/customer-wizard/fields.css'), [
    'depends' => [\yii\web\YiiAsset::class],
]);
$this->registerCssFile($ver('/css/customer-wizard/scan-camera.css'), [
    'depends' => [\yii\web\YiiAsset::class],
]);
$this->registerCssFile($ver('/css/customer-wizard/combo.css'), [
    'depends' => [\yii\web\YiiAsset::class],
]);
$this->registerCssFile($ver('/css/customer-wizard/review.css'), [
    'depends' => [\yii\web\YiiAsset::class],
]);
// intl-tel-input — international phone-number widget (v27.0.11).
// Vendored locally under /vendor/intl-tel-input/ to keep the wizard
// fully functional on offline / air-gapped deployments. The CSS file
// resolves its flag sprites via relative `../img/flags.webp` URLs, so
// the {css/, img/, js/} sibling layout MUST be preserved on disk.
$this->registerCssFile($ver('/vendor/intl-tel-input/css/intlTelInput.min.css'), [
    'depends' => [\yii\web\YiiAsset::class],
]);
$this->registerCssFile($ver('/css/customer-wizard/intl-phone.css'), [
    'depends' => [\yii\web\YiiAsset::class],
]);
// Leaflet — small (45 KB gzip) interactive-map library used by the
// address-map widget on Step 3. Vendored locally so the wizard works
// on offline / air-gapped deployments. The CSS resolves marker icons
// via relative `images/marker-icon.png` URLs, so the {leaflet.css,
// images/} sibling layout MUST be preserved on disk.
$this->registerCssFile($ver('/vendor/leaflet/leaflet.css'), [
    'depends' => [\yii\web\YiiAsset::class],
]);
$this->registerCssFile($ver('/css/customer-wizard/address-map.css'), [
    'depends' => [\yii\web\YiiAsset::class],
]);
$this->registerJsFile($ver('/js/customer-wizard/core.js'), [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
$this->registerJsFile($ver('/js/customer-wizard/fields.js'), [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
$this->registerJsFile($ver('/js/customer-wizard/combo.js'), [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
// scan-camera.js MUST load before scan.js — the latter feature-detects
// `window.CWCamera` synchronously at click time, so the camera module
// has to be present in the global scope first.
$this->registerJsFile($ver('/js/customer-wizard/scan-camera.js'), [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
$this->registerJsFile($ver('/js/customer-wizard/scan.js'), [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
$this->registerJsFile($ver('/js/customer-wizard/scan-income.js'), [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
// intl-tel-input bundle (library + libphonenumber utils). MUST load
// before our wrapper so `window.intlTelInput` exists when intl-phone.js
// runs. The wrapper auto-polls for the global for ~5s as a safety net,
// but the synchronous order is the happy path.
$this->registerJsFile($ver('/vendor/intl-tel-input/js/intlTelInputWithUtils.min.js'), [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
$this->registerJsFile($ver('/js/customer-wizard/intl-phone.js'), [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
// Leaflet — must load BEFORE address-map.js so `window.L` exists when
// the wrapper initializes its widgets.
$this->registerJsFile($ver('/vendor/leaflet/leaflet.js'), [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
$this->registerJsFile($ver('/js/customer-wizard/address-map.js'), [
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
    'scan'       => Url::to(['/customers/wizard/scan']),
    'scanIncome' => Url::to(['/customers/wizard/scan-income']),
    'addCity'    => Url::to(['/customers/wizard/add-city']),
    'addCitizen' => Url::to(['/customers/wizard/add-citizen']),
    'addJob'     => Url::to(['/customers/wizard/add-job']),
    'addBank'    => Url::to(['/customers/wizard/add-bank']),
    'jobMeta'    => Url::to(['/customers/wizard/job-meta']),
    // Step 3 — address-map widget. Both endpoints proxy to LocationResolverService.
    'resolveLocation' => Url::to(['/customers/wizard/resolve-location']),
    'searchPlaces'    => Url::to(['/customers/wizard/search-places']),
    'reverseGeocode'  => Url::to(['/customers/wizard/reverse-geocode']),
];

// ── Google Maps JS API (Places library) — loaded only when an API key is
// configured; the address-map widget gracefully falls back to the Nominatim
// + Photon multi-source search otherwise. We can't vendor this script
// because the Places autocomplete element calls back to Google's CDN at
// runtime regardless, so the CDN script tag is the canonical pattern.
$googleMapsKey = \common\models\SystemSettings::get('google_maps', 'api_key', null)
    ?? Yii::$app->params['googleMapsApiKey']
    ?? null;
if ($googleMapsKey) {
    $this->registerJsFile(
        'https://maps.googleapis.com/maps/api/js?key=' . urlencode($googleMapsKey) . '&libraries=places&language=ar&loading=async',
        [
            'position' => \yii\web\View::POS_END,
            'async' => true,
            'defer' => true,
        ]
    );
}

$steps = [
    1 => ['label' => 'التعريف بالعميل',     'icon' => 'fa-user'],
    2 => ['label' => 'الوضع المالي',         'icon' => 'fa-briefcase'],
    3 => ['label' => 'الكفلاء والعنوان',     'icon' => 'fa-users'],
    4 => ['label' => 'المراجعة والاعتماد',   'icon' => 'fa-check-circle'],
];
?>

<div id="cw-shell" class="cw-shell" data-cw-current-step="<?= $currentStep ?>">
    <div class="cw-container">

        <header class="cw-header">
            <div class="cw-header__title-group">
                <!-- The page layout supplies an h1 with the same text; here we
                     give a useful subtitle (not a duplicate title) + the live
                     status pill. h2 keeps the heading hierarchy clean. -->
                <h2 class="cw-header__title">
                    <i class="fa fa-magic" aria-hidden="true"></i>
                    <span>إنشاء ملف عميل عبر 4 خطوات</span>
                </h2>
                <span class="cw-pill"
                      data-cw-status
                      role="status"
                      aria-live="polite"
                      aria-atomic="true"
                      aria-label="حالة الحفظ">
                    <i class="fa fa-cloud" aria-hidden="true"></i>
                    <span>جاهز</span>
                </span>
            </div>
            <div class="cw-header__actions" role="group" aria-label="إجراءات سريعة">
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

        <!-- ARIA: progressbar pattern would be wrong here (we want navigation,
             not just status). Use a tablist-like nav with native buttons. -->
        <nav class="cw-stepper" data-cw-stepper aria-label="خطوات إنشاء العميل">
            <ol class="cw-stepper__list" role="list">
            <?php foreach ($steps as $n => $meta): ?>
                <li class="cw-stepper__item">
                    <button type="button"
                            class="cw-step <?= $n === $currentStep ? 'cw-step--current' : '' ?>"
                            data-cw-step="<?= $n ?>"
                            <?= $n === $currentStep ? 'aria-current="step"' : '' ?>
                            aria-label="الخطوة <?= $n ?> من <?= $totalSteps ?>: <?= Html::encode($meta['label']) ?>">
                        <span class="cw-step__circle" aria-hidden="true">
                            <span class="cw-step__num"><?= $n ?></span>
                        </span>
                        <span class="cw-step__label"><?= Html::encode($meta['label']) ?></span>
                    </button>
                </li>
            <?php endforeach ?>
            </ol>
        </nav>

        <!-- SR-only live region for step transitions ("الانتقال إلى الخطوة 2 من 4"). -->
        <div class="cw-sr-only"
             role="status"
             aria-live="polite"
             aria-atomic="true"
             data-cw-announcer></div>

        <main class="cw-main">
            <?php for ($i = 1; $i <= $totalSteps; $i++): ?>
                <section class="cw-section <?= $i === $currentStep ? 'cw-section--active' : '' ?>"
                         data-cw-section="<?= $i ?>"
                         <?= $i === $currentStep ? '' : 'hidden inert' ?>
                         tabindex="-1"
                         aria-label="الخطوة <?= $i ?> من <?= $totalSteps ?>: <?= Html::encode($steps[$i]['label']) ?>">
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
                        'lookups' => $lookups,
                    ]);
                    ?>
                </section>
            <?php endfor ?>
        </main>

        <!-- Toolbar (not <footer>) — semantically a navigation toolbar. -->
        <div class="cw-nav" role="toolbar" aria-label="التنقّل بين خطوات المعالج">
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
        </div>

    </div>
</div>

<!-- Pre-mount the toast host so first toast doesn't insert a new live region. -->
<div class="cw-toast-host" role="region" aria-label="إشعارات النظام" aria-live="polite"></div>

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
