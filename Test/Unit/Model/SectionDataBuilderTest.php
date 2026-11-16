<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model;

use GetDropFlow\FreeShippingBar\Model\MessageBuilder;
use GetDropFlow\FreeShippingBar\Model\ProgressCalculator;
use GetDropFlow\FreeShippingBar\Model\SectionDataBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Uses the real builder, real calculator and real message builder: nothing is mocked.
 * Regression test for P1: the section said "visible": false in every state, so the bar never showed.
 */
class SectionDataBuilderTest extends TestCase
{
    private function run1(float $subtotal, bool $empty = false, bool $virtual = false, bool $enabled = true, ?float $threshold = 50.0, array $messages = []): array
    {
        $builder = new SectionDataBuilder(new ProgressCalculator(), new MessageBuilder());
        return $builder->build(
            'default',
            'en',
            $enabled,
            $threshold,
            $empty,
            $virtual,
            $subtotal,
            [
                'messages' => $messages,
                'bar_color' => '#3b6ea5',
                'show_bar' => true,
                'show_percent' => false,
                'positions' => ['minicart' => true, 'cart' => true, 'product' => false],
            ],
            static function (float $amount): string {
                return '$' . number_format($amount, 2);
            }
        );
    }

    public function testVisibleIsTrueWhileInProgress(): void
    {
        $d = $this->run1(10.0);
        $this->assertTrue($d['visible']);
        $this->assertSame('progress', $d['state']);
        $this->assertSame(20, $d['percent']);
        $this->assertSame('Add $40.00 more to get free shipping!', $d['message']);
    }

    public function testVisibleIsTrueWhenReached(): void
    {
        foreach ([50.0, 120.0] as $subtotal) {
            $d = $this->run1($subtotal);
            $this->assertTrue($d['visible'], 'subtotal ' . $subtotal);
            $this->assertSame('done', $d['state']);
            $this->assertSame(100, $d['percent']);
        }
    }

    public function testEmptyCartShowsTheThresholdHint(): void
    {
        $d = $this->run1(0.0, true);
        $this->assertTrue($d['visible']);
        $this->assertSame('empty', $d['state']);
        $this->assertSame('Free shipping on orders over $50.00', $d['message']);
    }

    public function testHiddenWhenNothingToShow(): void
    {
        $this->assertFalse($this->run1(10.0, false, true)['visible'], 'virtual only cart');
        $this->assertFalse($this->run1(10.0, false, false, false)['visible'], 'module disabled');
        $this->assertFalse($this->run1(10.0, false, false, true, null)['visible'], 'no threshold');
        $hidden = $this->run1(10.0, false, true);
        $this->assertSame(['visible', 'store_code', 'lang'], array_keys($hidden));
    }

    public function testEveryFieldTheBrowserNeedsIsThere(): void
    {
        $d = $this->run1(10.0);
        foreach (['visible', 'store_code', 'lang', 'state', 'percent', 'message', 'bar_color', 'show_bar', 'show_percent', 'positions'] as $key) {
            $this->assertArrayHasKey($key, $d);
        }
        $this->assertSame(['minicart' => true, 'cart' => true, 'product' => false], $d['positions']);
        $this->assertSame('default', $d['store_code']);
        $this->assertSame('en', $d['lang']);
    }

    public function testAdminMessageOverridesPerState(): void
    {
        $messages = ['progress' => 'Faltan {{amount}} de {{threshold}}', 'success' => 'Listo', 'empty' => 'Desde {{threshold}}'];
        $this->assertSame('Faltan $40.00 de $50.00', $this->run1(10.0, false, false, true, 50.0, $messages)['message']);
        $this->assertSame('Listo', $this->run1(60.0, false, false, true, 50.0, $messages)['message']);
        $this->assertSame('Desde $50.00', $this->run1(0.0, true, false, true, 50.0, $messages)['message']);
    }
}
