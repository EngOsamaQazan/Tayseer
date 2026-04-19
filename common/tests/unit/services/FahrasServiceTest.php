<?php

namespace common\tests\unit\services;

use Codeception\Test\Unit;
use common\services\dto\FahrasVerdict;
use common\services\FahrasService;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Unit tests for {@see FahrasService}.
 *
 * The service issues real HTTP calls in production. The tests below stay
 * within the boundary of the *local* logic: input validation, the
 * "disabled → noRecord" short-circuit, the HTTPS guard in init(), and
 * the deterministic shape of failure responses when an idNumber/name is
 * missing. The HTTP plumbing itself is covered by integration tests
 * against the staging Fahras instance — out of scope here.
 */
class FahrasServiceTest extends Unit
{
    /** @var \common\tests\UnitTester */
    protected $tester;

    public function testDisabledServiceReturnsNoRecord(): void
    {
        $svc = new FahrasService(['enabled' => false, 'baseUrl' => 'https://example.test']);
        $verdict = $svc->check('1234567890', 'محمد أحمد', '0790000000');
        $this->assertSame(FahrasVerdict::VERDICT_NO_RECORD, $verdict->verdict);
        $this->assertFalse($verdict->blocks($svc->failurePolicy));
    }

    public function testMissingIdAndNameProducesValidationError(): void
    {
        $svc = $this->makeOfflineService();
        $verdict = $svc->check('', '');
        $this->assertSame(FahrasVerdict::VERDICT_ERROR, $verdict->verdict);
        $this->assertSame('CHECK_FAILED', $verdict->reasonCode);
    }

    public function testInvalidIdNumberFails(): void
    {
        $svc = $this->makeOfflineService();
        // Letters are stripped by cleanId(), so "abc" becomes "" — falls
        // through to "missing input" not "invalid id". Test a numeric
        // string that is too short to validate.
        $verdict = $svc->check('123', null);
        $this->assertSame(FahrasVerdict::VERDICT_ERROR, $verdict->verdict);
    }

    public function testInitRequiresHttpsInProduction(): void
    {
        $this->expectException(InvalidConfigException::class);
        new FahrasService([
            'enabled'      => true,
            'baseUrl'      => 'http://insecure.example.test',
            'requireHttps' => true,
        ]);
    }

    public function testInitAllowsHttpLocalhost(): void
    {
        // Should NOT throw — localhost dev environments are permitted.
        $svc = new FahrasService([
            'enabled' => true,
            'baseUrl' => 'http://localhost:8080',
        ]);
        $this->assertInstanceOf(FahrasService::class, $svc);
    }

    public function testFailurePolicyDefaultsToClosed(): void
    {
        $svc = $this->makeOfflineService(['failurePolicy' => 'banana-pancakes']);
        $this->assertSame('closed', $svc->failurePolicy);
    }

    public function testSearchByNameRejectsShortQuery(): void
    {
        $svc = $this->makeOfflineService();
        $r = $svc->searchByName('ab');
        $this->assertFalse($r['ok']);
        $this->assertSame('short_query', $r['error']);
    }

    private function makeOfflineService(array $extra = []): FahrasService
    {
        return new FahrasService(array_merge([
            'enabled'      => true,
            'baseUrl'      => 'https://fahras.test',
            'requireHttps' => true,
            'bypassCache'  => true,
        ], $extra));
    }
}
