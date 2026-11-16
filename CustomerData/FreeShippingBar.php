<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\CustomerData;

use GetDropFlow\FreeShippingBar\Model\Config;
use GetDropFlow\FreeShippingBar\Model\AmountFormatter;
use GetDropFlow\FreeShippingBar\Model\SectionDataBuilder;
use GetDropFlow\FreeShippingBar\Model\SectionSettings;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Storefront data for the bar. Loaded by the browser, so full page cache stays intact.
 * Only reads the cart; never changes it.
 */
class FreeShippingBar implements SectionSourceInterface
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
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var AmountFormatter
     */
    private $formatter;

    /**
     * @var SectionSettings
     */
    private $settings;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @param Config $config
     * @param SectionDataBuilder $builder
     * @param CheckoutSession $checkoutSession
     * @param AmountFormatter $formatter
     * @param SectionSettings $settings
     * @param StoreManagerInterface $storeManager
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Config $config,
        SectionDataBuilder $builder,
        CheckoutSession $checkoutSession,
        AmountFormatter $formatter,
        SectionSettings $settings,
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver
    ) {
        $this->config = $config;
        $this->builder = $builder;
        $this->checkoutSession = $checkoutSession;
        $this->formatter = $formatter;
        $this->settings = $settings;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @inheritDoc
     */
    public function getSectionData(): array
    {
        $store = $this->storeManager->getStore();
        $storeId = (int) $store->getId();
        $quote = $this->checkoutSession->getQuote();
        $isEmpty = !$quote->getId() || (float) $quote->getItemsQty() <= 0;

        return $this->builder->build(
            (string) $store->getCode(),
            strtolower((string) strstr($this->localeResolver->getLocale() . '_', '_', true)),
            $this->config->flag('general/enabled', $storeId),
            $this->config->getThreshold($storeId),
            $isEmpty,
            !$isEmpty && $quote->isVirtual(),
            $isEmpty ? 0.0 : (float) $quote->getBaseSubtotalWithDiscount(),
            $this->settings->get($storeId),
            function (float $baseAmount) use ($store): string {
                return $this->formatter->format($baseAmount, $store);
            }
        );
    }
}
