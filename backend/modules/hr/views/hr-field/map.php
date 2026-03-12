<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  خريطة تتبع الموظفين — Live Field Staff Map
 *  ──────────────────────────────────────
 *  Google Maps (عند توفر المفتاح) أو Leaflet — تحديث تلقائي كل 30 ثانية
 *  + المواقع المسماة (Saved Locations) + البحث + Autocomplete
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;

/** @var yii\web\View $this */
/** @var array $staffLocations */
/** @var array $savedLocations */
/** @var int $activeSessionCount */
/** @var int $tasksInProgress */

$this->title = 'خريطة تتبع المناديب';

$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);

$staffLocations     = $staffLocations ?? [];
$savedLocations     = $savedLocations ?? [];
$activeSessionCount = $activeSessionCount ?? 0;
$tasksInProgress    = $tasksInProgress ?? 0;
$refreshUrl         = Url::to(['map']);
$saveLocationUrl    = Url::to(['save-location']);
$deleteLocationUrl  = Url::to(['delete-location']);
$googleMapsKey     = \common\models\SystemSettings::get('google_maps', 'api_key', null)
    ?? Yii::$app->params['googleMapsApiKey'] ?? null;
$useGoogleMaps     = !empty($googleMapsKey);
?>

<?php if (!$useGoogleMaps): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<?php endif; ?>

<style>
/* ═══════════════════════════════════════
   Field Map — Layout
   ═══════════════════════════════════════ */
