<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'التتبع المباشر — خريطة حية';
$mapsKey = \common\models\SystemSettings::get('google_maps', 'api_key', '');
$liveDataUrl = Url::to(['/hr/hr-tracking-api/live-data']);
$summaryUrl = Url::to(['/hr/hr-tracking-api/attendance-summary']);
?>

<?= $this->render('@backend/modules/hr/views/_section_tabs', [
    'group' => 'tracking',
    'tabs'  => [
        ['label' => 'سجل الحضور',    'icon' => 'fa-calendar-check-o', 'url' => ['/hr/hr-tracking-api/attendance-board']],
        ['label' => 'التتبع المباشر', 'icon' => 'fa-crosshairs',       'url' => ['/hr/hr-tracking-api/live-map']],
        ['label' => 'الورديات',       'icon' => 'fa-clock-o',          'url' => ['/hr/hr-shift/index']],
        ['label' => 'مناطق العمل',    'icon' => 'fa-map-pin',          'url' => ['/hr/hr-work-zone/index']],
    ],
]) ?>

<style>
.tm-page{padding:0;height:calc(100vh - 60px);display:flex;flex-direction:column}
.tm-kpi-bar{display:flex;gap:0;background:#fff;border-bottom:1px solid #e2e8f0;flex-shrink:0}
.tm-kpi{flex:1;padding:12px 16px;text-align:center;border-left:1px solid #f0f0f0}
.tm-kpi:last-child{border-left:none}
.tm-kpi .val{font-size:24px;font-weight:800;color:#1e293b}
.tm-kpi .lbl{font-size:11px;color:#94a3b8;margin-top:2px}
.tm-kpi.success .val{color:#16a34a}
.tm-kpi.warning .val{color:#f59e0b}
.tm-kpi.danger .val{color:#dc2626}
.tm-kpi.info .val{color:#3b82f6}

.tm-body{display:flex;flex:1;overflow:hidden}
.tm-sidebar{width:320px;background:#fff;border-left:1px solid #e2e8f0;display:flex;flex-direction:column;overflow:hidden}
.tm-sidebar-head{padding:12px 16px;border-bottom:1px solid #f0f0f0;display:flex;gap:8px}
.tm-tab{flex:1;padding:8px;border-radius:8px;border:none;font-size:12px;font-weight:600;cursor:pointer;background:#f1f5f9;color:#64748b;transition:all .2s}
.tm-tab.active{background:var(--clr-primary,#800020);color:#fff}
.tm-sidebar-body{flex:1;overflow-y:auto;padding:8px}

.tm-emp-card{padding:10px 12px;border-radius:8px;margin-bottom:6px;cursor:pointer;transition:all .2s;border:1px solid #f0f0f0}
.tm-emp-card:hover{background:#fdf2f4;border-color:#800020}
.tm-emp-card.active{background:#fdf2f4;border-color:#800020}
.tm-emp-head{display:flex;align-items:center;gap:8px;margin-bottom:4px}
.tm-emp-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.tm-emp-dot.online{background:#16a34a}
.tm-emp-dot.offline{background:#cbd5e1}
.tm-emp-dot.mock{background:#dc2626}
.tm-emp-name{font-size:13px;font-weight:600;color:#1e293b;flex:1}
.tm-emp-badge{padding:2px 6px;border-radius:10px;font-size:9px;font-weight:700}
.tm-emp-badge.present{background:#dcfce7;color:#166534}
.tm-emp-badge.late{background:#fef3c7;color:#92400e}
.tm-emp-badge.absent{background:#fee2e2;color:#991b1b}
.tm-emp-badge.field_duty{background:#dbeafe;color:#1e40af}
.tm-emp-meta{font-size:11px;color:#94a3b8;display:flex;gap:8px;flex-wrap:wrap}
.tm-emp-meta i{width:12px}

.tm-zone-card{padding:10px 12px;border-radius:8px;margin-bottom:6px;border:1px solid #f0f0f0;cursor:pointer}
.tm-zone-card:hover{border-color:#3b82f6}
.tm-zone-name{font-size:13px;font-weight:600;color:#1e293b}
.tm-zone-info{font-size:11px;color:#94a3b8;margin-top:2px}

.tm-map{flex:1}
#live-map{width:100%;height:100%}

.tm-refresh{position:absolute;top:12px;right:340px;z-index:10;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;font-size:12px;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.tm-refresh:hover{background:#f8fafc}
.tm-last-update{position:absolute;bottom:12px;right:340px;z-index:10;background:rgba(255,255,255,.9);border-radius:6px;padding:4px 10px;font-size:11px;color:#64748b}
</style>

<div class="tm-page">
    <!-- KPI Bar -->
    <div class="tm-kpi-bar">
        <div class="tm-kpi success"><div class="val" id="kpi-present">0</div><div class="lbl">حاضر</div></div>
        <div class="tm-kpi danger"><div class="val" id="kpi-absent">0</div><div class="lbl">غائب</div></div>
        <div class="tm-kpi warning"><div class="val" id="kpi-late">0</div><div class="lbl">متأخر</div></div>
        <div class="tm-kpi info"><div class="val" id="kpi-field">0</div><div class="lbl">ميداني</div></div>
        <div class="tm-kpi"><div class="val" id="kpi-total">0</div><div class="lbl">إجمالي</div></div>
    </div>

    <div class="tm-body" style="position:relative">
        <!-- Sidebar -->
        <div class="tm-sidebar">
            <div class="tm-sidebar-head">
                <button class="tm-tab active" onclick="switchTab('staff',this)">الموظفون</button>
                <button class="tm-tab" onclick="switchTab('zones',this)">المناطق</button>
            </div>
            <div class="tm-sidebar-body" id="sidebar-staff"></div>
            <div class="tm-sidebar-body" id="sidebar-zones" style="display:none"></div>
        </div>

        <!-- Map -->
        <div class="tm-map">
            <div id="live-map"></div>
        </div>

        <button class="tm-refresh" onclick="refreshData()"><i class="fa fa-refresh"></i> تحديث</button>
        <div class="tm-last-update">آخر تحديث: <span id="last-update">--</span></div>
    </div>
</div>

<?php if ($mapsKey): ?>
<script>
var map, empMarkers = {}, zoneCircles = [], zoneMarkers = [];
var LIVE_URL = '<?= $liveDataUrl ?>';
var SUMMARY_URL = '<?= $summaryUrl ?>';
var refreshTimer;

var TYPE_COLORS = {
    office: '#3b82f6', field: '#16a34a', sales: '#f59e0b', hybrid: '#8b5cf6',
};
var STATUS_LABELS = {
    present: 'حاضر', late: 'متأخر', absent: 'غائب', half_day: 'نصف يوم',
    field_duty: 'ميداني', on_leave: 'إجازة',
};

function initMap() {
    map = new google.maps.Map(document.getElementById('live-map'), {
        center: {lat: 31.9539, lng: 35.9106},
        zoom: 12,
        mapTypeControl: false,
        streetViewControl: false,
        styles: [
            {featureType:'poi',stylers:[{visibility:'off'}]},
            {featureType:'transit',stylers:[{visibility:'off'}]},
        ],
    });
    refreshData();
    refreshTimer = setInterval(refreshData, 30000);
}

function refreshData() {
    fetch(LIVE_URL, {credentials:'same-origin'}).then(function(r){return r.json()}).then(function(data) {
        if (!data.success) return;
        renderEmployees(data.employees);
        renderZones(data.zones);
        updateKPIs(data.employees);
        document.getElementById('last-update').textContent = new Date().toLocaleTimeString('ar');
    });
}

function renderEmployees(employees) {
    var sidebar = document.getElementById('sidebar-staff');
    var html = '';
    var bounds = new google.maps.LatLngBounds();
    var hasPoints = false;

    var existing = Object.keys(empMarkers);

    employees.forEach(function(emp) {
        var isOnline = emp.latitude && emp.last_update;
        var minutesAgo = isOnline ? Math.floor((Date.now() - new Date(emp.last_update.replace(' ','T')).getTime()) / 60000) : null;
        var isStale = minutesAgo !== null && minutesAgo > 15;

        var status = emp.attendance ? emp.attendance.status : 'absent';
        var statusLabel = STATUS_LABELS[status] || status;

        var dotClass = isOnline && !isStale ? 'online' : emp.is_mock ? 'mock' : 'offline';
        var timeStr = minutesAgo !== null ? (minutesAgo < 1 ? 'الآن' : minutesAgo + ' د') : '—';

        html += '<div class="tm-emp-card" data-uid="' + emp.user_id + '" onclick="focusEmployee(' + emp.user_id + ')">';
        html += '<div class="tm-emp-head">';
        html += '<div class="tm-emp-dot ' + dotClass + '"></div>';
        html += '<span class="tm-emp-name">' + emp.name + '</span>';
        html += '<span class="tm-emp-badge ' + status + '">' + statusLabel + '</span>';
        html += '</div>';
        html += '<div class="tm-emp-meta">';
        if (emp.zone_name) html += '<span><i class="fa fa-map-pin"></i> ' + emp.zone_name + '</span>';
        if (isOnline) html += '<span><i class="fa fa-clock-o"></i> ' + timeStr + '</span>';
        if (emp.battery_level !== null) html += '<span><i class="fa fa-battery-half"></i> ' + emp.battery_level + '%</span>';
        if (emp.is_mock) html += '<span style="color:#dc2626"><i class="fa fa-warning"></i> موقع مزيّف!</span>';
        html += '</div></div>';

        if (emp.latitude && emp.longitude) {
            var pos = {lat: emp.latitude, lng: emp.longitude};
            hasPoints = true;
            bounds.extend(pos);

            if (empMarkers[emp.user_id]) {
                empMarkers[emp.user_id].setPosition(pos);
                empMarkers[emp.user_id].setTitle(emp.name);
            } else {
                var color = TYPE_COLORS[emp.employee_type] || '#800020';
                empMarkers[emp.user_id] = new google.maps.Marker({
                    position: pos,
                    map: map,
                    title: emp.name,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 10,
                        fillColor: color,
                        fillOpacity: 1,
                        strokeColor: '#fff',
                        strokeWeight: 2,
                    },
                });
                var infoContent = '<div style="font-family:Segoe UI;direction:rtl;padding:4px">'
                    + '<b>' + emp.name + '</b><br>'
                    + '<span style="font-size:12px;color:#64748b">' + statusLabel + '</span>';
                if (emp.attendance && emp.attendance.clock_in_at)
                    infoContent += '<br><small>دخول: ' + emp.attendance.clock_in_at.split(' ')[1] + '</small>';
                infoContent += '</div>';
                var iw = new google.maps.InfoWindow({content: infoContent});
                empMarkers[emp.user_id].addListener('click', (function(m,i){return function(){i.open(map,m)}})(empMarkers[emp.user_id],iw));
            }
            existing = existing.filter(function(k){return k != emp.user_id});
        }
    });

    existing.forEach(function(uid) {
        if (empMarkers[uid]) { empMarkers[uid].setMap(null); delete empMarkers[uid]; }
    });

    sidebar.innerHTML = html || '<div style="text-align:center;color:#94a3b8;padding:40px">لا يوجد موظفون</div>';
    if (hasPoints && Object.keys(empMarkers).length > 1) map.fitBounds(bounds);
}

function renderZones(zones) {
    zoneCircles.forEach(function(c){c.setMap(null)});
    zoneMarkers.forEach(function(m){m.setMap(null)});
    zoneCircles = [];
    zoneMarkers = [];

    var sidebar = document.getElementById('sidebar-zones');
    var html = '';
    var typeLabels = {office:'مكتب',branch:'فرع',client_site:'موقع عميل',field_area:'منطقة ميدانية',restricted:'محظورة'};

    zones.forEach(function(z) {
        var pos = {lat: z.latitude, lng: z.longitude};
        var circle = new google.maps.Circle({
            map: map,
            center: pos,
            radius: z.radius_meters,
            fillColor: z.zone_type === 'restricted' ? '#ef4444' : '#800020',
            fillOpacity: 0.1,
            strokeColor: z.zone_type === 'restricted' ? '#ef4444' : '#800020',
            strokeWeight: 1.5,
            strokeOpacity: 0.5,
        });
        zoneCircles.push(circle);

        html += '<div class="tm-zone-card" onclick="focusZone(' + z.latitude + ',' + z.longitude + ')">';
        html += '<div class="tm-zone-name">' + z.name + '</div>';
        html += '<div class="tm-zone-info">' + (typeLabels[z.zone_type]||z.zone_type) + ' — ' + z.radius_meters + 'm</div>';
        html += '</div>';
    });

    sidebar.innerHTML = html || '<div style="text-align:center;color:#94a3b8;padding:40px">لا توجد مناطق</div>';
}

function updateKPIs(employees) {
    var present = 0, absent = 0, late = 0, field = 0, total = employees.length;
    employees.forEach(function(e) {
        if (!e.attendance) { absent++; return; }
        var s = e.attendance.status;
        if (s === 'present' || s === 'half_day') present++;
        else if (s === 'late') { late++; present++; }
        else if (s === 'field_duty') { field++; present++; }
        else if (s === 'absent') absent++;
    });
    document.getElementById('kpi-present').textContent = present;
    document.getElementById('kpi-absent').textContent = absent;
    document.getElementById('kpi-late').textContent = late;
    document.getElementById('kpi-field').textContent = field;
    document.getElementById('kpi-total').textContent = total;
}

function focusEmployee(uid) {
    document.querySelectorAll('.tm-emp-card').forEach(function(c){c.classList.remove('active')});
    var card = document.querySelector('.tm-emp-card[data-uid="'+uid+'"]');
    if (card) card.classList.add('active');
    if (empMarkers[uid]) {
        map.panTo(empMarkers[uid].getPosition());
        map.setZoom(16);
        google.maps.event.trigger(empMarkers[uid], 'click');
    }
}

function focusZone(lat, lng) {
    map.panTo({lat: lat, lng: lng});
    map.setZoom(16);
}

function switchTab(tab, btn) {
    document.querySelectorAll('.tm-tab').forEach(function(t){t.classList.remove('active')});
    btn.classList.add('active');
    document.getElementById('sidebar-staff').style.display = tab === 'staff' ? '' : 'none';
    document.getElementById('sidebar-zones').style.display = tab === 'zones' ? '' : 'none';
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= Html::encode($mapsKey) ?>&callback=initMap" async defer></script>
<?php else: ?>
<script>
document.getElementById('live-map').innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8"><div style="text-align:center"><i class="fa fa-map" style="font-size:48px;display:block;margin-bottom:12px"></i>يرجى تكوين مفتاح Google Maps API في إعدادات النظام</div></div>';
</script>
<?php endif; ?>
