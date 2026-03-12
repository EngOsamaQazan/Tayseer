<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use backend\modules\hr\models\HrWorkZone;

$this->title = $model->isNewRecord ? 'إضافة منطقة عمل' : 'تعديل منطقة: ' . $model->name;
$mapsKey = \common\models\SystemSettings::get('google_maps', 'api_key', '');

$defaultLat = $model->latitude ?: 31.9539;
$defaultLng = $model->longitude ?: 35.9106;
$defaultRadius = $model->radius_meters ?: 100;
?>

<style>
.hr-page { padding: 20px; max-width: 900px; margin: 0 auto; }
.hr-page-header {
    display: flex; align-items: center; gap: 12px; margin-bottom: 24px;
}
.hr-page-header h1 {
    font-size: 22px; font-weight: 700; color: var(--clr-primary, #800020); margin: 0;
}
.wz-form-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 768px) { .wz-form-layout { grid-template-columns: 1fr; } }
.wz-f-card {
    background: #fff; border-radius: 12px; padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #f0f0f0;
}
.wz-f-card h3 {
    font-size: 15px; font-weight: 700; color: #334155; margin: 0 0 16px;
    padding-bottom: 10px; border-bottom: 1px solid #f0f0f0;
}
.wz-f-row { margin-bottom: 14px; }
.wz-f-label {
    display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;
}
.wz-f-input {
    width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: 14px; transition: border-color .2s;
}
.wz-f-input:focus { border-color: var(--clr-primary, #800020); outline: none; }
.wz-f-select {
    width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: 14px; background: #fff;
}
.wz-map-box {
    background: #fff; border-radius: 12px; overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #f0f0f0;
}
#zone-form-map { width: 100%; height: 380px; }
.map-hint {
    padding: 10px 16px; font-size: 12px; color: #64748b; background: #f8fafc;
    border-top: 1px solid #f0f0f0;
}
.radius-slider-wrap {
    margin-top: 12px; display: flex; align-items: center; gap: 12px;
}
.radius-slider-wrap input[type=range] { flex: 1; accent-color: var(--clr-primary, #800020); }
.radius-val {
    min-width: 60px; text-align: center; font-weight: 700; color: var(--clr-primary, #800020);
    font-size: 15px;
}
.wz-f-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
.wz-f-actions .btn { padding: 10px 24px; font-size: 14px; font-weight: 600; border-radius: 8px; }
.coord-row { display: flex; gap: 12px; }
.coord-row > div { flex: 1; }
</style>

<div class="hr-page">
    <div class="hr-page-header">
        <a href="<?= Url::to(['index']) ?>" class="btn btn-default btn-sm">
            <i class="fa fa-arrow-right"></i>
        </a>
        <h1><i class="fa fa-map-marker"></i> <?= $this->title ?></h1>
    </div>

    <?php $form = ActiveForm::begin(['id' => 'zone-form']); ?>

    <div class="wz-form-layout">
        <div>
            <div class="wz-f-card" style="margin-bottom:20px">
                <h3><i class="fa fa-info-circle"></i> البيانات الأساسية</h3>
                <div class="wz-f-row">
                    <label class="wz-f-label">اسم المنطقة *</label>
                    <?= Html::activeTextInput($model, 'name', ['class' => 'wz-f-input', 'placeholder' => 'مثال: المكتب الرئيسي']) ?>
                </div>
                <div class="wz-f-row">
                    <label class="wz-f-label">نوع المنطقة</label>
                    <?= Html::activeDropDownList($model, 'zone_type', HrWorkZone::getZoneTypes(), ['class' => 'wz-f-select']) ?>
                </div>
                <div class="wz-f-row">
                    <label class="wz-f-label">العنوان</label>
                    <?= Html::activeTextInput($model, 'address', ['class' => 'wz-f-input', 'id' => 'zone-address', 'placeholder' => 'العنوان التفصيلي...']) ?>
                </div>
            </div>

            <div class="wz-f-card" style="margin-bottom:20px">
                <h3><i class="fa fa-wifi"></i> تحقق Wi-Fi (اختياري)</h3>
                <div class="wz-f-row">
                    <label class="wz-f-label">اسم شبكة Wi-Fi (SSID)</label>
                    <?= Html::activeTextInput($model, 'wifi_ssid', ['class' => 'wz-f-input', 'placeholder' => 'اسم الشبكة...']) ?>
                </div>
                <div class="wz-f-row">
                    <label class="wz-f-label">معرف الشبكة (BSSID)</label>
                    <?= Html::activeTextInput($model, 'wifi_bssid', ['class' => 'wz-f-input', 'placeholder' => 'مثال: AA:BB:CC:DD:EE:FF', 'dir' => 'ltr']) ?>
                </div>
            </div>

            <div class="wz-f-card">
                <h3><i class="fa fa-crosshairs"></i> الإحداثيات ونصف القطر</h3>
                <div class="coord-row">
                    <div class="wz-f-row">
                        <label class="wz-f-label">خط العرض</label>
                        <?= Html::activeTextInput($model, 'latitude', ['class' => 'wz-f-input', 'id' => 'zone-lat', 'readonly' => true, 'dir' => 'ltr']) ?>
                    </div>
                    <div class="wz-f-row">
                        <label class="wz-f-label">خط الطول</label>
                        <?= Html::activeTextInput($model, 'longitude', ['class' => 'wz-f-input', 'id' => 'zone-lng', 'readonly' => true, 'dir' => 'ltr']) ?>
                    </div>
                </div>
                <div class="radius-slider-wrap">
                    <span style="font-size:12px;color:#64748b">نصف القطر:</span>
                    <input type="range" id="radius-slider" min="20" max="2000" step="10"
                           value="<?= $defaultRadius ?>">
                    <span class="radius-val" id="radius-display"><?= $defaultRadius ?>m</span>
                    <?= Html::activeHiddenInput($model, 'radius_meters', ['id' => 'zone-radius']) ?>
                </div>
            </div>
        </div>

        <div>
            <div class="wz-map-box">
                <div id="zone-form-map"></div>
                <div class="map-hint">
                    <i class="fa fa-hand-pointer-o"></i>
                    اضغط على الخريطة لتحديد موقع المنطقة، أو اسحب العلامة لتعديل الموقع
                </div>
            </div>
        </div>
    </div>

    <div class="wz-f-actions">
        <a href="<?= Url::to(['index']) ?>" class="btn btn-default">إلغاء</a>
        <?= Html::submitButton($model->isNewRecord ? 'إنشاء المنطقة' : 'حفظ التعديلات', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php if ($mapsKey): ?>
<script>
var map, marker, circle;
var currentLat = <?= (float)$defaultLat ?>;
var currentLng = <?= (float)$defaultLng ?>;
var currentRadius = <?= (int)$defaultRadius ?>;

function initMap() {
    map = new google.maps.Map(document.getElementById('zone-form-map'), {
        center: {lat: currentLat, lng: currentLng},
        zoom: 15,
        mapTypeControl: false,
        streetViewControl: false,
    });

    marker = new google.maps.Marker({
        position: {lat: currentLat, lng: currentLng},
        map: map,
        draggable: true,
        title: 'موقع المنطقة',
    });

    circle = new google.maps.Circle({
        map: map,
        center: {lat: currentLat, lng: currentLng},
        radius: currentRadius,
        fillColor: '#800020',
        fillOpacity: 0.15,
        strokeColor: '#800020',
        strokeWeight: 2,
        strokeOpacity: 0.6,
        editable: false,
    });

    map.addListener('click', function(e) {
        updatePosition(e.latLng.lat(), e.latLng.lng());
    });

    marker.addListener('dragend', function() {
        var pos = marker.getPosition();
        updatePosition(pos.lat(), pos.lng());
    });

    document.getElementById('radius-slider').addEventListener('input', function() {
        currentRadius = parseInt(this.value);
        document.getElementById('radius-display').textContent = currentRadius + 'm';
        document.getElementById('zone-radius').value = currentRadius;
        circle.setRadius(currentRadius);
    });
}

function updatePosition(lat, lng) {
    currentLat = lat;
    currentLng = lng;
    marker.setPosition({lat: lat, lng: lng});
    circle.setCenter({lat: lat, lng: lng});
    document.getElementById('zone-lat').value = lat.toFixed(7);
    document.getElementById('zone-lng').value = lng.toFixed(7);
    map.panTo({lat: lat, lng: lng});

    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({location: {lat: lat, lng: lng}}, function(results, status) {
        if (status === 'OK' && results[0]) {
            var addrField = document.getElementById('zone-address');
            if (!addrField.value) {
                addrField.value = results[0].formatted_address;
            }
        }
    });
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= Html::encode($mapsKey) ?>&callback=initMap" async defer></script>
<?php else: ?>
<script>
document.getElementById('zone-form-map').innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:380px;color:#94a3b8;font-size:14px"><div style="text-align:center"><i class="fa fa-map" style="font-size:48px;margin-bottom:12px;display:block"></i>يرجى تكوين مفتاح Google Maps API أولاً</div></div>';
document.getElementById('zone-lat').readOnly = false;
document.getElementById('zone-lng').readOnly = false;
</script>
<?php endif; ?>
