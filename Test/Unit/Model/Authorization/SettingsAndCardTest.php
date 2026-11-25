<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Authorization;

use GetDropFlow\FreeShippingBar\Model\Authorization\AuthorizationPolicy;
use GetDropFlow\FreeShippingBar\Model\Authorization\AuthorizationState;
use GetDropFlow\FreeShippingBar\Model\Authorization\CardData;
use GetDropFlow\FreeShippingBar\Model\Authorization\SettingsVisibility;
use PHPUnit\Framework\TestCase;

class SettingsAndCardTest extends TestCase
{
    private const NOW = 1800000000;

    public function testLockedShowsOnlyTheAuthorizationUpgradeAndAboutGroups(): void
    {
        $v = new SettingsVisibility();
        foreach (['authorization', 'upgrade', 'about'] as $g) {
            $this->assertTrue($v->isVisible('getdropflow_fsb/' . $g, true), $g);
        }
        foreach (['general', 'threshold', 'messages', 'appearance', 'theme'] as $g) {
            $this->assertFalse($v->isVisible('getdropflow_fsb/' . $g, true), $g);
            $this->assertTrue($v->isVisible('getdropflow_fsb/' . $g, false), $g);
        }
    }

    public function testOtherModulesPagesAreNeverTouched(): void
    {
        $v = new SettingsVisibility();
        $this->assertTrue($v->isVisible('carriers/freeshipping', true));
        $this->assertTrue($v->isVisible('dropshipkit_admin/general', true));
        $this->assertTrue($v->isVisible('getdropflow_fsb_other/general', true));
    }

    public function testEverySettingsGroupOfTheModuleIsKnown(): void
    {
        $sx = simplexml_load_string((string) file_get_contents(__DIR__ . '/../../../../etc/adminhtml/system.xml'));
        $v = new SettingsVisibility();
        $shownWhenLocked = [];
        foreach ($sx->xpath('//group') as $g) {
            if ($v->isVisible('getdropflow_fsb/' . (string) $g['id'], true)) {
                $shownWhenLocked[] = (string) $g['id'];
            }
        }
        sort($shownWhenLocked);
        $this->assertSame(['about', 'authorization', 'upgrade'], $shownWhenLocked);
    }

    public function testCardDataForALockedStoreWithARevokedCredential(): void
    {
        $d = (new CardData())->build(new AuthorizationState(AuthorizationPolicy::LOCKED), true, ['state' => 'rejected'], self::NOW);
        $this->assertTrue($d['locked']);
        $this->assertTrue($d['rejected']);
        $this->assertFalse($d['authorized']);
    }

    public function testCardDataCountsTheOfflineDaysLeft(): void
    {
        $record = ['state' => 'valid', 'last_valid_at' => self::NOW - 20 * 86400, 'plan' => 'free'];
        $d = (new CardData())->build(new AuthorizationState(AuthorizationPolicy::AUTHORIZED, true), true, $record, self::NOW);
        $this->assertTrue($d['offline']);
        $this->assertSame(10, $d['offline_days_left']);
        $this->assertSame('free', $d['plan']);
    }

    public function testCardDataShowsTheLapsedNoteWithoutLocking(): void
    {
        $record = ['state' => 'valid', 'plan' => 'free', 'lapsed_plan' => 'pro', 'lapsed_ended_at' => 1799000000];
        $d = (new CardData())->build(new AuthorizationState(AuthorizationPolicy::AUTHORIZED), true, $record, self::NOW);
        $this->assertSame(['plan' => 'pro', 'ended' => gmdate('Y-m-d', 1799000000)], $d['lapsed']);
        $this->assertFalse($d['locked']);
    }

    public function testNoLapsedNoteWhileLocked(): void
    {
        $record = ['lapsed_plan' => 'pro', 'lapsed_ended_at' => 1799000000];
        $d = (new CardData())->build(new AuthorizationState(AuthorizationPolicy::LOCKED), false, $record, self::NOW);
        $this->assertNull($d['lapsed']);
    }
}
