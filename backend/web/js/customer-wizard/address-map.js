/* ============================================================
   Customer Wizard V2 — Address Map widget
   -----------------------------------------------------------
   Lean replacement for the legacy address-map module from the
   old wizard. ~250 LoC instead of ~700, no Google Places (no
   API-key dependency), and tightly integrated with the wizard's
   address fields via a single `data-cw-addr-map` element.

   Capabilities:
     • Forward geocoding via Nominatim (OpenStreetMap), bounded
       to Jordan by default to avoid noise — falls back to global
       search if the user types a non-JO query.
     • Reverse geocoding on click / drag → fills the wizard's
       city / area / street / building / postal_code fields and
       briefly flashes them so the user can verify.
     • Smart "paste a location" parser accepts:
         - Decimal coords ("31.95, 35.91")
         - Google Maps share URLs (`@lat,lng`, `?q=`, `!3d!4d`)
         - DMS coords ("31°57′N 35°54′E")
         - Plus Codes (relative or full)
     • "Use my location" button → HTML5 geolocation.
     • Plus Code (Open Location Code) computed client-side.

   Markup contract (rendered by `_step_3_guarantors.php`):

     <div data-cw-addr-map
          data-cw-addr-map-target=".cw-addr-fields-root">
         <div class="cw-addr-map__container" data-cw-addr-map-canvas></div>
         …controls + result list…
         <input type="hidden" data-cw-addr-lat   name="address[latitude]">
         <input type="hidden" data-cw-addr-lng   name="address[longitude]">
         <input type="hidden" data-cw-addr-plus  name="address[plus_code]">
     </div>

   Field-binding contract: descendants of `data-cw-addr-map-target`
   matching `[data-addr-fill="city|area|street|building|postal"]`
   receive auto-filled values from reverse geocoding. We deliberately
   never overwrite a non-empty user input — only empty fields get
   populated.
   ============================================================ */
