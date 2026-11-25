<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model;

/**
 * Answers one question: may the bar be shown on the storefront? The Marketplace edition has no check (always yes);
 * the download edition binds the authorization gate here (etc/di.xml).
 */
// phpcs:ignore Magento2.NamingConvention.InterfaceName -- short name kept, same style as HttpClient
interface AccessCheck
{
    /**
     * Whether the bar may be shown. Must never contact the network.
     *
     * @return bool
     */
    public function isAllowed(): bool;
}
