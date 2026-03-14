<?php
/**
 * نموذج ديناميكي - عناوين العميل (بنفس أسلوب جهات العمل)
 */
use yii\helpers\Html;
use yii\helpers\Url;
use wbraganca\dynamicform\DynamicFormWidget;

$resolveLocationUrl = Url::to(['/jobs/resolve-location']);
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
        <div class="addrres-item panel panel-default addr-panel" data-addr-idx="<?= $i ?>" x-data="{ showMap: true }">
            <div class="panel-heading addr-panel-hdr">
                <span class="addr-type-badge"><?= $addr->address_type == 1 ? 'عنوان العمل' : ($addr->address_type == 2 ? 'عنوان السكن' : 'عنوان') ?></span>
                <div class="addr-panel-actions">
                    <button type="button" class="btn btn-xs btn-info addr-toggle-map" @click="showMap = !showMap" :title="showMap ? 'إخفاء الخريطة' : 'إظهار الخريطة'"><i class="fa" :class="showMap ? 'fa-map' : 'fa-map-o'"></i></button>
                    <button type="button" class="addrres-remove-item btn btn-danger btn-xs" title="حذف"><i class="fa fa-trash"></i></button>
                </div>
            </div>
            <div class="panel-body">
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
                        <?= $form->field($addr, "[{$i}]plus_code")->textInput(['placeholder' => 'مثل 8Q6G+4M عمان', 'dir' => 'ltr', 'style' => 'font-family:monospace', 'class' => 'form-control addr-plus-code'])->label('Plus Code') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($addr, "[{$i}]address")->textInput(['placeholder' => 'ملاحظات إضافية (اختياري)', 'class' => 'form-control'])->label('ملاحظات العنوان') ?>
                    </div>
                </div>

                <!-- خريطة -->
                <div class="addr-map-section" x-show="showMap" x-transition.duration.300ms x-cloak
                     x-effect="if (showMap) { $nextTick(() => { var panel = $el.closest('.addrres-item'); if (panel && typeof jQuery !== 'undefined') jQuery(panel).trigger('map:show'); }) }"
                >
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
                                <input type="text" class="form-control addr-map-search" placeholder="ابحث عن موقع..." autocomplete="off">
                                <button type="button" class="addr-map-search-btn" title="بحث"><i class="fa fa-search"></i></button>
                                <div class="addr-map-search-results"></div>
                            </div>
                            <div class="addr-map-container" style="height:300px;border-radius:8px;margin-top:8px"></div>
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

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php if ($googleMapsKey && strpos($googleMapsKey, 'AIza') === 0): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= Html::encode($googleMapsKey) ?>&libraries=places&language=ar&loading=async" async defer></script>
<?php endif; ?>

<?php
$js = <<<JS

/* ═══════════════════════════════════════════════════════════
 *  Customer Address Maps — dynamic per-item map management
 * ═══════════════════════════════════════════════════════════ */
