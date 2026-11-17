<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model;

/**
 * Builds the array the browser receives (customer-data section "free-shipping-bar").
 * No Magento dependency, so the real result can be unit tested without mocking anything.
 */
class SectionDataBuilder
{
    private const MESSAGE_KEYS = [
        ProgressCalculator::STATE_EMPTY => 'empty',
        ProgressCalculator::STATE_PROGRESS => 'progress',
        ProgressCalculator::STATE_DONE => 'success',
    ];

    /**
     * @var ProgressCalculator
     */
    private $calculator;

    /**
     * @var MessageBuilder
     */
    private $messageBuilder;

    /**
     * @param ProgressCalculator $calculator
     * @param MessageBuilder $messageBuilder
     */
    public function __construct(ProgressCalculator $calculator, MessageBuilder $messageBuilder)
    {
        $this->calculator = $calculator;
        $this->messageBuilder = $messageBuilder;
    }

    /**
     * Builds the data array the browser receives for the free shipping bar.
     *
     * @param string $storeCode
     * @param string $lang two letter language of the store view
     * @param bool $enabled module switched on for this store view
     * @param float|null $threshold free shipping amount (base currency) or null when there is none
     * @param bool $cartIsEmpty
     * @param bool $cartIsVirtual only virtual products (nothing is shipped)
     * @param float $baseSubtotal cart subtotal after discounts (base currency)
     * @param array $settings keys: messages (progress|success|empty => admin text), bar_color, show_bar,
     *                        show_percent, positions (minicart|cart|product => bool),
     *                        selectors (minicart|cart|product => CSS selector or ''), placement
     * @param callable $format float amount (base currency) => formatted text in the customer's currency
     * @return array
     */
    public function build(
        string $storeCode,
        string $lang,
        bool $enabled,
        ?float $threshold,
        bool $cartIsEmpty,
        bool $cartIsVirtual,
        float $baseSubtotal,
        array $settings,
        callable $format
    ): array {
        $hidden = ['visible' => false, 'store_code' => $storeCode, 'lang' => $lang];
        if (!$enabled || $threshold === null || (!$cartIsEmpty && $cartIsVirtual)) {
            return $hidden;
        }

        $result = $this->calculator->calculate($threshold, $cartIsEmpty ? 0.0 : $baseSubtotal, $cartIsEmpty);
        $override = (string) ($settings['messages'][self::MESSAGE_KEYS[$result['state']]] ?? '');

        // array_merge, not "+": with "+" the left side wins and "visible" would stay false.
        return array_merge($hidden, [
            'visible' => true,
            'state' => $result['state'],
            'percent' => $result['percent'],
            'message' => $this->messageBuilder->build(
                $result['state'],
                $override,
                (string) $format($result['remaining']),
                (string) $format($threshold)
            ),
            'bar_color' => (string) ($settings['bar_color'] ?? ''),
            'show_bar' => (bool) ($settings['show_bar'] ?? true),
            'show_percent' => (bool) ($settings['show_percent'] ?? false),
            'positions' => [
                'minicart' => (bool) ($settings['positions']['minicart'] ?? false),
                'cart' => (bool) ($settings['positions']['cart'] ?? false),
                'product' => (bool) ($settings['positions']['product'] ?? false),
            ],
            'selectors' => [
                'minicart' => (string) ($settings['selectors']['minicart'] ?? ''),
                'cart' => (string) ($settings['selectors']['cart'] ?? ''),
                'product' => (string) ($settings['selectors']['product'] ?? ''),
            ],
            'placement' => (string) ($settings['placement'] ?? 'after'),
        ]);
    }
}
