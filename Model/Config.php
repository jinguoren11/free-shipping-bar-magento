<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model;

use GetDropFlow\FreeShippingBar\Model\Config\Source\ThresholdSource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads module settings and the Magento Free Shipping threshold. Read only.
 */
class Config
{
    private const PATH = 'getdropflow_fsb/';
    private const CARRIER_ACTIVE = 'carriers/freeshipping/active';
    private const CARRIER_SUBTOTAL = 'carriers/freeshipping/free_shipping_subtotal';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SelectorSanitizer
     */
    private $sanitizer;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param SelectorSanitizer|null $sanitizer
     */
    public function __construct(ScopeConfigInterface $scopeConfig, ?SelectorSanitizer $sanitizer = null)
    {
        $this->scopeConfig = $scopeConfig;
        $this->sanitizer = $sanitizer ?: new SelectorSanitizer();
    }

    /**
     * @param string $key
     * @param int|string|null $storeId
     * @return bool
     */
    public function flag(string $key, $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::PATH . $key, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param string $key
     * @param int|string|null $storeId
     * @return string
     */
    public function value(string $key, $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(self::PATH . $key, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Free shipping threshold in base currency, or null when there is nothing to show.
     *
     * @param int|string|null $storeId
     * @return float|null
     */
    public function getThreshold($storeId = null): ?float
    {
        if ($this->value('threshold/source', $storeId) === ThresholdSource::CUSTOM) {
            $custom = (float) $this->value('threshold/custom_value', $storeId);
            return $custom > 0 ? $custom : null;
        }
        if (!$this->scopeConfig->isSetFlag(self::CARRIER_ACTIVE, ScopeInterface::SCOPE_STORE, $storeId)) {
            return null;
        }
        $amount = (float) $this->scopeConfig->getValue(self::CARRIER_SUBTOTAL, ScopeInterface::SCOPE_STORE, $storeId);
        return $amount > 0 ? $amount : null;
    }

    /**
     * Only accepts #rgb or #rrggbb so admin input can never break out of the style attribute.
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getBarColor($storeId = null): string
    {
        $color = trim($this->value('appearance/bar_color', $storeId));
        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color) ? $color : '#3b6ea5';
    }

    /**
     * Custom mount position (CSS selector) for "minicart", "cart" or "product". Empty = standard position.
     *
     * @param string $position
     * @param int|string|null $storeId
     * @return string
     */
    public function getSelector(string $position, $storeId = null): string
    {
        return $this->sanitizer->selector($this->value('theme/selector_' . $position, $storeId));
    }

    /**
     * @param int|string|null $storeId
     * @return string before|after|prepend|append
     */
    public function getPlacement($storeId = null): string
    {
        return $this->sanitizer->placement($this->value('theme/placement', $storeId));
    }
}
