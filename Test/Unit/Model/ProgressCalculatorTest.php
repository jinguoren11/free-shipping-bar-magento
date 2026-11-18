<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model;

use GetDropFlow\FreeShippingBar\Model\ProgressCalculator;
use PHPUnit\Framework\TestCase;

class ProgressCalculatorTest extends TestCase
{
    /**
     * @param float $threshold
     * @param float $subtotal
     * @param bool $empty
     * @param string $state
     * @param float $remaining
     * @param int $percent
     * @dataProvider cases
     */
    public function testCalculate(float $threshold, float $subtotal, bool $empty, string $state, float $remaining, int $percent): void
    {
        $r = (new ProgressCalculator())->calculate($threshold, $subtotal, $empty);
        $this->assertSame($state, $r['state']);
        $this->assertEqualsWithDelta($remaining, $r['remaining'], 0.0001);
        $this->assertSame($percent, $r['percent']);
    }

    /**
     * @return array
     */
    public static function cases(): array
    {
        return [
            'empty cart' => [50.0, 0.0, true, 'empty', 50.0, 0],
            'just started' => [50.0, 0.01, false, 'progress', 49.99, 0],
            'halfway' => [50.0, 25.0, false, 'progress', 25.0, 50],
            'one cent short' => [50.0, 49.99, false, 'progress', 0.01, 99],
            'exactly the threshold' => [50.0, 50.0, false, 'done', 0.0, 100],
            'above threshold' => [50.0, 120.0, false, 'done', 0.0, 100],
            'float noise (0.1+0.2 style)' => [0.3, 0.1 + 0.2, false, 'done', 0.0, 100],
            'discount drops below' => [100.0, 99.999, false, 'done', 0.0, 100],
            'non round threshold' => [74.5, 30.0, false, 'progress', 44.5, 40],
            'large amounts' => [1000000.0, 999999.99, false, 'progress', 0.01, 99],
        ];
    }
}
