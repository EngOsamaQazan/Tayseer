/* ============================================================
   Customer Wizard V2 — Address Map widget
   -----------------------------------------------------------
   Replacement for the legacy address-map module from the old
   wizard, with the SAME functional capabilities (geographic
   biasing, multi-source search, server-side smart-paste,
   forward/reverse geocoding, Google Places autocomplete) but
   adapted to the lean V2 markup contract — no visual rewrite.

   Capabilities (parity with old /customers/create):
     • Geographic biasing — every search is biased to Jordan
       (viewbox + countrycodes=jo for Nominatim, locationBias
       rectangle for Google Places, dedicated server endpoint
       for Google Places "searchText" with locationRestriction).
     • Multi-source search in PARALLEL: server-proxied Google
       Places + Nominatim + Photon, deduped (lat/lng rounded
       to 4 decimals) and sorted by proximity to map center.
       Jordan-only results shown first; falls back to global if
       none match.
     • Smart-paste resolver: tries client-side parsing first
       (decimal coords, Google Maps URL patterns, DMS coords
       with smart quotes), and only escalates to the server
       (`/customers/wizard/resolve-location`) for things that
       need server help — short Google URLs, Plus Codes (full
       and short with a city reference), or free-text addresses.
     • Forward geocoding — when the user edits city/area/street
       manually, we re-locate the marker via Nominatim (bounded
       to Jordan).
     • Reverse geocoding — clicking/dragging the map fills the
       wizard's city/area/street/building/postal_code fields,
       backed by a Jordanian postal-code fallback table for
       neighborhoods Nominatim doesn't know.
     • Google Places Autocomplete (Element API + legacy fallback)
       attached when the page loads with a Maps API key (script
       tag injected from `layout.php`); biased to the Jordan
       rectangle. When Google isn't available the widget remains
       fully functional via the multi-source search above.
     • "Use my location" geolocation button.
     • Plus Code (Open Location Code) computed client-side and
       echoed in both the marker popup and the widget footer.

   Markup contract (rendered by `_step_3_guarantors.php`):
     <div data-cw-addr-map data-cw-addr-map-target=".cw-addr-fields-root">
       <div data-cw-addr-map-canvas></div>
       <input data-cw-addr-search>
       <ul    data-cw-addr-results></ul>
       <input data-cw-addr-paste>
       <button data-cw-addr-geolocate></button>
       <button data-cw-addr-clear></button>
       <span  data-cw-addr-coord></span>
       <span  data-cw-addr-plus-out></span>
       <input type="hidden" data-cw-addr-lat>
       <input type="hidden" data-cw-addr-lng>
       <input type="hidden" data-cw-addr-plus>
     </div>

   Field-binding contract: descendants of `data-cw-addr-map-target`
   matching `[data-addr-fill="city|area|street|building|postal"]`
   receive auto-filled values from reverse geocoding. We deliberately
   never overwrite a non-empty user input — only empty fields get
   populated.
   ============================================================ */
