<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Authorization;

use GetDropFlow\FreeShippingBar\Model\Authorization\AuthorizationPolicy as P;
use PHPUnit\Framework\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    private const NOW = 1800000000;

    public function testNotEnforced(): void
    {
        $s = P::decide(false, false, false, false, null, self::NOW);
        $this->assertSame(P::NOT_ENFORCED, $s->state);
        $this->assertTrue($s->allowsUse());
    }

    public function testNewStoreWithNothingIsLocked(): void
    {
        $s = P::decide(true, false, false, false, null, self::NOW);
        $this->assertTrue($s->isLocked());
        $this->assertFalse($s->allowsUse());
    }

    public function testValidAnswerAuthorizes(): void
    {
        $s = P::decide(true, true, false, false, self::NOW, self::NOW);
        $this->assertSame(P::AUTHORIZED, $s->state);
        $this->assertFalse($s->offline);
    }

    public function testKnownStoreWhoseMembershipEndedStaysAuthorized(): void
    {
        $this->assertTrue(P::decide(true, false, true, false, null, self::NOW)->allowsUse());
    }

    public function testOffline29DaysWorks31DaysLocks(): void
    {
        $last = self::NOW - 29 * 86400;
        $s = P::decide(true, false, false, false, $last, self::NOW);
        $this->assertTrue($s->allowsUse());
        $this->assertTrue($s->offline);
        $this->assertTrue(P::decide(true, false, false, false, self::NOW - 31 * 86400, self::NOW)->isLocked());
        $this->assertTrue(P::decide(true, false, false, false, self::NOW - 30 * 86400, self::NOW)->allowsUse(), 'day 30 is the last good day');
    }

    public function testExplicitRejectionLocksEvenWithAFreshLastAnswer(): void
    {
        $this->assertTrue(P::decide(true, false, false, true, self::NOW - 3600, self::NOW)->isLocked());
    }

    public function testAClockBeforeTheLastAnswerDoesNotAuthorize(): void
    {
        $this->assertTrue(P::decide(true, false, false, false, self::NOW + 100, self::NOW)->isLocked());
    }
}
