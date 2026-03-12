<?php
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#800020">
<title>تسجيل الدخول — تيسير</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,sans-serif;background:linear-gradient(135deg,#800020 0%,#4a0012 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.login-card{background:#fff;border-radius:20px;padding:40px 32px;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.login-logo{text-align:center;margin-bottom:32px}
.login-logo .icon{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#800020,#b8003a);display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px}
.login-logo .icon i{font-size:28px;color:#fff}
.login-logo h1{font-size:22px;color:#1e293b;font-weight:700}
.login-logo p{font-size:13px;color:#94a3b8;margin-top:4px}
.form-group{margin-bottom:18px}
.form-group label{display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:6px}
.form-group input{width:100%;padding:12px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:15px;transition:border-color .2s}
.form-group input:focus{border-color:#800020;outline:none}
.login-btn{width:100%;padding:14px;background:linear-gradient(135deg,#800020,#b8003a);color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;transition:transform .1s}
.login-btn:active{transform:scale(.98)}
.error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;text-align:center}
</style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <div class="icon"><i class="fa fa-map-marker"></i></div>
        <h1>نظام الحضور الذكي</h1>
        <p>تسجيل الدخول للمتابعة</p>
    </div>
    <?php if ($error): ?>
        <div class="error-msg"><i class="fa fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>اسم المستخدم</label>
            <input type="text" name="username" placeholder="أدخل اسم المستخدم" required autofocus>
        </div>
        <div class="form-group">
            <label>كلمة المرور</label>
            <input type="password" name="password" placeholder="أدخل كلمة المرور" required>
        </div>
        <button type="submit" class="login-btn">
            <i class="fa fa-sign-in"></i> تسجيل الدخول
        </button>
    </form>
</div>
</body>
</html>
