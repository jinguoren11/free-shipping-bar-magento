<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\CustomerData;

use GetDropFlow\FreeShippingBar\Model\Config;
use GetDropFlow\FreeShippingBar\Model\SectionDataBuilder;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
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
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

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
     * @param PriceCurrencyInterface $priceCurrency
     * @param StoreManagerInterface $storeManager
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Config $config,
        SectionDataBuilder $builder,
        CheckoutSession $checkoutSession,
        PriceCurrencyInterface $priceCurrency,
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver
    ) {
        $this->config = $config;
        $this->builder = $builder;
        $this->checkoutSession = $checkoutSession;
        $this->priceCurrency = $priceCurrency;
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
            [
                'messages' => [
                    'progress' => $this->config->value('messages/progress_message', $storeId),
                    'success' => $this->config->value('messages/success_message', $storeId),
                    'empty' => $this->config->value('messages/empty_message', $storeId),
                ],
                'bar_color' => $this->config->getBarColor($storeId),
                'show_bar' => $this->config->flag('appearance/show_bar', $storeId),
                'show_percent' => $this->config->flag('appearance/show_percent', $storeId),
                'positions' => [
                    'minicart' => $this->config->flag('general/show_minicart', $storeId),
                    'cart' => $this->config->flag('general/show_cart', $storeId),
                    'product' => $this->config->flag('general/show_product', $storeId),
                ],
            ],
            function (float $baseAmount) use ($store): string {
                return $this->format($baseAmount, $store);
            }
        );
    }

    /**
     * @param float $baseAmount
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @return string
     */
    private function format(float $baseAmount, $store): string
    {
        return html_entity_decode(
            strip_tags((string) $this->priceCurrency->convertAndFormat($baseAmount, true, 2, $store)),
            ENT_QUOTES | ENT_HTML5
        );
    }
}
