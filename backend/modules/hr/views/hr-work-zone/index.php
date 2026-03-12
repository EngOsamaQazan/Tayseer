<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use backend\modules\hr\models\HrWorkZone;

$this->title = 'مناطق العمل (Geofences)';

$zoneTypes = HrWorkZone::getZoneTypes();
$typeIcons = [
    'office'      => 'fa-building',
    'branch'      => 'fa-bank',
    'client_site' => 'fa-user',
    'field_area'  => 'fa-map',
    'restricted'  => 'fa-ban',
];
$typeColors = [
    'office'      => '#3b82f6',
    'branch'      => '#8b5cf6',
    'client_site' => '#f59e0b',
    'field_area'  => '#10b981',
    'restricted'  => '#ef4444',
];
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
.hr-page { padding: 20px; }
.hr-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.hr-page-header h1 {
    font-size: 22px; font-weight: 700; color: var(--clr-primary, #800020); margin: 0;
}
.wz-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 992px) { .wz-layout { grid-template-columns: 1fr; } }
.wz-list { display: flex; flex-direction: column; gap: 12px; max-height: 600px; overflow-y: auto; padding-left: 4px; }
.wz-card {
    background: #fff; border-radius: 10px; padding: 16px 20px;
    box-shadow: 0 1px 6px rgba(0,0,0,.05); border: 2px solid transparent;
    cursor: pointer; transition: all .2s;
}
.wz-card:hover, .wz-card.active { border-color: var(--clr-primary, #800020); box-shadow: 0 3px 14px rgba(0,0,0,.1); }
.wz-card.inactive-zone { opacity: .55; }
.wz-card-head { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
.wz-icon {
    width: 36px; height: 36px; border-radius: 8px; display: flex;
    align-items: center; justify-content: center; color: #fff; font-size: 15px;
}
.wz-name { font-size: 15px; font-weight: 700; color: #1e293b; flex: 1; }
.wz-badge {
    padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600;
}
.wz-badge.on { background: #dcfce7; color: #166534; }
.wz-badge.off { background: #fee2e2; color: #991b1b; }
.wz-details { display: flex; gap: 12px; font-size: 12px; color: #64748b; flex-wrap: wrap; }
.wz-details .item { display: flex; align-items: center; gap: 4px; }
.wz-actions { display: flex; gap: 6px; margin-top: 8px; justify-content: flex-end; }
.wz-actions a, .wz-actions button {
    padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer;
}
.wz-map-wrap {
    background: #fff; border-radius: 12px; overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); min-height: 600px; position: relative;
}
#zones-map { width: 100%; height: 600px; }
.empty-state {
    text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
.empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; }
.empty-state h3 { font-size: 18px; color: #475569; margin-bottom: 8px; }
.empty-state p { color: #94a3b8; margin-bottom: 20px; }
</style>

<div class="hr-page">
    <div class="hr-page-header">
        <h1><i class="fa fa-map-marker"></i> <?= $this->title ?></h1>
        <a href="<?= Url::to(['create']) ?>" class="btn btn-primary btn-sm">
            <i class="fa fa-plus"></i> إضافة منطقة عمل
        </a>
    </div>

    <?php if ($dataProvider->getTotalCount() === 0): ?>
        <div class="empty-state">
            <i class="fa fa-map-marker"></i>
            <h3>لا توجد مناطق عمل</h3>
            <p>أضف أول منطقة عمل لتفعيل نظام السياج الجغرافي</p>
            <a href="<?= Url::to(['create']) ?>" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> إضافة منطقة عمل
            </a>
        </div>
    <?php else: ?>
        <div class="wz-layout">
            <div class="wz-list">
                <?php foreach ($dataProvider->getModels() as $zone): ?>
                    <div class="wz-card <?= $zone->is_active ? '' : 'inactive-zone' ?>"
                         data-lat="<?= $zone->latitude ?>"
                         data-lng="<?= $zone->longitude ?>"
                         data-radius="<?= $zone->radius_meters ?>"
                         data-name="<?= Html::encode($zone->name) ?>"
                         onclick="focusZone(this)">
                        <div class="wz-card-head">
                            <div class="wz-icon" style="background:<?= $typeColors[$zone->zone_type] ?? '#64748b' ?>">
                                <i class="fa <?= $typeIcons[$zone->zone_type] ?? 'fa-map-marker' ?>"></i>
                            </div>
                            <span class="wz-name"><?= Html::encode($zone->name) ?></span>
                            <span class="wz-badge <?= $zone->is_active ? 'on' : 'off' ?>">
                                <?= $zone->is_active ? 'فعّال' : 'معطّل' ?>
                            </span>
                        </div>
                        <div class="wz-details">
                            <div class="item">
                                <i class="fa fa-tag"></i>
                                <?= $zoneTypes[$zone->zone_type] ?? $zone->zone_type ?>
                            </div>
                            <div class="item">
                                <i class="fa fa-bullseye"></i>
                                <?= $zone->radius_meters ?> متر
                            </div>
                            <?php if ($zone->wifi_ssid): ?>
                            <div class="item">
                                <i class="fa fa-wifi"></i>
                                <?= Html::encode($zone->wifi_ssid) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($zone->address): ?>
                            <div style="font-size:12px;color:#94a3b8;margin-top:4px">
                                <i class="fa fa-map-pin"></i> <?= Html::encode($zone->address) ?>
                            </div>
                        <?php endif; ?>
                        <div class="wz-actions">
                            <a href="<?= Url::to(['update', 'id' => $zone->id]) ?>" style="background:#eff6ff;color:#1d4ed8">
                                <i class="fa fa-pencil"></i> تعديل
                            </a>
                            <?= Html::a(
                                $zone->is_active ? '<i class="fa fa-ban"></i> تعطيل' : '<i class="fa fa-check"></i> تفعيل',
                                ['toggle-active', 'id' => $zone->id],
                                [
                                    'style' => $zone->is_active ? 'background:#fef2f2;color:#dc2626' : 'background:#f0fdf4;color:#16a34a',
                                    'data-method' => 'post',
                                    'data-confirm' => $zone->is_active ? 'تعطيل هذه المنطقة؟' : 'تفعيل هذه المنطقة؟',
                                ]
                            ) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="wz-map-wrap">
                <div id="zones-map"></div>
            </div>
        </div>

        <div style="margin-top:20px; display:flex; justify-content:center;">
            <?= LinkPager::widget(['pagination' => $dataProvider->getPagination()]) ?>
        </div>

        <?php
        $mapsKey = \common\models\SystemSettings::get('google_maps', 'api_key', '');
        if ($mapsKey):
        ?>
        <script>
        var map, circles = [], markers = [];
        function initMap() {
            var zones = document.querySelectorAll('.wz-card');
            var firstLat = zones.length ? parseFloat(zones[0].dataset.lat) : 31.9539;
            var firstLng = zones.length ? parseFloat(zones[0].dataset.lng) : 35.9106;

            map = new google.maps.Map(document.getElementById('zones-map'), {
                center: {lat: firstLat, lng: firstLng},
                zoom: 14,
                mapTypeControl: false,
                streetViewControl: false,
            });

            var typeColors = <?= json_encode($typeColors) ?>;

            zones.forEach(function(el) {
                var lat = parseFloat(el.dataset.lat);
                var lng = parseFloat(el.dataset.lng);
                var radius = parseInt(el.dataset.radius);
                var name = el.dataset.name;

                var marker = new google.maps.Marker({
                    position: {lat: lat, lng: lng},
                    map: map,
                    title: name,
                });
                markers.push(marker);

                var circle = new google.maps.Circle({
                    map: map,
                    center: {lat: lat, lng: lng},
                    radius: radius,
                    fillColor: '#800020',
                    fillOpacity: 0.15,
                    strokeColor: '#800020',
                    strokeWeight: 2,
                    strokeOpacity: 0.6,
                });
                circles.push(circle);
            });

            if (zones.length > 1) {
                var bounds = new google.maps.LatLngBounds();
                markers.forEach(function(m) { bounds.extend(m.getPosition()); });
                map.fitBounds(bounds);
            }
        }
        function focusZone(el) {
            document.querySelectorAll('.wz-card').forEach(function(c){c.classList.remove('active')});
            el.classList.add('active');
            if (map) {
                var lat = parseFloat(el.dataset.lat);
                var lng = parseFloat(el.dataset.lng);
                map.panTo({lat: lat, lng: lng});
                map.setZoom(16);
            }
        }
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key=<?= Html::encode($mapsKey) ?>&callback=initMap" async defer></script>
        <?php else: ?>
        <script>
        document.getElementById('zones-map').innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;font-size:14px"><div style="text-align:center"><i class="fa fa-map" style="font-size:48px;margin-bottom:12px;display:block"></i>يرجى تكوين مفتاح Google Maps API في إعدادات النظام</div></div>';
        </script>
        <?php endif; ?>
    <?php endif; ?>
</div>
