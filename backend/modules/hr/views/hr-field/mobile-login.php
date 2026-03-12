<?php
/**
 * ════════════════════════════════════════════════════════
 *  صفحة تسجيل دخول نظام الحضور والانصراف — خفيفة جداً
 *  ─────────────────────────────────────────────────
 *  بدون Layout / AdminLTE / jQuery UI
 *  HTML + CSS + Vanilla JS فقط
 * ════════════════════════════════════════════════════════
 */

use yii\helpers\Url;

$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
$loginUrl  = Url::to(['mobile-login']);
$error     = $error ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#800020">
    <title>تسجيل الدخول — نظام الحضور والانصراف</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
    /* ══════════════ Variables ══════════════ */
    :root {
        --primary: #800020;
        --primary-light: #a8003a;
        --primary-dark: #5c0017;
        --success: #27ae60;
        --danger: #e74c3c;
        --bg: #f5f5f5;
        --card: #ffffff;
        --text: #333333;
        --text-muted: #888888;
        --radius: 16px;
        --shadow: 0 4px 24px rgba(0,0,0,0.08);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Tahoma, Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        direction: rtl;
    }

    .login-container {
        width: 100%;
        max-width: 400px;
    }

    /* ── Logo / Brand ── */
    .brand {
        text-align: center;
        margin-bottom: 32px;
    }
    .brand-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        box-shadow: 0 8px 32px rgba(128, 0, 32, 0.3);
    }
    .brand-icon i {
        font-size: 36px;
        color: #fff;
    }
    .brand h1 {
        font-size: 22px;
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 4px;
    }
    .brand p {
        font-size: 14px;
        color: var(--text-muted);
    }

    /* ── Card ── */
    .login-card {
        background: var(--card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 32px 24px;
    }

    /* ── Form ── */
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 8px;
    }
    .input-wrap {
        position: relative;
    }
    .input-wrap i {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 16px;
        pointer-events: none;
    }
    .input-wrap input {
        width: 100%;
        padding: 14px 44px 14px 14px;
        font-size: 16px;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        background: #fafafa;
        color: var(--text);
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        direction: ltr;
        text-align: right;
        -webkit-appearance: none;
    }
    .input-wrap input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.1);
        background: #fff;
    }
    .input-wrap input::placeholder {
        color: #bbb;
        text-align: right;
    }

    /* ── Toggle password ── */
    .toggle-pass {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-muted);
        font-size: 16px;
        cursor: pointer;
        padding: 4px;
    }

    /* ── Error ── */
    .error-msg {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: var(--danger);
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 14px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .error-msg i { font-size: 16px; }
    .error-msg.hidden { display: none; }

    /* ── Button ── */
    .btn-login {
        width: 100%;
        padding: 16px;
        font-size: 17px;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: transform 0.15s, box-shadow 0.15s;
        box-shadow: 0 4px 16px rgba(128, 0, 32, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-login:active {
        transform: scale(0.98);
    }
    .btn-login:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    .btn-login .spinner {
        display: none;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }
    .btn-login.loading .spinner { display: block; }
    .btn-login.loading .btn-text { display: none; }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* ── Footer ── */
    .footer {
        text-align: center;
        margin-top: 24px;
        font-size: 12px;
        color: var(--text-muted);
    }

    /* ── Responsive ── */
    @media (max-width: 420px) {
        body { padding: 16px; }
        .login-card { padding: 24px 18px; }
        .brand-icon { width: 64px; height: 64px; border-radius: 18px; }
        .brand-icon i { font-size: 28px; }
        .brand h1 { font-size: 20px; }
    }
    </style>
</head>
<body>

<div class="login-container">
    <!-- Brand -->
    <div class="brand">
        <div class="brand-icon">
            <i class="fa fa-map-marker"></i>
        </div>
        <h1>نظام الحضور والانصراف</h1>
        <p>سجّل دخولك لتسجيل الحضور والانصراف</p>
    </div>

    <!-- Login Card -->
    <div class="login-card">
        <div id="errorBox" class="error-msg <?= empty($error) ? 'hidden' : '' ?>">
            <i class="fa fa-exclamation-circle"></i>
            <span id="errorText"><?= htmlspecialchars($error) ?></span>
        </div>

        <form id="loginForm" method="POST" action="<?= htmlspecialchars($loginUrl) ?>">
            <input type="hidden" name="<?= $csrfParam ?>" value="<?= $csrfToken ?>">

            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <div class="input-wrap">
                    <i class="fa fa-user"></i>
                    <input type="text" id="username" name="LoginForm[username]"
                           placeholder="أدخل اسم المستخدم"
                           autocomplete="username" autocapitalize="off" autocorrect="off"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <div class="input-wrap">
                    <i class="fa fa-lock"></i>
                    <input type="password" id="password" name="LoginForm[password]"
                           placeholder="أدخل كلمة المرور"
                           autocomplete="current-password"
                           required>
                    <button type="button" class="toggle-pass" onclick="togglePassword()">
                        <i class="fa fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Remember Me — always on for field agents -->
            <input type="hidden" name="LoginForm[rememberMe]" value="1">

            <button type="submit" class="btn-login" id="btnLogin">
                <span class="btn-text"><i class="fa fa-sign-in"></i> تسجيل الدخول</span>
                <div class="spinner"></div>
            </button>
        </form>
    </div>

    <div class="footer">
        نظام تيسير — الحضور والانصراف
    </div>
</div>

<script>
/* Toggle password visibility */
function togglePassword() {
    var inp = document.getElementById('password');
    var ico = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'fa fa-eye-slash';
    } else {
        inp.type = 'password';
        ico.className = 'fa fa-eye';
    }
}

/* Loading state on submit */
document.getElementById('loginForm').addEventListener('submit', function() {
    var btn = document.getElementById('btnLogin');
    btn.classList.add('loading');
    btn.disabled = true;
});
</script>

</body>
</html>
