<?php
/**
 * Smoke test: validate FahrasVerdict DTO + FahrasService input handling
 * without booting a full Yii application. Run from repo root:
 *
 *     php common/tests/smoke_fahras.php
 *
 * Exit code 0 = all assertions passed, 1 = at least one failure.
 */

declare(strict_types=1);

// Minimal autoloader for our two source files (no composer needed).
require __DIR__ . '/../services/dto/FahrasVerdict.php';

// Minimal Yii stub so FahrasService::check() doesn't crash when calling
// Yii::$app->cache. The service short-circuits when has('cache') is false.
if (!class_exists('Yii')) {
    class _StubApp { public function has(string $k): bool { return false; } public function get(string $k) { return null; } }
    class Yii { public static $app; public static function warning(...$a) {} public static function info(...$a) {} public static function error(...$a) {} }
    Yii::$app = new _StubApp();
}

// Stub yii\base\Component & InvalidConfigException — FahrasService extends them.
if (!class_exists(\yii\base\Component::class)) {
    require __DIR__ . '/_yii_stubs.php';
}
require __DIR__ . '/../services/FahrasService.php';

use common\services\dto\FahrasVerdict;
use common\services\FahrasService;

$pass = 0; $fail = 0;
function ok(string $name, bool $cond) {
    global $pass, $fail;
    if ($cond) { echo "  ✓ $name\n"; $pass++; }
    else       { echo "  ✗ $name\n"; $fail++; }
}

echo "── FahrasVerdict DTO ─────────────────────────────────\n";
$v = FahrasVerdict::noRecord();
ok('no_record verdict', $v->verdict === 'no_record');
ok('no_record allows', !$v->blocks('closed'));

$v = FahrasVerdict::fromArray(['verdict' => 'cannot_sell']);
ok('cannot_sell blocks under closed', $v->blocks('closed'));
ok('cannot_sell blocks under open',   $v->blocks('open'));

$v = FahrasVerdict::fromArray(['verdict' => 'contact_first']);
ok('contact_first warns',     $v->warns());
ok('contact_first not block', !$v->blocks('closed'));

$v = FahrasVerdict::failure('boom');
ok('error blocks under closed', $v->blocks('closed'));
ok('error open does not block', !$v->blocks('open'));

$v = FahrasVerdict::fromApi('something_weird', 'X', 'X');
ok('unknown verdict normalises to error', $v->verdict === 'error');

echo "\n── FahrasService (offline) ───────────────────────────\n";
$svc = new FahrasService([
    'enabled'      => true,
    'baseUrl'      => 'https://fahras.test',
    'requireHttps' => true,
    'bypassCache'  => true,
]);
ok('service constructed',          $svc instanceof FahrasService);
ok('failure policy normalised',    $svc->failurePolicy === 'closed');

$svc2 = new FahrasService(['enabled' => false]);
$verdict = $svc2->check('1234567890', 'محمد أحمد');
ok('disabled service returns no_record', $verdict->verdict === 'no_record');

// The remaining checks exercise the input-cleaning path (cleanText →
// mb_substr) which requires the mbstring extension. Skip gracefully
// when it isn't available (typical of stripped-down PHP CLI builds).
if (function_exists('mb_substr')) {
    $verdict = $svc->check('', '');
    ok('empty input → error', $verdict->verdict === 'error');

    $verdict = $svc->check('abc', null);
    ok('non-numeric id → error', $verdict->verdict === 'error');

    $r = $svc->searchByName('ab');
    ok('short query rejected', $r['ok'] === false && $r['error'] === 'short_query');
} else {
    echo "  ↷ skipped 3 input-cleaning checks (mbstring not loaded in this CLI)\n";
}

// Reject http:// in production mode.
try {
    new FahrasService([
        'enabled'      => true,
        'baseUrl'      => 'http://insecure.test',
        'requireHttps' => true,
    ]);
    ok('https-only enforced', false);
} catch (\yii\base\InvalidConfigException $e) {
    ok('https-only enforced', true);
}

// Allow http://localhost
try {
    new FahrasService(['enabled' => true, 'baseUrl' => 'http://localhost:8080']);
    ok('localhost http allowed', true);
} catch (\Throwable $e) {
    ok('localhost http allowed', false);
}

echo "\n──────────────────────────────────────────────────────\n";
echo "Pass: $pass   Fail: $fail\n";
exit($fail > 0 ? 1 : 0);
