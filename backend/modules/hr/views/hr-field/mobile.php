<?php
/**
 * ════════════════════════════════════════════════════════
 *  واجهة نظام الحضور والانصراف — Mobile Attendance
 *  ─────────────────────────────────────────────────
 *  خفيفة جداً: HTML + CSS + Vanilla JS فقط
 *  بدون Kartik / GridView / jQuery UI
 *  تتبع GPS تلقائي كل 5 دقائق
 * ════════════════════════════════════════════════════════
 */

use yii\helpers\Url;

$this->title = 'نظام الحضور والانصراف';

/* ─── Disable default layout assets for lighter page ─── */
$userId   = Yii::$app->user->id;
$userName = Yii::$app->user->identity->name ?: Yii::$app->user->identity->username;
$csrfToken = Yii::$app->request->csrfToken;
$csrfParam = Yii::$app->request->csrfParam;

/* API endpoints */
$apiStartSession = Url::to(['api-start-session']);
$apiEndSession   = Url::to(['api-end-session']);
$apiSendLocation = Url::to(['api-send-location']);
$apiTaskList     = Url::to(['api-tasks']);
$apiTaskUpdate   = Url::to(['api-task-update']);
$apiLogEvent     = Url::to(['api-log-event']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#800020">
    <title>نظام الحضور والانصراف</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
    /* ══════════════ CSS Variables ══════════════ */
    :root {
        --primary: #800020;
        --primary-light: #a8003a;
        --primary-dark: #5c0017;
        --success: #27ae60;
        --success-dark: #1e8449;
        --warning: #f39c12;
        --danger: #e74c3c;
        --info: #3498db;
        --bg: #f0f2f5;
        --card: #ffffff;
        --text: #2c3e50;
        --text-light: #7f8c8d;
        --border: #e8ecf0;
        --shadow: 0 2px 12px rgba(0,0,0,.08);
        --radius: 16px;
        --radius-sm: 10px;
    }

    /* ══════════════ Reset & Base ══════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: -apple-system, 'Segoe UI', Tahoma, Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
        direction: rtl;
        min-height: 100vh;
        -webkit-font-smoothing: antialiased;
        overscroll-behavior: none;
    }

    /* ══════════════ Top Bar ══════════════ */
    .fd-topbar {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 2px 8px rgba(128,0,32,.3);
    }
    .fd-topbar__user { display: flex; align-items: center; gap: 10px; }
    .fd-topbar__avatar {
        width: 40px; height: 40px; border-radius: 50%;
        background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 16px;
    }
    .fd-topbar__name { font-size: 15px; font-weight: 600; }
    .fd-topbar__sub { font-size: 11px; opacity: .8; }
    .fd-topbar__status {
        display: flex; align-items: center; gap: 6px;
        font-size: 12px; padding: 6px 14px; border-radius: 20px;
        font-weight: 600;
    }
    .fd-topbar__status--off { background: rgba(255,255,255,.15); }
    .fd-topbar__status--on { background: var(--success); animation: pulse 2s infinite; }
    .fd-topbar__dot { width: 8px; height: 8px; border-radius: 50%; }
    .fd-topbar__dot--off { background: #bbb; }
    .fd-topbar__dot--on { background: #fff; }

    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(39,174,96,.4); }
        50% { box-shadow: 0 0 0 8px rgba(39,174,96,0); }
    }

    /* ══════════════ Main Button ══════════════ */
    .fd-main-btn-wrap {
        padding: 24px 20px 12px;
        display: flex;
        justify-content: center;
    }
    .fd-main-btn {
        width: 100%;
        max-width: 400px;
        padding: 20px;
        border: none;
        border-radius: var(--radius);
        font-size: 20px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        transition: all .2s;
        -webkit-tap-highlight-color: transparent;
    }
    .fd-main-btn--start {
        background: linear-gradient(135deg, var(--success), var(--success-dark));
        color: #fff;
        box-shadow: 0 4px 20px rgba(39,174,96,.3);
    }
    .fd-main-btn--start:active { transform: scale(.97); }
    .fd-main-btn--stop {
        background: linear-gradient(135deg, var(--danger), #c0392b);
        color: #fff;
        box-shadow: 0 4px 20px rgba(231,76,60,.3);
    }
    .fd-main-btn--stop:active { transform: scale(.97); }
    .fd-main-btn i { font-size: 24px; }

    /* ══════════════ Stats Row ══════════════ */
    .fd-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        padding: 12px 20px;
    }
    .fd-stat {
        background: var(--card);
        border-radius: var(--radius-sm);
        padding: 14px 10px;
        text-align: center;
        box-shadow: var(--shadow);
    }
    .fd-stat__val { font-size: 22px; font-weight: 700; color: var(--primary); }
    .fd-stat__label { font-size: 11px; color: var(--text-light); margin-top: 4px; }

    /* ══════════════ Section ══════════════ */
    .fd-section {
        padding: 12px 20px;
    }
    .fd-section__title {
        font-size: 15px; font-weight: 700; margin-bottom: 10px;
        display: flex; align-items: center; gap: 8px;
    }
    .fd-section__title i { color: var(--primary); }

    /* ══════════════ Task Cards ══════════════ */
    .fd-tasks { display: flex; flex-direction: column; gap: 10px; }
    .fd-task {
        background: var(--card);
        border-radius: var(--radius-sm);
        padding: 14px;
        box-shadow: var(--shadow);
        border-right: 4px solid var(--border);
        transition: all .2s;
    }
    .fd-task--urgent { border-right-color: var(--danger); }
    .fd-task--high { border-right-color: var(--warning); }
    .fd-task--normal { border-right-color: var(--info); }
    .fd-task--completed { border-right-color: var(--success); opacity: .7; }
    .fd-task__header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
    .fd-task__title { font-size: 14px; font-weight: 600; flex: 1; }
    .fd-task__badge {
        font-size: 10px; padding: 3px 8px; border-radius: 10px;
        font-weight: 600; white-space: nowrap;
    }
    .fd-task__badge--collection { background: #e8f8f0; color: var(--success); }
    .fd-task__badge--court { background: #fdf2e6; color: #e67e22; }
    .fd-task__badge--visit { background: #eaf2fd; color: var(--info); }
    .fd-task__badge--other { background: #f0f0f0; color: #666; }
    .fd-task__info { font-size: 12px; color: var(--text-light); display: flex; flex-wrap: wrap; gap: 12px; }
    .fd-task__info i { width: 14px; text-align: center; }
    .fd-task__actions { display: flex; gap: 8px; margin-top: 10px; }
    .fd-task__btn {
        flex: 1; padding: 10px; border: none; border-radius: 8px;
        font-size: 13px; font-weight: 600; font-family: inherit;
        cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
        transition: all .15s; -webkit-tap-highlight-color: transparent;
    }
    .fd-task__btn--arrive { background: var(--info); color: #fff; }
    .fd-task__btn--done { background: var(--success); color: #fff; }
    .fd-task__btn--fail { background: #f0f0f0; color: var(--danger); }
    .fd-task__btn--navigate { background: #f0f0f0; color: var(--text); }
    .fd-task__btn:active { transform: scale(.96); }

    /* ══════════════ GPS Status Bar ══════════════ */
    .fd-gps-bar {
        background: var(--card);
        margin: 12px 20px;
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
    }
    .fd-gps-bar__left { display: flex; align-items: center; gap: 8px; }
    .fd-gps-bar__dot {
        width: 10px; height: 10px; border-radius: 50%;
    }
    .fd-gps-bar__dot--ok { background: var(--success); }
    .fd-gps-bar__dot--wait { background: var(--warning); animation: pulse 1.5s infinite; }
    .fd-gps-bar__dot--off { background: #ccc; }
    .fd-gps-bar__right { color: var(--text-light); }

    /* ══════════════ Empty State ══════════════ */
    .fd-empty {
        text-align: center; padding: 40px 20px; color: var(--text-light);
    }
    .fd-empty i { font-size: 48px; margin-bottom: 12px; opacity: .3; }
    .fd-empty p { font-size: 14px; }

    /* ══════════════ Toast ══════════════ */
    .fd-toast {
        position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
        background: var(--text); color: #fff; padding: 12px 24px;
        border-radius: 30px; font-size: 13px; font-weight: 500;
        z-index: 1000; opacity: 0; transition: opacity .3s;
        pointer-events: none; white-space: nowrap;
    }
    .fd-toast--show { opacity: 1; }

    /* ══════════════ Loading Overlay ══════════════ */
    .fd-loading {
        position: fixed; inset: 0; background: rgba(255,255,255,.7);
        display: none; align-items: center; justify-content: center;
        z-index: 999;
    }
    .fd-loading--show { display: flex; }
    .fd-spinner {
        width: 40px; height: 40px; border: 4px solid var(--border);
        border-top-color: var(--primary); border-radius: 50%;
        animation: spin .8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ══════════════ Bottom Nav ══════════════ */
    .fd-bottomnav {
        position: fixed; bottom: 0; left: 0; right: 0;
        background: var(--card); border-top: 1px solid var(--border);
        display: flex; justify-content: space-around; padding: 8px 0 max(8px, env(safe-area-inset-bottom));
        z-index: 100;
    }
    .fd-bottomnav__item {
        display: flex; flex-direction: column; align-items: center; gap: 2px;
        font-size: 10px; color: var(--text-light); text-decoration: none;
        padding: 4px 12px; -webkit-tap-highlight-color: transparent;
    }
    .fd-bottomnav__item--active { color: var(--primary); }
    .fd-bottomnav__item i { font-size: 20px; }

    /* ══════════════ Safe Area ══════════════ */
    body { padding-bottom: 70px; }

    /* ══════════════ Result Modal ══════════════ */
    .fd-modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,.5);
        display: none; align-items: flex-end; justify-content: center;
        z-index: 200;
    }
    .fd-modal-overlay--show { display: flex; }
    .fd-modal {
        background: var(--card); border-radius: 20px 20px 0 0;
        width: 100%; max-width: 500px; padding: 20px;
        max-height: 80vh; overflow-y: auto;
        animation: slideUp .3s;
    }
    @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
    .fd-modal__title { font-size: 16px; font-weight: 700; margin-bottom: 16px; text-align: center; }
    .fd-modal__field { margin-bottom: 14px; }
    .fd-modal__label { font-size: 12px; color: var(--text-light); margin-bottom: 4px; }
    .fd-modal__select, .fd-modal__textarea, .fd-modal__input {
        width: 100%; padding: 12px; border: 1px solid var(--border);
        border-radius: 10px; font-family: inherit; font-size: 14px;
        direction: rtl;
    }
    .fd-modal__textarea { min-height: 80px; resize: vertical; }
    .fd-modal__btns { display: flex; gap: 10px; margin-top: 16px; }
    .fd-modal__btn {
        flex: 1; padding: 14px; border: none; border-radius: 10px;
        font-size: 15px; font-weight: 600; font-family: inherit; cursor: pointer;
    }
    .fd-modal__btn--confirm { background: var(--success); color: #fff; }
    .fd-modal__btn--cancel { background: #f0f0f0; color: var(--text); }
    </style>
</head>
<body>

<!-- ═══ Top Bar ═══ -->
<div class="fd-topbar">
    <div class="fd-topbar__user">
        <div class="fd-topbar__avatar"><?= mb_substr($userName, 0, 1) ?></div>
        <div>
            <div class="fd-topbar__name"><?= htmlspecialchars($userName) ?></div>
            <div class="fd-topbar__sub" id="fd-date"></div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <div class="fd-topbar__status fd-topbar__status--off" id="fd-duty-status">
            <span class="fd-topbar__dot fd-topbar__dot--off" id="fd-duty-dot"></span>
            <span id="fd-duty-text">خارج الدوام</span>
        </div>
        <a href="<?= Url::to(['mobile-logout']) ?>" style="color:#fff;opacity:.7;font-size:18px;padding:6px"
           title="تسجيل خروج" onclick="return confirm('هل تريد تسجيل الخروج؟')">
            <i class="fa fa-sign-out"></i>
        </a>
    </div>
</div>

<!-- ═══ Start/Stop Button ═══ -->
<div class="fd-main-btn-wrap">
    <button class="fd-main-btn fd-main-btn--start" id="fd-toggle-btn" onclick="toggleDuty()">
        <i class="fa fa-play-circle"></i>
        <span>بدء الجولة الميدانية</span>
    </button>
</div>

<!-- ═══ GPS Status ═══ -->
<div class="fd-gps-bar" id="fd-gps-bar" style="display:none">
    <div class="fd-gps-bar__left">
        <span class="fd-gps-bar__dot fd-gps-bar__dot--wait" id="fd-gps-dot"></span>
        <span id="fd-gps-text">جاري تحديد الموقع...</span>
    </div>
    <div class="fd-gps-bar__right" id="fd-gps-time">—</div>
</div>

<!-- ═══ Stats ═══ -->
<div class="fd-stats" id="fd-stats" style="display:none">
    <div class="fd-stat">
        <div class="fd-stat__val" id="fd-stat-tasks">0</div>
        <div class="fd-stat__label">مهام اليوم</div>
    </div>
    <div class="fd-stat">
        <div class="fd-stat__val" id="fd-stat-done">0</div>
        <div class="fd-stat__label">مكتملة</div>
    </div>
    <div class="fd-stat">
        <div class="fd-stat__val" id="fd-stat-time">00:00</div>
        <div class="fd-stat__label">وقت الجولة</div>
    </div>
</div>

<!-- ═══ Tasks ═══ -->
<div class="fd-section">
    <div class="fd-section__title"><i class="fa fa-tasks"></i> مهام اليوم</div>
    <div class="fd-tasks" id="fd-tasks-list">
        <div class="fd-empty">
            <i class="fa fa-inbox"></i>
            <p>لا توجد مهام حالياً</p>
        </div>
    </div>
</div>

<!-- ═══ Result Modal ═══ -->
<div class="fd-modal-overlay" id="fd-result-modal">
    <div class="fd-modal">
        <div class="fd-modal__title">نتيجة المهمة</div>
        <input type="hidden" id="fd-modal-task-id">
        <div class="fd-modal__field">
            <div class="fd-modal__label">النتيجة</div>
            <select class="fd-modal__select" id="fd-modal-result">
                <option value="completed">تم بنجاح ✅</option>
                <option value="partial">تم جزئياً ⚠️</option>
                <option value="failed">لم يتم ❌</option>
                <option value="not_found">العميل غير موجود 🚫</option>
            </select>
        </div>
        <div class="fd-modal__field">
            <div class="fd-modal__label">مبلغ محصّل (اختياري)</div>
            <input type="number" class="fd-modal__input" id="fd-modal-amount" placeholder="0.00" step="0.01" min="0">
        </div>
        <div class="fd-modal__field">
            <div class="fd-modal__label">ملاحظات</div>
            <textarea class="fd-modal__textarea" id="fd-modal-notes" placeholder="أي ملاحظات..."></textarea>
        </div>
        <div class="fd-modal__btns">
            <button class="fd-modal__btn fd-modal__btn--confirm" onclick="submitTaskResult()">تأكيد</button>
            <button class="fd-modal__btn fd-modal__btn--cancel" onclick="closeResultModal()">إلغاء</button>
        </div>
    </div>
</div>

<!-- ═══ Toast ═══ -->
<div class="fd-toast" id="fd-toast"></div>

<!-- ═══ Loading ═══ -->
<div class="fd-loading" id="fd-loading"><div class="fd-spinner"></div></div>

<!-- ═══ Bottom Nav ═══ -->
<div class="fd-bottomnav">
    <a href="<?= Url::to(['mobile']) ?>" class="fd-bottomnav__item fd-bottomnav__item--active">
        <i class="fa fa-compass"></i> <span>الجولة</span>
    </a>
    <a href="<?= Url::to(['index']) ?>" class="fd-bottomnav__item">
        <i class="fa fa-th-list"></i> <span>المهام</span>
    </a>
    <a href="<?= Url::to(['map']) ?>" class="fd-bottomnav__item">
        <i class="fa fa-map-o"></i> <span>الخريطة</span>
    </a>
    <a href="<?= Url::to(['/site/index']) ?>" class="fd-bottomnav__item">
        <i class="fa fa-home"></i> <span>الرئيسية</span>
    </a>
</div>

<script>
/* ════════════════════════════════════════════════
 *  Field Duty — Vanilla JS (zero dependencies)
 * ════════════════════════════════════════════════ */

const API = {
    startSession: '<?= $apiStartSession ?>',
    endSession:   '<?= $apiEndSession ?>',
    sendLocation: '<?= $apiSendLocation ?>',
    taskList:     '<?= $apiTaskList ?>',
    taskUpdate:   '<?= $apiTaskUpdate ?>',
    logEvent:     '<?= $apiLogEvent ?>',
};
const CSRF = { param: '<?= $csrfParam ?>', token: '<?= $csrfToken ?>' };
const TRACKING_INTERVAL = 5 * 60 * 1000; // 5 minutes

let state = {
    onDuty: false,
    sessionId: null,
    watchId: null,
    trackingTimer: null,
    startTime: null,
    lastLat: null,
    lastLng: null,
    lastAccuracy: null,
    locationQueue: [],  // offline queue
    tasks: [],
};

/* ─── Init ─── */
document.getElementById('fd-date').textContent = new Date().toLocaleDateString('ar-JO', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
});
loadTasks();

/* ─── Toggle Duty ─── */
async function toggleDuty() {
    if (state.onDuty) {
        await endDuty();
    } else {
        await startDuty();
    }
}

async function startDuty() {
    if (!navigator.geolocation) {
        toast('المتصفح لا يدعم تحديد الموقع');
        return;
    }

    showLoading();
    try {
        // Get initial position
        const pos = await getCurrentPosition();
        state.lastLat = pos.coords.latitude;
        state.lastLng = pos.coords.longitude;
        state.lastAccuracy = pos.coords.accuracy;

        // Start session on server
        const res = await apiPost(API.startSession, {
            latitude: state.lastLat,
            longitude: state.lastLng,
        });

        if (res.success) {
            state.onDuty = true;
            state.sessionId = res.session_id;
            state.startTime = Date.now();

            // Start continuous tracking
            startTracking();
            updateUI();
            toast('تم بدء الجولة الميدانية ✅');
            loadTasks();
        } else {
            toast(res.message || 'خطأ في بدء الجولة');
        }
    } catch (e) {
        toast('تعذر تحديد الموقع — تأكد من تفعيل GPS');
        console.error(e);
    }
    hideLoading();
}

async function endDuty() {
    showLoading();
    try {
        const pos = await getCurrentPosition();
        await apiPost(API.endSession, {
            session_id: state.sessionId,
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
        });

        stopTracking();
        state.onDuty = false;
        state.sessionId = null;
        state.startTime = null;
        updateUI();
        toast('تم إنهاء الجولة 🏁');
    } catch (e) {
        toast('خطأ في إنهاء الجولة');
    }
    hideLoading();
}

/* ─── GPS Tracking ─── */
function startTracking() {
    // Immediate first send
    sendCurrentLocation();

    // Then every 5 minutes
    state.trackingTimer = setInterval(() => {
        sendCurrentLocation();
    }, TRACKING_INTERVAL);

    // Also watch for significant movement
    if (navigator.geolocation) {
        state.watchId = navigator.geolocation.watchPosition(
            (pos) => {
                state.lastLat = pos.coords.latitude;
                state.lastLng = pos.coords.longitude;
                state.lastAccuracy = pos.coords.accuracy;
                updateGPSBar(true, pos.coords.accuracy);
            },
            (err) => {
                updateGPSBar(false);
            },
            { enableHighAccuracy: true, maximumAge: 60000 }
        );
    }

    // Timer display
    updateTimerDisplay();
    state.timerInterval = setInterval(updateTimerDisplay, 1000);
}

function stopTracking() {
    if (state.trackingTimer) clearInterval(state.trackingTimer);
    if (state.watchId !== null) navigator.geolocation.clearWatch(state.watchId);
    if (state.timerInterval) clearInterval(state.timerInterval);
    state.trackingTimer = null;
    state.watchId = null;

    // Flush offline queue
    flushLocationQueue();
}

async function sendCurrentLocation() {
    try {
        const pos = await getCurrentPosition();
        const data = {
            session_id: state.sessionId,
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
            accuracy: pos.coords.accuracy,
            altitude: pos.coords.altitude,
            speed: pos.coords.speed,
            battery_level: await getBatteryLevel(),
        };

        const res = await apiPost(API.sendLocation, data);
        if (!res.success) {
            // Queue for later
            state.locationQueue.push({ ...data, captured_at: new Date().toISOString() });
        }
        updateGPSBar(true, pos.coords.accuracy);
    } catch (e) {
        // Offline — queue it
        if (state.lastLat) {
            state.locationQueue.push({
                session_id: state.sessionId,
                latitude: state.lastLat,
                longitude: state.lastLng,
                accuracy: state.lastAccuracy,
                captured_at: new Date().toISOString(),
            });
        }
        updateGPSBar(false);
    }
}

async function flushLocationQueue() {
    if (state.locationQueue.length === 0) return;
    try {
        for (const loc of state.locationQueue) {
            await apiPost(API.sendLocation, loc);
        }
        state.locationQueue = [];
    } catch (e) { /* will try again later */ }
}

/* ─── Tasks ─── */
async function loadTasks() {
    try {
        const res = await apiGet(API.taskList);
        if (res.success) {
            state.tasks = res.tasks || [];
            renderTasks();
        }
    } catch (e) {
        console.error('Failed to load tasks:', e);
    }
}

function renderTasks() {
    const container = document.getElementById('fd-tasks-list');
    if (!state.tasks.length) {
        container.innerHTML = '<div class="fd-empty"><i class="fa fa-inbox"></i><p>لا توجد مهام حالياً</p></div>';
        return;
    }

    const typeLabels = { collection: 'تحصيل', court_visit: 'محكمة', customer_visit: 'زيارة عميل', delivery: 'تسليم', inspection: 'معاينة', other: 'أخرى' };
    const typeClasses = { collection: 'collection', court_visit: 'court', customer_visit: 'visit', delivery: 'visit', inspection: 'visit', other: 'other' };

    let totalTasks = state.tasks.length;
    let doneTasks = state.tasks.filter(t => t.status === 'completed').length;

    document.getElementById('fd-stat-tasks').textContent = totalTasks;
    document.getElementById('fd-stat-done').textContent = doneTasks;

    container.innerHTML = state.tasks.map(task => {
        const isActive = ['pending', 'accepted', 'en_route', 'arrived'].includes(task.status);
        const isCompleted = task.status === 'completed';
        const priorityClass = task.priority === 'urgent' ? 'urgent' : task.priority === 'high' ? 'high' : 'normal';
        const typeLabel = typeLabels[task.task_type] || task.task_type;
        const typeCls = typeClasses[task.task_type] || 'other';

        let actions = '';
        if (state.onDuty && isActive) {
            if (task.status === 'pending' || task.status === 'accepted') {
                actions = `
                    <button class="fd-task__btn fd-task__btn--navigate" onclick="navigateToTask(${task.id})">
                        <i class="fa fa-map-marker"></i> انتقال
                    </button>
                    <button class="fd-task__btn fd-task__btn--arrive" onclick="arriveTask(${task.id})">
                        <i class="fa fa-check-circle"></i> وصلت
                    </button>`;
            } else if (task.status === 'en_route' || task.status === 'arrived') {
                actions = `
                    <button class="fd-task__btn fd-task__btn--done" onclick="openResultModal(${task.id})">
                        <i class="fa fa-check"></i> إنهاء
                    </button>
                    <button class="fd-task__btn fd-task__btn--fail" onclick="failTask(${task.id})">
                        <i class="fa fa-times"></i> فشل
                    </button>`;
            }
        }

        return `
        <div class="fd-task fd-task--${isCompleted ? 'completed' : priorityClass}">
            <div class="fd-task__header">
                <div class="fd-task__title">${escHtml(task.title)}</div>
                <span class="fd-task__badge fd-task__badge--${typeCls}">${typeLabel}</span>
            </div>
            <div class="fd-task__info">
                ${task.target_address ? `<span><i class="fa fa-map-pin"></i> ${escHtml(task.target_address)}</span>` : ''}
                ${task.due_date ? `<span><i class="fa fa-clock-o"></i> ${task.due_date}</span>` : ''}
                ${isCompleted ? '<span><i class="fa fa-check"></i> مكتملة</span>' : ''}
            </div>
            ${actions ? `<div class="fd-task__actions">${actions}</div>` : ''}
        </div>`;
    }).join('');
}

async function arriveTask(taskId) {
    showLoading();
    try {
        const pos = await getCurrentPosition();
        await apiPost(API.taskUpdate, {
            task_id: taskId,
            status: 'arrived',
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
        });
        // Log event
        await apiPost(API.logEvent, {
            session_id: state.sessionId,
            task_id: taskId,
            event_type: 'check_in',
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
        });
        toast('تم تسجيل الوصول ✅');
        loadTasks();
    } catch (e) { toast('خطأ في تسجيل الوصول'); }
    hideLoading();
}

function openResultModal(taskId) {
    document.getElementById('fd-modal-task-id').value = taskId;
    document.getElementById('fd-modal-result').value = 'completed';
    document.getElementById('fd-modal-amount').value = '';
    document.getElementById('fd-modal-notes').value = '';
    document.getElementById('fd-result-modal').classList.add('fd-modal-overlay--show');
}
function closeResultModal() {
    document.getElementById('fd-result-modal').classList.remove('fd-modal-overlay--show');
}

async function submitTaskResult() {
    const taskId = document.getElementById('fd-modal-task-id').value;
    const result = document.getElementById('fd-modal-result').value;
    const amount = document.getElementById('fd-modal-amount').value;
    const notes = document.getElementById('fd-modal-notes').value;

    showLoading();
    closeResultModal();
    try {
        const pos = await getCurrentPosition();
        const newStatus = (result === 'failed' || result === 'not_found') ? 'failed' : 'completed';
        await apiPost(API.taskUpdate, {
            task_id: taskId,
            status: newStatus,
            result: result,
            notes: notes,
            amount_collected: amount || null,
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
        });
        // Log collection event if amount
        if (amount && parseFloat(amount) > 0) {
            await apiPost(API.logEvent, {
                session_id: state.sessionId,
                task_id: taskId,
                event_type: 'collection',
                amount_collected: amount,
                latitude: pos.coords.latitude,
                longitude: pos.coords.longitude,
                note: notes,
            });
        }
        toast(newStatus === 'completed' ? 'تم إنهاء المهمة ✅' : 'تم تسجيل عدم الإنجاز');
        loadTasks();
    } catch (e) { toast('خطأ في حفظ النتيجة'); }
    hideLoading();
}

async function failTask(taskId) {
    openResultModal(taskId);
    document.getElementById('fd-modal-result').value = 'failed';
}

function navigateToTask(taskId) {
    const task = state.tasks.find(t => t.id == taskId);
    if (task && task.target_lat && task.target_lng) {
        window.open(`https://www.google.com/maps/dir/?api=1&destination=${task.target_lat},${task.target_lng}`, '_blank');
    } else if (task && task.target_address) {
        window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(task.target_address)}`, '_blank');
    } else {
        toast('لا يوجد عنوان لهذه المهمة');
    }
}

/* ─── UI Updates ─── */
function updateUI() {
    const btn = document.getElementById('fd-toggle-btn');
    const statusEl = document.getElementById('fd-duty-status');
    const dotEl = document.getElementById('fd-duty-dot');
    const textEl = document.getElementById('fd-duty-text');
    const gpsBar = document.getElementById('fd-gps-bar');
    const stats = document.getElementById('fd-stats');

    if (state.onDuty) {
        btn.className = 'fd-main-btn fd-main-btn--stop';
        btn.innerHTML = '<i class="fa fa-stop-circle"></i><span>إنهاء الجولة</span>';
        statusEl.className = 'fd-topbar__status fd-topbar__status--on';
        dotEl.className = 'fd-topbar__dot fd-topbar__dot--on';
        textEl.textContent = 'في جولة';
        gpsBar.style.display = 'flex';
        stats.style.display = 'grid';
    } else {
        btn.className = 'fd-main-btn fd-main-btn--start';
        btn.innerHTML = '<i class="fa fa-play-circle"></i><span>بدء الجولة الميدانية</span>';
        statusEl.className = 'fd-topbar__status fd-topbar__status--off';
        dotEl.className = 'fd-topbar__dot fd-topbar__dot--off';
        textEl.textContent = 'خارج الدوام';
        gpsBar.style.display = 'none';
        stats.style.display = 'none';
    }
}

function updateGPSBar(ok, accuracy) {
    const dot = document.getElementById('fd-gps-dot');
    const text = document.getElementById('fd-gps-text');
    const time = document.getElementById('fd-gps-time');

    if (ok) {
        dot.className = 'fd-gps-bar__dot fd-gps-bar__dot--ok';
        text.textContent = `الموقع محدد — دقة ${Math.round(accuracy || 0)} م`;
    } else {
        dot.className = 'fd-gps-bar__dot fd-gps-bar__dot--wait';
        text.textContent = 'جاري تحديد الموقع...';
    }
    time.textContent = new Date().toLocaleTimeString('ar-JO', { hour: '2-digit', minute: '2-digit' });
}

function updateTimerDisplay() {
    if (!state.startTime) return;
    const elapsed = Math.floor((Date.now() - state.startTime) / 1000);
    const h = Math.floor(elapsed / 3600);
    const m = Math.floor((elapsed % 3600) / 60);
    document.getElementById('fd-stat-time').textContent =
        (h > 0 ? h + ':' : '') + String(m).padStart(2, '0') + ':' + String(elapsed % 60).padStart(2, '0');
}

/* ─── Helpers ─── */
function getCurrentPosition() {
    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 60000,
        });
    });
}

async function getBatteryLevel() {
    try {
        if (navigator.getBattery) {
            const b = await navigator.getBattery();
            return Math.round(b.level * 100);
        }
    } catch (e) {}
    return null;
}

async function apiPost(url, data) {
    const body = new FormData();
    body.append(CSRF.param, CSRF.token);
    for (const [k, v] of Object.entries(data)) {
        if (v !== null && v !== undefined) body.append(k, v);
    }
    const res = await fetch(url, { method: 'POST', body, credentials: 'same-origin' });
    return res.json();
}

async function apiGet(url) {
    const res = await fetch(url, { credentials: 'same-origin' });
    return res.json();
}

function toast(msg) {
    const el = document.getElementById('fd-toast');
    el.textContent = msg;
    el.classList.add('fd-toast--show');
    setTimeout(() => el.classList.remove('fd-toast--show'), 3000);
}

function showLoading() { document.getElementById('fd-loading').classList.add('fd-loading--show'); }
function hideLoading() { document.getElementById('fd-loading').classList.remove('fd-loading--show'); }
function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
</body>
</html>