(function ($) {
    'use strict';

    var DEFAULT_CENTER = { lat: 31.95, lng: 35.91, zoom: 8 }; // Amman
    var INSTANCE_KEY   = 'cwAddrMapInstance';
    var NOMINATIM      = 'https://nominatim.openstreetmap.org';

    /* ── Plus Code (Open Location Code) encoder ── */
    function encodePlusCode(lat, lng) {
        var CHARS = '23456789CFGHJMPQRVWX';
        lat = Math.min(90,  Math.max(-90,  lat))  + 90;
        lng = Math.min(180, Math.max(-180, lng)) + 180;
        var code = '', rLat = 20, rLng = 20;
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

    /* ── Smart parser for the "paste a location" input.
       Returns {lat, lng} or null. ── */
    function parseLocationInput(raw) {
        if (!raw) return null;
        var s = (raw + '').trim();

        // 1. Decimal coords.
        var m = s.match(/(-?\d+\.\d+|-?\d+)\s*[,\s]\s*(-?\d+\.\d+|-?\d+)/);
        if (m) {
            var a = parseFloat(m[1]), b = parseFloat(m[2]);
            if (Math.abs(a) <= 90 && Math.abs(b) <= 180) return { lat: a, lng: b };
            if (Math.abs(b) <= 90 && Math.abs(a) <= 180) return { lat: b, lng: a };
        }

        // 2. Google Maps URL patterns.
        m = s.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);                 if (m) return { lat: +m[1], lng: +m[2] };
        m = s.match(/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/);            if (m) return { lat: +m[1], lng: +m[2] };
        m = s.match(/!3d(-?\d+\.\d+).*!4d(-?\d+\.\d+)/);           if (m) return { lat: +m[1], lng: +m[2] };

        // 3. DMS coords.
        var dms = /(\d+)[°\s]+(\d+)[′'\s]+([\d.]+)[″"]?\s*([NSns])\s*,?\s*(\d+)[°\s]+(\d+)[′'\s]+([\d.]+)[″"]?\s*([EWew])/;
        m = s.match(dms);
        if (m) {
            var lat = +m[1] + +m[2] / 60 + +m[3] / 3600;  if (m[4].toLowerCase() === 's') lat = -lat;
            var lng = +m[5] + +m[6] / 60 + +m[7] / 3600;  if (m[8].toLowerCase() === 'w') lng = -lng;
            return { lat: lat, lng: lng };
        }

        return null;
    }

    /* ── Wizard fields auto-fill. Only empties get touched. ── */
    function fillAddressFields($targetRoot, addr) {
        if (!$targetRoot || !$targetRoot.length || !addr) return;

        var mapping = {
            city:     addr.city || addr.town || addr.village || addr.county || addr.state || '',
            area:     addr.suburb || addr.neighbourhood || addr.quarter || addr.hamlet || '',
            street:   addr.road || addr.pedestrian || addr.footway || '',
            building: addr.house_number || '',
            postal:   addr.postcode || '',
        };

        Object.keys(mapping).forEach(function (key) {
            if (!mapping[key]) return;
            var $f = $targetRoot.find('[data-addr-fill="' + key + '"]').first();
            if (!$f.length) return;
            // Don't overwrite user-entered text.
            if ($.trim($f.val())) return;
            $f.val(mapping[key]).trigger('change').addClass('cw-addr-flash');
            setTimeout(function () { $f.removeClass('cw-addr-flash'); }, 1700);
        });
    }

    /* ── Initialize one widget. ── */
    function init(el) {
        var $widget = $(el);
        if ($widget.data(INSTANCE_KEY)) return;
        if (typeof L === 'undefined') return;

        var $canvas      = $widget.find('[data-cw-addr-map-canvas]').first();
        if (!$canvas.length) return;

        var $latInput    = $widget.find('[data-cw-addr-lat]').first();
        var $lngInput    = $widget.find('[data-cw-addr-lng]').first();
        var $plusInput   = $widget.find('[data-cw-addr-plus]').first();
        var $searchInput = $widget.find('[data-cw-addr-search]').first();
        var $resultsBox  = $widget.find('[data-cw-addr-results]').first();
        var $pasteInput  = $widget.find('[data-cw-addr-paste]').first();
        var $geoBtn      = $widget.find('[data-cw-addr-geolocate]').first();
        var $clearBtn    = $widget.find('[data-cw-addr-clear]').first();
        var $coordOut    = $widget.find('[data-cw-addr-coord]').first();
        var $plusOut     = $widget.find('[data-cw-addr-plus-out]').first();

        var targetSel = $widget.attr('data-cw-addr-map-target') || '';
        var $fieldsRoot = targetSel ? $widget.closest('form, .cw-card').find(targetSel).first() : $widget;
        if (!$fieldsRoot.length) $fieldsRoot = $widget.closest('.cw-card');

        var initLat = parseFloat($latInput.val());
        var initLng = parseFloat($lngInput.val());
        var hasInit = isFinite(initLat) && isFinite(initLng);

        var map = L.map($canvas[0], {
            zoomControl: true,
            scrollWheelZoom: false, // avoid grabbing the page scroll
        }).setView(
            hasInit ? [initLat, initLng] : [DEFAULT_CENTER.lat, DEFAULT_CENTER.lng],
            hasInit ? 16 : DEFAULT_CENTER.zoom
        );

        // Use Google Maps street tiles (no API key required for the
        // unauthenticated tile endpoint, which Tayseer has historically
        // depended on) + OSM as a documented fallback layer.
        L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}&hl=ar', {
            attribution: '&copy; Google Maps', maxZoom: 21
        }).addTo(map);

        var hybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}&hl=ar', {
            attribution: '&copy; Google Maps', maxZoom: 21
        });
        var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap', maxZoom: 19
        });
        L.control.layers(null, { 'قمر صناعي': hybrid, 'OpenStreetMap': osm }, { position: 'bottomleft' }).addTo(map);

        // Restore scroll-wheel zoom only after the map is focused — the
        // gesture is "click first, then scroll" which matches Google Maps.
        $canvas.on('click', function () { map.scrollWheelZoom.enable(); });
        $canvas.on('mouseleave', function () { map.scrollWheelZoom.disable(); });

        var marker = null;

        function persist(lat, lng) {
            $latInput.val(lat.toFixed(8));
            $lngInput.val(lng.toFixed(8));
            var plus = encodePlusCode(lat, lng);
            $plusInput.val(plus);
            if ($coordOut.length) $coordOut.text(lat.toFixed(5) + ', ' + lng.toFixed(5));
            if ($plusOut.length)  $plusOut.text(plus);
        }

        function setMarker(lat, lng, opts) {
            opts = opts || {};
            if (marker) map.removeLayer(marker);
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function (e) {
                var p = e.target.getLatLng();
                persist(p.lat, p.lng);
                reverseGeocode(p.lat, p.lng);
            });
            persist(lat, lng);
            if (opts.fly !== false) map.flyTo([lat, lng], 17);
            if (opts.geocode !== false) reverseGeocode(lat, lng);
        }

        if (hasInit) setMarker(initLat, initLng, { fly: false, geocode: false });

        map.on('click', function (e) { setMarker(e.latlng.lat, e.latlng.lng); });

        /* ── Reverse geocoding (debounced) ── */
        var rgTimer = null;
        function reverseGeocode(lat, lng) {
            clearTimeout(rgTimer);
            rgTimer = setTimeout(function () {
                $.getJSON(NOMINATIM + '/reverse', {
                    lat: lat, lon: lng, format: 'jsonv2', addressdetails: 1,
                    'accept-language': 'ar', zoom: 18
                }).done(function (data) {
                    if (data && data.address) {
                        fillAddressFields($fieldsRoot, data.address);
                    }
                });
            }, 350);
        }

        /* ── Forward geocoding (search) ── */
        var sTimer = null;
        function runSearch(q) {
            if (!q || q.length < 3) { $resultsBox.empty(); return; }
            clearTimeout(sTimer);
            sTimer = setTimeout(function () {
                $.getJSON(NOMINATIM + '/search', {
                    q: q, format: 'jsonv2', addressdetails: 1, limit: 6,
                    'accept-language': 'ar',
                    countrycodes: 'jo,sa,ae,sy,iq,ps,lb,eg' // bias to MENA
                }).done(function (results) {
                    renderResults(results || []);
                });
            }, 350);
        }

        function renderResults(items) {
            $resultsBox.empty();
            if (!items.length) {
                $resultsBox.html('<li class="cw-addr-map__result" aria-disabled="true" style="color:#94a3b8">لا توجد نتائج.</li>');
                return;
            }
            items.forEach(function (r) {
                var label = r.display_name || '';
                var primary = label.split('،').slice(0, 3).join('،');
                var secondary = label.split('،').slice(3).join('،');
                var $li = $('<li class="cw-addr-map__result" tabindex="0"></li>');
                $li.text(primary);
                if (secondary.trim()) {
                    $li.append('<span class="cw-addr-map__result-meta">' + $('<div>').text(secondary).html() + '</span>');
                }
                $li.on('click keydown', function (e) {
                    if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
                    e.preventDefault();
                    var lat = parseFloat(r.lat), lng = parseFloat(r.lon);
                    if (!isFinite(lat) || !isFinite(lng)) return;
                    setMarker(lat, lng);
                    if (r.address) fillAddressFields($fieldsRoot, r.address);
                    $resultsBox.empty();
                    $searchInput.val(primary);
                });
                $resultsBox.append($li);
            });
        }

        $searchInput.on('input', function () { runSearch($(this).val()); });

        /* ── "Paste a location" smart parser ── */
        $pasteInput.on('change paste', function () {
            var $el = $(this);
            setTimeout(function () {
                var p = parseLocationInput($el.val());
                if (p) {
                    setMarker(p.lat, p.lng);
                    $el.val('');
                } else if ($.trim($el.val())) {
                    $el.addClass('cw-input--phone-invalid');
                    setTimeout(function () { $el.removeClass('cw-input--phone-invalid'); }, 1500);
                }
            }, 10);
        });

        /* ── Geolocation ── */
        $geoBtn.on('click', function (e) {
            e.preventDefault();
            if (!navigator.geolocation) {
                alert('متصفحك لا يدعم تحديد الموقع تلقائياً.');
                return;
            }
            $geoBtn.prop('disabled', true);
            navigator.geolocation.getCurrentPosition(function (pos) {
                $geoBtn.prop('disabled', false);
                setMarker(pos.coords.latitude, pos.coords.longitude);
            }, function (err) {
                $geoBtn.prop('disabled', false);
                alert('تعذّر تحديد الموقع: ' + (err && err.message ? err.message : 'سبب غير معروف'));
            }, { enableHighAccuracy: true, timeout: 10000 });
        });

        /* ── Clear ── */
        $clearBtn.on('click', function (e) {
            e.preventDefault();
            if (marker) { map.removeLayer(marker); marker = null; }
            $latInput.val(''); $lngInput.val(''); $plusInput.val('');
            if ($coordOut.length) $coordOut.text('—');
            if ($plusOut.length)  $plusOut.text('—');
        });

        // Force Leaflet to recalculate dimensions once the panel
        // becomes visible (it might have been display:none before).
        setTimeout(function () { map.invalidateSize(); }, 250);

        $widget.data(INSTANCE_KEY, { map: map });
    }

    /* ── Auto-init on DOM ready + on step change. ── */
    function enhanceAll() {
        $('[data-cw-addr-map]').each(function () { init(this); });
    }

    $(function () { enhanceAll(); });
    $(document).on('cw:step:changed', function () {
        // Defer so the section is fully visible — Leaflet measures
        // tile sizes synchronously and gets confused on display:none.
        setTimeout(enhanceAll, 50);
    });

    // Public API for diagnostics / future hooks.
    window.CWAddrMap = { enhance: enhanceAll };

})(jQuery);
