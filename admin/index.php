<?php
session_start();
date_default_timezone_set('Asia/Amman');

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die('config.php not found. Copy config.sample.php to config.php and configure it.');
}
$cfg = require $configFile;

// ─── Auth ────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if ($_POST['user'] === $cfg['admin_user'] && $_POST['pass'] === $cfg['admin_pass']) {
        $_SESSION['authed'] = true;
        header('Location: index.php');
        exit;
    }
    $loginError = 'اسم المستخدم أو كلمة المرور غير صحيحة';
}

$authed = !empty($_SESSION['authed']);
$page   = $_GET['page'] ?? ($authed ? 'dashboard' : 'login');

// Force login if not authed
if (!$authed && $page !== 'login') {
    $page = 'login';
}

// ─── DB Helper ───────────────────────────────────────────────
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $c = $GLOBALS['cfg'];
        $pdo = new PDO("mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4", $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

// ─── Data ────────────────────────────────────────────────────
$companies = [];
$company   = null;
if ($authed) {
    try {
        $companies = db()->query("SELECT * FROM os_companies ORDER BY id ASC")->fetchAll();
    } catch (Exception $e) {
        $companies = [];
    }

    if ($page === 'view' && isset($_GET['id'])) {
        foreach ($companies as $c) {
            if ((int)$c['id'] === (int)$_GET['id']) { $company = $c; break; }
        }
        if (!$company) { $page = 'dashboard'; }
    }
}

// ─── Handle Create POST ──────────────────────────────────────
$createErrors = [];
if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_company') {
    $slug     = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['slug'] ?? '')));
    $name_ar  = trim($_POST['name_ar'] ?? '');
    $name_en  = trim($_POST['name_en'] ?? '');
    $sms_sender = trim($_POST['sms_sender'] ?? '') ?: strtoupper($slug);
    $sms_user   = trim($_POST['sms_user'] ?? '') ?: ($slug . 'SMS');
    $sms_pass   = trim($_POST['sms_pass'] ?? '');
    $admin_user = trim($_POST['admin_username'] ?? 'admin');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_pass  = trim($_POST['admin_password'] ?? '');

    if (empty($slug) || strlen($slug) < 2) $createErrors[] = 'المعرف مطلوب (حرفين على الأقل)';
    if (empty($name_ar)) $createErrors[] = 'اسم الشركة بالعربي مطلوب';
    if (empty($admin_email)) $createErrors[] = 'بريد المدير مطلوب';
    if (empty($admin_pass) || strlen($admin_pass) < 6) $createErrors[] = 'كلمة مرور المدير مطلوبة (6 أحرف على الأقل)';

    // Check unique slug
    if (empty($createErrors)) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM os_companies WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) $createErrors[] = 'المعرف مستخدم بالفعل';
    }

    if (empty($createErrors)) {
        $now = time();
        $stmt = db()->prepare("INSERT INTO os_companies
            (slug, name_ar, name_en, domain, db_name, server_ip, sms_sender, sms_user, sms_pass,
             og_title, og_description, og_image, status, provision_log, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $slug, $name_ar, $name_en ?: null,
            $slug . '.aqssat.co',
            'tayseer_' . $slug,
            $cfg['server_ip'],
            $sms_sender, $sms_user, $sms_pass ?: null,
            'نظام تيسير — ' . $name_ar,
            'نظام إدارة التقسيط والأعمال المتكامل — ' . $name_ar,
            '/img/og-' . $slug . '.png',
            'pending',
            '[' . date('Y-m-d H:i:s') . '] تم إنشاء سجل الشركة',
            $now, $now,
        ]);
        $newId = db()->lastInsertId();
        // Store admin credentials in session for provisioning
        $_SESSION['admin_data_' . $newId] = [
            'username' => $admin_user, 'email' => $admin_email, 'password' => $admin_pass,
        ];
        header("Location: index.php?page=view&id={$newId}");
        exit;
    }
}