.map-kpis { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.map-kpi {
    display: flex; align-items: center; gap: 10px;
    background: #fff; padding: 12px 18px; border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06); min-width: 180px;
}
.map-kpi__icon {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff; flex-shrink: 0;
}
.map-kpi__icon.green { background: #27ae60; }
.map-kpi__icon.blue { background: #3498db; }
.map-kpi__icon.burgundy { background: #800020; }
.map-kpi__icon.orange { background: #e67e22; }
.map-kpi__value { font-size: 22px; font-weight: 800; color: #2c3e50; line-height: 1; }
.map-kpi__label { font-size: 12px; color: #95a5a6; margin-top: 2px; }

.field-map-layout {
    display: flex; gap: 0;
    height: calc(100vh - 240px); min-height: 500px;
    border-radius: 10px; overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    background: #fff;
}

/* Sidebar */
.fms { width: 320px; flex-shrink: 0; background: #fff;
    border-left: 1px solid #e8e8e8; display: flex; flex-direction: column; }
.fms__head { padding: 14px 18px; border-bottom: 1px solid #e8e8e8; background: #fafafa; }
.fms__head h3 { margin: 0; font-size: 14px; font-weight: 700; color: #2c3e50;
    display: flex; align-items: center; gap: 6px; }
.fms__head h3 i { color: #800020; }
.fms__sub { font-size: 11px; color: #95a5a6; margin-top: 3px; }
.fms__list { flex: 1; overflow-y: auto; padding: 0; margin: 0; list-style: none; }
.fms__item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 12px 18px; border-bottom: 1px solid #f5f5f5;
    cursor: pointer; transition: background .15s;
}
.fms__item:hover { background: #fdf0f3; }
.fms__avatar {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: #fff;
    flex-shrink: 0; background: #800020;
}
.fms__info { flex: 1; min-width: 0; }
.fms__name { font-size: 13px; font-weight: 600; color: #2c3e50;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fms__meta { font-size: 11px; color: #95a5a6; margin-top: 2px; line-height: 1.5; }
.fms__meta i { width: 14px; display: inline-block; text-align: center; }
.fms__badge {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 10px; font-weight: 600; padding: 2px 8px;
    border-radius: 20px; color: #fff; flex-shrink: 0; margin-top: 2px;
}
.fms__empty {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 40px 20px;
    text-align: center; color: #bbb; flex: 1;
}
.fms__empty i { font-size: 40px; opacity: 0.3; margin-bottom: 10px; }

/* Sidebar tabs */
.fms__tabs {
    display: flex; border-bottom: 2px solid #e8e8e8; background: #fafafa;
}
.fms__tab {
    flex: 1; padding: 10px 8px; text-align: center; font-size: 12px; font-weight: 600;
    color: #95a5a6; cursor: pointer; transition: all .2s; border-bottom: 2px solid transparent;
    margin-bottom: -2px;
}
.fms__tab:hover { color: #800020; }
.fms__tab.active { color: #800020; border-bottom-color: #800020; background: #fff; }
.fms__tab i { display: block; font-size: 16px; margin-bottom: 3px; }
.fms__panel { display: none; flex: 1; overflow-y: auto; flex-direction: column; }
.fms__panel.active { display: flex; }

/* Saved location items */
.sloc-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 10px 18px; border-bottom: 1px solid #f5f5f5;
    cursor: pointer; transition: background .15s;
}
.sloc-item:hover { background: #f0f9ff; }
.sloc-icon {
    width: 34px; height: 34px; border-radius: 8px; background: #3498db;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 14px; flex-shrink: 0;
}
.sloc-info { flex: 1; min-width: 0; }
.sloc-name { font-size: 13px; font-weight: 600; color: #2c3e50; }
.sloc-desc { font-size: 11px; color: #95a5a6; margin-top: 1px; }
.sloc-radius { font-size: 10px; color: #3498db; margin-top: 2px; }
.sloc-actions { display: flex; gap: 4px; flex-shrink: 0; margin-top: 2px; }
.sloc-actions .btn-xs { padding: 2px 6px; font-size: 11px; border-radius: 4px; }

/* Add location form */
.add-loc-form { padding: 14px 18px; border-top: 1px solid #e8e8e8; background: #fafbfc; }
.add-loc-form .form-group { margin-bottom: 8px; }
.add-loc-form label { font-size: 11px; font-weight: 600; color: #555; margin-bottom: 2px; }
.add-loc-form .form-control { font-size: 12px; padding: 6px 10px; height: auto; border-radius: 6px; }
.add-loc-form .btn-save-loc {
    width: 100%; padding: 8px; font-size: 13px; font-weight: 600;
    border-radius: 8px; background: #3498db; color: #fff; border: none;
    cursor: pointer; transition: background .2s;
}
.add-loc-form .btn-save-loc:hover { background: #2980b9; }

/* Map */
.field-map-container { flex: 1; position: relative; }
#fieldMap { width: 100%; height: 100%; }

/* Search bar on map */
.map-search-bar {
    position: absolute; top: 10px; right: 60px; z-index: 999;
    display: flex; gap: 0; width: 380px; max-width: calc(100% - 120px);
}
.map-search-bar input {
    flex: 1; border: none; padding: 10px 14px; font-size: 13px;
    border-radius: 8px 0 0 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    outline: none; background: #fff;
}
.map-search-bar button {
    padding: 10px 14px; border: none; background: #800020; color: #fff;
    border-radius: 0 8px 8px 0; cursor: pointer; font-size: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.map-search-bar button:hover { background: #a0002e; }

/* Auto-refresh badge */
.auto-refresh-badge {
    position: absolute; top: 10px; left: 10px; z-index: 999;
    background: rgba(0,0,0,0.7); color: #fff;
    padding: 5px 12px; border-radius: 20px;
    font-size: 11px; display: flex; align-items: center; gap: 6px;
}
.auto-refresh-badge .pulse {
    width: 8px; height: 8px; border-radius: 50%; background: #27ae60;
    animation: pulse-anim 2s infinite;
}
@keyframes pulse-anim {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.3); }
}

/* Autocomplete dropdown */
.map-ac-results {
    position: absolute; top: 46px; right: 60px; z-index: 1000;
    width: 380px; max-width: calc(100% - 120px);
    background: #fff; border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    max-height: 260px; overflow-y: auto; display: none;
}
.map-ac-results .ac-item {
    padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f5f5f5;
    font-size: 12px; color: #333; display: flex; align-items: center; gap: 8px;
}
.map-ac-results .ac-item:hover { background: #fdf0f3; }
.map-ac-results .ac-item i { color: #800020; flex-shrink: 0; }

/* Modal */
.loc-modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5);
    z-index: 10000; align-items: center; justify-content: center;
}
.loc-modal-overlay.show { display: flex; }
.loc-modal {
    background: #fff; border-radius: 12px; width: 420px; max-width: 90vw;
    padding: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    direction: rtl;
}
.loc-modal h3 { margin: 0 0 16px; font-size: 16px; color: #2c3e50; }
.loc-modal .form-group { margin-bottom: 12px; }
.loc-modal label { font-size: 12px; font-weight: 600; color: #555; display: block; margin-bottom: 4px; }
.loc-modal .form-control { font-size: 13px; border-radius: 8px; }
.loc-modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 18px; }
.loc-modal-actions .btn { padding: 8px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; }

/* Responsive */
@media (max-width: 992px) {
    .field-map-layout { flex-direction: column-reverse; height: auto; }
    .fms { width: 100%; max-height: 300px; border-left: none; border-top: 1px solid #e8e8e8; }
    .field-map-container { min-height: 400px; }
    .map-search-bar { width: 260px; right: 10px; }
    .map-ac-results { width: 260px; right: 10px; }
}
</style>

<div class="hr-page">

    <div class="hr-header">
        <h1><i class="fa fa-map"></i> <?= Html::encode($this->title) ?></h1>
        <div style="display:flex;gap:8px;align-items:center">
            <?= Html::a('<i class="fa fa-tasks"></i> المهام', ['index'], ['class' => 'hr-btn hr-btn--outline-primary hr-btn--sm']) ?>
            <?= Html::a('<i class="fa fa-history"></i> الجلسات', ['sessions'], ['class' => 'hr-btn hr-btn--outline-primary hr-btn--sm']) ?>
            <?= Html::a('<i class="fa fa-refresh"></i> تحديث', ['map'], ['class' => 'hr-btn hr-btn--info hr-btn--sm', 'id' => 'btnRefresh']) ?>
        </div>
    </div>

    <div class="map-kpis">
        <div class="map-kpi">
            <div class="map-kpi__icon green"><i class="fa fa-users"></i></div>
            <div>
                <div class="map-kpi__value" id="kpiActive"><?= $activeSessionCount ?></div>
                <div class="map-kpi__label">مندوب في الميدان</div>
            </div>
        </div>
        <div class="map-kpi">
            <div class="map-kpi__icon blue"><i class="fa fa-tasks"></i></div>
            <div>
                <div class="map-kpi__value" id="kpiTasks"><?= $tasksInProgress ?></div>
                <div class="map-kpi__label">مهمة قيد التنفيذ</div>
            </div>
        </div>
        <div class="map-kpi">
            <div class="map-kpi__icon orange"><i class="fa fa-map-pin"></i></div>
            <div>
                <div class="map-kpi__value" id="kpiLocations"><?= count($savedLocations) ?></div>
                <div class="map-kpi__label">موقع مسمّى</div>
            </div>
        </div>
        <div class="map-kpi">
            <div class="map-kpi__icon burgundy"><i class="fa fa-clock-o"></i></div>
            <div>
                <div class="map-kpi__value" id="kpiTime"><?= date('H:i') ?></div>
                <div class="map-kpi__label">آخر تحديث</div>
            </div>
        </div>
    </div>

    <div class="field-map-layout">

        <!-- الشريط الجانبي -->
        <div class="fms">
            <div class="fms__tabs">
                <div class="fms__tab active" data-panel="staffPanel">
                    <i class="fa fa-street-view"></i>
                    المناديب <span class="badge" style="background:#27ae60;color:#fff;font-size:9px;margin-right:2px"><?= $activeSessionCount ?></span>
                </div>
                <div class="fms__tab" data-panel="locationsPanel">
                    <i class="fa fa-map-marker"></i>
                    المواقع المسماة <span class="badge" style="background:#3498db;color:#fff;font-size:9px;margin-right:2px"><?= count($savedLocations) ?></span>
                </div>
            </div>

            <!-- تبويب المناديب -->
            <div class="fms__panel active" id="staffPanel">
                <div class="fms__head">
                    <h3><i class="fa fa-street-view"></i> المناديب النشطون</h3>
                    <div class="fms__sub" id="sidebarCount"><?= $activeSessionCount ?> موظف في الميدان الآن</div>
                </div>
                <ul class="fms__list" id="sidebarList">
                    <?php if (!empty($staffLocations)): ?>
                        <?php foreach ($staffLocations as $i => $sl):
                            $s = $sl['session'];
                            $pt = $sl['lastPoint'];
                            $task = $sl['currentTask'];
                            $name = $s['name'] ?: $s['username'];
                            $initials = mb_substr($name, 0, 1);
                            $lat = $pt ? $pt['latitude'] : null;
                            $lng = $pt ? $pt['longitude'] : null;
                            $time = $pt ? date('H:i', strtotime($pt['captured_at'])) : '—';
                            $battery = $pt && $pt['battery_level'] ? round($pt['battery_level'] * 100) . '%' : '';
                            $accuracy = $pt && $pt['accuracy'] ? round($pt['accuracy']) . 'm' : '';
                        ?>
                        <li class="fms__item" data-idx="<?= $i ?>" data-lat="<?= $lat ?>" data-lng="<?= $lng ?>">
                            <span class="fms__avatar"><?= $initials ?></span>
                            <div class="fms__info">
                                <div class="fms__name"><?= Html::encode($name) ?></div>
                                <div class="fms__meta">
                                    <i class="fa fa-clock-o"></i> بدأ: <?= date('H:i', strtotime($s['started_at'])) ?>
                                    <?php if ($time !== '—'): ?> &nbsp;|&nbsp; <i class="fa fa-crosshairs"></i> آخر موقع: <?= $time ?><?php endif; ?>
                                </div>
                                <div class="fms__meta">
                                    <?php if ($battery): ?><i class="fa fa-battery-half"></i> <?= $battery ?> &nbsp;<?php endif; ?>
                                    <?php if ($accuracy): ?><i class="fa fa-dot-circle-o"></i> دقة: <?= $accuracy ?><?php endif; ?>
                                </div>
                                <?php if ($task): ?>
                                <div class="fms__meta" style="color:#800020;font-weight:600">
                                    <i class="fa fa-briefcase"></i> <?= Html::encode($task['title']) ?>
                                    (<?= Html::encode($task['status']) ?>)
                                </div>
                                <?php endif; ?>
                            </div>
                            <span class="fms__badge" style="background:#27ae60"><i class="fa fa-circle"></i> نشط</span>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="fms__empty" id="sidebarEmpty">
                            <i class="fa fa-map-marker"></i>
                            <p>لا يوجد مناديب في الميدان حالياً</p>
                            <p style="margin-top:6px;font-size:11px">ستظهر هنا عند بدء أي مندوب جولته الميدانية</p>
                        </div>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- تبويب المواقع المسماة -->
            <div class="fms__panel" id="locationsPanel">
                <div class="fms__head">
                    <h3><i class="fa fa-map-marker"></i> المواقع المسماة</h3>
                    <div class="fms__sub">مواقع محفوظة لتسهيل التتبع</div>
                </div>
                <ul class="fms__list" id="savedLocList">
                    <?php if (!empty($savedLocations)): ?>
                        <?php foreach ($savedLocations as $loc): ?>
                        <li class="sloc-item" data-id="<?= $loc['id'] ?>" data-lat="<?= $loc['latitude'] ?>" data-lng="<?= $loc['longitude'] ?>" data-radius="<?= $loc['radius'] ?>">
                            <span class="sloc-icon"><i class="fa fa-map-pin"></i></span>
                            <div class="sloc-info">
                                <div class="sloc-name"><?= Html::encode($loc['name']) ?></div>
                                <?php if ($loc['description']): ?>
                                <div class="sloc-desc"><?= Html::encode($loc['description']) ?></div>
                                <?php endif; ?>
                                <div class="sloc-radius"><i class="fa fa-circle-o"></i> نطاق: <?= $loc['radius'] ?> متر</div>
                            </div>
                            <div class="sloc-actions">
                                <button class="btn btn-default btn-xs btn-edit-loc" title="تعديل"><i class="fa fa-pencil"></i></button>
                                <button class="btn btn-danger btn-xs btn-del-loc" title="حذف"><i class="fa fa-trash"></i></button>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="fms__empty" id="locEmpty">
                            <i class="fa fa-map-pin"></i>
                            <p>لا يوجد مواقع مسماة</p>
                            <p style="margin-top:6px;font-size:11px">انقر "إضافة موقع" أو انقر بالزر الأيمن على الخريطة</p>
                        </div>
                    <?php endif; ?>
                </ul>
                <div style="padding:10px 18px;border-top:1px solid #e8e8e8">
                    <button class="btn btn-primary btn-sm btn-block" id="btnAddLocation" style="border-radius:8px;font-weight:600">
                        <i class="fa fa-plus"></i> إضافة موقع جديد
                    </button>
                </div>
            </div>
        </div>

        <!-- الخريطة -->
        <div class="field-map-container">
            <div class="auto-refresh-badge">
                <span class="pulse"></span>
                <span>تحديث تلقائي كل 30 ثانية</span>
            </div>
            <div class="map-search-bar">
                <input type="text" id="mapSearchInput" placeholder="ابحث عن موقع (مثل: مستشفى الأمير حمزة، شركة نماء)..." autocomplete="off" />
                <button id="btnMapSearch"><i class="fa fa-search"></i></button>
            </div>
            <div class="map-ac-results" id="mapAcResults"></div>
            <div id="fieldMap"></div>
        </div>

    </div>

</div>

<!-- Modal: إضافة/تعديل موقع مسمى -->
<div class="loc-modal-overlay" id="locModal">
    <div class="loc-modal">
        <h3 id="locModalTitle"><i class="fa fa-map-pin"></i> إضافة موقع مسمى</h3>
        <input type="hidden" id="locId" value="" />
        <div class="form-group">
            <label>اسم الموقع *</label>
            <input type="text" class="form-control" id="locName" placeholder="مثل: مكتب الشركة، مستودع الزرقاء..." />
        </div>
        <div class="form-group">
            <label>الوصف</label>
            <input type="text" class="form-control" id="locDesc" placeholder="وصف اختياري..." />
        </div>
        <div class="row">
            <div class="col-xs-4">
                <div class="form-group">
                    <label>خط العرض</label>
                    <input type="text" class="form-control" id="locLat" readonly />
                </div>
            </div>
            <div class="col-xs-4">
                <div class="form-group">
                    <label>خط الطول</label>
                    <input type="text" class="form-control" id="locLng" readonly />
                </div>
            </div>
            <div class="col-xs-4">
                <div class="form-group">
                    <label>نصف القطر (م)</label>
                    <input type="number" class="form-control" id="locRadius" value="100" min="10" max="5000" />
                </div>
            </div>
        </div>
        <p style="font-size:11px;color:#888;margin-top:4px"><i class="fa fa-info-circle"></i> انقر على الخريطة لتحديد الإحداثيات، أو اسحب الدبوس.</p>
        <div class="loc-modal-actions">
            <button class="btn btn-default" id="locModalCancel">إلغاء</button>
            <button class="btn btn-primary" id="locModalSave"><i class="fa fa-check"></i> حفظ الموقع</button>
        </div>
    </div>
</div>

<?php
$initialData = Json::encode($staffLocations);
$savedLocsData = Json::encode($savedLocations);
$ajaxUrl = Json::encode($refreshUrl);
$saveUrl = Json::encode($saveLocationUrl);
$deleteUrl = Json::encode($deleteLocationUrl);
$csrfParam = Json::encode(Yii::$app->request->csrfParam);
$csrfToken = Json::encode(Yii::$app->request->csrfToken);
?>

<script>
var _staffData = <?= $initialData ?>;
var _savedLocs = <?= $savedLocsData ?>;
var _refreshUrl = <?= $ajaxUrl ?>;
var _saveUrl = <?= $saveUrl ?>;
var _deleteUrl = <?= $deleteUrl ?>;
var _csrf = {}; _csrf[<?= $csrfParam ?>] = <?= $csrfToken ?>;
</script>

<?php if ($useGoogleMaps): ?>
<script>
function initFieldMap() {
    var jordanCenter = { lat: 31.95, lng: 35.93 };
    var map = new google.maps.Map(document.getElementById('fieldMap'), {
        center: jordanCenter, zoom: 12,
        mapTypeControl: true,
        mapTypeControlOptions: { style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR, position: google.maps.ControlPosition.TOP_RIGHT },
        zoomControl: true, scaleControl: true, streetViewControl: true, fullscreenControl: true,
        language: 'ar'
    });

    var staffMarkers = [], staffCircles = [], infoWindow = new google.maps.InfoWindow();
    var locMarkers = [], locCircles = [];
    var tempMarker = null;
    var _isAddingLocation = false;

    /* ─── Staff info popup ─── */
    function buildStaffInfo(sl) {
        var pt = sl.lastPoint, s = sl.session, task = sl.currentTask;
        var name = (s && (s.name || s.username)) || '—';
        var battery = (pt && pt.battery_level) ? Math.round(pt.battery_level*100)+'%' : '—';
        var acc = (pt && pt.accuracy) ? Math.round(pt.accuracy)+' م' : '—';
        var time = (pt && pt.captured_at) ? pt.captured_at.substring(11,16) : '';
        var html = '<div style="direction:rtl;text-align:right;min-width:200px;padding:4px">' +
            '<div style="font-size:14px;font-weight:700;color:#800020;margin-bottom:6px">'+name+'</div>' +
            '<div style="font-size:12px;color:#555;line-height:1.8">' +
            'آخر تحديث: '+time+'<br>دقة: '+acc+'<br>بطارية: '+battery;
        if (task && task.title) {
            html += '<br><b>'+task.title+'</b>';
            if (task.target_address) html += '<br>'+task.target_address;
        }
        html += '</div></div>';
        return html;
    }

    /* ─── Render staff markers ─── */
    function renderStaff(data) {
        staffMarkers.forEach(function(m){ m.setMap(null); });
        staffCircles.forEach(function(c){ c.setMap(null); });
        staffMarkers = []; staffCircles = [];
        var bounds = new google.maps.LatLngBounds();
        var hasMarkers = false;
        (data||[]).forEach(function(sl,idx){
            var pt = sl.lastPoint;
            if (!pt || !pt.latitude || !pt.longitude) return;
            var pos = { lat: parseFloat(pt.latitude), lng: parseFloat(pt.longitude) };
            bounds.extend(pos);
            hasMarkers = true;
            var marker = new google.maps.Marker({
                position: pos, map: map,
                title: (sl.session && (sl.session.name || sl.session.username)) || 'موظف',
                icon: { path: google.maps.SymbolPath.CIRCLE, scale: 12, fillColor: '#800020', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 3 },
                zIndex: 100+idx
            });
            marker.staffData = sl;
            marker.addListener('click', function(){ infoWindow.setContent(buildStaffInfo(marker.staffData)); infoWindow.open(map, marker); });
            staffMarkers.push(marker);
            if (pt.accuracy && pt.accuracy < 200) {
                staffCircles.push(new google.maps.Circle({ map: map, center: pos, radius: parseFloat(pt.accuracy), fillColor: '#800020', fillOpacity: 0.08, strokeColor: '#800020', strokeOpacity: 0.3, strokeWeight: 1 }));
            }
        });
        if (hasMarkers && locMarkers.length === 0) { map.fitBounds(bounds, {padding:50}); if (map.getZoom()>16) map.setZoom(16); }
    }

    /* ─── Render saved location markers ─── */
    function renderSavedLocations(locs) {
        locMarkers.forEach(function(m){ m.setMap(null); });
        locCircles.forEach(function(c){ c.setMap(null); });
        locMarkers = []; locCircles = [];
        (locs||[]).forEach(function(loc){
            if (!loc.latitude || !loc.longitude) return;
            var pos = { lat: parseFloat(loc.latitude), lng: parseFloat(loc.longitude) };
            var marker = new google.maps.Marker({
                position: pos, map: map,
                title: loc.name,
                icon: { url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png', scaledSize: new google.maps.Size(36,36) },
                zIndex: 50
            });
            marker.locData = loc;
            marker.addListener('click', function(){
                var html = '<div style="direction:rtl;text-align:right;min-width:180px;padding:4px">' +
                    '<div style="font-size:14px;font-weight:700;color:#3498db;margin-bottom:4px"><i class="fa fa-map-pin"></i> '+loc.name+'</div>' +
                    (loc.description ? '<div style="font-size:12px;color:#666;margin-bottom:4px">'+loc.description+'</div>' : '') +
                    '<div style="font-size:11px;color:#888">نطاق: '+loc.radius+' م</div></div>';
                infoWindow.setContent(html);
                infoWindow.open(map, marker);
            });
            locMarkers.push(marker);
            var circle = new google.maps.Circle({
                map: map, center: pos, radius: parseInt(loc.radius)||100,
                fillColor: '#3498db', fillOpacity: 0.1,
                strokeColor: '#3498db', strokeOpacity: 0.5, strokeWeight: 2,
                strokeDashArray: [4,4]
            });
            locCircles.push(circle);
        });
    }

    renderStaff(_staffData);
    renderSavedLocations(_savedLocs);
    if (staffMarkers.length === 0 && locMarkers.length > 0) {
        var b = new google.maps.LatLngBounds();
        locMarkers.forEach(function(m){ b.extend(m.getPosition()); });
        map.fitBounds(b, {padding:50});
    }

    /* ─── Google Places Autocomplete for search ─── */
    try {
        var searchInput = document.getElementById('mapSearchInput');
        var autocomplete = new google.maps.places.Autocomplete(searchInput, {
            componentRestrictions: { country: 'jo' },
            fields: ['geometry', 'name', 'formatted_address']
        });
        autocomplete.bindTo('bounds', map);
        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (place.geometry && place.geometry.location) {
                map.setCenter(place.geometry.location);
                map.setZoom(16);
            }
        });
        $('#mapAcResults').hide();
    } catch(e) {
        initNominatimSearch();
    }

    function initNominatimSearch() {
        var timer = null;
        $('#mapSearchInput').on('input', function(){
            var q = $(this).val().trim();
            clearTimeout(timer);
            if (q.length < 3) { $('#mapAcResults').hide(); return; }
            timer = setTimeout(function(){
                $.getJSON('https://nominatim.openstreetmap.org/search', {
                    q: q + ' الأردن', format: 'json', limit: 6, 'accept-language': 'ar'
                }, function(results){
                    var $r = $('#mapAcResults').empty();
                    if (!results.length) { $r.hide(); return; }
                    results.forEach(function(r){
                        $r.append('<div class="ac-item" data-lat="'+r.lat+'" data-lng="'+r.lon+'"><i class="fa fa-map-marker"></i> '+r.display_name.split(',').slice(0,3).join('، ')+'</div>');
                    });
                    $r.show();
                });
            }, 400);
        });
        $(document).on('click', '#mapAcResults .ac-item', function(){
            var lat = parseFloat($(this).data('lat')), lng = parseFloat($(this).data('lng'));
            map.setCenter({lat:lat, lng:lng});
            map.setZoom(16);
            $('#mapAcResults').hide();
            $('#mapSearchInput').val($(this).text().trim());
        });
    }

    $('#btnMapSearch').on('click', function(){
        var q = $('#mapSearchInput').val().trim();
        if (!q) return;
        $.getJSON('https://nominatim.openstreetmap.org/search', {
            q: q + ' الأردن', format: 'json', limit: 1, 'accept-language': 'ar'
        }, function(results){
            if (results.length) {
                map.setCenter({lat: parseFloat(results[0].lat), lng: parseFloat(results[0].lon)});
                map.setZoom(16);
            }
        });
    });
    $('#mapSearchInput').on('keydown', function(e){ if (e.key==='Enter') { e.preventDefault(); $('#btnMapSearch').click(); } });

    /* ─── Sidebar interactions ─── */
    $(document).on('click', '.fms__tab', function(){
        $('.fms__tab').removeClass('active');
        $(this).addClass('active');
        $('.fms__panel').removeClass('active');
        $('#'+$(this).data('panel')).addClass('active');
    });
    $(document).on('click', '.fms__item', function(){
        var lat = $(this).data('lat'), lng = $(this).data('lng');
        if (lat && lng) {
            map.setCenter({lat: parseFloat(lat), lng: parseFloat(lng)});
            map.setZoom(16);
            staffMarkers.forEach(function(m){
                var p = m.getPosition();
                if (Math.abs(p.lat()-lat)<0.0001 && Math.abs(p.lng()-lng)<0.0001) {
                    infoWindow.setContent(buildStaffInfo(m.staffData||{}));
                    infoWindow.open(map, m);
                }
            });
        }
    });
    $(document).on('click', '.sloc-item', function(e){
        if ($(e.target).closest('.sloc-actions').length) return;
        var lat = $(this).data('lat'), lng = $(this).data('lng');
        if (lat && lng) {
            map.setCenter({lat: parseFloat(lat), lng: parseFloat(lng)});
            map.setZoom(16);
            locMarkers.forEach(function(m){
                if (m.locData && m.locData.id == $(e.currentTarget).data('id')) {
                    google.maps.event.trigger(m, 'click');
                }
            });
        }
    });

    /* ─── Add / Edit location ─── */
    function openLocModal(data) {
        data = data || {};
        $('#locId').val(data.id || '');
        $('#locName').val(data.name || '');
        $('#locDesc').val(data.description || '');
        $('#locLat').val(data.latitude || '');
        $('#locLng').val(data.longitude || '');
        $('#locRadius').val(data.radius || 100);
        $('#locModalTitle').html('<i class="fa fa-map-pin"></i> ' + (data.id ? 'تعديل الموقع' : 'إضافة موقع مسمى'));
        $('#locModal').addClass('show');
        _isAddingLocation = true;
        if (data.latitude && data.longitude) {
            setTempMarker(parseFloat(data.latitude), parseFloat(data.longitude));
        }
    }

    function setTempMarker(lat, lng) {
        if (tempMarker) tempMarker.setMap(null);
        var pos = {lat:lat, lng:lng};
        tempMarker = new google.maps.Marker({
            position: pos, map: map, draggable: true,
            icon: { url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png', scaledSize: new google.maps.Size(40,40) },
            zIndex: 200
        });
        tempMarker.addListener('dragend', function(){
            var p = tempMarker.getPosition();
            $('#locLat').val(p.lat().toFixed(8));
            $('#locLng').val(p.lng().toFixed(8));
        });
        map.setCenter(pos);
    }

    map.addListener('click', function(e){
        if (_isAddingLocation) {
            var lat = e.latLng.lat(), lng = e.latLng.lng();
            $('#locLat').val(lat.toFixed(8));
            $('#locLng').val(lng.toFixed(8));
            setTempMarker(lat, lng);
        }
    });

    map.addListener('rightclick', function(e){
        var lat = e.latLng.lat(), lng = e.latLng.lng();
        openLocModal({latitude: lat.toFixed(8), longitude: lng.toFixed(8)});
    });

    $('#btnAddLocation').on('click', function(){
        var center = map.getCenter();
        openLocModal({latitude: center.lat().toFixed(8), longitude: center.lng().toFixed(8)});
    });

    $('#locModalCancel').on('click', function(){
        $('#locModal').removeClass('show');
        _isAddingLocation = false;
        if (tempMarker) { tempMarker.setMap(null); tempMarker = null; }
    });

    $('#locModalSave').on('click', function(){
        var name = $('#locName').val().trim();
        if (!name) { alert('يرجى إدخال اسم الموقع'); return; }
        var postData = $.extend({}, _csrf, {
            id: $('#locId').val(),
            name: name,
            description: $('#locDesc').val().trim(),
            latitude: $('#locLat').val(),
            longitude: $('#locLng').val(),
            radius: $('#locRadius').val() || 100
        });
        $.post(_saveUrl, postData, function(resp){
            if (resp.success) {
                $('#locModal').removeClass('show');
                _isAddingLocation = false;
                if (tempMarker) { tempMarker.setMap(null); tempMarker = null; }
                if (postData.id) {
                    for (var i=0;i<_savedLocs.length;i++) {
                        if (_savedLocs[i].id == postData.id) { _savedLocs[i] = resp.location; break; }
                    }
                } else {
                    _savedLocs.push(resp.location);
                }
                renderSavedLocations(_savedLocs);
                rebuildLocSidebar();
                $('#kpiLocations').text(_savedLocs.length);
            } else {
                alert(resp.message || 'حدث خطأ');
            }
        });
    });

    $(document).on('click', '.btn-edit-loc', function(e){
        e.stopPropagation();
        var $li = $(this).closest('.sloc-item');
        var id = $li.data('id');
        var loc = _savedLocs.find(function(l){ return l.id == id; });
        if (loc) openLocModal(loc);
    });

    $(document).on('click', '.btn-del-loc', function(e){
        e.stopPropagation();
        if (!confirm('هل تريد حذف هذا الموقع؟')) return;
        var $li = $(this).closest('.sloc-item');
        var id = $li.data('id');
        $.post(_deleteUrl, $.extend({}, _csrf, {id: id}), function(resp){
            if (resp.success) {
                _savedLocs = _savedLocs.filter(function(l){ return l.id != id; });
                renderSavedLocations(_savedLocs);
                rebuildLocSidebar();
                $('#kpiLocations').text(_savedLocs.length);
            }
        });
    });

    function rebuildLocSidebar() {
        var $list = $('#savedLocList').empty();
        if (!_savedLocs.length) {
            $list.html('<div class="fms__empty"><i class="fa fa-map-pin"></i><p>لا يوجد مواقع مسماة</p></div>');
            return;
        }
        _savedLocs.forEach(function(loc){
            var desc = loc.description ? '<div class="sloc-desc">'+loc.description+'</div>' : '';
            $list.append(
                '<li class="sloc-item" data-id="'+loc.id+'" data-lat="'+loc.latitude+'" data-lng="'+loc.longitude+'" data-radius="'+loc.radius+'">' +
                '<span class="sloc-icon"><i class="fa fa-map-pin"></i></span>' +
                '<div class="sloc-info"><div class="sloc-name">'+loc.name+'</div>'+desc+'<div class="sloc-radius"><i class="fa fa-circle-o"></i> نطاق: '+loc.radius+' متر</div></div>' +
                '<div class="sloc-actions"><button class="btn btn-default btn-xs btn-edit-loc" title="تعديل"><i class="fa fa-pencil"></i></button><button class="btn btn-danger btn-xs btn-del-loc" title="حذف"><i class="fa fa-trash"></i></button></div></li>'
            );
        });
    }

    /* ─── Auto-refresh ─── */
    setInterval(function(){
        $.ajax({ url: _refreshUrl, type: 'GET', dataType: 'json', headers: {'X-Requested-With':'XMLHttpRequest'}, success: function(resp){
            if (resp.staffLocations) {
                renderStaff(resp.staffLocations);
                _staffData = resp.staffLocations;
                $('#kpiActive').text(resp.activeSessionCount||0);
                $('#kpiTasks').text(resp.tasksInProgress||0);
                var now = new Date();
                $('#kpiTime').text(String(now.getHours()).padStart(2,'0')+':'+String(now.getMinutes()).padStart(2,'0'));
                $('#sidebarCount').text((resp.activeSessionCount||0)+' موظف في الميدان الآن');
            }
        }});
    }, 30000);
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= Html::encode($googleMapsKey) ?>&libraries=places&callback=initFieldMap&language=ar" async defer></script>
<?php else: ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function(){
    var map = L.map('fieldMap', { center: [31.95, 35.93], zoom: 12 });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(map);
    var staffGroup = L.layerGroup().addTo(map);
    var locGroup = L.layerGroup().addTo(map);
    var tempMarker = null, _isAddingLocation = false;

    function createIcon(c) {
        return L.divIcon({
            className:'custom-marker',
            html:'<div style="width:32px;height:32px;border-radius:50%;background:'+c+';border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center"><i class="fa fa-user" style="color:#fff;font-size:14px"></i></div>',
            iconSize:[32,32],iconAnchor:[16,16],popupAnchor:[0,-20]
        });
    }
    function locIcon() {
        return L.divIcon({
            className:'custom-marker',
            html:'<div style="width:30px;height:30px;border-radius:50%;background:#3498db;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center"><i class="fa fa-map-pin" style="color:#fff;font-size:13px"></i></div>',
            iconSize:[30,30],iconAnchor:[15,15],popupAnchor:[0,-18]
        });
    }

    function renderStaff(data) {
        staffGroup.clearLayers();
        var bounds = [];
        data.forEach(function(sl){
            var pt=sl.lastPoint; if(!pt||!pt.latitude||!pt.longitude) return;
            var lat=parseFloat(pt.latitude),lng=parseFloat(pt.longitude);
            var s=sl.session,name=(s&&(s.name||s.username))||'—',task=sl.currentTask;
            var battery=(pt.battery_level)?Math.round(pt.battery_level*100)+'%':'—';
            var acc=(pt.accuracy)?Math.round(pt.accuracy)+' م':'—';
            var time=(pt.captured_at)?pt.captured_at.substring(11,16):'';
            var popup='<div style="direction:rtl;text-align:right;min-width:180px"><div style="font-weight:700;color:#800020">'+name+'</div>'+'آخر تحديث: '+time+'<br>دقة: '+acc+'<br>بطارية: '+battery+(task&&task.title?'<br><b>'+task.title+'</b>':'')+'</div>';
            staffGroup.addLayer(L.marker([lat,lng],{icon:createIcon('#800020')}).bindPopup(popup));
            bounds.push([lat,lng]);
            if(pt.accuracy&&pt.accuracy<200) staffGroup.addLayer(L.circle([lat,lng],{radius:parseFloat(pt.accuracy),color:'#800020',fillColor:'#800020',fillOpacity:0.08,weight:1}));
        });
        if(bounds.length>0) map.fitBounds(bounds,{padding:[50,50],maxZoom:15});
    }

    function renderSavedLocations(locs) {
        locGroup.clearLayers();
        (locs||[]).forEach(function(loc){
            if(!loc.latitude||!loc.longitude) return;
            var lat=parseFloat(loc.latitude),lng=parseFloat(loc.longitude);
            var popup='<div style="direction:rtl;text-align:right;min-width:160px"><div style="font-weight:700;color:#3498db"><i class="fa fa-map-pin"></i> '+loc.name+'</div>'+(loc.description?'<div style="font-size:12px;color:#666">'+loc.description+'</div>':'')+'<div style="font-size:11px;color:#888">نطاق: '+loc.radius+' م</div></div>';
            locGroup.addLayer(L.marker([lat,lng],{icon:locIcon()}).bindPopup(popup));
            locGroup.addLayer(L.circle([lat,lng],{radius:parseInt(loc.radius)||100,color:'#3498db',fillColor:'#3498db',fillOpacity:0.1,weight:2,dashArray:'6,4'}));
        });
    }

    renderStaff(_staffData);
    renderSavedLocations(_savedLocs);
    setTimeout(function(){ map.invalidateSize(); },300);

    /* Search */
    var searchTimer = null;
    $('#mapSearchInput').on('input', function(){
        var q=$(this).val().trim(); clearTimeout(searchTimer);
        if(q.length<3){$('#mapAcResults').hide();return;}
        searchTimer=setTimeout(function(){
            $.getJSON('https://nominatim.openstreetmap.org/search',{q:q+' الأردن',format:'json',limit:6,'accept-language':'ar'},function(results){
                var r=$('#mapAcResults').empty();
                if(!results.length){r.hide();return;}
                results.forEach(function(res){r.append('<div class="ac-item" data-lat="'+res.lat+'" data-lng="'+res.lon+'"><i class="fa fa-map-marker"></i> '+res.display_name.split(',').slice(0,3).join('، ')+'</div>');});
                r.show();
            });
        },400);
    });
    $(document).on('click','#mapAcResults .ac-item',function(){
        map.setView([parseFloat($(this).data('lat')),parseFloat($(this).data('lng'))],16);
        $('#mapAcResults').hide();$('#mapSearchInput').val($(this).text().trim());
    });
    $('#btnMapSearch').on('click',function(){
        var q=$('#mapSearchInput').val().trim(); if(!q) return;
        $.getJSON('https://nominatim.openstreetmap.org/search',{q:q+' الأردن',format:'json',limit:1,'accept-language':'ar'},function(r){
            if(r.length) map.setView([parseFloat(r[0].lat),parseFloat(r[0].lon)],16);
        });
    });
    $('#mapSearchInput').on('keydown',function(e){if(e.key==='Enter'){e.preventDefault();$('#btnMapSearch').click();}});

    /* Tabs */
    $(document).on('click','.fms__tab',function(){$('.fms__tab').removeClass('active');$(this).addClass('active');$('.fms__panel').removeClass('active');$('#'+$(this).data('panel')).addClass('active');});
    $(document).on('click','.fms__item',function(){
        var lat=$(this).data('lat'),lng=$(this).data('lng');
        if(lat&&lng){map.setView([lat,lng],16);staffGroup.eachLayer(function(l){if(l.getLatLng){var ll=l.getLatLng();if(Math.abs(ll.lat-lat)<0.0001&&Math.abs(ll.lng-lng)<0.0001) l.openPopup();}});}
    });
    $(document).on('click','.sloc-item',function(e){
        if($(e.target).closest('.sloc-actions').length) return;
        var lat=$(this).data('lat'),lng=$(this).data('lng');
        if(lat&&lng) map.setView([parseFloat(lat),parseFloat(lng)],16);
    });

    /* Add/Edit location */
    function openLocModal(data){
        data=data||{};
        $('#locId').val(data.id||'');$('#locName').val(data.name||'');$('#locDesc').val(data.description||'');
        $('#locLat').val(data.latitude||'');$('#locLng').val(data.longitude||'');$('#locRadius').val(data.radius||100);
        $('#locModalTitle').html('<i class="fa fa-map-pin"></i> '+(data.id?'تعديل الموقع':'إضافة موقع مسمى'));
        $('#locModal').addClass('show');_isAddingLocation=true;
        if(data.latitude&&data.longitude) setTempMarker(parseFloat(data.latitude),parseFloat(data.longitude));
    }
    function setTempMarker(lat,lng){
        if(tempMarker) map.removeLayer(tempMarker);
        tempMarker=L.marker([lat,lng],{draggable:true,icon:L.divIcon({className:'custom-marker',html:'<div style="width:34px;height:34px;border-radius:50%;background:#27ae60;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center"><i class="fa fa-plus" style="color:#fff;font-size:14px"></i></div>',iconSize:[34,34],iconAnchor:[17,17]})}).addTo(map);
        tempMarker.on('dragend',function(){var p=tempMarker.getLatLng();$('#locLat').val(p.lat.toFixed(8));$('#locLng').val(p.lng.toFixed(8));});
        map.setView([lat,lng],map.getZoom());
    }
    map.on('click',function(e){if(_isAddingLocation){$('#locLat').val(e.latlng.lat.toFixed(8));$('#locLng').val(e.latlng.lng.toFixed(8));setTempMarker(e.latlng.lat,e.latlng.lng);}});
    map.on('contextmenu',function(e){openLocModal({latitude:e.latlng.lat.toFixed(8),longitude:e.latlng.lng.toFixed(8)});});

    $('#btnAddLocation').on('click',function(){var c=map.getCenter();openLocModal({latitude:c.lat.toFixed(8),longitude:c.lng.toFixed(8)});});
    $('#locModalCancel').on('click',function(){$('#locModal').removeClass('show');_isAddingLocation=false;if(tempMarker){map.removeLayer(tempMarker);tempMarker=null;}});
    $('#locModalSave').on('click',function(){
        var name=$('#locName').val().trim(); if(!name){alert('يرجى إدخال اسم الموقع');return;}
        var postData=$.extend({},_csrf,{id:$('#locId').val(),name:name,description:$('#locDesc').val().trim(),latitude:$('#locLat').val(),longitude:$('#locLng').val(),radius:$('#locRadius').val()||100});
        $.post(_saveUrl,postData,function(resp){
            if(resp.success){
                $('#locModal').removeClass('show');_isAddingLocation=false;
                if(tempMarker){map.removeLayer(tempMarker);tempMarker=null;}
                if(postData.id){for(var i=0;i<_savedLocs.length;i++){if(_savedLocs[i].id==postData.id){_savedLocs[i]=resp.location;break;}}}else{_savedLocs.push(resp.location);}
                renderSavedLocations(_savedLocs);rebuildLocSidebar();$('#kpiLocations').text(_savedLocs.length);
            }else{alert(resp.message||'حدث خطأ');}
        });
    });
    $(document).on('click','.btn-edit-loc',function(e){e.stopPropagation();var id=$(this).closest('.sloc-item').data('id');var loc=_savedLocs.find(function(l){return l.id==id;});if(loc) openLocModal(loc);});
    $(document).on('click','.btn-del-loc',function(e){
        e.stopPropagation(); if(!confirm('هل تريد حذف هذا الموقع؟')) return;
        var id=$(this).closest('.sloc-item').data('id');
        $.post(_deleteUrl,$.extend({},_csrf,{id:id}),function(resp){
            if(resp.success){_savedLocs=_savedLocs.filter(function(l){return l.id!=id;});renderSavedLocations(_savedLocs);rebuildLocSidebar();$('#kpiLocations').text(_savedLocs.length);}
        });
    });

    function rebuildLocSidebar(){
        var l=$('#savedLocList').empty();
        if(!_savedLocs.length){l.html('<div class="fms__empty"><i class="fa fa-map-pin"></i><p>لا يوجد مواقع مسماة</p></div>');return;}
        _savedLocs.forEach(function(loc){
            var d=loc.description?'<div class="sloc-desc">'+loc.description+'</div>':'';
            l.append('<li class="sloc-item" data-id="'+loc.id+'" data-lat="'+loc.latitude+'" data-lng="'+loc.longitude+'" data-radius="'+loc.radius+'"><span class="sloc-icon"><i class="fa fa-map-pin"></i></span><div class="sloc-info"><div class="sloc-name">'+loc.name+'</div>'+d+'<div class="sloc-radius"><i class="fa fa-circle-o"></i> نطاق: '+loc.radius+' متر</div></div><div class="sloc-actions"><button class="btn btn-default btn-xs btn-edit-loc" title="تعديل"><i class="fa fa-pencil"></i></button><button class="btn btn-danger btn-xs btn-del-loc" title="حذف"><i class="fa fa-trash"></i></button></div></li>');
        });
    }

    /* Auto-refresh */
    setInterval(function(){
        $.ajax({url:_refreshUrl,type:'GET',dataType:'json',headers:{'X-Requested-With':'XMLHttpRequest'},success:function(resp){
            if(resp.staffLocations){renderStaff(resp.staffLocations);$('#kpiActive').text(resp.activeSessionCount||0);$('#kpiTasks').text(resp.tasksInProgress||0);$('#kpiTime').text(new Date().toTimeString().slice(0,5));$('#sidebarCount').text((resp.activeSessionCount||0)+' موظف في الميدان الآن');}
        }});
    },30000);
})();
</script>
<?php endif; ?>
