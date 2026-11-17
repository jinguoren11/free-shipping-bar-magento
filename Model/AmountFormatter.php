<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Formats a base-currency amount in the customer's display currency, as plain text.
 */
class AmountFormatter
{
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(PriceCurrencyInterface $priceCurrency)
    {
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Formats a base-currency amount in the customer display currency as plain text.
     *
     * @param float $baseAmount
     * @param StoreInterface $store
     * @return string
     */
    public function format(float $baseAmount, StoreInterface $store): string
    {
        // includeContainer = false: plain text, no HTML wrapper to strip
        return html_entity_decode(
            (string) $this->priceCurrency->convertAndFormat($baseAmount, false, 2, $store),
            ENT_QUOTES | ENT_HTML5
        );
    }
}
