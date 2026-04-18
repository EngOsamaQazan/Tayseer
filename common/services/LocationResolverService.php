<?php

namespace common\services;

use Yii;
use yii\base\Component;
use common\models\SystemSettings;

/**
 * LocationResolverService — geographic resolution helpers shared between
 * the legacy Jobs/Customers location pickers and the Customer Wizard V2.
 *
 * Capabilities:
 *   • resolveAny($raw) — accepts ANY of:
 *       - Google Maps short URLs (https://maps.app.goo.gl/…, https://goo.gl/maps/…)
 *       - Full Plus Codes (e.g. "8G3QXW26+XX")
 *       - Short Plus Codes with a city reference (e.g. "22HC+4M الرصيفة")
 *       - Free-text addresses (Nominatim fallback, biased to Jordan)
 *   • searchGooglePlaces($q, $lat, $lng) — Google Places "searchText" with a
 *     locationRestriction rectangle covering Jordan; gracefully returns []
 *     when no API key is configured (caller falls back to Nominatim/Photon).
 *
 * Design rationale (vs. inlining everything in WizardController):
 *   • The same helpers are needed by JobsController (legacy) and the new
 *     WizardController; centralizing them keeps the bug-fix surface single.
 *   • Easier to unit-test in isolation (no controller harness needed).
 *
 * NOTE: Existing callers (JobsController) still inline copies of these
 * helpers for now; we leave those alone to keep this rewrite minimal-blast-
 * radius. Future cleanup task: route them through this service too.
 */
class LocationResolverService extends Component
{
    /** Jordan rough bounding box: [west, south, east, north]. */
    public const JORDAN_BBOX = [34.8, 29.1, 39.3, 33.4];

    public const NOMINATIM_BASE = 'https://nominatim.openstreetmap.org';
    public const GOOGLE_PLACES_TEXT_SEARCH = 'https://places.googleapis.com/v1/places:searchText';

    private const PLUS_CHARSET = '23456789CFGHJMPQRVWX';
    private const HTTP_TIMEOUT_SECONDS = 10;
    private const SHORT_URL_MAX_REDIRECTS = 10;

    /**
     * Unified resolver — see class docblock for input formats.
     *
     * @return array{success:bool, lat?:float, lng?:float, display_name?:string, source?:string}
     */
    public function resolveAny(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['success' => false];
        }

        if (preg_match('#^https?://(maps\.app\.goo\.gl|goo\.gl/maps)/#i', $raw)) {
            $coords = $this->resolveGoogleShortUrl($raw);
            if ($coords) {
                return ['success' => true, 'lat' => $coords['lat'], 'lng' => $coords['lng'], 'source' => 'google_short_url'];
            }
            return ['success' => false];
        }

        if (preg_match('/^[23456789CFGHJMPQRVWX]{8,}\+[23456789CFGHJMPQRVWX]*$/i', $raw)) {
            $coords = $this->decodePlusCode($raw);
            if ($coords) {
                return ['success' => true, 'lat' => $coords['lat'], 'lng' => $coords['lng'], 'source' => 'plus_code'];
            }
        }

        if (preg_match('/^([23456789CFGHJMPQRVWX]{2,6}\+[23456789CFGHJMPQRVWX]{0,3})\s*[,،\s]\s*(.+)$/iu', $raw, $m)) {
            $shortCode = strtoupper(trim($m[1]));
            $cityRef   = trim($m[2]);

            // Preferred path — let Google's Geocoder resolve the whole
            // "<shortCode> <locality>" string. Google's geocoder natively
            // understands short Plus Codes with a locality reference and
            // gets the answer right where Nominatim+recovery often picks
            // the wrong reference cell (e.g. "المفرق" disambiguates to the
            // *governorate* centroid which lives ~140 km east of the city).
            $g = $this->googleGeocode($raw);
            if ($g) {
                return ['success' => true, 'lat' => $g['lat'], 'lng' => $g['lng'], 'source' => 'google_plus_code'];
            }

            // Fallback path — recover the prefix using a Nominatim-resolved
            // locality. We ask Nominatim for several candidates and pick a
            // city/town/village over an admin boundary, because admin
            // centroids are often hundreds of km from the populated place
            // that shares the same name.
            $geo = $this->nominatimGeocode($cityRef, true, /* preferPlace */ true);
            if ($geo) {
                $coords = $this->recoverAndDecodePlusCode($shortCode, $geo['lat'], $geo['lng']);
                if ($coords) {
                    return ['success' => true, 'lat' => $coords['lat'], 'lng' => $coords['lng'], 'source' => 'plus_code_short'];
                }
            }
        }

