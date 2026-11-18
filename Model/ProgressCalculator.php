<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model;

/**
 * Pure progress math. No Magento dependency, so it is easy to unit test.
 */
class ProgressCalculator
{
    public const STATE_EMPTY = 'empty';
    public const STATE_PROGRESS = 'progress';
    public const STATE_DONE = 'done';

    /**
     * Works out the state, the missing amount and the percentage of the progress bar.
     *
     * @param float $threshold Free shipping minimum (base currency, > 0)
     * @param float $subtotal Cart subtotal after discounts (base currency)
     * @param bool $cartIsEmpty True when the cart has no items
     * @return array{state: string, remaining: float, percent: int}
     */
    public function calculate(float $threshold, float $subtotal, bool $cartIsEmpty): array
    {
        if ($cartIsEmpty) {
            return ['state' => self::STATE_EMPTY, 'remaining' => $threshold, 'percent' => 0];
        }
        // Compare in cents to avoid float noise such as 49.99999999
        $thresholdCents = (int) round($threshold * 100);
        $subtotalCents = (int) round($subtotal * 100);
        if ($subtotalCents >= $thresholdCents) {
            return ['state' => self::STATE_DONE, 'remaining' => 0.0, 'percent' => 100];
        }
        $percent = (int) floor($subtotalCents / $thresholdCents * 100);
        return [
            'state' => self::STATE_PROGRESS,
            'remaining' => ($thresholdCents - $subtotalCents) / 100,
            'percent' => max(0, min(99, $percent)),
        ];
    }
}
