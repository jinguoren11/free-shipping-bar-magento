<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model;

/**
 * Reads every setting the browser needs, for one store view, in the shape SectionDataBuilder expects.
 * Shared by the Luma customer-data section and the Hyvä compatibility module.
 */
class SectionSettings
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param int $storeId
     * @return array
     */
    public function get(int $storeId): array
    {
        return [
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
            'selectors' => [
                'minicart' => $this->config->getSelector('minicart', $storeId),
                'cart' => $this->config->getSelector('cart', $storeId),
                'product' => $this->config->getSelector('product', $storeId),
            ],
            'placement' => $this->config->getPlacement($storeId),
        ];
    }
}
