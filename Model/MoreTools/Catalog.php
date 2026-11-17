<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\MoreTools;

/**
 * Fixed list of "More tools" shown at the bottom of the settings page.
 * Static on purpose: nothing is downloaded, the list only changes with a new module version.
 * To add a tool, add one entry. Descriptions are plain English source strings translated with __().
 */
class Catalog
{
    /**
     * Returns the fixed list of tools shown in the "More tools" panel.
     *
     * @return array[] each item has name, url, host and description
     */
    public function getItems(): array
    {
        return [
            [
                'name' => 'GetDropFlow',
                'url' => 'https://getdropflow.com',
                'host' => 'getdropflow.com',
                'description' => 'Dropshipping plugin for Magento 2: import AliExpress products, keep prices and '
                    . 'stock in sync, and send order tracking numbers back to your store.',
            ],
        ];
    }
}