(function(){
    var resolveUrl = '$resolveLocationUrl';
    var defaultLat = 31.95, defaultLng = 35.93;
    var jordanBounds = L.latLngBounds([29.0, 34.8], [33.5, 39.4]);
    var maps = {};

    /* ─── Jordanian postal codes fallback ─── */
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

    function parseLocationInput(raw) {
        var m;
        m = raw.match(/(-?\d+\.?\d*)[,\s]+(-?\d+\.?\d*)/);
        if (m) {
            var a = parseFloat(m[1]), b = parseFloat(m[2]);
            if (!isNaN(a) && !isNaN(b)) {
                if (Math.abs(a) <= 90 && Math.abs(b) <= 180) return {lat: a, lng: b};
                if (Math.abs(b) <= 90 && Math.abs(a) <= 180) return {lat: b, lng: a};
            }
        }
        m = raw.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
        if (m) return {lat: parseFloat(m[1]), lng: parseFloat(m[2])};
        m = raw.match(/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/);
        if (m) return {lat: parseFloat(m[1]), lng: parseFloat(m[2])};
        m = raw.match(/!3d(-?\d+\.\d+).*!4d(-?\d+\.\d+)/);
        if (m) return {lat: parseFloat(m[1]), lng: parseFloat(m[2])};
        var dmsRe = /(\d+)[°](\d+)[′''](\d+\.?\d*)[″""]?\s*([NSns])\s*,?\s*(\d+)[°](\d+)[′''](\d+\.?\d*)[″""]?\s*([EWew])/;
        m = raw.match(dmsRe);
        if (m) {
            var lat2 = parseInt(m[1]) + parseInt(m[2])/60 + parseFloat(m[3])/3600;
            if (m[4].toLowerCase() === 's') lat2 = -lat2;
            var lng2 = parseInt(m[5]) + parseInt(m[6])/60 + parseFloat(m[7])/3600;
            if (m[8].toLowerCase() === 'w') lng2 = -lng2;
            return {lat: lat2, lng: lng2};
        }
        return null;
    }

    /* ─── Get item panel reference ─── */
    function getPanel(el) { return $(el).closest('.addrres-item'); }
    function getPanelId(panel) { return panel.data('addr-idx') || panel.index(); }

    /* ─── Init map for a panel ─── */
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

        var map = L.map(container, {
            maxBounds: jordanBounds.pad(0.5),
            maxBoundsViscosity: 0.3
        }).setView([initLat, initLng], initZoom);

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
        L.control.layers({'خريطة Google': googleStreets, 'قمر صناعي': googleHybrid, 'OpenStreetMap': osmLayer}, null, {position: 'topright'}).addTo(map);

        var entry = { map: map, marker: null, panel: panel };
        maps[pid] = entry;
        $(container).data('leafletMap', map);

        map.on('click', function(e) {
            setMarker(entry, e.latlng.lat, e.latlng.lng, false);
        });

        if (latInput.val() && lngInput.val()) {
            setMarker(entry, initLat, initLng, false);
        }

        setTimeout(function(){ map.invalidateSize(); }, 200);
        return entry;
    }

    /* ─── Set marker and reverse geocode ─── */
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

    /* ─── Reverse geocode ─── */
    function reverseGeocode(entry, lat, lng) {
        var panel = entry.panel;
        var plusCode = encodePlusCode(lat, lng);
        panel.find('.addr-plus-code').val(plusCode);

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

            panel.find('.addr-field, input[name*="address_building"], input[name*="postal_code"]').filter(function(){ return $(this).val(); }).addClass('geo-filled');
            setTimeout(function(){ panel.find('.geo-filled').removeClass('geo-filled'); }, 2500);

            var popup = '<div style="direction:rtl;font-size:12px;line-height:1.6;max-width:240px">';
            var parts = [street, area, city].filter(Boolean);
            popup += '<strong>' + (parts.length ? parts.join('، ') : (data && data.display_name ? data.display_name.split('،').slice(0,3).join('،') : '')) + '</strong>';
            if (postal) popup += '<br><i class="fa fa-envelope-o" style="color:#94a3b8"></i> ' + postal;
            if (plusCode) popup += '<br><span style="color:#4285f4;font-family:monospace;font-size:11px"><i class="fa fa-plus-square"></i> ' + plusCode + '</span>';
            popup += '</div>';
            if (entry.marker) entry.marker.bindPopup(popup).openPopup();
        });
    }

    /* ─── Forward geocode (addr fields → map) ─── */
    function forwardGeocode(entry) {
        var panel = entry.panel;
        var parts = [
            panel.find('[data-addr=street]').val(),
            panel.find('[data-addr=area]').val(),
            panel.find('[data-addr=city]').val()
        ].filter(Boolean);
        if (!parts.length) return;
        $.getJSON('https://nominatim.openstreetmap.org/search', {
            q: parts.join(', '), format: 'json', limit: 1, 'accept-language': 'ar',
            viewbox: '34.8,33.4,39.3,29.1', bounded: 1
        }, function(results) {
            if (results && results.length > 0) {
                setMarker(entry, parseFloat(results[0].lat), parseFloat(results[0].lon), true);
            }
        });
    }

    /* ─── Map search ─── */
    function doMapSearch(entry, q) {
        if (!q || q.length < 2) { entry.panel.find('.addr-map-search-results').removeClass('show').empty(); return; }
        var resEl = entry.panel.find('.addr-map-search-results');
        resEl.html('<div class="map-search-loading"><i class="fa fa-spinner fa-spin"></i> جاري البحث...</div>').addClass('show');
        var c = entry.map.getCenter();
        $.getJSON('https://photon.komoot.io/api/', {
            q: q, lat: defaultLat, lon: defaultLng, limit: 10
        }, function(data) {
            if (!data || !data.features || data.features.length === 0) {
                nominatimFallback(entry, q); return;
            }
            var features = data.features.filter(function(f) {
                var lat = f.geometry.coordinates[1], lng = f.geometry.coordinates[0];
                return lat >= 29.0 && lat <= 33.5 && lng >= 34.8 && lng <= 39.4;
            });
            if (features.length === 0) { nominatimFallback(entry, q); return; }
            var html = '';
            features.forEach(function(f) {
                var p = f.properties, g = f.geometry;
                var name = p.name || p.street || '';
                var addr = [p.city, p.state, p.country].filter(Boolean).join('، ');
                html += '<div class="result-item" data-lat="'+g.coordinates[1]+'" data-lng="'+g.coordinates[0]+'">';
                html += '<span class="result-icon"><i class="fa fa-map-marker"></i></span>';
                html += '<span class="result-text"><span class="result-name">'+name+'</span>';
                if (addr) html += '<span class="result-addr">'+addr+'</span>';
                html += '</span></div>';
            });
            resEl.html(html).addClass('show');
        }).fail(function(){ nominatimFallback(entry, q); });
    }

    function nominatimFallback(entry, q) {
        var resEl = entry.panel.find('.addr-map-search-results');
        $.getJSON('https://nominatim.openstreetmap.org/search', {
            q: q, format: 'json', limit: 6, addressdetails: 1, 'accept-language': 'ar',
            viewbox: '34.8,33.4,39.3,29.1', bounded: 1
        }, function(nd) {
            if (!nd || nd.length === 0) { resEl.html('<div class="map-search-loading">لا توجد نتائج</div>').addClass('show'); return; }
            var html = '';
            nd.forEach(function(r) {
                html += '<div class="result-item" data-lat="'+r.lat+'" data-lng="'+r.lon+'">';
                html += '<span class="result-icon"><i class="fa fa-map-marker"></i></span>';
                html += '<span class="result-text"><span class="result-name">'+r.display_name+'</span></span></div>';
            });
            resEl.html(html).addClass('show');
        });
    }

    /* ─── Event Delegation ─── */

    // Toggle map section — Alpine.js handles visibility via x-show/showMap
    // Re-init map when Alpine triggers map:show event
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

    // Auto-init all maps on page load (only for visible containers)
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
        clearTimeout(_searchTimers[pid]);
        var q = $(this).val().trim();
        if (q.length < 2) { panel.find('.addr-map-search-results').removeClass('show').empty(); return; }
        _searchTimers[pid] = setTimeout(function() {
            var entry = maps[pid]; if (!entry) return;
            doMapSearch(entry, q);
        }, 350);
    });
    $(document).on('keydown', '.addr-map-search', function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            var panel = getPanel(this);
            var pid = getPanelId(panel);
            clearTimeout(_searchTimers[pid]);
            var entry = maps[pid]; if (!entry) return;
            doMapSearch(entry, $(this).val().trim());
        }
    });
    $(document).on('click', '.addr-map-search-btn', function() {
        var panel = getPanel(this);
        var pid = getPanelId(panel);
        var entry = maps[pid]; if (!entry) return;
        doMapSearch(entry, panel.find('.addr-map-search').val().trim());
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
        setTimeout(function(){ panel.find('.addr-map-search-results').removeClass('show'); }, 300);
    });

    // Smart paste
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

        resEl.html('<i class="fa fa-spinner fa-spin"></i> جاري التحليل...').css({background:'#fef3c7',color:'#92400e'}).addClass('show');
        setTimeout(function() {
            $.getJSON(resolveUrl, {q: raw}, function(data) {
                if (data && data.success) {
                    var lat = parseFloat(data.lat), lng = parseFloat(data.lng);
                    var entry = maps[pid] || initMap(panel);
                    if (entry) setMarker(entry, lat, lng, true);
                    var msg = '<i class="fa fa-check-circle"></i> ';
                    msg += data.display_name ? data.display_name + ' (' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ')' : 'تم التعرف على الموقع: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
                    resEl.html(msg).css({background:'#dcfce7',color:'#15803d'}).addClass('show');
                } else {
                    resEl.html('<i class="fa fa-exclamation-circle"></i> لم يتم التعرف على الموقع.').css({background:'#fee2e2',color:'#b91c1c'}).addClass('show');
                }
            }).fail(function() {
                resEl.html('<i class="fa fa-exclamation-circle"></i> خطأ في التحليل.').css({background:'#fee2e2',color:'#b91c1c'}).addClass('show');
            });
        }, 200);
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

    // Forward geocode on addr field change
    var _fwdTimers = {};
    $(document).on('change', '.addr-field', function() {
        var panel = getPanel(this);
        var pid = getPanelId(panel);
        var entry = maps[pid];
        if (!entry) return;
        clearTimeout(_fwdTimers[pid]);
        _fwdTimers[pid] = setTimeout(function(){ forwardGeocode(entry); }, 500);
    });

    // Plus Code → map (resolve when user types/pastes a Plus Code)
    var _plusTimers = {};
    $(document).on('input', '.addr-plus-code', function() {
        var panel = getPanel(this);
        var pid = getPanelId(panel);
        var raw = $(this).val().trim();
        if (!raw || raw.length < 4) return;
        var isPlusCode = /[23456789CFGHJMPQRVWX]{2,}\+/i.test(raw);
        if (!isPlusCode) return;
        clearTimeout(_plusTimers[pid]);
        var _pcInput = $(this);
        _plusTimers[pid] = setTimeout(function() {
            _pcInput.css('border-color', '#fbbf24');
            $.getJSON(resolveUrl, {q: raw}, function(data) {
                if (data && data.success) {
                    var lat = parseFloat(data.lat), lng = parseFloat(data.lng);
                    var entry = maps[pid] || initMap(panel);
                    if (entry) setMarker(entry, lat, lng, true);
                    _pcInput.css('border-color', '#22c55e');
                    setTimeout(function(){ _pcInput.css('border-color', ''); }, 2000);
                } else {
                    _pcInput.css('border-color', '#ef4444');
                    setTimeout(function(){ _pcInput.css('border-color', ''); }, 2000);
                }
            }).fail(function() {
                _pcInput.css('border-color', '#ef4444');
                setTimeout(function(){ _pcInput.css('border-color', ''); }, 2000);
            });
        }, 600);
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

    // Init map for newly added address item
    $('.dynamicform_wrapper').on('afterInsert', function(e, item) {
        var newIdx = 'new-' + Date.now();
        $(item).attr('data-addr-idx', newIdx);
        // Clear any cloned map container content and re-init
        $(item).find('.addr-map-container').empty();
        $(item).find('.addr-lat, .addr-lng, .addr-plus-code').val('');
        $(item).find('.addr-smart-paste').val('');
        $(item).find('.addr-smart-result').removeClass('show');
        setTimeout(function() {
            var entry = initMap($(item));
            if (entry) setTimeout(function(){ entry.map.invalidateSize(); }, 200);
        }, 300);
    });

})();
JS;
$this->registerJs($js);
?>
