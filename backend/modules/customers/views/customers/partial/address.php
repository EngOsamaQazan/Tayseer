<?php
/**
 * نموذج ديناميكي - عناوين العميل
 * كود الخريطة منقول بالكامل من شاشة الوظائف (_form.php)
 */
use yii\helpers\Html;
use yii\helpers\Url;
use wbraganca\dynamicform\DynamicFormWidget;

$resolveLocationUrl = Url::to(['/jobs/resolve-location']);
$searchPlacesUrl = Url::to(['/jobs/search-places']);
$googleMapsKey = \common\models\SystemSettings::get('google_maps', 'api_key', null)
    ?? Yii::$app->params['googleMapsApiKey'] ?? null;
$formId = $form->id ?? 'smart-onboarding-form';

DynamicFormWidget::begin([
    'widgetContainer' => 'dynamicform_wrapper',
    'widgetBody' => '.container-items',
    'widgetItem' => '.addrres-item',
    'limit' => 20,
    'min' => 1,
    'insertButton' => '.addrres-add-item',
    'deleteButton' => '.addrres-remove-item',
    'model' => $modelsAddress[0],
    'formId' => $formId,
    'formFields' => ['address', 'address_type', 'address_city', 'address_area', 'address_street', 'address_building', 'postal_code', 'plus_code', 'latitude', 'longitude'],
]);
?>

<div class="container-items">
    <?php foreach ($modelsAddress as $i => $addr): ?>
        <div class="addrres-item card addr-panel" data-addr-idx="<?= $i ?>">
            <div class="card-header addr-panel-hdr">
                <span class="addr-type-badge"><?= $addr->address_type == 1 ? 'عنوان العمل' : ($addr->address_type == 2 ? 'عنوان السكن' : 'عنوان') ?></span>
                <div class="addr-panel-actions">
                    <button type="button" class="btn btn-xs btn-info addr-toggle-map" title="إظهار/إخفاء الخريطة"><i class="fa fa-map"></i></button>
                    <button type="button" class="addrres-remove-item btn btn-danger btn-xs" title="حذف"><i class="fa fa-trash"></i></button>
                </div>
            </div>
            <div class="card-body">
                <?php if (!$addr->isNewRecord) echo Html::activeHiddenInput($addr, "[{$i}]id") ?>

                <div class="row">
                    <div class="col-md-3">
                        <?= $form->field($addr, "[{$i}]address_type")->dropDownList([1 => 'عنوان العمل', 2 => 'عنوان السكن'], ['class' => 'form-control addr-type-select'])->label('النوع') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($addr, "[{$i}]address_city")->textInput(['placeholder' => 'المدينة', 'class' => 'form-control addr-field', 'data-addr' => 'city'])->label('المدينة') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($addr, "[{$i}]address_area")->textInput(['placeholder' => 'المنطقة أو الحي', 'class' => 'form-control addr-field', 'data-addr' => 'area'])->label('المنطقة/الحي') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($addr, "[{$i}]address_street")->textInput(['placeholder' => 'الشارع والعنوان التفصيلي', 'class' => 'form-control addr-field', 'data-addr' => 'street'])->label('الشارع') ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <?= $form->field($addr, "[{$i}]address_building")->textInput(['placeholder' => 'المبنى / الطابق / الرقم'])->label('المبنى/الطابق') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($addr, "[{$i}]postal_code")->textInput(['placeholder' => 'مثل 11937', 'dir' => 'ltr', 'style' => 'font-family:monospace'])->label('الرمز البريدي') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($addr, "[{$i}]plus_code")->textInput(['placeholder' => 'مثل 8Q6G+4M', 'dir' => 'ltr', 'style' => 'font-family:monospace', 'readonly' => true, 'class' => 'form-control addr-plus-code'])->label('Plus Code') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($addr, "[{$i}]address")->textInput(['placeholder' => 'ملاحظات إضافية (اختياري)', 'class' => 'form-control'])->label('ملاحظات العنوان') ?>
                    </div>
                </div>

                <!-- خريطة -->
                <div class="addr-map-section">
                    <div class="row" style="margin-bottom:10px">
                        <div class="col-md-12">
                            <div class="addr-smart-loc">
                                <label><i class="fa fa-paste"></i> لصق موقع (من جوجل ماب أو أي مصدر)</label>
                                <textarea class="form-control addr-smart-paste" rows="2" placeholder="الصق هنا: إحداثيات (31.95, 35.91) أو رابط جوجل ماب أو Plus Code..."></textarea>
                                <div class="addr-smart-hint">يقبل: إحداثيات عددية، روابط Google Maps، Plus Codes</div>
                                <div class="addr-smart-result"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="addr-map-search-wrap">
                                <input type="text" class="form-control addr-map-search" placeholder="ابحث عن موقع (مثل: مستشفى الأمير حمزة، شركة نماء عمان)..." autocomplete="off">
                                <button type="button" class="addr-map-search-btn" title="بحث"><i class="fa fa-search"></i></button>
                                <div class="addr-map-search-results"></div>
                            </div>
                            <div class="addr-map-container" style="height:350px;border-radius:8px;margin-top:8px"></div>
                        </div>
                    </div>
                    <div class="row" style="margin-top:8px">
                        <div class="col-md-3">
                            <?= $form->field($addr, "[{$i}]latitude")->textInput(['placeholder' => 'خط العرض', 'dir' => 'ltr', 'class' => 'form-control addr-lat', 'style' => 'background:#f8fafc;font-family:monospace;font-size:13px'])->label('خط العرض') ?>
                        </div>
                        <div class="col-md-3">
                            <?= $form->field($addr, "[{$i}]longitude")->textInput(['placeholder' => 'خط الطول', 'dir' => 'ltr', 'class' => 'form-control addr-lng', 'style' => 'background:#f8fafc;font-family:monospace;font-size:13px'])->label('خط الطول') ?>
                        </div>
                        <div class="col-md-6" style="padding-top:26px">
                            <button type="button" class="btn btn-info btn-sm addr-btn-locate" style="border-radius:8px;font-weight:600">
                                <i class="fa fa-crosshairs"></i> موقعي الحالي
                            </button>
                            <button type="button" class="btn btn-warning btn-sm addr-btn-clear" style="border-radius:8px;font-weight:600">
                                <i class="fa fa-eraser"></i> مسح الموقع
                            </button>
                            <?php if (!$addr->isNewRecord && $addr->latitude && $addr->longitude): ?>
                                <a href="<?= $addr->getMapUrl() ?>" target="_blank" class="btn btn-success btn-sm" style="border-radius:8px;font-weight:600">
                                    <i class="fa fa-external-link"></i> فتح في جوجل ماب
                                </a>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<button type="button" class="addrres-add-item btn btn-success btn-xs"><i class="fa fa-plus"></i> إضافة عنوان</button>

