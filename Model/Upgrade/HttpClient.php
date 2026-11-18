<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Minimal web access used by the upgrade. Only plain GET requests, no data of the shop is ever sent.
 */
// phpcs:ignore Magento2.NamingConvention.InterfaceName -- short name kept, the engine is Download-edition only
interface HttpClient
{
    /**
     * Downloads a small text (JSON).
     *
     * @param string $url
     * @param int $maxBytes
     * @return string
     * @throws UpgradeRejected
     */
    public function getText(string $url, int $maxBytes): string;

    /**
     * Downloads a file.
     *
     * @param string $url
     * @param string $destination
     * @param int $maxBytes
     * @return void
     * @throws UpgradeRejected
     */
    public function download(string $url, string $destination, int $maxBytes): void;
}
