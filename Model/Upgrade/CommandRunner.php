<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Runs bin/magento commands for the upgrade.
 */
interface CommandRunner
{
    /**
     * Runs one bin/magento command. Throws when it fails.
     *
     * @param string[] $arguments for example ['setup:upgrade', '--keep-generated']
     * @return string the command output
     * @throws UpgradeRejected
     */
    public function magento(array $arguments): string;
}