<?php DynamicFormWidget::end() ?>

<!-- Leaflet CSS/JS from CDN -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php if ($googleMapsKey): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= Html::encode($googleMapsKey) ?>&libraries=places&language=ar&loading=async" async defer></script>
<?php endif; ?>

<?php
$js = <<<JS

/* ═══════════════════════════════════════════════════════════
 *  Customer Address Maps
 *  منقول بالكامل من شاشة الوظائف - مع دعم عناوين متعددة
 * ═══════════════════════════════════════════════════════════ */
(function(){
    var resolveUrl = '$resolveLocationUrl';
    var searchPlacesUrl = '$searchPlacesUrl';
    var defaultLat = 31.95;
    var defaultLng = 35.91;
    var maps = {};
    var _jordanBBox = '34.8,33.4,39.3,29.1';

    /* ─── Jordanian postal codes fallback table ─── */
    var _joPostal = {
        'عمان':'11110','جبل عمان':'11181','العبدلي':'11190','الشميساني':'11194','جبل الحسين':'11118',
        'جبل اللويبدة':'11191','ماركا':'11511','طارق':'11947','الهاشمي':'11141','المقابلين':'11710',
        'أبو نصير':'11764','شفا بدران':'11934','الجبيهة':'11941','صويلح':'19110','تلاع العلي':'11183',
        'خلدا':'11953','الرابية':'11215','ضاحية الرشيد':'11593','ضاحية الأمير حسن':'11842',
        'الدوار السابع':'11195','أم أذينة':'11821','الصويفية':'11910','دير غبار':'11954',
        'طبربور':'11171','الرصيفة':'13710','الزرقاء':'13110','الهاشمية':'13125',
        'إربد':'21110','الحصن':'21510','الرمثا':'21410','حواره':'21146','المزار الشمالي':'21610',
        'دير أبي سعيد':'21710','الطيبة':'21810','كفرسوم':'21941','أم قيس':'21986',
        'جرش':'26110','عجلون':'26810','المفرق':'25110',
        'السلط':'19110','الفحيص':'19152','ماحص':'19160','عين الباشا':'19484',
        'مادبا':'17110','ذيبان':'17711',
        'الكرك':'61110','المزار الجنوبي':'61510','غور الصافي':'61710',
        'الطفيلة':'66110','بصيرا':'66165',
        'معان':'71110','الشوبك':'71810','البتراء':'71810',
        'العقبة':'77110',
        'الشونة الشمالية':'28110','الأغوار':'28110','ديرعلا':'25810','الشونة الجنوبية':'18110',
        'وادي السير':'11821','ناعور':'11710','الموقر':'11218','الجيزة':'11814',
        'سحاب':'11512','القويسمة':'11164','المدينة الرياضية':'11196','الياسمين':'11264',
        'الجامعة الأردنية':'11942','مطار الملكة علياء':'11104'
    };

    function lookupPostal(city, area) {
        if (!city && !area) return '';
        var normalize = function(s) { return s.replace(/[\u0640\u064B-\u065F]/g,'').replace(/[أإآ]/g,'ا').replace(/ة/g,'ه').replace(/ى/g,'ي').trim(); };
        if (area) { var na = normalize(area); for (var k in _joPostal) { if (normalize(k) === na) return _joPostal[k]; } }
        if (city) { var nc = normalize(city); for (var k in _joPostal) { if (normalize(k) === nc) return _joPostal[k]; } }
        if (city) { var nc = normalize(city); for (var k in _joPostal) { if (nc.indexOf(normalize(k)) !== -1 || normalize(k).indexOf(nc) !== -1) return _joPostal[k]; } }
        return '';
    }

    /* ─── Plus Code (Open Location Code) encoder ─── */
    function encodePlusCode(lat, lng) {
        var CHARS = '23456789CFGHJMPQRVWX';
        lat = Math.min(90, Math.max(-90, lat)) + 90;
        lng = Math.min(180, Math.max(-180, lng)) + 180;
        var code = '';
        var rLat = 20, rLng = 20;
        for (var i = 0; i < 5; i++) {
            var dLat = Math.floor(lat / rLat);
            var dLng = Math.floor(lng / rLng);
            lat -= dLat * rLat;
            lng -= dLng * rLng;
            code += CHARS.charAt(dLat) + CHARS.charAt(dLng);
            rLat /= 20; rLng /= 20;
            if (i === 3) code += '+';
        }
        return code;
    }

    /* ─── Parse location input (coordinates, URLs, DMS) ─── */
    function parseLocationInput(raw) {
        var m;
        /* 1. Decimal coordinates */
        m = raw.match(/(-?\d+\.?\d*)[,\s]+(-?\d+\.?\d*)/);
        if (m) {
            var a = parseFloat(m[1]), b = parseFloat(m[2]);
            if (!isNaN(a) && !isNaN(b)) {
                if (Math.abs(a) <= 90 && Math.abs(b) <= 180) return {lat: a, lng: b};
                if (Math.abs(b) <= 90 && Math.abs(a) <= 180) return {lat: b, lng: a};
            }
        }
        /* 2. Google Maps URL with coordinates */
        m = raw.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
        if (m) return {lat: parseFloat(m[1]), lng: parseFloat(m[2])};
        m = raw.match(/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/);
        if (m) return {lat: parseFloat(m[1]), lng: parseFloat(m[2])};
        m = raw.match(/!3d(-?\d+\.\d+).*!4d(-?\d+\.\d+)/);
        if (m) return {lat: parseFloat(m[1]), lng: parseFloat(m[2])};
        /* 3. DMS coordinates */
        var dmsRe = /(\d+)[°](\d+)[′''](\d+\.?\d*)[″""]?\s*([NSns])\s*,?\s*(\d+)[°](\d+)[′''](\d+\.?\d*)[″""]?\s*([EWew])/;
        m = raw.match(dmsRe);
        if (m) {
            var lat = parseInt(m[1]) + parseInt(m[2])/60 + parseFloat(m[3])/3600;
            if (m[4].toLowerCase() === 's') lat = -lat;
            var lng = parseInt(m[5]) + parseInt(m[6])/60 + parseFloat(m[7])/3600;
            if (m[8].toLowerCase() === 'w') lng = -lng;
            return {lat: lat, lng: lng};
        }
        return null;
    }

    /* ─── Panel helpers ─── */
    function getPanel(el) { return $(el).closest('.addrres-item'); }
    function getPanelId(panel) { return panel.data('addr-idx') || panel.index(); }

    /* ═══════════════════════════════════════════════════════════
     *  الخريطة التفاعلية (Leaflet + OpenStreetMap)
     *  — نسخة طبق الأصل من شاشة الوظائف
     * ═══════════════════════════════════════════════════════════ */
    function initMap(panel) {
        var pid = getPanelId(panel);
        if (maps[pid]) return maps[pid];

        var container = panel.find('.addr-map-container')[0];
        if (!container) return null;

        var latInput = panel.find('.addr-lat');
        var lngInput = panel.find('.addr-lng');
        var initLat = parseFloat(latInput.val()) || defaultLat;
        var initLng = parseFloat(lngInput.val()) || defaultLng;
        var initZoom = (latInput.val() && lngInput.val()) ? 15 : 8;

        var map = L.map(container).setView([initLat, initLng], initZoom);

        var googleStreets = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}&hl=ar', {
            attribution: '&copy; Google Maps', maxZoom: 21
        });
        var googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}&hl=ar', {
            attribution: '&copy; Google Maps', maxZoom: 21
        });
        var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap', maxZoom: 19
        });

        googleStreets.addTo(map);
        L.control.layers({
            'خريطة Google': googleStreets,
            'قمر صناعي': googleHybrid,
            'OpenStreetMap': osmLayer
        }, null, {position: 'bottomleft'}).addTo(map);

        var entry = { map: map, marker: null, panel: panel, _googlePlacesActive: false };
        maps[pid] = entry;
        $(container).data('leafletMap', map);

        map.on('click', function(e) {
            setMarker(entry, e.latlng.lat, e.latlng.lng, false);
        });

        if (latInput.val() && lngInput.val()) {
            setMarker(entry, initLat, initLng, false);
        }

        setTimeout(function(){ map.invalidateSize(); }, 200);

        if (!tryInitGooglePlaces(entry)) {
            var _gpRetry = setInterval(function(){
                if (tryInitGooglePlaces(entry)) clearInterval(_gpRetry);
            }, 800);
            setTimeout(function(){ clearInterval(_gpRetry); }, 12000);
        }

        return entry;
    }

    /* ─── Set marker ─── */
    function setMarker(entry, lat, lng, flyTo) {
        var panel = entry.panel;
        if (entry.marker) entry.map.removeLayer(entry.marker);
        entry.marker = L.marker([lat, lng], {draggable: true}).addTo(entry.map);

        entry.marker.on('dragend', function(e) {
            var pos = e.target.getLatLng();
            panel.find('.addr-lat').val(pos.lat.toFixed(8));
            panel.find('.addr-lng').val(pos.lng.toFixed(8));
            reverseGeocode(entry, pos.lat, pos.lng);
        });

        panel.find('.addr-lat').val(lat.toFixed ? lat.toFixed(8) : lat);
        panel.find('.addr-lng').val(lng.toFixed ? lng.toFixed(8) : lng);
        if (flyTo !== false) entry.map.flyTo([lat, lng], 16);
        reverseGeocode(entry, lat, lng);
    }

    /* ─── Reverse Geocoding: coordinates → address fields ─── */
    var _rgTimers = {};
    function reverseGeocode(entry, lat, lng) {
        var panel = entry.panel;
        var pid = getPanelId(panel);
        clearTimeout(_rgTimers[pid]);

        var plusCode = encodePlusCode(lat, lng);
        panel.find('.addr-plus-code').val(plusCode);

        _rgTimers[pid] = setTimeout(function() {
            $.getJSON('https://nominatim.openstreetmap.org/reverse', {
                lat: lat, lon: lng, format: 'json', addressdetails: 1,
                'accept-language': 'ar', zoom: 18
            }, function(data) {
                var a = (data && data.address) ? data.address : {};

                var city = a.city || a.town || a.village || a.county || a.state || '';
                var area = a.suburb || a.neighbourhood || a.quarter || a.hamlet || '';
                var street = a.road || a.pedestrian || a.footway || '';
                var building = a.house_number || '';
                var postal = a.postcode || lookupPostal(city, area);

                panel.find('[data-addr=city]').val(city);
                panel.find('[data-addr=area]').val(area);
                panel.find('[data-addr=street]').val(street);
                panel.find('input[name*="address_building"]').val(building);
                panel.find('input[name*="postal_code"]').val(postal);

                panel.find('.addr-field, input[name*="address_building"], input[name*="postal_code"]').filter(function() { return $(this).val(); }).addClass('geo-filled');
                setTimeout(function() { panel.find('.geo-filled').removeClass('geo-filled'); }, 2500);

                var popup = '<div style="direction:rtl;font-size:12px;line-height:1.6;max-width:240px">';
                var parts = [street, area, city].filter(Boolean);
                popup += '<strong>' + (parts.length ? parts.join('، ') : (data && data.display_name ? data.display_name.split('،').slice(0,3).join('،') : '')) + '</strong>';
                if (postal) popup += '<br><i class="fa fa-envelope-o" style="color:#94a3b8"></i> ' + postal;
                if (plusCode) popup += '<br><span style="color:#4285f4;font-family:monospace;font-size:11px"><i class="fa fa-plus-square"></i> ' + plusCode + '</span>';
                popup += '</div>';
                if (entry.marker) entry.marker.bindPopup(popup).openPopup();
            });
        }, 300);
    }

    /* ─── Utility functions for search ─── */
    function _distKm(lat1, lng1, lat2, lng2) {
        var R = 6371, dLat = (lat2-lat1)*Math.PI/180, dLng = (lng2-lng1)*Math.PI/180;
        var a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)*Math.sin(dLng/2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }
    function _distLabel(km) {
        return km < 1 ? Math.round(km * 1000) + ' م' : km.toFixed(1) + ' كم';
    }
    function _isInJordan(lat, lng) {
        return lat >= 29.1 && lat <= 33.4 && lng >= 34.8 && lng <= 39.3;
    }
    function _sortByProximity(items, cLat, cLng) {
        return items.sort(function(a, b) {
            return _distKm(cLat, cLng, a.lat, a.lng) - _distKm(cLat, cLng, b.lat, b.lng);
        });
    }
    function _placeTypeIcon(types) {
        if (!types) return 'fa-map-marker';
        var t = (typeof types === 'string') ? types : types.join(',');
        if (t.indexOf('restaurant') >= 0 || t.indexOf('cafe') >= 0 || t.indexOf('food') >= 0) return 'fa-cutlery';
        if (t.indexOf('hospital') >= 0 || t.indexOf('health') >= 0 || t.indexOf('pharmacy') >= 0 || t.indexOf('doctor') >= 0) return 'fa-medkit';
        if (t.indexOf('school') >= 0 || t.indexOf('university') >= 0) return 'fa-graduation-cap';
        if (t.indexOf('store') >= 0 || t.indexOf('shop') >= 0 || t.indexOf('mall') >= 0) return 'fa-shopping-cart';
        if (t.indexOf('bank') >= 0 || t.indexOf('finance') >= 0) return 'fa-university';
        if (t.indexOf('lodging') >= 0 || t.indexOf('hotel') >= 0) return 'fa-bed';
        if (t.indexOf('gas_station') >= 0 || t.indexOf('fuel') >= 0) return 'fa-car';
        if (t.indexOf('mosque') >= 0 || t.indexOf('church') >= 0 || t.indexOf('worship') >= 0) return 'fa-moon-o';
        if (t.indexOf('company') >= 0 || t.indexOf('establishment') >= 0 || t.indexOf('office') >= 0) return 'fa-building';
        if (t.indexOf('route') >= 0 || t.indexOf('road') >= 0 || t.indexOf('highway') >= 0) return 'fa-road';
        if (t.indexOf('place') >= 0) return 'fa-map-pin';
        return 'fa-map-marker';
    }
    function _shortName(r) {
        if (!r || !r.address) return r.display_name || '';
        var a = r.address;
        var name = a.amenity || a.building || a.shop || a.office || a.tourism || a.leisure || '';
        if (name) {
            var area = a.suburb || a.neighbourhood || a.city || a.town || '';
            return area ? name + '، ' + area : name;
        }
        var parts = (r.display_name || '').split('،');
        return parts.slice(0, Math.min(3, parts.length)).join('،').trim();
    }
    function _extractAddr(r) {
        if (!r || !r.address) return '';
        var a = r.address;
        return [a.road, a.suburb || a.neighbourhood, a.city || a.town || a.village, a.country].filter(Boolean).join('، ');
    }

    /* ─── Loading UI with progress indicators ─── */
    var _addrSearchTimerIntervals = {};
    function _showAddrSearchLoading(resEl, pid) {
        clearInterval(_addrSearchTimerIntervals[pid]);
        var html = '<div class="map-search-loading" id="addr-search-loading-'+pid+'">';
        html += '<div><i class="fa fa-spinner fa-spin" style="color:#3b82f6;font-size:16px"></i></div>';
        html += '<div style="font-weight:700;color:#334155;margin-top:6px">جاري البحث في كل الأردن...</div>';
        html += '<div class="search-progress">';
        html += '<span class="sp-dot active" data-src="google"></span>';
        html += '<span class="sp-dot active" data-src="osm"></span>';
        html += '<span class="sp-dot active" data-src="photon"></span>';
        html += '</div>';
        html += '<div class="search-timer">يتم جمع النتائج من 3 مصادر مختلفة...</div>';
        html += '<div class="search-hint"><i class="fa fa-info-circle"></i> انتظر 3-5 ثوانٍ للحصول على أفضل النتائج مرتبة حسب القرب</div>';
        html += '</div>';
        resEl.html(html).addClass('show');
        var _elapsed = 0;
        _addrSearchTimerIntervals[pid] = setInterval(function() {
            _elapsed++;
            var el = resEl.find('.search-timer');
            if (!el.length) { clearInterval(_addrSearchTimerIntervals[pid]); return; }
            if (_elapsed <= 2) el.text('يتم جمع النتائج من 3 مصادر مختلفة... (' + _elapsed + ' ثانية)');
            else if (_elapsed <= 5) el.text('جاري ترتيب النتائج حسب الأقرب إليك... (' + _elapsed + ' ثوانٍ)');
            else el.text('البحث يستغرق وقتاً أطول من المعتاد... (' + _elapsed + ' ثوانٍ)');
        }, 1000);
    }
    function _markAddrSourceDone(resEl, src) {
        resEl.find('.sp-dot[data-src="'+src+'"]').removeClass('active').addClass('done');
    }
    function _stopAddrSearchTimer(pid) {
        clearInterval(_addrSearchTimerIntervals[pid]);
    }

    /* ─── Render search results ─── */
    function _renderAddrItems(resEl, items, bLat, bLng, originalQuery) {
        if (!items || items.length === 0) {
            resEl.html('<div class="map-search-loading">لا توجد نتائج</div>').addClass('show');
            return;
        }
        var html = '';
        html += '<div style="padding:6px 14px;background:#f0f9ff;border-bottom:1px solid #e0e7ff;direction:rtl;font-size:11px;color:#3b82f6;display:flex;align-items:center;gap:6px">';
        html += '<i class="fa fa-map-marker"></i> ';
        html += '<span>' + items.length + ' نتيجة — مرتبة حسب الأقرب إليك</span>';
        html += '</div>';
        items.forEach(function(r) {
            var dist = _distKm(bLat, bLng, r.lat, r.lng);
            var icon = (r.types && r.types.length) ? _placeTypeIcon(r.types) : 'fa-map-marker';
            var srcBadge = r.src === 'google' ? ' <span style="font-size:9px;color:#4285f4;font-weight:700">G</span>' : '';
            var distColor = dist < 5 ? '#22c55e' : (dist < 20 ? '#3b82f6' : '#94a3b8');
            html += '<div class="result-item" data-lat="' + r.lat + '" data-lng="' + r.lng + '">';
            html += '<span class="result-icon"><i class="fa ' + icon + '"></i>' + srcBadge + '</span>';
            html += '<span class="result-text"><span class="result-name">' + r.name + '</span>';
            html += '<span class="result-addr">' + (r.addr || '') + ' · <span style="color:' + distColor + ';font-weight:600"><i class="fa fa-location-arrow"></i> ' + _distLabel(dist) + '</span></span>';
            html += '</span></div>';
        });
        if (originalQuery) {
            var gUrl = 'https://www.google.com/maps/search/' + encodeURIComponent(originalQuery + ' الأردن');
            html += '<div style="padding:8px 14px;background:#f8fafc;border-top:1px solid #e2e8f0;direction:rtl;font-size:11px;text-align:center">';
            html += '<a href="' + gUrl + '" target="_blank" style="color:#4285f4;font-weight:600;text-decoration:none">';
            html += '<i class="fa fa-external-link"></i> لم تجد ما تبحث عنه؟ ابحث في خرائط جوجل</a></div>';
        }
        resEl.html(html).addClass('show');
    }

    function _showAddrNoResults(resEl, q) {
        var gUrl = 'https://www.google.com/maps/search/' + encodeURIComponent(q + ' الأردن');
        var html = '<div style="padding:14px;text-align:center;direction:rtl">';
        html += '<div style="color:#94a3b8;font-size:13px;margin-bottom:8px"><i class="fa fa-search"></i> لم يتم العثور على نتائج</div>';
        html += '<a href="' + gUrl + '" target="_blank" style="display:inline-block;padding:8px 16px;background:#4285f4;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:700">';
        html += '<i class="fa fa-external-link"></i> ابحث في خرائط جوجل</a>';
        html += '<div style="font-size:11px;color:#94a3b8;margin-top:8px">ابحث في جوجل ماب ثم انسخ الرابط وألصقه في حقل "لصق موقع" أعلاه</div>';
        html += '</div>';
        resEl.html(html).addClass('show');
    }

    /* ─── Combined Search: Google Places (server) + Nominatim + Photon in parallel ─── */
    function fallbackMapSearch(entry, q) {
        if (!q || q.length < 2) { entry.panel.find('.addr-map-search-results').removeClass('show').empty(); return; }
        var resEl = entry.panel.find('.addr-map-search-results');
        var pid = getPanelId(entry.panel);
        var mapCenter = entry.map.getCenter();
        var bLat = mapCenter.lat, bLng = mapCenter.lng;
        var pending = 3, allItems = [], _earlyRendered = false;

        _showAddrSearchLoading(resEl, pid);

        function _dedup(items) {
            var seen = {};
            return items.filter(function(it) {
                var key = it.lat.toFixed(4) + ',' + it.lng.toFixed(4);
                if (seen[key]) return false;
                seen[key] = true;
                return true;
            });
        }

        function _renderCurrent() {
            var unique = _dedup(allItems);
            var inJordan = unique.filter(function(it) { return _isInJordan(it.lat, it.lng); });
            var toShow = inJordan.length > 0 ? inJordan : unique;
            toShow = _sortByProximity(toShow, bLat, bLng).slice(0, 12);
            if (toShow.length > 0) {
                _renderAddrItems(resEl, toShow, bLat, bLng, q);
                _earlyRendered = true;
            }
        }

        function onBatchDone() {
            pending--;
            if (pending > 0) {
                if (allItems.length > 0 && !_earlyRendered) _renderCurrent();
                return;
            }
            _stopAddrSearchTimer(pid);
            var unique = _dedup(allItems);
            var inJordan = unique.filter(function(it) { return _isInJordan(it.lat, it.lng); });
            var toShow = inJordan.length > 0 ? inJordan : unique;
            toShow = _sortByProximity(toShow, bLat, bLng).slice(0, 12);
            if (toShow.length > 0) {
                _renderAddrItems(resEl, toShow, bLat, bLng, q);
            } else {
                _showAddrNoResults(resEl, q);
            }
        }

        $.getJSON(searchPlacesUrl, {
            q: q, lat: bLat, lng: bLng
        }, function(data) {
            _markAddrSourceDone(resEl, 'google');
            if (data && data.results) {
                data.results.forEach(function(r) {
                    allItems.push({
                        lat: r.lat, lng: r.lng,
                        name: r.name, addr: r.addr,
                        types: r.types, src: 'google'
                    });
                });
            }
            onBatchDone();
        }).fail(function(){ _markAddrSourceDone(resEl, 'google'); onBatchDone(); });

        $.getJSON('https://nominatim.openstreetmap.org/search', {
            q: q, format: 'json', limit: 15, addressdetails: 1, 'accept-language': 'ar',
            viewbox: _jordanBBox, bounded: 0, countrycodes: 'jo'
        }, function(nd) {
            _markAddrSourceDone(resEl, 'osm');
            if (nd && nd.length > 0) {
                nd.forEach(function(r) {
                    allItems.push({ lat: parseFloat(r.lat), lng: parseFloat(r.lon), name: _shortName(r), addr: _extractAddr(r), src: 'nom' });
                });
            }
            onBatchDone();
        }).fail(function(){ _markAddrSourceDone(resEl, 'osm'); onBatchDone(); });

        $.getJSON('https://photon.komoot.io/api/', {
            q: q + ' الأردن', lat: bLat, lon: bLng, limit: 15, lang: 'default'
        }, function(data) {
            _markAddrSourceDone(resEl, 'photon');
            (data && data.features || []).forEach(function(f) {
                var p = f.properties, g = f.geometry;
                allItems.push({
                    lat: g.coordinates[1], lng: g.coordinates[0],
                    name: p.name || p.street || '',
                    addr: [p.city, p.state, p.country].filter(Boolean).join('، '),
                    src: 'photon'
                });
            });
            onBatchDone();
        }).fail(function(){ _markAddrSourceDone(resEl, 'photon'); onBatchDone(); });
    }

    /* ─── Google Places Autocomplete — نسخة طبق الأصل من الوظائف ─── */
    function tryInitGooglePlaces(entry) {
        if (typeof google === 'undefined' || !google.maps || !google.maps.places) return false;
        if (entry._googlePlacesActive) return true;

        var wrap = entry.panel.find('.addr-map-search-wrap')[0];
        if (!wrap) return false;

        if (google.maps.places.PlaceAutocompleteElement) {
            try {
                var pac = new google.maps.places.PlaceAutocompleteElement({
                    locationBias: { north: 33.4, south: 29.1, east: 39.3, west: 34.8 }
                });
                pac.id = 'gmp-place-input-' + getPanelId(entry.panel);
                pac.style.cssText = 'width:100%;';
                pac.setAttribute('placeholder', 'ابحث بالاسم: شركة، مستشفى، مطعم، شارع...');

                entry.panel.find('.addr-map-search').hide();
                entry.panel.find('.addr-map-search-btn').hide();
                entry.panel.find('.addr-map-search-results').remove();
                wrap.insertBefore(pac, wrap.firstChild);

                pac.addEventListener('gmp-select', async function(e) {
                    var place = e.placePrediction.toPlace();
                    await place.fetchFields({ fields: ['displayName', 'formattedAddress', 'location'] });
                    if (place.location) {
                        setMarker(entry, place.location.lat(), place.location.lng(), true);
                    }
                });

                entry._googlePlacesActive = true;
                return true;
            } catch(e) { /* fall through to legacy */ }
        }

        if (google.maps.places.Autocomplete) {
            var input = entry.panel.find('.addr-map-search')[0];
            var autocomplete = new google.maps.places.Autocomplete(input, {
                fields: ['geometry', 'name', 'formatted_address']
            });
            autocomplete.setBounds(new google.maps.LatLngBounds(
                new google.maps.LatLng(29.1, 34.8),
                new google.maps.LatLng(33.4, 39.3)
            ));
            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (place && place.geometry) {
                    setMarker(entry, place.geometry.location.lat(), place.geometry.location.lng(), true);
                    input.value = place.name || place.formatted_address || '';
                }
            });
            entry.panel.find('.addr-map-search').off('input keydown');
            entry.panel.find('.addr-map-search-btn').hide();
            entry.panel.find('.addr-map-search-results').remove();
            entry._googlePlacesActive = true;
            return true;
        }

        return false;
    }

    /* ═══════════════════════════════════════════════════════════
     *  Event Delegation — أحداث مفوّضة للعناصر الديناميكية
     * ═══════════════════════════════════════════════════════════ */

    // Fix map when wizard step becomes visible
    $(document).on('map:show', '.addrres-item', function() {
        var panel = $(this);
        var entry = initMap(panel);
        if (entry) {
            setTimeout(function(){ entry.map.invalidateSize(); }, 100);
            setTimeout(function(){ entry.map.invalidateSize(); }, 400);
            setTimeout(function(){
                entry.map.invalidateSize();
                entry.map.setView(entry.map.getCenter(), entry.map.getZoom());
            }, 800);
        }
    });

    // Toggle map section
    $(document).on('click', '.addr-toggle-map', function() {
        var panel = getPanel(this);
        var section = panel.find('.addr-map-section');
        section.slideToggle(300, function() {
            if (section.is(':visible')) {
                var entry = initMap(panel);
                if (entry) {
                    setTimeout(function(){ entry.map.invalidateSize(); }, 100);
                    setTimeout(function(){ entry.map.invalidateSize(); }, 500);
                }
            }
        });
    });

    // Auto-init all maps on page load
    setTimeout(function() {
        $('.addrres-item').each(function() {
            var panel = $(this);
            var entry = initMap(panel);
            if (entry) setTimeout(function(){ entry.map.invalidateSize(); }, 300);
        });
    }, 400);

    // Map search input
    var _searchTimers = {};
    $(document).on('input', '.addr-map-search', function() {
        var panel = getPanel(this);
        var pid = getPanelId(panel);
        var entry = maps[pid];
        if (entry && entry._googlePlacesActive) return;
        clearTimeout(_searchTimers[pid]);
        var q = $(this).val().trim();
        if (q.length < 2) { panel.find('.addr-map-search-results').removeClass('show').empty(); return; }
        _searchTimers[pid] = setTimeout(function() {
            var entry = maps[pid]; if (!entry) return;
            fallbackMapSearch(entry, q);
        }, 200);
    });
    $(document).on('keydown', '.addr-map-search', function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            var panel = getPanel(this);
            var pid = getPanelId(panel);
            var entry = maps[pid];
            if (entry && entry._googlePlacesActive) return;
            clearTimeout(_searchTimers[pid]);
            if (!entry) return;
            fallbackMapSearch(entry, $(this).val().trim());
        }
    });
    $(document).on('click', '.addr-map-search-btn', function() {
        var panel = getPanel(this);
        var pid = getPanelId(panel);
        var entry = maps[pid];
        if (entry && entry._googlePlacesActive) return;
        if (!entry) return;
        fallbackMapSearch(entry, panel.find('.addr-map-search').val().trim());
    });
    $(document).on('click', '.addr-map-search-results .result-item', function() {
        var panel = getPanel(this);
        var pid = getPanelId(panel);
        var entry = maps[pid]; if (!entry) return;
        var lat = parseFloat($(this).data('lat'));
        var lng = parseFloat($(this).data('lng'));
        if (!isNaN(lat) && !isNaN(lng)) {
            setMarker(entry, lat, lng, true);
            panel.find('.addr-map-search').val($(this).find('.result-name').text().trim());
        }
        panel.find('.addr-map-search-results').removeClass('show');
    });
    $(document).on('blur', '.addr-map-search', function() {
        var panel = getPanel(this);
        var entry = maps[getPanelId(panel)];
        if (entry && !entry._googlePlacesActive) {
            setTimeout(function(){ panel.find('.addr-map-search-results').removeClass('show'); }, 300);
        }
    });

    /* ─── Smart paste — لصق الموقع الذكي ─── */
    $(document).on('input', '.addr-smart-paste', function() {
        var panel = getPanel(this);
        var pid = getPanelId(panel);
        var raw = $(this).val().trim();
        var resEl = panel.find('.addr-smart-result');
        if (!raw) { resEl.removeClass('show').removeAttr('style'); return; }

        var coords = parseLocationInput(raw);
        if (coords) {
            var entry = maps[pid] || initMap(panel);
            if (entry) setMarker(entry, coords.lat, coords.lng, true);
            resEl.html('<i class="fa fa-check-circle"></i> تم التعرف على الموقع: ' + coords.lat.toFixed(6) + ', ' + coords.lng.toFixed(6))
                .css({background:'#dcfce7',color:'#15803d'}).addClass('show');
            return;
        }

        var isUrl = /^https?:\/\//i.test(raw);
        var isPlusCode = /[23456789CFGHJMPQRVWX]{2,}\+/i.test(raw);
        var delay = (isUrl || isPlusCode) ? 100 : 600;

        resEl.html('<i class="fa fa-spinner fa-spin"></i> جاري التحليل...').css({background:'#fef3c7',color:'#92400e'}).addClass('show');

        setTimeout(function() {
            $.getJSON(resolveUrl, {q: raw}, function(data) {
                if (data && data.success) {
                    var lat = parseFloat(data.lat), lng = parseFloat(data.lng);
                    var entry = maps[pid] || initMap(panel);
                    if (entry) setMarker(entry, lat, lng, true);
                    var msg = '<i class="fa fa-check-circle"></i> ';
                    if (data.display_name) {
                        msg += data.display_name + ' (' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ')';
                    } else {
                        msg += 'تم التعرف على الموقع: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
                    }
                    resEl.html(msg).css({background:'#dcfce7',color:'#15803d'}).addClass('show');
                } else {
                    resEl.html('<i class="fa fa-exclamation-circle"></i> لم يتم التعرف على الموقع. جرب إحداثيات عددية أو رابط جوجل ماب أو Plus Code.')
                        .css({background:'#fee2e2',color:'#b91c1c'}).addClass('show');
                }
            }).fail(function() {
                resEl.html('<i class="fa fa-exclamation-circle"></i> خطأ في التحليل.')
                    .css({background:'#fee2e2',color:'#b91c1c'}).addClass('show');
            });
        }, delay);
    });

    // Current location
    $(document).on('click', '.addr-btn-locate', function() {
        var btn = $(this);
        var panel = getPanel(this);
        var pid = getPanelId(panel);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري...');
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                var entry = maps[pid] || initMap(panel);
                if (entry) setMarker(entry, pos.coords.latitude, pos.coords.longitude, true);
                btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i> موقعي الحالي');
            }, function(err) {
                alert('لم نتمكن من تحديد موقعك: ' + err.message);
                btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i> موقعي الحالي');
            }, {enableHighAccuracy: true, timeout: 10000});
        } else {
            alert('المتصفح لا يدعم تحديد الموقع');
            btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i> موقعي الحالي');
        }
    });

    // Clear location
    $(document).on('click', '.addr-btn-clear', function() {
        var panel = getPanel(this);
        var pid = getPanelId(panel);
        var entry = maps[pid];
        if (entry) {
            if (entry.marker) { entry.map.removeLayer(entry.marker); entry.marker = null; }
            entry.map.setView([defaultLat, defaultLng], 8);
        }
        panel.find('.addr-lat, .addr-lng').val('');
        panel.find('[data-addr=city], [data-addr=area], [data-addr=street]').val('');
        panel.find('input[name*="address_building"], input[name*="postal_code"]').val('');
        panel.find('.addr-plus-code').val('');
        panel.find('.addr-smart-paste').val('');
        panel.find('.addr-smart-result').removeClass('show');
    });

    /* ─── Address fields → map sync (forward geocoding) ─── */
    var _fwdTimers = {};
    $(document).on('change', '.addr-field', function() {
        var panel = getPanel(this);
        var pid = getPanelId(panel);
        var entry = maps[pid];
        if (!entry) return;
        clearTimeout(_fwdTimers[pid]);
        _fwdTimers[pid] = setTimeout(function() {
            var parts = [
                panel.find('[data-addr=street]').val(),
                panel.find('[data-addr=area]').val(),
                panel.find('[data-addr=city]').val()
            ].filter(Boolean);
            if (!parts.length) return;
            var q = parts.join(', ');
            $.getJSON('https://nominatim.openstreetmap.org/search', {
                q: q, format: 'json', limit: 1, 'accept-language': 'ar',
                viewbox: '34.8,33.4,39.3,29.1', bounded: 1
            }, function(results) {
                if (results && results.length > 0) {
                    var r = results[0];
                    setMarker(entry, parseFloat(r.lat), parseFloat(r.lon), true);
                }
            });
        }, 500);
    });

    // Update badge on type change
    $(document).on('change', '.addr-type-select', function() {
        var panel = getPanel(this);
        var val = $(this).val();
        var label = val == 1 ? 'عنوان العمل' : (val == 2 ? 'عنوان السكن' : 'عنوان');
        panel.find('.addr-type-badge').text(label);
    });

    // Cleanup map on item remove
    $(document).on('click', '.addrres-remove-item', function() {
        var panel = getPanel(this);
        var pid = getPanelId(panel);
        if (maps[pid]) {
            maps[pid].map.remove();
            delete maps[pid];
        }
    });

    // Init map for newly added address items
    function initNewAddrPanel(panel) {
        if (!panel.data('_addr_inited')) {
            panel.data('_addr_inited', true);
            var uid = 'new-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5);
            panel.attr('data-addr-idx', uid);
            panel.removeData('addr-idx');

            var oldC = panel.find('.addr-map-container');
            if (oldC.length) {
                var freshC = jQuery('<div class="addr-map-container" style="height:350px;border-radius:8px;margin-top:8px"></div>');
                oldC.replaceWith(freshC);
            }

            panel.find('gmp-place-autocomplete, [id^="gmp-place-input-"]').remove();
            panel.find('.addr-map-search').show().val('');
            panel.find('.addr-map-search-btn').show();
            if (!panel.find('.addr-map-search-results').length) {
                panel.find('.addr-map-search-wrap').append('<div class="addr-map-search-results"></div>');
            }

            panel.find('.addr-lat, .addr-lng, .addr-plus-code').val('');
            panel.find('.addr-smart-paste').val('');
            panel.find('.addr-smart-result').removeClass('show');

            setTimeout(function() {
                var entry = initMap(panel);
                if (entry) {
                    setTimeout(function(){ entry.map.invalidateSize(); }, 200);
                    setTimeout(function(){ entry.map.invalidateSize(); }, 600);
                }
            }, 300);
        }
    }

    // Primary: afterInsert event from DynamicFormWidget
    $('.dynamicform_wrapper').on('afterInsert', function(e, item) {
        initNewAddrPanel($(item));
    });

    // Fallback: click handler on add button for cases where afterInsert doesn't fire
    $(document).on('click', '.addrres-add-item', function() {
        setTimeout(function() {
            $('.addrres-item').each(function() {
                var p = $(this);
                var container = p.find('.addr-map-container')[0];
                if (container && !$(container).data('leafletMap') && !p.data('_addr_inited')) {
                    initNewAddrPanel(p);
                }
            });
        }, 400);
    });

    /* Fix Leaflet map rendering */
    setTimeout(function(){
        for (var pid in maps) { if (maps[pid]) maps[pid].map.invalidateSize(); }
    }, 500);

})();
JS;
$this->registerJs($js);
?>