        $geo = $this->nominatimGeocode($raw, true);
        if ($geo) {
            return [
                'success'      => true,
                'lat'          => $geo['lat'],
                'lng'          => $geo['lng'],
                'display_name' => $geo['display_name'] ?? null,
                'source'       => 'nominatim',
            ];
        }

        return ['success' => false];
    }

    /**
     * Search Google Places (Text Search v1), restricted to the Jordan
     * bounding box. Returns [] when no API key is configured.
     *
     * @return array{results:array, source:string}
     */
    public function searchGooglePlaces(string $q, float $lat = 31.95, float $lng = 35.91): array
    {
        $q = trim($q);
        if ($q === '') {
            return ['results' => [], 'source' => 'none'];
        }

        $apiKey = SystemSettings::get('google_maps', 'api_key', null)
            ?? Yii::$app->params['googleMapsApiKey']
            ?? null;

        if (!$apiKey) {
            return ['results' => [], 'source' => 'none'];
        }

        $bbox = self::JORDAN_BBOX;
        $body = json_encode([
            'textQuery' => $q,
            'locationRestriction' => [
                'rectangle' => [
                    'low'  => ['latitude' => $bbox[1], 'longitude' => $bbox[0]],
                    'high' => ['latitude' => $bbox[3], 'longitude' => $bbox[2]],
                ],
            ],
            'languageCode'   => 'ar',
            'maxResultCount' => 15,
        ]);

        $ch = curl_init(self::GOOGLE_PLACES_TEXT_SEARCH);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Goog-Api-Key: ' . $apiKey,
                // addressComponents lets the client fill city/area/street/postal
                // directly from the user's Google Places choice — no extra
                // Nominatim reverse-geocode round-trip needed.
                'X-Goog-FieldMask: places.displayName,places.formattedAddress,places.location,places.types,places.addressComponents',
            ],
            CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT_SECONDS,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close() is a deprecated no-op since PHP 8.0; relying on
        // the handle going out of scope to release resources.

        if ($httpCode !== 200 || !$response) {
            return ['results' => [], 'source' => 'google_error'];
        }

        $data = json_decode($response, true);
        if (empty($data['places']) || !is_array($data['places'])) {
            return ['results' => [], 'source' => 'google_empty'];
        }

        $results = [];
        foreach ($data['places'] as $p) {
            $pLat = $p['location']['latitude']  ?? null;
            $pLng = $p['location']['longitude'] ?? null;
            if ($pLat === null || $pLng === null) continue;
            $results[] = [
                'name'    => (string)($p['displayName']['text'] ?? ''),
                'addr'    => (string)($p['formattedAddress'] ?? ''),
                'lat'     => (float)$pLat,
                'lng'     => (float)$pLng,
                'types'   => $p['types'] ?? [],
                // Nominatim-shaped {city, suburb, road, house_number,
                // postcode} — consumed by the wizard's fillAddressFields().
                'address' => $this->mapGoogleAddressComponents($p['addressComponents'] ?? []),
            ];
        }

        return ['results' => $results, 'source' => 'google'];
    }

    /**
     * Normalize Google Places `addressComponents` into the same shape that
     * Nominatim returns from `/reverse?addressdetails=1`. This lets the
     * client-side `fillAddressFields()` mapping work for both data sources
     * without a special case for Google.
     *
     * @param  array $components  Google Places `addressComponents` array
     * @return array              Subset of {city, town, village, suburb,
     *                            neighbourhood, road, house_number, postcode}
     */
    private function mapGoogleAddressComponents(array $components): array
    {
        $out = [];
        foreach ($components as $c) {
            $types = $c['types'] ?? [];
            $text  = (string)($c['longText'] ?? $c['shortText'] ?? '');
            if ($text === '' || !is_array($types)) continue;

            // The first match wins — Google sometimes lists several types
            // for one component (e.g. ["sublocality_level_1","sublocality"]).
            if (!isset($out['house_number']) && in_array('street_number', $types, true)) {
                $out['house_number'] = $text;
            }
            if (!isset($out['road']) && in_array('route', $types, true)) {
                $out['road'] = $text;
            }
            if (!isset($out['suburb'])) {
                if (in_array('sublocality_level_1', $types, true)
                    || in_array('sublocality', $types, true)
                    || in_array('neighborhood', $types, true)
                ) {
                    $out['suburb'] = $text;
                }
            }
            if (!isset($out['city'])) {
                if (in_array('locality', $types, true)
                    || in_array('postal_town', $types, true)
                ) {
                    $out['city'] = $text;
                }
            }
            if (!isset($out['postcode']) && in_array('postal_code', $types, true)) {
                $out['postcode'] = $text;
            }
        }
        return $out;
    }

    /* ════════════════════════════════════════════════════════════════
       Internal helpers
       ════════════════════════════════════════════════════════════════ */

    /**
     * Walk a Google Maps short URL through up to N redirects + meta refresh
     * + JS location.href to extract the underlying coordinates. Returns
     * ['lat'=>…, 'lng'=>…] or null.
     */
    public function resolveGoogleShortUrl(string $url): ?array
    {
        $current = $url;

        for ($i = 0; $i < self::SHORT_URL_MAX_REDIRECTS; $i++) {
            $ch = curl_init($current);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HEADER         => true,
                CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT_SECONDS,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER     => [
                    'Accept: text/html,application/xhtml+xml',
                    'Accept-Language: ar,en;q=0.9',
                ],
            ]);
            $response   = curl_exec($ch);
            $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            // No curl_close() — deprecated no-op since PHP 8.0.

            if ($response === false) break;

            $headers = substr($response, 0, $headerSize);
            $body    = substr($response, $headerSize);

            if ($httpCode >= 301 && $httpCode <= 308 && preg_match('/^Location:\s*(.+)$/mi', $headers, $lm)) {
                $current = trim($lm[1]);
                continue;
            }

            $coords = $this->extractCoordsFromText($current);
            if ($coords) return $coords;

            $coords = $this->extractCoordsFromText($body);
            if ($coords) return $coords;

            if (preg_match('/content=["\']?\d+;\s*url=([^"\'>\s]+)/i', $body, $mr)) {
                $current = html_entity_decode($mr[1]);
                continue;
            }
            if (preg_match('/(?:window\.location|location\.href)\s*=\s*["\']([^"\']+)/i', $body, $jr)) {
                $current = html_entity_decode($jr[1]);
                continue;
            }

            break;
        }

        return null;
    }

    /**
     * Extract `lat,lng` from any of Google Maps' URL/page coordinate
     * conventions. Returns null when nothing matches.
     */
    public function extractCoordsFromText(string $text): ?array
    {
        if ($text === '') return null;

        $patterns = [
            '/@(-?\d+\.\d+),(-?\d+\.\d+)/',
            '/!3d(-?\d+\.\d+).*?!4d(-?\d+\.\d+)/',
            '/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/',
            '/center=(-?\d+\.\d+)%2C(-?\d+\.\d+)/',
            '/ll=(-?\d+\.\d+),(-?\d+\.\d+)/',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $text, $m)) {
                return ['lat' => (float)$m[1], 'lng' => (float)$m[2]];
            }
        }
        return null;
    }

    /**
     * Forward geocode via Nominatim. When $boundJordan is true we add the
     * Jordan viewbox and bounded=1 so off-country garbage is filtered out.
     *
     * When $preferPlace is true we ask for several candidates and pick the
     * first populated-place hit (city/town/village/suburb/hamlet) over any
     * administrative boundary. This matters for Plus-Code short-code
     * recovery: a name like "المفرق" can match BOTH the governorate
     * (centroid lives in the desert ~140 km east of the city) and the city
     * itself — using the boundary centroid as the reference point makes
     * the OLC recovery jump to the wrong cell.
     *
     * @return array{lat:float,lng:float,display_name:string}|null
     */
    public function nominatimGeocode(string $query, bool $boundJordan = true, bool $preferPlace = false): ?array
    {
        $bbox = self::JORDAN_BBOX;
        $params = [
            'q'               => $query,
            'format'          => 'json',
            'limit'           => $preferPlace ? 8 : 1,
            'accept-language' => 'ar',
        ];
        if ($boundJordan) {
            $params['viewbox'] = sprintf('%s,%s,%s,%s', $bbox[0], $bbox[3], $bbox[2], $bbox[1]); // left,top,right,bottom
            $params['bounded'] = 1;
        }
        $url = self::NOMINATIM_BASE . '/search?' . http_build_query($params);

        $ctx = stream_context_create(['http' => [
            'header'  => "User-Agent: TayseerApp/2.0 (customer-wizard)\r\n",
            'timeout' => self::HTTP_TIMEOUT_SECONDS,
        ]]);
        $json = @file_get_contents($url, false, $ctx);
        if ($json === false) return null;

        $data = json_decode($json, true);
        if (empty($data) || !isset($data[0])) return null;

        $pick = $data[0];
        if ($preferPlace) {
            // Score candidates so populated places beat admin boundaries.
            // Within places we rank city > town > village > suburb > hamlet
            // (the typical reference for Plus-Code locality recovery).
            $rank = ['city' => 5, 'town' => 4, 'village' => 3, 'suburb' => 2, 'hamlet' => 1];
            $best = null; $bestScore = -1;
            foreach ($data as $row) {
                $cls = (string)($row['class'] ?? '');
                $typ = (string)($row['type']  ?? '');
                $score = ($cls === 'place' && isset($rank[$typ])) ? $rank[$typ] : 0;
                if ($score > $bestScore) { $best = $row; $bestScore = $score; }
            }
            if ($best && $bestScore > 0) $pick = $best;
        }

        return [
            'lat'          => (float)$pick['lat'],
            'lng'          => (float)$pick['lon'],
            'display_name' => (string)($pick['display_name'] ?? ''),
        ];
    }

    /**
     * Forward geocode via Google's Geocoding API. Returns null when no API
     * key is configured or Google has nothing useful — caller is expected
     * to fall back to Nominatim.
     *
     * Why we call Google specifically for Plus-Code recovery:
     *   Google's geocoder natively understands BOTH full Plus Codes and
     *   short codes with a locality reference (e.g. "85PX+MG المفرق") and
     *   resolves them to the exact same point Google Maps would.
     *   Recreating that locally requires an accurate locality centroid +
     *   a working OLC recoverNearest implementation — Nominatim's
     *   centroids are often miles off (governorate vs. city, etc.).
     *
     * @return array{lat:float,lng:float,display_name:string}|null
     */
    public function googleGeocode(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') return null;

        $apiKey = SystemSettings::get('google_maps', 'api_key', null)
            ?? Yii::$app->params['googleMapsApiKey']
            ?? null;
        if (!$apiKey) return null;

        $bbox = self::JORDAN_BBOX; // [west, south, east, north]
        $url  = 'https://maps.googleapis.com/maps/api/geocode/json?'
              . http_build_query([
                    'address'  => $query,
                    'language' => 'ar',
                    'region'   => 'jo',
                    // Bias (not restrict) to Jordan. Strict restriction
                    // would reject valid Plus-Code resolutions that
                    // briefly land outside the rectangle while we
                    // disambiguate them.
                    'bounds'   => sprintf('%s,%s|%s,%s', $bbox[1], $bbox[0], $bbox[3], $bbox[2]),
                    'key'      => $apiKey,
                ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT_SECONDS,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close() is a deprecated no-op since PHP 8.0.

        if ($httpCode !== 200 || !$response) return null;
        $data = json_decode($response, true);
        if (empty($data['results'][0]['geometry']['location'])) return null;

        // Filter out non-Jordan hits — Google can return e.g. a same-named
        // place in another country if the bias is weak. We drop anything
        // outside our bbox so the marker doesn't jump abroad.
        foreach ($data['results'] as $r) {
            $lat = (float)($r['geometry']['location']['lat'] ?? 0);
            $lng = (float)($r['geometry']['location']['lng'] ?? 0);
            if ($lng < $bbox[0] || $lng > $bbox[2] || $lat < $bbox[1] || $lat > $bbox[3]) continue;
            return [
                'lat'          => $lat,
                'lng'          => $lng,
                'display_name' => (string)($r['formatted_address'] ?? ''),
            ];
        }
        return null;
    }

    /**
     * Decode an Open Location Code (Plus Code) into lat/lng.
     * Operates on the *full* form (e.g. "8G3QXW26+XX"); use
     * recoverAndDecodePlusCode() for short codes with a reference point.
     */
    public function decodePlusCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        $code = str_replace('+', '', $code);
        if (strlen($code) < 2) return null;

        for ($i = 0; $i < strlen($code); $i++) {
            if (strpos(self::PLUS_CHARSET, $code[$i]) === false) return null;
        }

        $lat = 0.0;
        $lng = 0.0;
        $res = [20.0, 1.0, 0.05, 0.0025, 0.000125];
        $numPairs = min(5, intdiv(strlen($code), 2));

        for ($i = 0; $i < $numPairs; $i++) {
            $lat += strpos(self::PLUS_CHARSET, $code[$i * 2])     * $res[$i];
            $lng += strpos(self::PLUS_CHARSET, $code[$i * 2 + 1]) * $res[$i];
        }

        $latRes = $res[$numPairs - 1];
        $lngRes = $latRes;

        for ($i = 10; $i < strlen($code); $i++) {
            $latRes /= 5;
            $lngRes /= 4;
            $v = strpos(self::PLUS_CHARSET, $code[$i]);
            $lat += intdiv($v, 4) * $latRes;
            $lng += ($v % 4)      * $lngRes;
        }

        return [
            'lat' => round($lat - 90  + $latRes / 2, 8),
            'lng' => round($lng - 180 + $lngRes / 2, 8),
        ];
    }

    /**
     * Recover the prefix of a *short* Plus Code from a reference lat/lng,
     * then decode the resulting full code. Tries the four nearest "padding"
     * candidates and picks the one closest to the reference point.
     */
    public function recoverAndDecodePlusCode(string $shortCode, float $refLat, float $refLng): ?array
    {
        $short = strtoupper(trim($shortCode));
        $sep = strpos($short, '+');
        if ($sep === false || $sep >= 8) return null;

        $paddingLen = 8 - $sep;
        $pairs = intdiv($paddingLen, 2);
        $res   = [20.0, 1.0, 0.05, 0.0025, 0.000125];

        $aLat = $refLat + 90;
        $aLng = $refLng + 180;

        $prefix = '';
        $tLat = $aLat; $tLng = $aLng;
        for ($i = 0; $i < $pairs; $i++) {
            $ld = min(19, (int)floor($tLat / $res[$i]));
            $gd = min(19, (int)floor($tLng / $res[$i]));
            $prefix .= self::PLUS_CHARSET[$ld] . self::PLUS_CHARSET[$gd];
            $tLat -= $ld * $res[$i];
            $tLng -= $gd * $res[$i];
        }

        $body = str_replace('+', '', $short);
        $full = $prefix . $body;
        $fullCode = substr($full, 0, 8) . '+' . substr($full, 8);

        $decoded = $this->decodePlusCode($fullCode);
        if (!$decoded) return null;

        $best     = $decoded;
        $bestDist = pow($decoded['lat'] - $refLat, 2) + pow($decoded['lng'] - $refLng, 2);

        $bigRes = $res[min(4, $pairs - 1)] * 20;
        foreach ([[-1,0],[1,0],[0,-1],[0,1]] as $offset) {
            $nLat = $aLat + $offset[0] * $bigRes;
            $nLng = $aLng + $offset[1] * $bigRes;
            if ($nLat < 0 || $nLat >= 180 || $nLng < 0 || $nLng >= 360) continue;

            $np = ''; $t1 = $nLat; $t2 = $nLng;
            for ($i = 0; $i < $pairs; $i++) {
                $ld2 = min(19, (int)floor($t1 / $res[$i]));
                $gd2 = min(19, (int)floor($t2 / $res[$i]));
                $np .= self::PLUS_CHARSET[$ld2] . self::PLUS_CHARSET[$gd2];
                $t1 -= $ld2 * $res[$i];
                $t2 -= $gd2 * $res[$i];
            }

            $nf = $np . $body;
            $nfCode = substr($nf, 0, 8) . '+' . substr($nf, 8);
            $nd = $this->decodePlusCode($nfCode);
            if ($nd) {
                $dist = pow($nd['lat'] - $refLat, 2) + pow($nd['lng'] - $refLng, 2);
                if ($dist < $bestDist) {
                    $best     = $nd;
                    $bestDist = $dist;
                }
            }
        }

        return $best;
    }
}