(function ($) {
    'use strict';

    /* ── Constants ── */
    var DEFAULT_CENTER = { lat: 31.95, lng: 35.91, zoom: 8 }; // Amman
    var INSTANCE_KEY   = 'cwAddrMapInstance';
    var NOMINATIM      = 'https://nominatim.openstreetmap.org';
    var PHOTON         = 'https://photon.komoot.io/api/';
    // Jordan rough bounding box. Nominatim viewbox uses (left,top,right,bottom)
    // where top has a higher latitude than bottom, so this string is in
    // (W, N, E, S) order.
    var JORDAN_VIEWBOX = '34.8,33.4,39.3,29.1';
    var JORDAN_BOUNDS  = { west: 34.8, south: 29.1, east: 39.3, north: 33.4 };
    var GP_RETRY_INTERVAL_MS = 800;
    var GP_RETRY_TIMEOUT_MS  = 12000;

    /* ════════════════════════════════════════════════════════════════
       Helpers (pure)
       ════════════════════════════════════════════════════════════════ */

    /* Plus Code (Open Location Code) encoder — matches the server-side
       implementation in LocationResolverService::decodePlusCode. */
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

    /* Smart parser for the "paste a location" input — handles the cheap
       cases (anything that doesn't need a server round-trip). Returns
       {lat, lng} or null; null means "ask the server". */
    function parseLocationInput(raw) {
        if (!raw) return null;
        var s = (raw + '').trim();

        // 1. Decimal coords ("31.95, 35.91" or "31.95 35.91").
        var m = s.match(/(-?\d+\.\d+|-?\d+)\s*[,\s]\s*(-?\d+\.\d+|-?\d+)/);
        if (m) {
            var a = parseFloat(m[1]), b = parseFloat(m[2]);
            if (!isNaN(a) && !isNaN(b)) {
                if (Math.abs(a) <= 90 && Math.abs(b) <= 180) return { lat: a, lng: b };
                if (Math.abs(b) <= 90 && Math.abs(a) <= 180) return { lat: b, lng: a };
            }
        }

        // 2. Google Maps URL patterns we can decode without a HEAD request.
        m = s.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);                if (m) return { lat: +m[1], lng: +m[2] };
        m = s.match(/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/);           if (m) return { lat: +m[1], lng: +m[2] };
        m = s.match(/!3d(-?\d+\.\d+).*?!4d(-?\d+\.\d+)/);         if (m) return { lat: +m[1], lng: +m[2] };
        m = s.match(/center=(-?\d+\.\d+)%2C(-?\d+\.\d+)/);        if (m) return { lat: +m[1], lng: +m[2] };
        m = s.match(/[?&]ll=(-?\d+\.\d+),(-?\d+\.\d+)/);          if (m) return { lat: +m[1], lng: +m[2] };

        // 3. DMS coords. Accept ASCII quotes (', ") and the smart Unicode
        //    variants (′, ″, ’, ”) that copy/paste from typographic sites.
        var dms = /(\d+)[°\s]+(\d+)[′''\u2019\s]+([\d.]+)[″""\u201d]?\s*([NSns])\s*[,،\s]\s*(\d+)[°\s]+(\d+)[′''\u2019\s]+([\d.]+)[″""\u201d]?\s*([EWew])/;
        m = s.match(dms);
        if (m) {
            var lat = +m[1] + +m[2] / 60 + +m[3] / 3600;  if (m[4].toLowerCase() === 's') lat = -lat;
            var lng = +m[5] + +m[6] / 60 + +m[7] / 3600;  if (m[8].toLowerCase() === 'w') lng = -lng;
            return { lat: lat, lng: lng };
        }

        return null;
    }

    /* Great-circle distance (km) — used to sort search results by
       proximity to the current map center. */
    function distKm(lat1, lng1, lat2, lng2) {
        var R = 6371;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLng = (lng2 - lng1) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
              + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
              * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }
    function distLabel(km) {
        return km < 1 ? Math.round(km * 1000) + ' م' : km.toFixed(1) + ' كم';
    }
    function isInJordan(lat, lng) {
        return lat >= JORDAN_BOUNDS.south && lat <= JORDAN_BOUNDS.north
            && lng >= JORDAN_BOUNDS.west  && lng <= JORDAN_BOUNDS.east;
    }
    /* Map a Nominatim/Google place "type" string to a Font Awesome icon. */
    function placeTypeIcon(types) {
        if (!types) return 'fa-map-marker';
        var t = (typeof types === 'string') ? types : types.join(',');
        if (/restaurant|cafe|food/.test(t))                       return 'fa-cutlery';
        if (/hospital|health|pharmacy|doctor|clinic/.test(t))     return 'fa-medkit';
        if (/school|university|college/.test(t))                  return 'fa-graduation-cap';
        if (/store|shop|mall|supermarket/.test(t))                return 'fa-shopping-cart';
        if (/bank|finance|atm/.test(t))                           return 'fa-university';
        if (/lodging|hotel/.test(t))                              return 'fa-bed';
        if (/gas_station|fuel/.test(t))                           return 'fa-car';
        if (/mosque|church|worship|synagogue/.test(t))            return 'fa-moon-o';
        if (/company|establishment|office/.test(t))               return 'fa-building';
        if (/route|road|highway/.test(t))                         return 'fa-road';
        if (/place/.test(t))                                      return 'fa-map-pin';
        return 'fa-map-marker';
    }

    /* Pick a short, human-friendly name out of a Nominatim feature. */
    function shortName(r) {
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
    function shortAddr(r) {
        if (!r || !r.address) return r.display_name || '';
        var a = r.address;
        return [a.road, a.suburb || a.neighbourhood, a.city || a.town || a.village, a.country]
            .filter(Boolean).join('، ');
    }

    /* ── Jordanian postal-codes fallback table — used only when Nominatim
       doesn't return a postcode for the reverse-geocoded coordinate.
       Compiled from Jordan Post + verified samples. Keys are normalized
       Arabic (no diacritics, ا/أ/إ/آ unified, ة→ه, ى→ي). ── */
    var JO_POSTAL = {
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
    function normalizeAr(s) {
        return (s + '')
            .replace(/[\u0640\u064B-\u065F]/g, '')
            .replace(/[أإآ]/g, 'ا')
            .replace(/ة/g, 'ه')
            .replace(/ى/g, 'ي')
            .trim();
    }
    function lookupPostal(city, area) {
        if (!city && !area) return '';
        var probes = [area, city].filter(Boolean);
        for (var p = 0; p < probes.length; p++) {
            var n = normalizeAr(probes[p]);
            for (var k in JO_POSTAL) if (normalizeAr(k) === n) return JO_POSTAL[k];
        }
        // Fuzzy contains-match for the city probe — handles "عمان الغربية" → "عمان".
        if (city) {
            var nc = normalizeAr(city);
            for (var k2 in JO_POSTAL) {
                var nk = normalizeAr(k2);
                if (nc.indexOf(nk) !== -1 || nk.indexOf(nc) !== -1) return JO_POSTAL[k2];
            }
        }
        return '';
    }

    /* Read a wizard URL configured in `layout.php`'s urls map. */
    function cwUrl(key) {
        return (window.CW && CW._urls && CW._urls[key]) || '';
    }

    /* ════════════════════════════════════════════════════════════════
       Field auto-fill — owned-by-user vs. owned-by-map.
       ────────────────────────────────────────────────────────────────
       Tracking model: a field carries `data-cw-user-touched="true"` once
       the user has typed something into it (the input handler — wired at
       init time — sets/clears this flag). User-touched fields are NEVER
       overwritten by location-derived data. Anything else (initial draft
       value, prior auto-fill) is considered map-owned and is freely
       refreshed when the location changes.

       Crucially: when a new location source has NO value for a key, we
       clear the existing (map-owned) value instead of leaving stale data
       behind — that's the user's "ما يعبي عشوائي" requirement.

       opts.includeBuilding (default true) — set to false for
       reverse-geocoding because Nominatim's `house_number` for an
       arbitrary clicked coordinate is whatever the closest road segment
       got tagged with in OSM, not the building the user actually picked.
       Building is only filled when we have a SPECIFIC place pick (Google
       Places autocomplete or search-result click) where `street_number`
       reflects the chosen establishment.
       ════════════════════════════════════════════════════════════════ */
    function fillAddressFields($targetRoot, addr, opts) {
        if (!$targetRoot || !$targetRoot.length || !addr) return;
        opts = opts || {};
        var includeBuilding = opts.includeBuilding !== false;
        // Map-triggered fills (clicks, marker drag, Plus-Code paste, search
        // pick, Google Places select) must overwrite EVERYTHING the source
        // returned — that's how the legacy wizard worked, and field officers
        // explicitly preferred it (they want a fresh refill on every map
        // action; manual edits stick only until the next map action).
        var force           = !!opts.force;

        var city   = addr.city || addr.town || addr.village || addr.county || addr.state || '';
        // Nominatim's "area"-shaped fields vary wildly by region — some
        // Jordanian governorates only tag `city_district`, others use
        // `residential`, etc. Walk a wider fallback chain so we surface
        // whichever the source actually populated.
        var area   = addr.suburb         || addr.neighbourhood || addr.quarter
                  || addr.city_district  || addr.district      || addr.borough
                  || addr.residential    || addr.allotments    || addr.hamlet
                  || addr.locality       || '';
        var street = addr.road   || addr.pedestrian    || addr.footway || '';

        // Validate the source postcode before trusting it. Google's
        // geocoder occasionally returns the country dialing code
        // ("00962" / "962") in `postal_code` for points that have no
        // real Jordanian post code — that ends up displayed as the
        // postcode, which confuses staff. Jordanian postal codes are
        // strictly 5 digits in the 11000-77999 band.
        var rawPostal = (addr.postcode || '') + '';
        var srcPostal = /^\d{5}$/.test(rawPostal) ? rawPostal : '';

        var mapping = {
            city:   city,
            area:   area,
            street: street,
            // Nominatim is unreliable on Jordanian postcodes — fall back
            // to our local table when the source provided neither (or
            // the source provided junk like "00962").
            postal: srcPostal || lookupPostal(city, area),
        };
        if (includeBuilding) {
            mapping.building = addr.house_number || '';
        }

        Object.keys(mapping).forEach(function (key) {
            var $f = $targetRoot.find('[data-addr-fill="' + key + '"]').first();
            if (!$f.length) return;
            // Respect manual edits ONLY for non-forced fills (e.g. soft
            // enrichment passes). Map actions always pass force:true.
            if (!force && $f.attr('data-cw-user-touched') === 'true') return;

            var newVal  = mapping[key] || '';
            var current = $.trim($f.val());
            if (newVal === current) return;

            if (newVal) {
                $f.val(newVal).addClass('cw-addr-flash').trigger('change');
                // After a successful map-triggered overwrite, clear the
                // user-touched flag — a fresh map action is the new source
                // of truth and subsequent edits start counting again.
                if (force) $f.removeAttr('data-cw-user-touched');
                setTimeout(function () { $f.removeClass('cw-addr-flash'); }, 1700);
            } else if (current && force) {
                // Forced fill, no value from source — clear the stale entry
                // so we never display a postcode/building from a previous
                // location that doesn't apply here. (Non-forced soft passes
                // leave existing values alone.)
                $f.val('').trigger('change');
            }
        });
    }

    /* Convert Google Places `addressComponents` (both modern Places API v1
       — { longText, shortText, types } — and the legacy Autocomplete
       — { long_name, short_name, types }) into the same Nominatim-shaped
       address object that fillAddressFields() consumes. Mirrors
       LocationResolverService::mapGoogleAddressComponents on the server. */
    function googleComponentsToAddress(components) {
        if (!components || !components.length) return null;
        var out = {};
        for (var i = 0; i < components.length; i++) {
            var c = components[i] || {};
            var text = c.longText || c.long_name || c.shortText || c.short_name || '';
            var types = c.types || [];
            if (!text || !types.length) continue;
            function has(t) { return types.indexOf(t) !== -1; }
            if (!out.house_number && has('street_number'))                                  out.house_number = text;
            if (!out.road         && has('route'))                                          out.road = text;
            if (!out.suburb       && (has('sublocality_level_1') || has('sublocality_level_2')
                                      || has('sublocality')      || has('neighborhood')
                                      || has('administrative_area_level_3')))               out.suburb = text;
            if (!out.city         && (has('locality') || has('postal_town')))               out.city = text;
            if (!out.postcode     && has('postal_code'))                                    out.postcode = text;
        }
        return out;
    }

    /* ════════════════════════════════════════════════════════════════
       Initialize one widget instance.
       ════════════════════════════════════════════════════════════════ */
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

        // The visible (read-only) Plus Code mirror lives inside the address
        // grid for at-a-glance visibility — kept in sync from persist().
        var $plusDisplay = $fieldsRoot.find('[data-cw-addr-plus-display]').first();

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

        // Drop Leaflet's default "🇺🇦 Leaflet | …" prefix — the Ukrainian
        // flag was added upstream as a solidarity gesture, but it confuses
        // Tayseer users who think it's part of their government UI.
        // We still show "© Google Maps" (the tile-license attribution).
        if (map.attributionControl) {
            map.attributionControl.setPrefix(false);
        }

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
        var googleAutocompleteActive = false;

        /* ─── Marker + value persistence ─── */
        function persist(lat, lng) {
            $latInput.val(lat.toFixed(8));
            $lngInput.val(lng.toFixed(8));
            var plus = encodePlusCode(lat, lng);
            $plusInput.val(plus);
            if ($coordOut.length)    $coordOut.text(lat.toFixed(5) + ', ' + lng.toFixed(5));
            if ($plusOut.length)     $plusOut.text(plus);
            // Mirror the freshly-encoded code into the visible Plus Code
            // field — it's a derived value from the marker, not user
            // input we need to protect — UNLESS the user is actively
            // typing into it (because that field doubles as a search
            // box: clobbering it mid-keystroke would feel hostile).
            if ($plusDisplay.length && !$plusDisplay.is(':focus')) {
                $plusDisplay.val(plus).addClass('cw-addr-flash');
                setTimeout(function () { $plusDisplay.removeClass('cw-addr-flash'); }, 1700);
            }
            return plus;
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
            var plus = persist(lat, lng);
            // Brief popup so the user can verify the coordinates Plus Code
            // they just selected — auto-close when the next click happens.
            marker.bindPopup(
                '<div style="direction:rtl;font-size:12px;line-height:1.6;font-family:inherit">' +
                    '<strong>الموقع المحدّد</strong><br>' +
                    '<span style="font-family:monospace">' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</span><br>' +
                    '<span style="color:#4285f4;font-family:monospace">+' + plus + '</span>' +
                '</div>'
            );
            if (opts.fly !== false) map.flyTo([lat, lng], 17);
            if (opts.geocode !== false) reverseGeocode(lat, lng);
        }

        if (hasInit) setMarker(initLat, initLng, { fly: false, geocode: false });

        map.on('click', function (e) { setMarker(e.latlng.lat, e.latlng.lng); });

        /* ════════════════════════════════════════════════════════════
           Reverse geocoding — coordinates → address fields
           ════════════════════════════════════════════════════════════ */

        /* Google reverse-geocode fallback. We hit it only when Nominatim
           came back without a suburb/neighbourhood (Jordan coverage gap).
           The server normalizes Google's components into Nominatim's
           shape so fillAddressFields() doesn't need to special-case it. */
        function googleReverseFill(lat, lng) {
            var url = (CW && CW._urls && CW._urls.reverseGeocode) || '';
            if (!url) return;
            $.getJSON(url, { lat: lat, lng: lng }).done(function (resp) {
                if (resp && resp.ok && resp.address) {
                    fillAddressFields($fieldsRoot, resp.address, { force: true });
                }
            });
        }

        var rgTimer = null;
        function reverseGeocode(lat, lng) {
            clearTimeout(rgTimer);
            rgTimer = setTimeout(function () {
                $.getJSON(NOMINATIM + '/reverse', {
                    lat: lat, lon: lng, format: 'jsonv2', addressdetails: 1,
                    'accept-language': 'ar', zoom: 18
                }).done(function (data) {
                    if (data && data.address) {
                        // Mirror the legacy wizard exactly — every map
                        // action overwrites all reverse-geocoded fields
                        // (city/area/street/building/postal). Field staff
                        // explicitly preferred this: a fresh click means
                        // "use what the map says, drop my previous typing".
                        fillAddressFields($fieldsRoot, data.address, { force: true });

                        // Nominatim's coverage of Jordanian sub-localities
                        // is patchy — when no area-shaped field came back,
                        // ask Google. Its `address_components` almost
                        // always carries a usable suburb/neighbourhood.
                        var hasArea = !!(data.address.suburb || data.address.neighbourhood
                            || data.address.quarter        || data.address.city_district
                            || data.address.district       || data.address.borough
                            || data.address.residential    || data.address.allotments
                            || data.address.hamlet         || data.address.locality);
                        if (!hasArea) {
                            googleReverseFill(lat, lng);
                        }

                        if (marker && marker.getPopup()) {
                            // Enrich the popup with the resolved street/area.
                            var a = data.address;
                            var parts = [a.road, a.suburb || a.neighbourhood, a.city || a.town].filter(Boolean);
                            if (parts.length) {
                                marker.bindPopup(
                                    '<div style="direction:rtl;font-size:12px;line-height:1.6;font-family:inherit;max-width:240px">' +
                                        '<strong>' + parts.join('، ') + '</strong><br>' +
                                        '<span style="font-family:monospace">' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</span><br>' +
                                        '<span style="color:#4285f4;font-family:monospace">+' + encodePlusCode(lat, lng) + '</span>' +
                                    '</div>'
                                );
                            }
                        }
                    } else {
                        // Nominatim returned nothing usable — try Google
                        // directly so the user still gets autofill.
                        googleReverseFill(lat, lng);
                    }
                }).fail(function () {
                    // Network/Nominatim failure — fall back to Google.
                    googleReverseFill(lat, lng);
                });
            }, 350);
        }

        /* ════════════════════════════════════════════════════════════
           Forward geocoding (search) — multi-source, Jordan-biased
           ════════════════════════════════════════════════════════════ */

        /* Dedupe by lat/lng rounded to 4 decimals (~11 m). Different
           sources return slightly different coordinates for the same
           point of interest. */
        function dedup(items) {
            var seen = {};
            return items.filter(function (it) {
                var key = it.lat.toFixed(4) + ',' + it.lng.toFixed(4);
                if (seen[key]) return false;
                seen[key] = true;
                return true;
            });
        }

        function sortAndFilter(items) {
            var c = map.getCenter();
            var cLat = c.lat, cLng = c.lng;
            var unique = dedup(items);
            var inJo = unique.filter(function (it) { return isInJordan(it.lat, it.lng); });
            var pool = inJo.length ? inJo : unique;
            pool.sort(function (a, b) {
                return distKm(cLat, cLng, a.lat, a.lng) - distKm(cLat, cLng, b.lat, b.lng);
            });
            return pool.slice(0, 12);
        }

        function renderResults(items) {
            $resultsBox.empty();
            if (!items || !items.length) {
                $resultsBox.html('<li class="cw-addr-map__result" aria-disabled="true" style="color:#94a3b8">لا توجد نتائج.</li>');
                return;
            }
            var c = map.getCenter();
            items.forEach(function (r) {
                var d = distKm(c.lat, c.lng, r.lat, r.lng);
                var iconClass = placeTypeIcon(r.types);
                var srcBadge = '';
                if (r.src === 'google') srcBadge = ' <span class="cw-addr-map__src cw-addr-map__src--google" title="Google Places">G</span>';
                else if (r.src === 'photon') srcBadge = ' <span class="cw-addr-map__src cw-addr-map__src--photon" title="Photon">P</span>';

                var $li = $('<li class="cw-addr-map__result" tabindex="0" role="option"></li>');
                var $row = $('<span class="cw-addr-map__result-row"></span>');
                $row.append('<i class="fa ' + iconClass + ' cw-addr-map__result-icon" aria-hidden="true"></i>');

                var $body = $('<span class="cw-addr-map__result-body"></span>');
                var $name = $('<span class="cw-addr-map__result-name"></span>').text(r.name || r.addr || '');
                if (srcBadge) $name.append(srcBadge);
                $body.append($name);
                if (r.addr) {
                    $body.append('<span class="cw-addr-map__result-meta">' +
                        $('<div>').text(r.addr).html() +
                        ' · <span class="cw-addr-map__result-dist">' + distLabel(d) + '</span></span>');
                } else {
                    $body.append('<span class="cw-addr-map__result-meta"><span class="cw-addr-map__result-dist">' + distLabel(d) + '</span></span>');
                }
                $row.append($body);
                $li.append($row);

                $li.on('click keydown', function (e) {
                    if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
                    e.preventDefault();
                    var lat = parseFloat(r.lat), lng = parseFloat(r.lng);
                    if (!isFinite(lat) || !isFinite(lng)) return;
                    // If the result already carries a structured address
                    // (Google + Nominatim search results both do), use it
                    // directly and skip the reverse-geocode round-trip —
                    // otherwise let setMarker() trigger reverseGeocode as a
                    // fallback for sources like Photon that don't.
                    // Always trigger Nominatim reverseGeocode (default
                    // setMarker behaviour) — legacy wizard parity. Google's
                    // address_components are often less detailed than
                    // Nominatim for Jordanian sub-localities (no suburb,
                    // wrong postcode, etc.), so we ignore r.address even
                    // when present and let Nominatim be the source of truth.
                    setMarker(lat, lng);
                    $resultsBox.empty();
                    $searchInput.val(r.name || r.addr || '');
                });
                $resultsBox.append($li);
            });
        }

        /* Multi-source parallel search: server-proxied Google Places +
           Nominatim (Jordan-biased) + Photon. Render incrementally as
           each source returns so the user sees something within ~500ms
           even if Photon stalls. */
        var sTimer = null;
        function runSearch(q) {
            if (!q || q.length < 3) { $resultsBox.empty(); return; }
            clearTimeout(sTimer);
            sTimer = setTimeout(function () {
                $resultsBox.html('<li class="cw-addr-map__result" aria-disabled="true">' +
                    '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> جارٍ البحث في الأردن…</li>');

                var c = map.getCenter();
                var bLat = c.lat, bLng = c.lng;
                var pending = 3;
                var bag = [];
                var earlyRendered = false;

                function done() {
                    pending--;
                    if (pending > 0) {
                        if (bag.length && !earlyRendered) {
                            renderResults(sortAndFilter(bag));
                            earlyRendered = true;
                        }
                        return;
                    }
                    var final = sortAndFilter(bag);
                    if (final.length) renderResults(final);
                    else {
                        // No hits anywhere — offer a graceful Google Maps escape hatch.
                        var gUrl = 'https://www.google.com/maps/search/' + encodeURIComponent(q + ' الأردن');
                        $resultsBox.html(
                            '<li class="cw-addr-map__result" aria-disabled="true" style="color:#94a3b8">' +
                                '<i class="fa fa-search" aria-hidden="true"></i> لم يتم العثور على نتائج.' +
                            '</li>' +
                            '<li class="cw-addr-map__result">' +
                                '<a href="' + gUrl + '" target="_blank" rel="noopener" style="color:#4285f4;font-weight:600;text-decoration:none">' +
                                    '<i class="fa fa-external-link" aria-hidden="true"></i> ابحث في خرائط جوجل ثم الصق الرابط أعلاه' +
                                '</a>' +
                            '</li>'
                        );
                    }
                }

                /* (1) Google Places via the server — silent no-op when no API key. */
                var spUrl = cwUrl('searchPlaces');
                if (spUrl) {
                    $.getJSON(spUrl, { q: q, lat: bLat, lng: bLng })
                        .done(function (data) {
                            (data && data.results || []).forEach(function (r) {
                                bag.push({
                                    lat: r.lat, lng: r.lng,
                                    name: r.name, addr: r.addr,
                                    types: r.types, src: 'google'
                                });
                            });
                        })
                        .always(done);
                } else {
                    done();
                }

                /* (2) Nominatim — Jordan viewbox + countrycodes=jo, but
                       NOT bounded so cross-border hits still surface for
                       border towns; sortAndFilter() prefers JO results. */
                $.getJSON(NOMINATIM + '/search', {
                    q: q, format: 'jsonv2', addressdetails: 1, limit: 15,
                    'accept-language': 'ar',
                    viewbox: JORDAN_VIEWBOX, bounded: 0, countrycodes: 'jo'
                })
                    .done(function (results) {
                        (results || []).forEach(function (r) {
                            bag.push({
                                lat: parseFloat(r.lat), lng: parseFloat(r.lon),
                                name: shortName(r), addr: shortAddr(r),
                                address: r.address, src: 'nominatim'
                            });
                        });
                    })
                    .always(done);

                /* (3) Photon — biased to Jordan via lat/lon and ' الأردن'
                       suffix. Photon doesn't support countrycodes, so this
                       is a soft-bias (sortAndFilter() does the hard one). */
                $.getJSON(PHOTON, {
                    q: q + ' الأردن', lat: bLat, lon: bLng, limit: 15, lang: 'default'
                })
                    .done(function (data) {
                        ((data && data.features) || []).forEach(function (f) {
                            var p = f.properties || {}, g = f.geometry || {};
                            if (!g.coordinates) return;
                            bag.push({
                                lat: g.coordinates[1], lng: g.coordinates[0],
                                name: p.name || p.street || p.city || '',
                                addr: [p.city, p.state, p.country].filter(Boolean).join('، '),
                                src: 'photon'
                            });
                        });
                    })
                    .always(done);
            }, 250);
        }

        /* ════════════════════════════════════════════════════════════
           Smart-paste — server fallback for things we can't decode
           client-side (short URLs, Plus Codes, free-text addresses).
           ════════════════════════════════════════════════════════════ */
        function smartPaste(raw, $source) {
            raw = $.trim(raw || '');
            if (!raw) return;

            // The "source" is whichever field the user typed/pasted into.
            // Defaults to the dedicated paste box, but the Plus Code field
            // calls in here too — error/loading affordances must follow
            // the actual input so the user knows what was rejected.
            var $src = ($source && $source.length) ? $source : $pasteInput;

            var flagInvalid = function () {
                $src.addClass('cw-input--phone-invalid');
                setTimeout(function () { $src.removeClass('cw-input--phone-invalid'); }, 1800);
            };

            // Fast path: cheap client-side decode (decimal/DMS/Maps URL).
            var local = parseLocationInput(raw);
            if (local) {
                setMarker(local.lat, local.lng);
                // Only the throw-away paste field gets cleared on success;
                // the Plus Code mirror is repopulated by persist() with
                // the canonical value derived from the resolved marker.
                if ($src.is($pasteInput)) $src.val('');
                return;
            }

            // Slow path: ask the server. Covers Google short URLs, Plus
            // Codes (full + short with city ref), and Nominatim text.
            var url = cwUrl('resolveLocation');
            if (!url) { flagInvalid(); return; }

            $src.prop('disabled', true).addClass('cw-addr-paste--loading');
            $.getJSON(url, { q: raw })
                .done(function (data) {
                    if (data && data.success) {
                        setMarker(parseFloat(data.lat), parseFloat(data.lng));
                        if ($src.is($pasteInput)) $src.val('');
                    } else {
                        flagInvalid();
                    }
                })
                .fail(flagInvalid)
                .always(function () {
                    $src.prop('disabled', false).removeClass('cw-addr-paste--loading');
                });
        }

        /* ════════════════════════════════════════════════════════════
           Forward geocoding from the address fields → marker
           ════════════════════════════════════════════════════════════
           Only the user's MANUALLY typed addresses re-locate the marker.
           Autofilled values came from the map itself, so re-geocoding them
           would just bounce around (autofill → change → forward geocode
           → setMarker → reverseGeocode → autofill → …). */
        var fwdTimer = null;
        $fieldsRoot.on('change', '[data-addr-fill="city"], [data-addr-fill="area"], [data-addr-fill="street"]', function () {
            if ($(this).attr('data-cw-user-touched') !== 'true') return;
            clearTimeout(fwdTimer);
            fwdTimer = setTimeout(function () {
                var parts = [
                    $fieldsRoot.find('[data-addr-fill="street"]').val(),
                    $fieldsRoot.find('[data-addr-fill="area"]').val(),
                    $fieldsRoot.find('[data-addr-fill="city"]').val()
                ].filter(Boolean);
                if (!parts.length) return;
                $.getJSON(NOMINATIM + '/search', {
                    q: parts.join(', '), format: 'jsonv2', limit: 1,
                    'accept-language': 'ar',
                    viewbox: JORDAN_VIEWBOX, bounded: 1, countrycodes: 'jo'
                }).done(function (results) {
                    if (results && results.length) {
                        var r = results[0];
                        // setMarker w/ geocode:false because we're already
                        // inside a "user typed an address" flow — no need
                        // to bounce the values back through reverseGeocode.
                        setMarker(parseFloat(r.lat), parseFloat(r.lon), { geocode: false });
                    }
                });
            }, 500);
        });

        /* Track user ownership: any keystroke claims the field, an empty
           value releases it back to the map for future auto-fills. */
        $fieldsRoot.on('input', '[data-addr-fill]', function () {
            var $f = $(this);
            if ($.trim($f.val())) {
                $f.attr('data-cw-user-touched', 'true');
            } else {
                $f.removeAttr('data-cw-user-touched');
            }
        });

        /* ════════════════════════════════════════════════════════════
           Plus Code search — the visible Plus Code field doubles as a
           search box. Pressing Enter (or blurring) routes the value
           through smartPaste, which already understands full + short
           Plus Codes server-side.
           ════════════════════════════════════════════════════════════ */
        if ($plusDisplay.length) {
            // Enter triggers a search and prevents accidental form submit.
            $plusDisplay.on('keydown', function (e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    var v = $.trim($plusDisplay.val());
                    if (v && v !== $plusInput.val()) {
                        smartPaste(v, $plusDisplay);
                    }
                    $plusDisplay.blur();
                }
            });
            // Blur catches the "type code, then click the map" workflow.
            // We compare against the hidden $plusInput so a no-op blur
            // (user clicked away without changing anything) doesn't fire
            // a useless network request.
            $plusDisplay.on('change', function () {
                var v = $.trim($plusDisplay.val());
                if (v && v !== $plusInput.val()) {
                    smartPaste(v, $plusDisplay);
                }
            });
        }

        /* ════════════════════════════════════════════════════════════
           Google Places Autocomplete (optional — needs API key)
           ════════════════════════════════════════════════════════════
           Tries the modern PlaceAutocompleteElement first, falls back to
           the legacy Autocomplete widget on the search input. Both are
           biased to the Jordan rectangle. We poll for `window.google`
           because the Maps script is loaded with `loading=async`. */
        function tryAttachGoogleAutocomplete() {
            if (googleAutocompleteActive) return true;
            if (typeof google === 'undefined' || !google.maps || !google.maps.places) return false;

            // Modern PlaceAutocompleteElement (2024+). If the browser
            // doesn't ship it (old Chrome on enterprise machines), fall
            // back to the legacy Autocomplete on the existing input.
            if (google.maps.places.PlaceAutocompleteElement) {
                try {
                    var pac = new google.maps.places.PlaceAutocompleteElement({
                        locationBias: {
                            north: JORDAN_BOUNDS.north, south: JORDAN_BOUNDS.south,
                            east:  JORDAN_BOUNDS.east,  west:  JORDAN_BOUNDS.west
                        }
                    });
                    pac.id = 'cw-gmp-' + Math.random().toString(36).slice(2, 8);
                    pac.setAttribute('placeholder', $searchInput.attr('placeholder') || 'ابحث بالاسم');
                    // Inherit the wizard's input styling so the swap is invisible.
                    pac.style.cssText = 'width:100%;display:block';

                    $searchInput.hide().after(pac);
                    $resultsBox.empty();

                    pac.addEventListener('gmp-select', function (e) {
                        var place = e.placePrediction.toPlace();
                        // Don't fetch addressComponents — legacy wizard
                        // parity. We always let Nominatim reverseGeocode
                        // populate the address grid because Google's
                        // components are often less detailed than Nominatim
                        // for Jordanian sub-localities (no suburb, wrong
                        // postcode like "00962", side-street routing).
                        place.fetchFields({
                            fields: ['displayName', 'formattedAddress', 'location']
                        }).then(function () {
                            if (!place.location) return;
                            setMarker(place.location.lat(), place.location.lng(), { fly: true });
                        });
                    });

                    googleAutocompleteActive = true;
                    return true;
                } catch (e) {
                    // Fall through to legacy.
                }
            }

            if (google.maps.places.Autocomplete && $searchInput.length) {
                // Same legacy-parity rule as the modern PlaceAutocompleteElement
                // path above: we don't ask for address_components — Nominatim
                // reverse-geocode (triggered by setMarker's default) is the
                // single source of truth for the grid fields.
                var ac = new google.maps.places.Autocomplete($searchInput[0], {
                    fields: ['geometry', 'name', 'formatted_address']
                });
                ac.setBounds(new google.maps.LatLngBounds(
                    new google.maps.LatLng(JORDAN_BOUNDS.south, JORDAN_BOUNDS.west),
                    new google.maps.LatLng(JORDAN_BOUNDS.north, JORDAN_BOUNDS.east)
                ));
                ac.addListener('place_changed', function () {
                    var place = ac.getPlace();
                    if (!place || !place.geometry) return;
                    setMarker(place.geometry.location.lat(), place.geometry.location.lng(), { fly: true });
                    $searchInput.val(place.name || place.formatted_address || '');
                    $resultsBox.empty();
                });
                googleAutocompleteActive = true;
                return true;
            }

            return false;
        }
        if (!tryAttachGoogleAutocomplete()) {
            var gpRetry = setInterval(function () {
                if (tryAttachGoogleAutocomplete()) clearInterval(gpRetry);
            }, GP_RETRY_INTERVAL_MS);
            setTimeout(function () { clearInterval(gpRetry); }, GP_RETRY_TIMEOUT_MS);
        }

        /* ════════════════════════════════════════════════════════════
           Wire UI events
           ════════════════════════════════════════════════════════════ */

        // The Google Places element supersedes our search input; only
        // run the multi-source search when Google isn't active.
        $searchInput.on('input', function () {
            if (googleAutocompleteActive) return;
            runSearch($(this).val());
        });
        $searchInput.on('keydown', function (e) {
            if (e.key === 'Enter' && !googleAutocompleteActive) {
                e.preventDefault();
                runSearch($(this).val());
            }
        });

        $pasteInput.on('change paste', function () {
            var $el = $(this);
            // The native `paste` event fires BEFORE the value is updated.
            setTimeout(function () { smartPaste($el.val()); }, 10);
        });

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

        $clearBtn.on('click', function (e) {
            e.preventDefault();
            if (marker) { map.removeLayer(marker); marker = null; }
            $latInput.val(''); $lngInput.val(''); $plusInput.val('');
            if ($coordOut.length)    $coordOut.text('—');
            if ($plusOut.length)     $plusOut.text('—');
            if ($plusDisplay.length) $plusDisplay.val('');
            $resultsBox.empty();
            $pasteInput.val('');

            // The user's mental model is "Clear = start over", so we
            // also wipe the visible address grid fields (city, area,
            // street, building, postal) plus any free-form notes. The
            // `data-cw-user-touched` flag is dropped so a follow-up
            // location selection can repopulate the slots automatically
            // — without it the auto-fill would treat the fields as
            // sacred and refuse to overwrite the (now-empty) values.
            $fieldsRoot.find('[data-addr-fill]').each(function () {
                $(this).val('').removeAttr('data-cw-user-touched').trigger('change');
            });
            // Notes input doesn't carry data-addr-fill (it's free-form
            // and never auto-filled by reverse-geocoding), but the
            // visual reset wouldn't feel complete without it.
            $fieldsRoot.find('input[name$="[address]"]').val('');
        });

        // Force Leaflet to recalculate dimensions once the panel becomes
        // visible (it might have been display:none before).
        setTimeout(function () { map.invalidateSize(); }, 250);

        $widget.data(INSTANCE_KEY, { map: map });
    }

    /* ── Auto-init on DOM ready + on step change. ──
     *   Widgets sitting inside a CLOSED <details data-cw-addr-collapsible>
     *   are skipped — Leaflet can't measure tile dimensions inside a
     *   display:none container, so we defer init until the user expands
     *   the block (handled by the `toggle` listener further below). */
    function enhanceAll() {
        $('[data-cw-addr-map]').each(function () {
            var $details = $(this).closest('details[data-cw-addr-collapsible]');
            if ($details.length && !$details.prop('open')) return;
            init(this);
        });
    }

    /* Build the "city · area · 31.95, 35.91" summary shown in the
     * collapsed header. Mirrors the PHP-side first paint so toggling
     * never makes the chip flicker between two different formats. */
    function refreshChip($block) {
        var $chip = $block.find('[data-cw-addr-chip]').first();
        if (!$chip.length) return;

        var $root = $block.find('[data-cw-addr-fields-root]').first();
        var city  = $.trim(($root.find('[data-addr-fill="city"]').val() || '') + '');
        var area  = $.trim(($root.find('[data-addr-fill="area"]').val() || '') + '');
        var lat   = $.trim(($block.find('[data-cw-addr-lat]').val() || '') + '');
        var lng   = $.trim(($block.find('[data-cw-addr-lng]').val() || '') + '');

        var parts = [];
        if (city) parts.push(city);
        if (area) parts.push(area);
        if (lat && lng && isFinite(+lat) && isFinite(+lng)) {
            parts.push((+lat).toFixed(2) + ', ' + (+lng).toFixed(2));
        }

        if (parts.length) {
            $chip.text(parts.join(' · ')).removeClass('cw-fieldset__chip--empty');
        } else {
            $chip.text('غير مُعبَّأ').addClass('cw-fieldset__chip--empty');
        }
    }

    /* Native <details> "toggle" handler:
     *   • On open  — late-init the contained widget (if it wasn't already)
     *                and force Leaflet to recompute tile sizes after the
     *                browser has painted the now-visible container.
     *   • On close — refresh the summary chip so the next render reflects
     *                whatever the user just typed.
     *
     *   IMPORTANT: the native `toggle` event does NOT bubble, so we can't
     *   use jQuery's delegated $(document).on('toggle', …). We have to
     *   listen during the capture phase instead — that way one document-
     *   level handler still catches every <details> on the page without
     *   needing per-element wiring (matters when the wizard re-renders a
     *   step). */
    document.addEventListener('toggle', function (ev) {
        var el = ev.target;
        if (!el || el.tagName !== 'DETAILS') return;
        if (!el.hasAttribute('data-cw-addr-collapsible')) return;

        var $d = $(el);
        if ($d.prop('open')) {
            $d.find('[data-cw-addr-map]').each(function () {
                var widget = this;
                init(widget);
                // Two RAFs ensure the <details> body is fully laid out
                // before Leaflet asks for the canvas dimensions.
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        var inst = $(widget).data(INSTANCE_KEY);
                        if (inst && inst.map) inst.map.invalidateSize();
                    });
                });
            });
        } else {
            refreshChip($d);
        }
    }, true);

    /* Keep the chip live-accurate while the block is OPEN too — without
     * this, a user who fills city/area then closes the block via the
     * chevron would see a stale chip flash for one frame before the
     * "toggle" handler catches up. */
    $(document).on('input change',
        'details[data-cw-addr-collapsible] [data-addr-fill],' +
        'details[data-cw-addr-collapsible] [data-cw-addr-lat],' +
        'details[data-cw-addr-collapsible] [data-cw-addr-lng]',
        function () {
            var $d = $(this).closest('details[data-cw-addr-collapsible]');
            if ($d.length) refreshChip($d);
        }
    );

    /* ── Address-type dropdown → live-update the summary title + icon ──
     *   Mirrors the legacy wizard's "type badge" behaviour. The user can
     *   re-classify a block at any time and the collapsed header reflects
     *   the choice immediately, so they can tell at a glance which slot
     *   holds which kind of address.
     *
     *   Vocabulary stays in lock-step with `_step_3_address_block.php`. */
    var ADDR_TYPE_LABELS = { 1: 'عنوان العمل', 2: 'عنوان السكن' };
    var ADDR_TYPE_ICONS  = { 1: 'fa-briefcase', 2: 'fa-home' };

    $(document).on('change', '[data-cw-addr-type]', function () {
        var $sel   = $(this);
        var $block = $sel.closest('details[data-cw-addr-collapsible]');
        if (!$block.length) return;

        var v     = parseInt($sel.val(), 10);
        var label = ADDR_TYPE_LABELS[v] || 'عنوان';
        var icon  = ADDR_TYPE_ICONS[v]  || 'fa-map-marker';

        $block.find('[data-cw-addr-title-text]').first().text(label);
        // Replace ALL fa-* modifiers atomically (the icon element only
        // ever carries `fa <type-icon>` so a hard reset is safe).
        $block.find('[data-cw-addr-title-icon]').first().attr('class', 'fa ' + icon);
    });

    $(function () { enhanceAll(); });
    $(document).on('cw:step:changed', function () {
        // Defer so the section is fully visible — Leaflet measures tile
        // sizes synchronously and gets confused on display:none.
        setTimeout(enhanceAll, 50);
    });

    // Public API for diagnostics / future hooks.
    window.CWAddrMap = { enhance: enhanceAll, refreshChip: refreshChip };

})(jQuery);
