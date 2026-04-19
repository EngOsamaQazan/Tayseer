<?php

namespace common\tests\unit\services\dto;

use Codeception\Test\Unit;
use common\services\dto\FahrasVerdict;

/**
 * Unit tests for the {@see FahrasVerdict} DTO. The DTO carries no
 * dependencies (no DB, no HTTP, no Yii application context), so these
 * tests live in `common\tests\unit` and run in milliseconds.
 *
 * The intent is to lock down the verdict semantics — especially the
 * fail-closed `blocks()` policy that decides whether the wizard should
 * hard-stop the user.
 */
class FahrasVerdictTest extends Unit
{
    /** @var \common\tests\UnitTester */
    protected $tester;

    public function testNoRecordIsAllowed(): void
    {
        $v = FahrasVerdict::noRecord();
        $this->assertSame(FahrasVerdict::VERDICT_NO_RECORD, $v->verdict);
        $this->assertFalse($v->blocks('closed'));
        $this->assertFalse($v->blocks('open'));
        $this->assertFalse($v->warns());
    }

    public function testCanSellIsAllowed(): void
    {
        $v = FahrasVerdict::fromArray([
            'verdict'     => 'can_sell',
            'reason_code' => 'OK',
            'reason_ar'   => 'مسموح',
        ]);
        $this->assertFalse($v->blocks('closed'));
        $this->assertFalse($v->warns());
    }

    public function testContactFirstWarnsButDoesNotBlock(): void
    {
        $v = FahrasVerdict::fromArray(['verdict' => 'contact_first']);
        $this->assertFalse($v->blocks('closed'));
        $this->assertTrue($v->warns());
    }

    public function testCannotSellAlwaysBlocks(): void
    {
        $v = FahrasVerdict::fromArray(['verdict' => 'cannot_sell']);
        $this->assertTrue($v->blocks('closed'));
        $this->assertTrue($v->blocks('open'));
    }

    public function testErrorBlocksOnlyUnderClosedPolicy(): void
    {
        $v = FahrasVerdict::failure('connection refused');
        $this->assertSame(FahrasVerdict::VERDICT_ERROR, $v->verdict);
        $this->assertTrue($v->blocks('closed'));
        $this->assertFalse($v->blocks('open'));
        $this->assertTrue($v->warns());
    }

    public function testUnknownVerdictNormalisesToError(): void
    {
        $v = FahrasVerdict::fromApi('something_weird', 'X', 'X');
        $this->assertSame(FahrasVerdict::VERDICT_ERROR, $v->verdict);
        $this->assertTrue($v->blocks('closed'));
    }

    public function testToArrayPreservesPayload(): void
    {
        $v = FahrasVerdict::fromApi(
            'cannot_sell',
            'BLOCKED',
            'العميل محظور',
            [['name' => 'أحمد', 'id_number' => '9999999999']],
            [['source' => 'zajal', 'error' => 'timeout']],
            'req-123',
            200,
            150
        );
        $arr = $v->toArray();
        $this->assertSame('cannot_sell', $arr['verdict']);
        $this->assertSame('BLOCKED', $arr['reason_code']);
        $this->assertSame('req-123', $arr['request_id']);
        $this->assertCount(1, $arr['matches']);
        $this->assertCount(1, $arr['remote_errors']);
        $this->assertSame(150, $arr['duration_ms']);
    }
}
