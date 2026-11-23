<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Authorization;

use GetDropFlow\FreeShippingBar\Model\Access;
use GetDropFlow\FreeShippingBar\Model\Authorization\AuthorizationGate;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support.php';

class AuthorizationGateTest extends TestCase
{
    private const NOW = 1800000000;

    private function gate(MemoryStore $store, int $now = self::NOW): AuthorizationGate
    {
        return new AuthorizationGate($store, new FixedClock($now));
    }

    private function store(string $state, int $answerAt, ?int $lastValid): MemoryStore
    {
        $s = new MemoryStore();
        $s->credential = 'gdt_' . str_repeat('a', 43);
        $s->record = ['state' => $state, 'answer_at' => $answerAt, 'last_valid_at' => $lastValid, 'plan' => 'free'];
        return $s;
    }

    public function testNewInstallIsLockedAndTheBarIsHidden(): void
    {
        $gate = $this->gate(new MemoryStore());
        $this->assertTrue($gate->state()->isLocked());
        $this->assertFalse($gate->isAllowed());
        $this->assertFalse((new Access($gate))->isAllowed());
    }

    public function testCredentialWithoutAnyAnswerYetIsLocked(): void
    {
        $s = new MemoryStore();
        $s->credential = 'gdt_' . str_repeat('a', 43);
        $this->assertTrue($this->gate($s)->state()->isLocked());
    }

    public function testAuthorizedWithAFreshValidAnswer(): void
    {
        $gate = $this->gate($this->store('valid', self::NOW - 3600, self::NOW - 3600));
        $this->assertTrue($gate->isAllowed());
        $this->assertFalse($gate->state()->offline);
    }

    /**
     * @dataProvider offlineDays
     */
    public function testOfflineGraceFromTheLastValidAnswer(int $days, bool $allowed): void
    {
        $at = self::NOW - $days * 86400;
        $gate = $this->gate($this->store('valid', $at, $at));
        $this->assertSame($allowed, $gate->isAllowed(), $days . ' days');
        if ($allowed && $days >= 3) {
            $this->assertTrue($gate->state()->offline);
        }
    }

    public static function offlineDays(): array
    {
        return [[1, true], [3, true], [29, true], [30, true], [31, false], [60, false]];
    }

    public function testTheServersClearRejectionLocksAtOnce(): void
    {
        $this->assertFalse($this->gate($this->store('rejected', self::NOW - 60, self::NOW - 7200))->isAllowed());
    }

    public function testEndedMembershipOfAKnownStoreDoesNotLock(): void
    {
        $this->assertTrue($this->gate($this->store('ended', self::NOW - 60, self::NOW - 86400 * 90))->isAllowed());
    }

    public function testDisconnectLocksAgain(): void
    {
        $s = $this->store('valid', self::NOW, self::NOW);
        $gate = $this->gate($s);
        $this->assertTrue($gate->isAllowed());
        $s->forget();
        $gate->forget();
        $this->assertFalse($gate->isAllowed());
    }

    public function testAccessFailsSafeWhenTheCheckBreaks(): void
    {
        $bad = new class implements \GetDropFlow\FreeShippingBar\Model\AccessCheck {
            public function isAllowed(): bool
            {
                throw new \RuntimeException('database down');
            }
        };
        $this->assertFalse((new Access($bad))->isAllowed());
    }

    public function testMarketplaceEditionHasNoCheckAndIsNotLocked(): void
    {
        $this->assertTrue((new Access())->isAllowed());
    }
}
