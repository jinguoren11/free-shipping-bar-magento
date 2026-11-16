<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Block;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;

/**
 * Mount point for the bar on the cart page and the product page.
 * The actual content is filled in by the browser from the customer-data section.
 */
class Bar extends Template
{
    /**
     * @var Json
     */
    private $json;

    /**
     * @param Template\Context $context
     * @param Json $json
     * @param array $data
     */
    public function __construct(Template\Context $context, Json $json, array $data = [])
    {
        parent::__construct($context, $data);
        $this->json = $json;
    }

    /**
     * Scope name, unique per position.
     *
     * @return string
     */
    public function getScopeName(): string
    {
        return 'fsb_' . preg_replace('/[^a-z_]/', '', (string) $this->getData('position'));
    }

    /**
     * JSON for the Magento_Ui/js/core/app initializer.
     *
     * @return string
     */
    public function getInitJson(): string
    {
        return (string) $this->json->serialize([
            '*' => [
                'Magento_Ui/js/core/app' => [
                    'components' => [
                        $this->getScopeName() => [
                            'component' => 'GetDropFlow_FreeShippingBar/js/view/bar',
                            'position' => (string) $this->getData('position'),
                        ],
                    ],
                ],
            ],
        ]);
    }
}