// ─── Status helpers ──────────────────────────────────────────
function statusLabel(string $s): string {
    return ['pending'=>'قيد الانتظار','dns_ready'=>'DNS جاهز','provisioned'=>'تم التجهيز','active'=>'نشط','disabled'=>'معطّل'][$s] ?? $s;
}
function statusColor(string $s): string {
    return ['pending'=>'#c8a04a','dns_ready'=>'#4a9ec8','provisioned'=>'#a3324d','active'=>'#28c840','disabled'=>'#ff5f57'][$s] ?? '#9a9ab0';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تيسير — لوحة إدارة المنشآت</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
:root {
    --burgundy:#800020; --burgundy-light:#a3324d; --burgundy-dark:#5c0017;
    --gold:#c8a04a; --gold-light:#e0c068; --gold-dark:#9a7a30;
    --bg-primary:#06060b; --bg-secondary:#0c0c14; --bg-card:#12121e; --bg-card-hover:#1a1a2e;
    --text-primary:#f0ece4; --text-secondary:#9a9ab0; --text-muted:#5a5a70;
    --glass-bg:rgba(18,18,30,0.6); --glass-border:rgba(200,160,74,0.12);
    --gradient-gold:linear-gradient(135deg,#c8a04a,#e0c068,#c8a04a);
    --gradient-burgundy:linear-gradient(135deg,#800020,#a3324d);
    --green:#28c840; --red:#ff5f57;
}
html { scroll-behavior:smooth; }
body { font-family:'Cairo','Tajawal',sans-serif; background:var(--bg-primary); color:var(--text-primary); line-height:1.7; min-height:100vh; }

.bg-grid { position:fixed; inset:0; z-index:0; pointer-events:none;
    background-image:linear-gradient(rgba(200,160,74,0.03) 1px,transparent 1px), linear-gradient(90deg,rgba(200,160,74,0.03) 1px,transparent 1px);
    background-size:60px 60px; }
.bg-glow { position:fixed; width:600px; height:600px; border-radius:50%; filter:blur(150px); opacity:0.12; pointer-events:none; z-index:0; }
.bg-glow-1 { background:var(--burgundy); top:-200px; right:-200px; }
.bg-glow-2 { background:var(--gold-dark); bottom:-200px; left:-200px; opacity:0.06; }

.gradient-text { background:var(--gradient-gold); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }

/* ===== TOPBAR ===== */
.topbar { position:sticky; top:0; z-index:100; background:rgba(6,6,11,0.9); backdrop-filter:blur(20px);
    border-bottom:1px solid var(--glass-border); padding:14px 32px; display:flex; align-items:center; justify-content:space-between; }
.topbar-logo { display:flex; align-items:center; gap:12px; text-decoration:none; }
.topbar-icon { width:38px; height:38px; background:var(--gradient-gold); border-radius:10px;
    display:flex; align-items:center; justify-content:center; font-family:'Tajawal',sans-serif; font-weight:900; font-size:18px; color:var(--bg-primary); }
.topbar-title { font-family:'Tajawal',sans-serif; font-size:18px; font-weight:800; color:var(--text-primary); }
.topbar-subtitle { font-size:12px; color:var(--text-muted); font-weight:500; }
.topbar-actions { display:flex; align-items:center; gap:12px; }
.topbar-link { color:var(--text-secondary); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:8px; transition:all .3s; }
.topbar-link:hover { color:var(--gold); background:rgba(200,160,74,0.06); }

/* ===== CONTENT ===== */
.content { position:relative; z-index:1; max-width:1200px; margin:0 auto; padding:32px 24px; }

/* ===== CARDS ===== */
.card { background:var(--bg-card); border:1px solid var(--glass-border); border-radius:20px; overflow:hidden; margin-bottom:24px; }
.card-header { padding:24px 28px; border-bottom:1px solid rgba(200,160,74,0.06); display:flex; align-items:center; justify-content:space-between; }
.card-header h2 { font-family:'Tajawal',sans-serif; font-size:20px; font-weight:700; display:flex; align-items:center; gap:10px; }
.card-body { padding:28px; }

/* ===== BUTTONS ===== */
.btn { display:inline-flex; align-items:center; gap:8px; padding:12px 28px; border-radius:50px; font-family:'Cairo',sans-serif;
    font-weight:700; font-size:14px; text-decoration:none; cursor:pointer; border:none; transition:all .3s; }
.btn-gold { background:var(--gradient-gold); color:var(--bg-primary); box-shadow:0 4px 20px rgba(200,160,74,0.25); }
.btn-gold:hover { transform:translateY(-2px); box-shadow:0 6px 30px rgba(200,160,74,0.4); }
.btn-outline { background:transparent; color:var(--gold); border:1.5px solid rgba(200,160,74,0.3); }
.btn-outline:hover { background:rgba(200,160,74,0.08); border-color:var(--gold); }
.btn-sm { padding:8px 18px; font-size:13px; border-radius:10px; }
.btn-burgundy { background:var(--gradient-burgundy); color:#fff; }
.btn-burgundy:hover { box-shadow:0 4px 20px rgba(128,0,32,0.4); }
.btn:disabled { opacity:.5; cursor:not-allowed; transform:none !important; }

/* ===== TABLE ===== */
.tbl { width:100%; border-collapse:collapse; }
.tbl th { text-align:right; padding:14px 16px; font-size:13px; color:var(--text-muted); font-weight:600;
    border-bottom:1px solid rgba(200,160,74,0.08); }
.tbl td { padding:14px 16px; font-size:14px; border-bottom:1px solid rgba(200,160,74,0.04); vertical-align:middle; }
.tbl tr:hover td { background:rgba(200,160,74,0.02); }
.tbl a { color:var(--gold); text-decoration:none; }
.tbl a:hover { text-decoration:underline; }

.badge { display:inline-block; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700; }

/* ===== FORM ===== */
.form-group { margin-bottom:20px; }
.form-group label { display:block; font-size:14px; font-weight:600; color:var(--text-secondary); margin-bottom:8px; }
.form-control { width:100%; padding:12px 16px; background:var(--bg-secondary); border:1.5px solid rgba(200,160,74,0.1);
    border-radius:12px; color:var(--text-primary); font-family:'Cairo',sans-serif; font-size:14px; transition:all .3s; outline:none; }
.form-control:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(200,160,74,0.1); }
.form-control::placeholder { color:var(--text-muted); }
.form-hint { font-size:12px; color:var(--text-muted); margin-top:6px; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; }

/* ===== STATS ROW ===== */
.stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px; }
.stat-card { background:rgba(200,160,74,0.04); border:1px solid rgba(200,160,74,0.08); border-radius:16px; padding:24px 20px; text-align:center; }
.stat-val { font-family:'Tajawal',sans-serif; font-size:32px; font-weight:900; margin-bottom:4px; }
.stat-lbl { font-size:13px; color:var(--text-muted); }

/* ===== PROVISION STEPS ===== */
.step { padding:20px; border:1px solid rgba(200,160,74,0.08); border-radius:14px; margin-bottom:14px; transition:all .3s; }
.step:hover { border-color:rgba(200,160,74,0.15); }
.step-top { display:flex; align-items:center; justify-content:space-between; }
.step-info { display:flex; align-items:center; gap:14px; }
.step-icon { width:42px; height:42px; border-radius:12px; background:rgba(200,160,74,0.06);
    display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.step-label { font-weight:700; font-size:15px; }
.step-desc { font-size:12px; color:var(--text-muted); margin-top:2px; }
.step-actions { display:flex; align-items:center; gap:10px; }
.step-badge { padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700; }
.step-output { margin-top:14px; display:none; }
.step-output pre { background:var(--bg-primary); border:1px solid rgba(200,160,74,0.06); border-radius:10px;
    padding:14px; font-size:12px; color:var(--text-secondary); direction:ltr; text-align:left;
    max-height:200px; overflow-y:auto; white-space:pre-wrap; font-family:monospace; }

/* ===== DETAIL GRID ===== */
.detail-grid { display:grid; grid-template-columns:140px 1fr; gap:0; }
.detail-grid dt { padding:12px 16px; font-size:13px; color:var(--text-muted); font-weight:600; border-bottom:1px solid rgba(200,160,74,0.04); }
.detail-grid dd { padding:12px 16px; font-size:14px; border-bottom:1px solid rgba(200,160,74,0.04); margin:0; }

/* ===== LOG ===== */
.log-box { background:var(--bg-primary); border:1px solid rgba(200,160,74,0.06); border-radius:12px;
    padding:16px; max-height:300px; overflow-y:auto; white-space:pre-wrap; font-size:12px;
    color:var(--text-secondary); direction:ltr; text-align:left; font-family:monospace; }

/* ===== LOGIN ===== */
.login-wrap { display:flex; align-items:center; justify-content:center; min-height:100vh; padding:24px; position:relative; z-index:1; }
.login-card { background:var(--bg-card); border:1px solid var(--glass-border); border-radius:24px;
    padding:48px 40px; width:100%; max-width:420px; text-align:center;
    box-shadow:0 40px 100px rgba(0,0,0,0.4), 0 0 60px rgba(200,160,74,0.04); }
.login-logo { width:60px; height:60px; background:var(--gradient-gold); border-radius:16px; margin:0 auto 20px;
    display:flex; align-items:center; justify-content:center; font-family:'Tajawal',sans-serif; font-weight:900; font-size:28px; color:var(--bg-primary); }
.login-title { font-family:'Tajawal',sans-serif; font-size:24px; font-weight:800; margin-bottom:8px; }
.login-sub { font-size:14px; color:var(--text-muted); margin-bottom:32px; }
.login-error { background:rgba(255,95,87,0.1); border:1px solid rgba(255,95,87,0.3); color:#ff5f57;
    padding:10px 16px; border-radius:10px; font-size:13px; margin-bottom:20px; }

/* ===== RESPONSIVE ===== */
@media(max-width:768px) {
    .stats-row { grid-template-columns:1fr 1fr; }
    .form-row { grid-template-columns:1fr; }
    .topbar { padding:12px 16px; }
    .content { padding:20px 16px; }
    .login-card { padding:36px 24px; }
    .card-body { padding:20px; }
}
@media(max-width:480px) {
    .stats-row { grid-template-columns:1fr; }
}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar { width:6px; }
::-webkit-scrollbar-track { background:var(--bg-primary); }
::-webkit-scrollbar-thumb { background:var(--burgundy); border-radius:3px; }
::-webkit-scrollbar-thumb:hover { background:var(--burgundy-light); }

/* ===== SPINNER ===== */
@keyframes spin { to { transform:rotate(360deg); } }
.spinner { display:inline-block; width:16px; height:16px; border:2px solid rgba(6,6,11,0.3);
    border-top-color:var(--bg-primary); border-radius:50%; animation:spin .6s linear infinite; }
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="bg-glow bg-glow-1"></div>
<div class="bg-glow bg-glow-2"></div>

<?php if ($page === 'login'): ?>
<!-- ═══════════════ LOGIN ═══════════════ -->
<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">ت</div>
        <h1 class="login-title">لوحة إدارة <span class="gradient-text">المنشآت</span></h1>
        <p class="login-sub">admin.aqssat.co — خاص بمالك النظام</p>
        <?php if (!empty($loginError)): ?>
            <div class="login-error"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <input class="form-control" type="text" name="user" placeholder="اسم المستخدم" dir="ltr" style="text-align:left" required autofocus>
            </div>
            <div class="form-group">
                <input class="form-control" type="password" name="pass" placeholder="كلمة المرور" dir="ltr" style="text-align:left" required>
            </div>
            <button type="submit" class="btn btn-gold" style="width:100%; justify-content:center; margin-top:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                تسجيل الدخول
            </button>
        </form>
        <p style="margin-top:24px; font-size:12px; color:var(--text-muted);">
            <a href="https://aqssat.co" style="color:var(--gold); text-decoration:none;">← العودة إلى الموقع الرئيسي</a>
        </p>
    </div>
</div>

<?php else: ?>
<!-- ═══════════════ TOPBAR ═══════════════ -->
<div class="topbar">
    <a href="index.php" class="topbar-logo">
        <div class="topbar-icon">ت</div>
        <div>
            <div class="topbar-title">تيسير</div>
            <div class="topbar-subtitle">لوحة إدارة المنشآت</div>
        </div>
    </a>
    <div class="topbar-actions">
        <a href="index.php?page=dashboard" class="topbar-link">المنشآت</a>
        <a href="index.php?page=create" class="topbar-link">+ إضافة</a>
        <a href="https://aqssat.co" target="_blank" class="topbar-link">الموقع</a>
        <a href="index.php?logout=1" class="topbar-link" style="color:var(--burgundy-light);">خروج</a>
    </div>
</div>

<div class="content">
<?php if ($page === 'dashboard'): ?>
<!-- ═══════════════ DASHBOARD ═══════════════ -->
<?php
    $total   = count($companies);
    $active  = count(array_filter($companies, fn($c) => $c['status'] === 'active'));
    $pending = count(array_filter($companies, fn($c) => $c['status'] === 'pending'));
?>
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-val gradient-text"><?= $total ?></div>
        <div class="stat-lbl">إجمالي المنشآت</div>
    </div>
    <div class="stat-card">
        <div class="stat-val" style="color:var(--green)"><?= $active ?></div>
        <div class="stat-lbl">نشطة</div>
    </div>
    <div class="stat-card">
        <div class="stat-val" style="color:var(--gold)"><?= $pending ?></div>
        <div class="stat-lbl">قيد الانتظار</div>
    </div>
    <div class="stat-card">
        <div class="stat-val" style="color:var(--burgundy-light)"><?= $total - $active - $pending ?></div>
        <div class="stat-lbl">أخرى</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            المنشآت المسجلة
        </h2>
        <a href="index.php?page=create" class="btn btn-gold btn-sm">+ إضافة منشأة</a>
    </div>
    <div class="card-body" style="padding:0;">
        <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المعرف</th>
                    <th>اسم الشركة</th>
                    <th>النطاق</th>
                    <th>قاعدة البيانات</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($companies)): ?>
                <tr><td colspan="7" style="text-align:center; padding:40px; color:var(--text-muted);">لا توجد منشآت بعد</td></tr>
            <?php else: foreach ($companies as $i => $c): ?>
                <tr>
                    <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                    <td><code style="color:var(--gold)"><?= htmlspecialchars($c['slug']) ?></code></td>
                    <td><?= htmlspecialchars($c['name_ar']) ?></td>
                    <td><a href="https://<?= htmlspecialchars($c['domain']) ?>" target="_blank"><?= htmlspecialchars($c['domain']) ?></a></td>
                    <td style="direction:ltr; text-align:left; color:var(--text-secondary)"><?= htmlspecialchars($c['db_name']) ?></td>
                    <td><span class="badge" style="background:<?= statusColor($c['status']) ?>22; color:<?= statusColor($c['status']) ?>"><?= statusLabel($c['status']) ?></span></td>
                    <td><a href="index.php?page=view&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">عرض</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php elseif ($page === 'create'): ?>
<!-- ═══════════════ CREATE ═══════════════ -->
<div class="card">
    <div class="card-header">
        <h2>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            إضافة منشأة جديدة
        </h2>
        <a href="index.php" class="btn btn-outline btn-sm">→ العودة</a>
    </div>
    <div class="card-body">
        <?php if (!empty($createErrors)): ?>
            <div style="background:rgba(255,95,87,0.08); border:1px solid rgba(255,95,87,0.2); padding:14px 20px; border-radius:12px; margin-bottom:24px;">
                <?php foreach ($createErrors as $e): ?>
                    <div style="color:#ff5f57; font-size:14px;">• <?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="create_company">
            <div class="form-row">
                <div>
                    <h3 style="font-family:'Tajawal',sans-serif; font-size:16px; font-weight:700; margin-bottom:20px; color:var(--gold);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="vertical-align:middle"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                        بيانات الشركة
                    </h3>
                    <div class="form-group">
                        <label>اسم الشركة (عربي) *</label>
                        <input class="form-control" name="name_ar" placeholder="مثال: عالم المجد للتقسيط" value="<?= htmlspecialchars($_POST['name_ar'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>اسم الشركة (إنجليزي)</label>
                        <input class="form-control" name="name_en" placeholder="Alam Al-Majd" dir="ltr" style="text-align:left" value="<?= htmlspecialchars($_POST['name_en'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>المعرف (Slug) *</label>
                        <input class="form-control" name="slug" id="slug-input" placeholder="majd" dir="ltr" style="text-align:left" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" required>
                        <div class="form-hint">سيُستخدم كمعرّف فريد — النطاق: <strong id="preview-domain">—.aqssat.co</strong> | قاعدة البيانات: <strong id="preview-db">tayseer_—</strong></div>
                    </div>
                    <div class="form-group">
                        <label>اسم مرسل SMS</label>
                        <input class="form-control" name="sms_sender" placeholder="MAJD (اختياري)" dir="ltr" style="text-align:left" value="<?= htmlspecialchars($_POST['sms_sender'] ?? '') ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>مستخدم SMS</label>
                            <input class="form-control" name="sms_user" placeholder="majdSMS" dir="ltr" style="text-align:left" value="<?= htmlspecialchars($_POST['sms_user'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>كلمة مرور SMS</label>
                            <input class="form-control" name="sms_pass" type="password" placeholder="اختياري">
                        </div>
                    </div>
                </div>
                <div>
                    <h3 style="font-family:'Tajawal',sans-serif; font-size:16px; font-weight:700; margin-bottom:20px; color:var(--burgundy-light);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="vertical-align:middle"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        المدير الرئيسي
                    </h3>
                    <div class="form-group">
                        <label>اسم المستخدم *</label>
                        <input class="form-control" name="admin_username" dir="ltr" style="text-align:left" value="<?= htmlspecialchars($_POST['admin_username'] ?? 'admin') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>البريد الإلكتروني *</label>
                        <input class="form-control" name="admin_email" type="email" dir="ltr" style="text-align:left" placeholder="admin@company.com" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور *</label>
                        <input class="form-control" name="admin_password" type="password" placeholder="6 أحرف على الأقل" required>
                    </div>
                    <div style="background:rgba(200,160,74,0.04); border:1px solid rgba(200,160,74,0.1); border-radius:14px; padding:20px; margin-top:24px;">
                        <div style="font-size:14px; font-weight:700; margin-bottom:10px; color:var(--gold);">بعد الإنشاء</div>
                        <div style="font-size:13px; color:var(--text-secondary); line-height:2;">
                            ستتمكن من تجهيز المنشأة خطوة بخطوة:<br>
                            1️⃣ DNS (GoDaddy) → 2️⃣ قاعدة بيانات → 3️⃣ سيرفر<br>
                            4️⃣ Apache + SSL → 5️⃣ تهجير + صلاحيات → 6️⃣ نشر
                        </div>
                    </div>
                </div>
            </div>
            <div style="border-top:1px solid rgba(200,160,74,0.06); padding-top:24px; margin-top:28px; display:flex; justify-content:space-between; align-items:center;">
                <a href="index.php" class="btn btn-outline">→ إلغاء</a>
                <button type="submit" class="btn btn-gold">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                    إنشاء الشركة
                </button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('slug-input').addEventListener('input', function() {
    var s = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
    this.value = s;
    document.getElementById('preview-domain').textContent = s ? s + '.aqssat.co' : '—.aqssat.co';
    document.getElementById('preview-db').textContent = s ? 'tayseer_' + s : 'tayseer_—';
});
</script>

<?php elseif ($page === 'view' && $company): ?>
<!-- ═══════════════ VIEW ═══════════════ -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
    <div>
        <div class="card">
            <div class="card-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    <?= htmlspecialchars($company['name_ar']) ?>
                </h2>
                <span class="badge" style="background:<?= statusColor($company['status']) ?>22; color:<?= statusColor($company['status']) ?>; font-size:13px;">
                    <?= statusLabel($company['status']) ?>
                </span>
            </div>
            <div class="card-body" style="padding:0;">
                <dl class="detail-grid">
                    <dt>المعرف</dt><dd><code style="color:var(--gold)"><?= htmlspecialchars($company['slug']) ?></code></dd>
                    <dt>الاسم</dt><dd><?= htmlspecialchars($company['name_ar']) ?></dd>
                    <dt>النطاق</dt><dd><a href="https://<?= htmlspecialchars($company['domain']) ?>" target="_blank" style="color:var(--gold)"><?= htmlspecialchars($company['domain']) ?></a></dd>
                    <dt>قاعدة البيانات</dt><dd style="direction:ltr; text-align:left"><?= htmlspecialchars($company['db_name']) ?></dd>
                    <dt>IP الخادم</dt><dd style="direction:ltr; text-align:left"><?= htmlspecialchars($company['server_ip']) ?></dd>
                    <dt>SMS</dt><dd><?= htmlspecialchars($company['sms_sender'] ?? '—') ?></dd>
                    <dt>الإنشاء</dt><dd><?= $company['created_at'] ? date('Y-m-d H:i', $company['created_at']) : '—' ?></dd>
                </dl>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2>سجل العمليات</h2></div>
            <div class="card-body">
                <div class="log-box" id="provision-log"><?= htmlspecialchars($company['provision_log'] ?? 'لا يوجد سجل بعد') ?></div>
            </div>
        </div>
    </div>
    <div>
        <div class="card">
            <div class="card-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--burgundy-light)" stroke-width="2" stroke-linecap="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    تجهيز المنشأة
                </h2>
            </div>
            <div class="card-body">
                <?php if ($company['status'] === 'active'): ?>
                    <div style="background:rgba(40,200,64,0.06); border:1px solid rgba(40,200,64,0.2); padding:20px; border-radius:14px; text-align:center;">
                        <div style="font-size:28px; margin-bottom:8px;">✓</div>
                        <div style="font-weight:700; color:var(--green);">المنشأة مُجهزة ونشطة بالكامل</div>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted); font-size:13px; margin-bottom:20px;">اضغط على كل خطوة بالترتيب لتجهيز المنشأة تلقائياً</p>
                <?php endif; ?>

                <?php
                $steps = [
                    ['id'=>'dns',      'icon'=>'🌐', 'label'=>'1. إنشاء DNS (GoDaddy)',    'desc'=>'إضافة سجل A للنطاق الفرعي'],
                    ['id'=>'database', 'icon'=>'🗄️', 'label'=>'2. قاعدة البيانات',          'desc'=>'إنشاء القاعدة وتعيين الصلاحيات'],
                    ['id'=>'server',   'icon'=>'🖥️', 'label'=>'3. إعداد السيرفر',           'desc'=>'استنساخ الكود وتطبيق البيئة'],
                    ['id'=>'ssl',      'icon'=>'🔒', 'label'=>'4. Apache + SSL',            'desc'=>'إعداد VirtualHost وشهادة SSL'],
                    ['id'=>'migrate',  'icon'=>'⚙️', 'label'=>'5. التهجير والصلاحيات',      'desc'=>'تشغيل التهجير وإنشاء المدير'],
                    ['id'=>'deploy',   'icon'=>'🚀', 'label'=>'6. تحديث سكريبتات النشر',    'desc'=>'تحديث deploy.yml و deploy-pull.sh'],
                ];
                $canProvision = in_array($company['status'], ['pending','dns_ready','provisioned']);
                foreach ($steps as $step):
                ?>
                <div class="step" id="step-<?= $step['id'] ?>">
                    <div class="step-top">
                        <div class="step-info">
                            <div class="step-icon"><?= $step['icon'] ?></div>
                            <div>
                                <div class="step-label"><?= $step['label'] ?></div>
                                <div class="step-desc"><?= $step['desc'] ?></div>
                            </div>
                        </div>
                        <div class="step-actions">
                            <span class="step-badge" style="background:rgba(154,154,176,0.1); color:var(--text-muted);">معلق</span>
                            <?php if ($canProvision): ?>
                            <button class="btn btn-burgundy btn-sm btn-run" data-step="<?= $step['id'] ?>" data-company="<?= $company['id'] ?>">
                                ▶ تشغيل
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="step-output"><pre></pre></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.btn-run').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var step = this.dataset.step;
        var companyId = this.dataset.company;
        var stepEl = document.getElementById('step-' + step);
        var badge = stepEl.querySelector('.step-badge');
        var output = stepEl.querySelector('.step-output');
        var pre = output.querySelector('pre');

        this.disabled = true;
        this.innerHTML = '<span class="spinner"></span> جاري...';
        badge.style.background = 'rgba(200,160,74,0.15)';
        badge.style.color = 'var(--gold)';
        badge.textContent = 'جاري التنفيذ...';
        output.style.display = 'block';
        pre.textContent = 'جاري التنفيذ...';

        var self = this;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.timeout = 300000;
        xhr.onload = function() {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) {
                    badge.style.background = 'rgba(40,200,64,0.15)';
                    badge.style.color = 'var(--green)';
                    badge.textContent = 'تم بنجاح';
                    self.innerHTML = '✓ تم';
                    self.className = 'btn btn-sm';
                    self.style.background = 'rgba(40,200,64,0.1)';
                    self.style.color = 'var(--green)';
                    self.style.border = '1px solid rgba(40,200,64,0.2)';
                } else {
                    badge.style.background = 'rgba(255,95,87,0.15)';
                    badge.style.color = 'var(--red)';
                    badge.textContent = 'فشل';
                    self.disabled = false;
                    self.innerHTML = '↻ إعادة';
                }
                pre.textContent = res.message || (res.success ? 'تم' : 'خطأ');
            } catch(e) {
                badge.textContent = 'خطأ';
                badge.style.color = 'var(--red)';
                pre.textContent = xhr.responseText || 'خطأ في التحليل';
                self.disabled = false;
                self.innerHTML = '↻ إعادة';
            }
            // Refresh log
            fetch('api.php?action=status&id=' + companyId).then(r=>r.json()).then(function(d){
                if(d.log) document.getElementById('provision-log').textContent = d.log;
            }).catch(function(){});
        };
        xhr.onerror = xhr.ontimeout = function() {
            badge.textContent = 'خطأ اتصال';
            badge.style.color = 'var(--red)';
            pre.textContent = 'انتهت مهلة الاتصال';
            self.disabled = false;
            self.innerHTML = '↻ إعادة';
        };
        xhr.send('action=provision&step=' + step + '&company_id=' + companyId);
    });
});
</script>

<?php endif; ?>
</div><!-- .content -->
<?php endif; ?>
</body>
</html>
