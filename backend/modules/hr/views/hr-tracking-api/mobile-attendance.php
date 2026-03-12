<?php
use yii\helpers\Url;
$userName = Yii::$app->user->identity->name ?? 'موظف';
$apiBase = Url::to(['/hr/hr-tracking-api/'], true);
$logoutUrl = Url::to(['/hr/hr-tracking-api/mobile-logout']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#800020">
<title>الحضور الذكي — تيسير</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
:root{--primary:#800020;--primary-dark:#5a0016;--success:#16a34a;--warning:#f59e0b;--danger:#dc2626;--bg:#f1f5f9;--card:#fff;--text:#1e293b;--muted:#94a3b8;--radius:14px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,sans-serif;background:var(--bg);min-height:100vh;overflow-x:hidden;color:var(--text)}

/* ─── Top Bar ─── */
.top-bar{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;padding:16px 20px;padding-top:calc(16px + env(safe-area-inset-top));display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.top-bar .user-info{display:flex;align-items:center;gap:10px}
.top-bar .avatar{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:16px}
.top-bar .name{font-size:15px;font-weight:700}
.top-bar .subtitle{font-size:11px;opacity:.8}
.top-bar .logout-btn{background:rgba(255,255,255,.15);border:none;color:#fff;padding:8px 14px;border-radius:8px;font-size:12px;cursor:pointer}

/* ─── Zone Status Banner ─── */
.zone-banner{padding:12px 20px;display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600}
.zone-banner.inside{background:#dcfce7;color:#166534}
.zone-banner.outside{background:#fef3c7;color:#92400e}
.zone-banner.no-gps{background:#fee2e2;color:#991b1b}
.zone-banner i{font-size:16px}
.zone-banner .distance{margin-right:auto;font-weight:400;font-size:12px}

/* ─── Main Clock Card ─── */
.clock-card{margin:16px;background:var(--card);border-radius:var(--radius);padding:28px 24px;box-shadow:0 4px 20px rgba(0,0,0,.06);text-align:center}
.clock-time{font-size:48px;font-weight:800;color:var(--text);letter-spacing:2px;direction:ltr;font-variant-numeric:tabular-nums}
.clock-date{font-size:14px;color:var(--muted);margin-top:4px}

/* ─── Action Button ─── */
.action-area{padding:0 16px;margin-top:20px;text-align:center}
.clock-btn{width:160px;height:160px;border-radius:50%;border:none;font-size:18px;font-weight:700;color:#fff;cursor:pointer;position:relative;transition:all .3s;box-shadow:0 8px 30px rgba(0,0,0,.15)}
.clock-btn.clock-in{background:linear-gradient(135deg,var(--success),#15803d)}
.clock-btn.clock-out{background:linear-gradient(135deg,var(--danger),#b91c1c)}
.clock-btn.disabled{background:#cbd5e1;cursor:not-allowed;box-shadow:none}
.clock-btn:active:not(.disabled){transform:scale(.95)}
.clock-btn i{display:block;font-size:36px;margin-bottom:6px}
.clock-btn .btn-sub{font-size:11px;font-weight:400;opacity:.9;margin-top:2px}
.clock-btn .pulse-ring{position:absolute;top:-4px;left:-4px;right:-4px;bottom:-4px;border-radius:50%;border:3px solid currentColor;opacity:0;animation:pulse 2s infinite}
@keyframes pulse{0%{transform:scale(1);opacity:.5}100%{transform:scale(1.2);opacity:0}}

/* ─── Stats Grid ─── */
.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:20px 16px}
.stat-card{background:var(--card);border-radius:10px;padding:14px 10px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.stat-card .value{font-size:20px;font-weight:800;color:var(--text)}
.stat-card .label{font-size:11px;color:var(--muted);margin-top:2px}
.stat-card.late .value{color:var(--warning)}
.stat-card.mock .value{color:var(--danger)}

/* ─── Status Badge ─── */
.status-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;margin-top:14px}
.status-badge.present{background:#dcfce7;color:#166534}
.status-badge.late{background:#fef3c7;color:#92400e}
.status-badge.absent{background:#fee2e2;color:#991b1b}
.status-badge.field_duty{background:#dbeafe;color:#1e40af}
.status-badge.half_day{background:#f3e8ff;color:#7c3aed}

/* ─── Today Timeline ─── */
.timeline-card{margin:16px;background:var(--card);border-radius:var(--radius);padding:20px;box-shadow:0 2px 10px rgba(0,0,0,.04)}
.timeline-card h3{font-size:14px;font-weight:700;color:var(--text);margin-bottom:14px;display:flex;align-items:center;gap:8px}
.tl-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9}
.tl-row:last-child{border-bottom:none}
.tl-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;color:#fff;flex-shrink:0}
.tl-icon.in{background:var(--success)}
.tl-icon.out{background:var(--danger)}
.tl-icon.zone{background:#3b82f6}
.tl-text{flex:1;font-size:13px;color:#475569}
.tl-time{font-size:13px;font-weight:600;color:var(--text);direction:ltr}

/* ─── GPS Status ─── */
.gps-bar{position:fixed;bottom:0;left:0;right:0;padding:8px 16px;padding-bottom:calc(8px + env(safe-area-inset-bottom));background:var(--card);border-top:1px solid #e2e8f0;display:flex;align-items:center;gap:8px;font-size:11px;color:var(--muted);z-index:100}
.gps-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.gps-dot.good{background:var(--success)}
.gps-dot.fair{background:var(--warning)}
.gps-dot.poor{background:var(--danger)}
.gps-dot.off{background:#cbd5e1}

/* ─── Toast ─── */
.toast{position:fixed;top:80px;left:50%;transform:translateX(-50%);padding:10px 20px;border-radius:10px;color:#fff;font-size:13px;font-weight:600;z-index:999;opacity:0;transition:opacity .3s;pointer-events:none;max-width:90%;text-align:center}
.toast.show{opacity:1}
.toast.success{background:var(--success)}
.toast.error{background:var(--danger)}
.toast.warning{background:var(--warning);color:#1e293b}

/* ─── Loading ─── */
.loading-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.4);display:none;align-items:center;justify-content:center;z-index:200}
.loading-overlay.show{display:flex}
.spinner{width:48px;height:48px;border:4px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

body{padding-bottom:60px}
</style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="user-info">
        <div class="avatar"><i class="fa fa-user"></i></div>
        <div>
            <div class="name"><?= htmlspecialchars($userName) ?></div>
            <div class="subtitle" id="emp-type-label">جارٍ التحميل...</div>
        </div>
    </div>
    <a href="<?= $logoutUrl ?>" class="logout-btn"><i class="fa fa-sign-out"></i></a>
</div>

<!-- Zone Status -->
<div class="zone-banner no-gps" id="zone-banner">
    <i class="fa fa-crosshairs"></i>
    <span id="zone-text">جارٍ تحديد الموقع...</span>
    <span class="distance" id="zone-distance"></span>
</div>

<!-- Clock Card -->
<div class="clock-card">
    <div class="clock-time" id="clock-time">--:--:--</div>
    <div class="clock-date" id="clock-date"></div>
    <div id="status-area"></div>
</div>

<!-- Action Button -->
<div class="action-area">
    <button class="clock-btn disabled" id="main-btn" onclick="handleMainAction()">
        <div class="pulse-ring"></div>
        <i class="fa fa-spinner fa-spin"></i>
        <span id="btn-label">جارٍ التحميل</span>
        <div class="btn-sub" id="btn-sub"></div>
    </button>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="value" id="stat-workhours">--:--</div>
        <div class="label">ساعات العمل</div>
    </div>
    <div class="stat-card late">
        <div class="value" id="stat-late">0 د</div>
        <div class="label">تأخير</div>
    </div>
    <div class="stat-card">
        <div class="value" id="stat-shift">--</div>
        <div class="label">الوردية</div>
    </div>
</div>

<!-- Timeline -->
<div class="timeline-card">
    <h3><i class="fa fa-history"></i> أحداث اليوم</h3>
    <div id="timeline-body">
        <div class="tl-row">
            <div class="tl-text" style="color:var(--muted);text-align:center">لا توجد أحداث بعد</div>
        </div>
    </div>
</div>

<!-- GPS Bar -->
<div class="gps-bar">
    <div class="gps-dot off" id="gps-dot"></div>
    <span id="gps-text">GPS غير متصل</span>
    <span style="margin-right:auto" id="gps-accuracy"></span>
    <span id="gps-battery"></span>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<!-- Loading -->
<div class="loading-overlay" id="loading"><div class="spinner"></div></div>

<script>
var API = '<?= rtrim($apiBase, '/') ?>';
var state = {
    zones: [],
    assignedZone: null,
    attendance: null,
    shift: null,
    employee: null,
    lat: null, lng: null, accuracy: null,
    insideZone: null,
    watchId: null,
    trackingTimer: null,
    locationQueue: [],
    batteryLevel: null,
};

var TRACKING_INTERVAL = 300000;
var LABELS = {
    office: 'مكتبي', field: 'ميداني', sales: 'مبيعات', hybrid: 'مختلط',
    present: 'حاضر', late: 'متأخر', absent: 'غائب', half_day: 'نصف يوم',
    field_duty: 'مهمة ميدانية', on_leave: 'إجازة', holiday: 'عطلة', weekend: 'عطلة أسبوعية',
};

// ─── Init ───
document.addEventListener('DOMContentLoaded', function() {
    updateClock();
    setInterval(updateClock, 1000);
    startGPS();
    loadStatus();
    setInterval(loadStatus, 60000);
    initBattery();
});

function updateClock() {
    var now = new Date();
    var h = String(now.getHours()).padStart(2,'0');
    var m = String(now.getMinutes()).padStart(2,'0');
    var s = String(now.getSeconds()).padStart(2,'0');
    document.getElementById('clock-time').textContent = h + ':' + m + ':' + s;

    var days = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
    var months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    document.getElementById('clock-date').textContent = days[now.getDay()] + ' ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
}

// ─── GPS ───
function startGPS() {
    if (!navigator.geolocation) {
        updateGPSBar('off', 'GPS غير مدعوم');
        return;
    }
    state.watchId = navigator.geolocation.watchPosition(
        function(pos) {
            state.lat = pos.coords.latitude;
            state.lng = pos.coords.longitude;
            state.accuracy = pos.coords.accuracy;

            var cls = state.accuracy < 20 ? 'good' : state.accuracy < 50 ? 'fair' : 'poor';
            updateGPSBar(cls, 'GPS متصل');
            document.getElementById('gps-accuracy').textContent = Math.round(state.accuracy) + 'm';

            checkGeofence();
        },
        function(err) {
            updateGPSBar('poor', 'خطأ GPS: ' + err.message);
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 }
    );
}

function updateGPSBar(cls, text) {
    var dot = document.getElementById('gps-dot');
    dot.className = 'gps-dot ' + cls;
    document.getElementById('gps-text').textContent = text;
}

function initBattery() {
    if (navigator.getBattery) {
        navigator.getBattery().then(function(battery) {
            state.batteryLevel = Math.round(battery.level * 100);
            document.getElementById('gps-battery').textContent = '🔋 ' + state.batteryLevel + '%';
            battery.addEventListener('levelchange', function() {
                state.batteryLevel = Math.round(battery.level * 100);
                document.getElementById('gps-battery').textContent = '🔋 ' + state.batteryLevel + '%';
            });
        });
    }
}

// ─── Geofence Check ───
function checkGeofence() {
    if (!state.lat || !state.zones.length) return;

    var closest = null, closestDist = Infinity;
    var insideAny = false;

    for (var i = 0; i < state.zones.length; i++) {
        var z = state.zones[i];
        var d = haversine(state.lat, state.lng, z.latitude, z.longitude);
        if (d < closestDist) { closestDist = d; closest = z; }
        if (d <= z.radius_meters) {
            insideAny = true;
            state.insideZone = z;
        }
    }

    var banner = document.getElementById('zone-banner');
    var text = document.getElementById('zone-text');
    var dist = document.getElementById('zone-distance');

    if (insideAny) {
        banner.className = 'zone-banner inside';
        text.innerHTML = '<i class="fa fa-check-circle"></i> داخل منطقة العمل: ' + state.insideZone.name;
        dist.textContent = '';
    } else if (closest) {
        banner.className = 'zone-banner outside';
        text.innerHTML = '<i class="fa fa-exclamation-triangle"></i> خارج منطقة العمل: ' + closest.name;
        dist.textContent = Math.round(closestDist) + ' متر';
        state.insideZone = null;
    }

    updateMainButton();
}

function haversine(lat1, lng1, lat2, lng2) {
    var R = 6371000;
    var dLat = (lat2 - lat1) * Math.PI / 180;
    var dLng = (lng2 - lng1) * Math.PI / 180;
    var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) *
            Math.sin(dLng/2) * Math.sin(dLng/2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

// ─── Load Status ───
function loadStatus() {
    fetch(API + '/status', {credentials: 'same-origin'})
    .then(function(r){ return r.json(); })
    .then(function(data) {
        if (!data.success) return;

        state.employee = data.employee;
        state.attendance = data.attendance;
        state.shift = data.shift;
        state.zones = data.all_zones || [];
        state.assignedZone = data.assigned_zone;

        var typeLabel = document.getElementById('emp-type-label');
        if (state.employee) {
            typeLabel.textContent = LABELS[state.employee.employee_type] || state.employee.employee_type;
        }

        if (state.shift) {
            document.getElementById('stat-shift').textContent = state.shift.title;
        }

        updateAttendanceUI();
        updateMainButton();
        checkGeofence();
        updateTimeline();
    })
    .catch(function(e) { console.error('Status error:', e); });
}

// ─── Update UI ───
function updateAttendanceUI() {
    var att = state.attendance;
    var statusArea = document.getElementById('status-area');
    var workHours = document.getElementById('stat-workhours');
    var lateStat = document.getElementById('stat-late');

    if (att && att.clock_in_at) {
        var statusClass = att.status || 'present';
        var statusLabel = LABELS[att.status] || att.status;
        statusArea.innerHTML = '<div class="status-badge ' + statusClass + '"><i class="fa fa-check-circle"></i> ' + statusLabel + '</div>';

        if (att.clock_out_at) {
            var mins = att.total_minutes || 0;
            workHours.textContent = Math.floor(mins/60) + ':' + String(mins%60).padStart(2,'0');
        } else {
            startWorkTimer(att.clock_in_at);
        }

        lateStat.textContent = (att.late_minutes || 0) + ' د';
    } else {
        statusArea.innerHTML = '<div class="status-badge absent"><i class="fa fa-minus-circle"></i> لم يتم التسجيل</div>';
        workHours.textContent = '--:--';
        lateStat.textContent = '0 د';
    }
}

var workTimerInterval = null;
function startWorkTimer(clockInStr) {
    if (workTimerInterval) clearInterval(workTimerInterval);
    var clockIn = new Date(clockInStr.replace(' ', 'T'));
    workTimerInterval = setInterval(function() {
        var now = new Date();
        var diff = Math.floor((now - clockIn) / 60000);
        if (diff < 0) diff = 0;
        document.getElementById('stat-workhours').textContent = Math.floor(diff/60) + ':' + String(diff%60).padStart(2,'0');
    }, 1000);
}

function updateMainButton() {
    var btn = document.getElementById('main-btn');
    var label = document.getElementById('btn-label');
    var sub = document.getElementById('btn-sub');
    var icon = btn.querySelector('i');
    var att = state.attendance;

    if (!state.lat) {
        btn.className = 'clock-btn disabled';
        icon.className = 'fa fa-crosshairs';
        label.textContent = 'تحديد الموقع...';
        sub.textContent = '';
        return;
    }

    if (!att || !att.clock_in_at) {
        var canClockIn = state.insideZone || (state.employee && state.employee.employee_type === 'field');
        if (canClockIn) {
            btn.className = 'clock-btn clock-in';
            icon.className = 'fa fa-sign-in';
            label.textContent = 'تسجيل دخول';
            sub.textContent = state.insideZone ? state.insideZone.name : 'ميداني';
        } else {
            btn.className = 'clock-btn disabled';
            icon.className = 'fa fa-ban';
            label.textContent = 'خارج المنطقة';
            sub.textContent = 'ادخل منطقة العمل أولاً';
        }
    } else if (!att.clock_out_at) {
        btn.className = 'clock-btn clock-out';
        icon.className = 'fa fa-sign-out';
        label.textContent = 'تسجيل خروج';
        sub.textContent = '';
    } else {
        btn.className = 'clock-btn disabled';
        icon.className = 'fa fa-check-circle';
        label.textContent = 'اكتمل اليوم';
        sub.textContent = '';
    }
}

// ─── Main Action ───
function handleMainAction() {
    var btn = document.getElementById('main-btn');
    if (btn.classList.contains('disabled')) return;

    if (btn.classList.contains('clock-in')) {
        doClockIn();
    } else if (btn.classList.contains('clock-out')) {
        if (confirm('هل تريد تسجيل الخروج؟')) doClockOut();
    }
}

function doClockIn() {
    showLoading(true);
    var isMock = false;
    apiPost(API + '/clock-in', {
        latitude: state.lat,
        longitude: state.lng,
        accuracy: state.accuracy,
        method: state.insideZone ? 'geofence_auto' : 'manual',
        is_mock: isMock ? 1 : 0,
    }).then(function(data) {
        showLoading(false);
        if (data.success) {
            showToast('تم تسجيل الدخول بنجاح ✓', 'success');
            startLocationTracking();
            loadStatus();
        } else {
            showToast(data.message || 'فشل تسجيل الدخول', data.mock_detected ? 'error' : 'warning');
        }
    }).catch(function() {
        showLoading(false);
        showToast('خطأ في الاتصال', 'error');
    });
}

function doClockOut() {
    showLoading(true);
    apiPost(API + '/clock-out', {
        latitude: state.lat,
        longitude: state.lng,
        method: 'manual',
    }).then(function(data) {
        showLoading(false);
        if (data.success) {
            showToast('تم تسجيل الخروج — ' + Math.floor(data.total_minutes/60) + ' ساعة ' + (data.total_minutes%60) + ' دقيقة', 'success');
            stopLocationTracking();
            loadStatus();
        } else {
            showToast(data.message || 'فشل تسجيل الخروج', 'error');
        }
    }).catch(function() {
        showLoading(false);
        showToast('خطأ في الاتصال', 'error');
    });
}

// ─── Location Tracking ───
function startLocationTracking() {
    if (state.trackingTimer) return;
    sendLocation();
    state.trackingTimer = setInterval(sendLocation, TRACKING_INTERVAL);
}

function stopLocationTracking() {
    if (state.trackingTimer) { clearInterval(state.trackingTimer); state.trackingTimer = null; }
    flushQueue();
}

function sendLocation() {
    if (!state.lat) return;
    apiPost(API + '/send-location', {
        latitude: state.lat,
        longitude: state.lng,
        accuracy: state.accuracy,
        battery_level: state.batteryLevel,
        is_mock: 0,
    }).then(function(data) {
        if (data.events && data.events.length) {
            data.events.forEach(function(ev) {
                if (ev.type === 'exit') {
                    showToast('خروج من منطقة: ' + ev.zone + ' — تسجيل خروج تلقائي', 'warning');
                    loadStatus();
                } else if (ev.type === 'enter') {
                    showToast('دخول منطقة: ' + ev.zone, 'success');
                    loadStatus();
                }
            });
        }
    }).catch(function() {
        state.locationQueue.push({
            latitude: state.lat, longitude: state.lng,
            accuracy: state.accuracy, battery_level: state.batteryLevel,
            captured_at: new Date().toISOString().replace('T',' ').substr(0,19),
        });
    });
}

function flushQueue() {
    if (!state.locationQueue.length) return;
    apiPost(API + '/batch-locations', { points: state.locationQueue }).then(function() {
        state.locationQueue = [];
    });
}

// ─── Timeline ───
function updateTimeline() {
    var att = state.attendance;
    var body = document.getElementById('timeline-body');
    if (!att || !att.clock_in_at) {
        body.innerHTML = '<div class="tl-row"><div class="tl-text" style="color:var(--muted);text-align:center">لا توجد أحداث بعد</div></div>';
        return;
    }

    var html = '';
    html += '<div class="tl-row">';
    html += '<div class="tl-icon in"><i class="fa fa-sign-in"></i></div>';
    html += '<div class="tl-text">تسجيل دخول — ' + (att.clock_in_method === 'geofence_auto' ? 'تلقائي' : 'يدوي') + '</div>';
    html += '<div class="tl-time">' + formatTime(att.clock_in_at) + '</div>';
    html += '</div>';

    if (att.late_minutes > 0) {
        html += '<div class="tl-row">';
        html += '<div class="tl-icon" style="background:var(--warning)"><i class="fa fa-clock-o"></i></div>';
        html += '<div class="tl-text">تأخير: ' + att.late_minutes + ' دقيقة</div>';
        html += '<div class="tl-time"></div>';
        html += '</div>';
    }

    if (att.clock_out_at) {
        html += '<div class="tl-row">';
        html += '<div class="tl-icon out"><i class="fa fa-sign-out"></i></div>';
        html += '<div class="tl-text">تسجيل خروج</div>';
        html += '<div class="tl-time">' + formatTime(att.clock_out_at) + '</div>';
        html += '</div>';
    }

    body.innerHTML = html;
}

function formatTime(dt) {
    if (!dt) return '';
    var parts = dt.split(' ');
    if (parts.length < 2) return dt;
    var t = parts[1].split(':');
    var h = parseInt(t[0]), m = t[1];
    var ampm = h >= 12 ? 'PM' : 'AM';
    if (h > 12) h -= 12;
    if (h === 0) h = 12;
    return h + ':' + m + ' ' + ampm;
}

// ─── Helpers ───
function apiPost(url, data) {
    var fd = new FormData();
    for (var key in data) {
        if (data[key] !== null && data[key] !== undefined) {
            if (typeof data[key] === 'object') {
                fd.append(key, JSON.stringify(data[key]));
            } else {
                fd.append(key, data[key]);
            }
        }
    }
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r) { return r.json(); });
}

function showToast(msg, type) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast ' + type + ' show';
    setTimeout(function() { t.className = 'toast'; }, 4000);
}

function showLoading(show) {
    document.getElementById('loading').className = show ? 'loading-overlay show' : 'loading-overlay';
}

// Auto clock-in/out via geofence
if (state.employee && state.employee.tracking_mode !== 'disabled') {
    setInterval(function() {
        if (!state.lat || !state.zones.length) return;
        var att = state.attendance;
        if (state.insideZone && (!att || !att.clock_in_at)) {
            doClockIn();
        }
    }, 10000);
}
</script>
</body>
</html>
