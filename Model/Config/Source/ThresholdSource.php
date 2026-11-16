<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ThresholdSource implements OptionSourceInterface
{
    public const MAGENTO = 'magento';
    public const CUSTOM = 'custom';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::MAGENTO, 'label' => __('Magento Free Shipping method (automatic)')],
            ['value' => self::CUSTOM, 'label' => __('Custom amount')],
        ];
    }
}
