<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * The host name of this store (no scheme, no path); empty when it cannot be determined.
 */
// phpcs:ignore Magento2.NamingConvention.InterfaceName -- short name kept, same style as HttpClient
interface Domain
{
    /**
     * Host name, lower case.
     *
     * @return string
     */
    public function get(): string;
}
