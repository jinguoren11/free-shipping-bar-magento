<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBarHyva\ViewModel;

use GetDropFlow\FreeShippingBar\Model\Access;
use GetDropFlow\FreeShippingBar\Model\AmountFormatter;
use GetDropFlow\FreeShippingBar\Model\Config;
use GetDropFlow\FreeShippingBar\Model\SectionDataBuilder;
use GetDropFlow\FreeShippingBar\Model\SectionSettings;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Hyva shows "default section data" (an empty array for our section) to visitors who have no session yet,
 * so a first-time visitor would see no bar. For them the cart is empty, which does not depend on the visitor
 * (only on store view and currency, which the full page cache already varies by), so the empty-cart state is
 * rendered into the page. Everything else comes from the customer-data section.
 */
class InitialState implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var SectionDataBuilder
     */
    private $builder;

    /**
     * @var SectionSettings
     */
    private $settings;

    /**
     * @var AmountFormatter
     */
    private $formatter;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Access
     */
    private $access;

    /**
     * @param Config $config
     * @param SectionDataBuilder $builder
     * @param SectionSettings $settings
     * @param AmountFormatter $formatter
     * @param StoreManagerInterface $storeManager
     * @param ResolverInterface $localeResolver
     * @param Json $json
     * @param Access $access the access gate (always allows in the Marketplace edition)
     */
    public function __construct(
        Config $config,
        SectionDataBuilder $builder,
        SectionSettings $settings,
        AmountFormatter $formatter,
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver,
        Json $json,
        Access $access
    ) {
        $this->config = $config;
        $this->builder = $builder;
        $this->settings = $settings;
        $this->formatter = $formatter;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
        $this->json = $json;
        $this->access = $access;
    }

    /**
     * Empty-cart state as JSON.
     *
     * @return string
     */
    public function getJson(): string
    {
        $store = $this->storeManager->getStore();
        $storeId = (int) $store->getId();
        $data = $this->builder->build(
            (string) $store->getCode(),
            strtolower((string) strstr($this->localeResolver->getLocale() . '_', '_', true)),
            // not allowed: the bar is simply not shown (same rule as the customer-data section)
            $this->access->isAllowed() && $this->config->flag('general/enabled', $storeId),
            $this->config->getThreshold($storeId),
            true,
            false,
            0.0,
            $this->settings->get($storeId),
            function (float $baseAmount) use ($store): string {
                return $this->formatter->format($baseAmount, $store);
            }
        );
        return (string) $this->json->serialize($data);
    }
}
