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
     * @param float $baseAmount
     * @param StoreInterface $store
     * @return string
     */
    public function format(float $baseAmount, StoreInterface $store): string
    {
        return html_entity_decode(
            strip_tags((string) $this->priceCurrency->convertAndFormat($baseAmount, true, 2, $store)),
            ENT_QUOTES | ENT_HTML5
        );
    }
}
