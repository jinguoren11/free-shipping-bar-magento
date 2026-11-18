<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Placement implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'after', 'label' => __('After the element')],
            ['value' => 'before', 'label' => __('Before the element')],
            ['value' => 'append', 'label' => __('Inside the element, at the end')],
            ['value' => 'prepend', 'label' => __('Inside the element, at the start')],
        ];
    }
}
