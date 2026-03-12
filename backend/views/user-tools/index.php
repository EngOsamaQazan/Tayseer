<?php
/**
 * أدوات المستخدم: فحص حساب، إصلاح، تعيين كلمة مرور
 * @var yii\web\View $this
 * @var string $login
 * @var string $password
 * @var array|null $result
 * @var string|null $error
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'أدوات المستخدم';
?>

<div class="user-tools-page" style="max-width: 600px; margin: 20px auto;">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">فحص حساب مستخدم، تأكيد البريد، إلغاء الحظر، أو تعيين كلمة مرور جديدة (بدون استخدام الطرفية).</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= Html::encode($error) ?></div>
    <?php endif; ?>

    <?php if ($result && isset($result['fixed'])): ?>
        <?php if ($result['fixed'] === true): ?>
            <div class="alert alert-success">تم إصلاح الحساب (تأكيد البريد وإلغاء الحظر).</div>
        <?php elseif ($result['fixed'] === 'no_change'): ?>
            <div class="alert alert-info">الحساب سليم ولا يحتاج إصلاحاً.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($result && isset($result['password_set'])): ?>
        <div class="alert alert-success">تم تعيين كلمة المرور بنجاح.</div>
    <?php endif; ?>

    <div class="panel panel-default">
        <div class="panel-heading">البريد أو اسم المستخدم</div>
        <div class="panel-body">
            <?php $form = \yii\widgets\ActiveForm::begin(['method' => 'post', 'action' => ['index']]); ?>
            <div class="form-group">
                <label>البريد أو اسم المستخدم</label>
                <input type="text" name="login" class="form-control" value="<?= Html::encode($login) ?>" placeholder="مثال: abu.danial.1993@gmail.com" required />
            </div>
            <div class="form-group">
                <label>كلمة المرور الجديدة (لتعيين كلمة مرور فقط)</label>
                <input type="password" name="password" class="form-control" value="" placeholder="اتركه فارغاً إن لم ترد تعيين كلمة مرور" />
            </div>
            <div class="form-group">
                <button type="submit" name="action" value="check" class="btn btn-default">فحص الحساب</button>
                <button type="submit" name="action" value="fix" class="btn btn-warning">إصلاح (تأكيد + إلغاء حظر)</button>
                <button type="submit" name="action" value="set_password" class="btn btn-primary">تعيين كلمة المرور</button>
            </div>
            <?php \yii\widgets\ActiveForm::end(); ?>
        </div>
    </div>

    <?php if (!empty($result)): ?>
        <div class="panel panel-info">
            <div class="panel-heading">نتيجة الفحص</div>
            <div class="panel-body">
                <p><strong>المستخدم:</strong> <?= Html::encode($result['username']) ?> (<?= Html::encode($result['email']) ?>) — id: <?= (int)$result['id'] ?></p>
                <p><strong>تأكيد البريد:</strong> <?= $result['confirmed_at'] ? Html::encode($result['confirmed_at']) : '<span class="text-warning">غير مؤكد</span>' ?></p>
                <p><strong>الحظر:</strong> <?= $result['blocked_at'] ? '<span class="text-danger">محظور منذ ' . Html::encode($result['blocked_at']) . '</span>' : 'غير محظور' ?></p>
                <p><strong>كلمة المرور:</strong> <?= $result['has_password'] ? 'معينة' : '<span class="text-warning">فارغة</span>' ?></p>
                <?php if (!$result['confirmed_at'] || $result['blocked_at']): ?>
                    <p class="text-muted">استخدم زر "إصلاح (تأكيد + إلغاء حظر)" ثم اطلب من صاحب الحساب تسجيل الدخول مرة أخرى.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <p><a href="<?= Url::to(['/site/index']) ?>">← العودة للوحة التحكم</a></p>
</div>
