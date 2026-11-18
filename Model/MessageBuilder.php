<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model;

/**
 * Builds the customer facing text. Admin overrides win, otherwise the translated built-in text is used.
 */
class MessageBuilder
{
    /**
     * Builds the text for one cart state, using the admin text when there is one.
     *
     * @param string $state One of ProgressCalculator::STATE_*
     * @param string $override Admin text for this state (may be empty)
     * @param string $amount Formatted amount still missing
     * @param string $threshold Formatted threshold
     * @return string
     */
    public function build(string $state, string $override, string $amount, string $threshold): string
    {
        $override = trim($override);
        if ($override !== '') {
            return str_replace(['{{amount}}', '{{threshold}}'], [$amount, $threshold], $override);
        }
        switch ($state) {
            case ProgressCalculator::STATE_DONE:
                return (string) __('Congratulations! You have free shipping.');
            case ProgressCalculator::STATE_EMPTY:
                return (string) __('Free shipping on orders over %1', $threshold);
            default:
                return (string) __('Add %1 more to get free shipping!', $amount);
        }
    }
}
