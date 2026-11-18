<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Marks an upgrade step that changes the code or the generated files of the shop. When such a step was started and
 * the upgrade fails, the shop is rebuilt on the old code; when none was started nothing is rebuilt.
 */
// phpcs:ignore Magento2.NamingConvention.InterfaceName -- a marker, named for what it says
interface ChangesShop
{
}
