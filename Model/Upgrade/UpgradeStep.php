<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * One reversible step of an upgrade.
 */
// phpcs:ignore Magento2.NamingConvention.InterfaceName -- short name kept, the engine is Download-edition only
interface UpgradeStep
{
    /**
     * Short plain-language name of the step (shown on the upgrade card).
     *
     * @return string
     */
    public function label(): string;

    /**
     * Does the step. Throws when it cannot.
     *
     * @return void
     */
    public function run(): void;

    /**
     * Puts back what run() changed. Must be safe to call when run() failed half way.
     *
     * @return void
     */
    public function undo(): void;
}
