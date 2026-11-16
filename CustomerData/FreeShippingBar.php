<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\CustomerData;

use GetDropFlow\FreeShippingBar\Model\Config;
use GetDropFlow\FreeShippingBar\Model\MessageBuilder;
use GetDropFlow\FreeShippingBar\Model\ProgressCalculator;
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
    private const MESSAGE_KEYS = [
        ProgressCalculator::STATE_EMPTY => 'messages/empty_message',
        ProgressCalculator::STATE_PROGRESS => 'messages/progress_message',
        ProgressCalculator::STATE_DONE => 'messages/success_message',
    ];

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProgressCalculator
     */
    private $calculator;

    /**
     * @var MessageBuilder
     */
    private $messageBuilder;

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
     * @param ProgressCalculator $calculator
     * @param MessageBuilder $messageBuilder
     * @param CheckoutSession $checkoutSession
     * @param PriceCurrencyInterface $priceCurrency
     * @param StoreManagerInterface $storeManager
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Config $config,
        ProgressCalculator $calculator,
        MessageBuilder $messageBuilder,
        CheckoutSession $checkoutSession,
        PriceCurrencyInterface $priceCurrency,
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver
    ) {
        $this->config = $config;
        $this->calculator = $calculator;
        $this->messageBuilder = $messageBuilder;
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
        $base = [
            'visible' => false,
            'store_code' => $store->getCode(),
            'lang' => strtolower((string) strstr($this->localeResolver->getLocale() . '_', '_', true)),
        ];

        $threshold = $this->config->getThreshold($storeId);
        if (!$this->config->flag('general/enabled', $storeId) || $threshold === null) {
            return $base;
        }

        $quote = $this->checkoutSession->getQuote();
        $isEmpty = !$quote->getId() || (float) $quote->getItemsQty() <= 0;
        if (!$isEmpty && $quote->isVirtual()) {
            return $base; // nothing is shipped, so a free shipping hint would be misleading
        }

        $result = $this->calculator->calculate(
            $threshold,
            $isEmpty ? 0.0 : (float) $quote->getBaseSubtotalWithDiscount(),
            $isEmpty
        );

        $amountText = $this->format($result['remaining'], $store);
        $thresholdText = $this->format($threshold, $store);

        return $base + [
            'visible' => true,
            'state' => $result['state'],
            'percent' => $result['percent'],
            'message' => $this->messageBuilder->build(
                $result['state'],
                $this->config->value(self::MESSAGE_KEYS[$result['state']], $storeId),
                $amountText,
                $thresholdText
            ),
            'bar_color' => $this->config->getBarColor($storeId),
            'show_bar' => $this->config->flag('appearance/show_bar', $storeId),
            'show_percent' => $this->config->flag('appearance/show_percent', $storeId),
            'positions' => [
                'minicart' => $this->config->flag('general/show_minicart', $storeId),
                'cart' => $this->config->flag('general/show_cart', $storeId),
                'product' => $this->config->flag('general/show_product', $storeId),
            ],
        ];
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
