<?php
/**
 * صفحة عرض الأخطاء — متوافقة مع الثيم
 *
 * @var yii\web\View $this
 * @var string $name
 * @var string $message
 * @var Exception $exception
 */
use yii\helpers\Html;

$this->title = 'حدث خطأ';

$statusCode = isset($exception->statusCode) ? $exception->statusCode : 500;

$arabicNames = [
    400 => 'طلب غير صالح',
    401 => 'غير مصرّح لك',
    403 => 'الوصول مرفوض',
    404 => 'الصفحة غير موجودة',
    405 => 'طريقة الطلب غير مسموحة',
    408 => 'انتهت مهلة الطلب',
    429 => 'طلبات كثيرة جداً',
    500 => 'خطأ في الخادم',
    502 => 'بوابة غير صالحة',
    503 => 'الخدمة غير متاحة مؤقتاً',
];

$arabicMessages = [
    400 => 'البيانات المرسلة غير صحيحة. تأكد من تعبئة جميع الحقول المطلوبة بشكل صحيح.',
    401 => 'يجب تسجيل الدخول للوصول إلى هذه الصفحة.',
    403 => 'ليس لديك الصلاحيات الكافية للوصول إلى هذه الصفحة. تواصل مع مدير النظام.',
    404 => 'الصفحة التي تبحث عنها غير موجودة. ربما تم نقلها أو حذفها.',
    500 => 'حدث خطأ داخلي في النظام. تم تسجيل المشكلة وسيتم مراجعتها.',
    503 => 'النظام قيد الصيانة حالياً. يرجى المحاولة لاحقاً.',
];

$arabicName = $arabicNames[$statusCode] ?? 'خطأ غير متوقع';
$arabicMsg  = $arabicMessages[$statusCode] ?? 'حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى.';

$icons = [
    400 => 'fa-exclamation-circle',
    401 => 'fa-lock',
    403 => 'fa-ban',
    404 => 'fa-search',
    500 => 'fa-server',
    503 => 'fa-wrench',
];
$icon = $icons[$statusCode] ?? 'fa-exclamation-triangle';

$colorMap = [
    400 => ['bg' => '#6f42c1', 'bg-dark' => '#9b6dd7'],
    401 => ['bg' => '#fd7e14', 'bg-dark' => '#ffad60'],
    403 => ['bg' => '#fd7e14', 'bg-dark' => '#ffad60'],
    404 => ['bg' => 'var(--bs-primary, #800020)', 'bg-dark' => 'var(--bs-primary, #e06080)'],
    500 => ['bg' => '#dc3545', 'bg-dark' => '#f06070'],
    503 => ['bg' => '#ffc107', 'bg-dark' => '#ffe066'],
];
$colors = $colorMap[$statusCode] ?? ['bg' => '#dc3545', 'bg-dark' => '#f06070'];
?>

<style>
.ty-error-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 70vh;
    padding: 30px 15px;
}
.ty-error-card {
    background: var(--bs-card-bg, #fff);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0,0,0,.08);
    max-width: 600px;
    width: 100%;
    text-align: center;
    padding: 50px 40px;
    direction: rtl;
    position: relative;
    overflow: hidden;
}
.ty-error-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: <?= $colors['bg'] ?>;
}
.ty-error-icon {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 28px;
    font-size: 42px;
    color: #fff;
    background: <?= $colors['bg'] ?>;
    animation: ty-error-pulse 2s ease-in-out infinite;
}
@keyframes ty-error-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(128,0,32,.2); }
    50% { box-shadow: 0 0 0 16px rgba(128,0,32,0); }
}
.ty-error-code {
    font-size: 64px;
    font-weight: 800;
    margin: 0 0 8px;
    line-height: 1;
    color: <?= $colors['bg'] ?>;
    letter-spacing: -2px;
}
.ty-error-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--bs-heading-color, var(--bs-body-color, #333));
    margin: 0 0 16px;
}
.ty-error-message {
    font-size: 15px;
    color: var(--bs-secondary-color, #6c757d);
    line-height: 1.8;
    margin-bottom: 10px;
}
.ty-error-detail {
    background: var(--bs-tertiary-bg, #f8f9fa);
    border: 1px solid var(--bs-border-color, #e9ecef);
    border-radius: 10px;
    padding: 14px 18px;
    font-size: 13px;
    color: var(--bs-secondary-color, #888);
    margin: 24px 0;
    word-break: break-word;
    text-align: right;
}
.ty-error-actions {
    margin-top: 32px;
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}
.ty-error-actions .btn {
    padding: 11px 30px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(.4,0,.2,1);
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.ty-error-actions .btn:active { transform: scale(0.96); }
.ty-error-btn-back {
    color: #fff;
    border: none;
    background: <?= $colors['bg'] ?>;
}
.ty-error-btn-back:hover {
    opacity: .9;
    color: #fff;
    text-decoration: none;
}
.ty-error-btn-home {
    background: var(--bs-tertiary-bg, #f8f9fa);
    color: var(--bs-body-color, #333);
    border: 1px solid var(--bs-border-color, #dee2e6);
}
.ty-error-btn-home:hover {
    background: var(--bs-secondary-bg, #e9ecef);
    color: var(--bs-body-color, #333);
    text-decoration: none;
}
.ty-error-ref {
    margin-top: 20px;
    font-size: 11px;
    color: var(--bs-tertiary-color, #adb5bd);
}

[data-bs-theme="dark"] .ty-error-icon { background: <?= $colors['bg-dark'] ?>; }
[data-bs-theme="dark"] .ty-error-code { color: <?= $colors['bg-dark'] ?>; }
[data-bs-theme="dark"] .ty-error-btn-back { background: <?= $colors['bg-dark'] ?>; }
[data-bs-theme="dark"] .ty-error-card::before { background: <?= $colors['bg-dark'] ?>; }

@media (max-width: 575.98px) {
    .ty-error-card { padding: 30px 20px; border-radius: 14px; }
    .ty-error-icon { width: 80px; height: 80px; font-size: 34px; }
    .ty-error-code { font-size: 48px; }
    .ty-error-title { font-size: 18px; }
    .ty-error-message { font-size: 13px; }
}
</style>

<div class="ty-error-page" role="alert" aria-live="assertive">
    <div class="ty-error-card">

        <div class="ty-error-icon" aria-hidden="true">
            <i class="fa <?= $icon ?>"></i>
        </div>

        <div class="ty-error-code"><?= $statusCode ?></div>

        <h1 class="ty-error-title"><?= Html::encode($arabicName) ?></h1>

        <p class="ty-error-message"><?= Html::encode($arabicMsg) ?></p>

        <?php if (!empty($message) && $message !== $arabicMsg): ?>
        <div class="ty-error-detail">
            <strong>تفاصيل:</strong> <?= Html::encode($message) ?>
        </div>
        <?php endif; ?>

        <div class="ty-error-actions">
            <a href="javascript:history.back()" class="btn ty-error-btn-back" aria-label="العودة للصفحة السابقة">
                <i class="fa fa-arrow-right"></i> رجوع
            </a>
            <a href="<?= Yii::$app->homeUrl ?>" class="btn ty-error-btn-home" aria-label="الذهاب للصفحة الرئيسية">
                <i class="fa fa-home"></i> الصفحة الرئيسية
            </a>
        </div>

        <div class="ty-error-ref">
            <?= date('Y-m-d H:i') ?> — <?= Html::encode(Yii::$app->request->url) ?>
        </div>

    </div>
</div>
