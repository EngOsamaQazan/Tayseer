<?php
/**
 * صفحة عرض الأخطاء — عربي
 *
 * @var yii\web\View $this
 * @var string $name
 * @var string $message
 * @var Exception $exception
 */
use yii\helpers\Html;

$this->title = 'حدث خطأ';

/* تحديد رمز الخطأ */
$statusCode = isset($exception->statusCode) ? $exception->statusCode : 500;

/* ترجمة أسماء الأخطاء الشائعة */
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

/* رموز تعبيرية حسب نوع الخطأ */
$icons = [
    400 => 'fa-exclamation-circle',
    401 => 'fa-lock',
    403 => 'fa-ban',
    404 => 'fa-search',
    500 => 'fa-server',
    503 => 'fa-wrench',
];
$icon = $icons[$statusCode] ?? 'fa-exclamation-triangle';

/* لون حسب الخطورة */
$color = ($statusCode >= 500) ? '#dc3545' : (($statusCode === 403 || $statusCode === 401) ? '#fd7e14' : '#6f42c1');
?>

<style>
.error-page-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 70vh;
    padding: 30px 15px;
}
.error-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,.08);
    max-width: 580px;
    width: 100%;
    text-align: center;
    padding: 50px 40px;
    direction: rtl;
}
.error-icon {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
    font-size: 40px;
    color: #fff;
}
.error-code {
    font-size: 56px;
    font-weight: 800;
    margin: 0 0 6px;
    line-height: 1;
}
.error-title {
    font-size: 22px;
    font-weight: 700;
    color: #333;
    margin: 0 0 16px;
}
.error-message {
    font-size: 15px;
    color: #666;
    line-height: 1.8;
    margin-bottom: 10px;
}
.error-detail {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 13px;
    color: #888;
    margin: 20px 0;
    word-break: break-word;
    text-align: right;
}
.error-actions {
    margin-top: 28px;
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}
.error-actions .btn {
    padding: 10px 28px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s;
}
.btn-back {
    color: #fff;
    border: none;
}
.btn-back:hover {
    opacity: .9;
    color: #fff;
    text-decoration: none;
}
.btn-home {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #dee2e6;
}
.btn-home:hover {
    background: #e9ecef;
    color: #333;
    text-decoration: none;
}
</style>

<div class="error-page-wrapper">
    <div class="error-card">

        <div class="error-icon" style="background: <?= $color ?>">
            <i class="fa <?= $icon ?>"></i>
        </div>

        <div class="error-code" style="color: <?= $color ?>"><?= $statusCode ?></div>

        <h1 class="error-title"><?= Html::encode($arabicName) ?></h1>

        <p class="error-message"><?= Html::encode($arabicMsg) ?></p>

        <?php if (!empty($message) && $message !== $arabicMsg): ?>
        <div class="error-detail">
            <strong>تفاصيل:</strong> <?= Html::encode($message) ?>
        </div>
        <?php endif; ?>

        <div class="error-actions">
            <a href="javascript:history.back()" class="btn btn-back" style="background: <?= $color ?>">
                <i class="fa fa-arrow-right"></i> &nbsp;رجوع
            </a>
            <a href="<?= Yii::$app->homeUrl ?>" class="btn btn-home">
                <i class="fa fa-home"></i> &nbsp;الصفحة الرئيسية
            </a>
        </div>

    </div>
</div>
