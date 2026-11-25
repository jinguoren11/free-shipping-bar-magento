<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

use Magento\Store\Model\StoreManagerInterface;

/**
 * The host of the store's base URL. This and the account's e-mail (given on the website) are all the plugin ever tells the website.
 */
class StoreDomain implements Domain
{
    /**
     * @var StoreManagerInterface
     */
    private $stores;

    /**
     * @param StoreManagerInterface $stores
     */
    public function __construct(StoreManagerInterface $stores)
    {
        $this->stores = $stores;
    }

    /**
     * @inheritDoc
     */
    public function get(): string
    {
        $host = parse_url((string) $this->stores->getDefaultStoreView()->getBaseUrl(), PHP_URL_HOST); // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return is_string($host) ? strtolower($host) : '';
    }
}
