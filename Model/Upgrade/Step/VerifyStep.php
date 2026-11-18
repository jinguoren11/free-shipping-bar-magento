<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade\Step;

use GetDropFlow\FreeShippingBar\Model\Upgrade\CommandRunner;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeRejected;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeStep;

/**
 * Checks the result: the installed folder holds the target version and Magento lists the module as enabled.
 */
class VerifyStep implements UpgradeStep
{
    /** @var CommandRunner */
    private $runner;
    /** @var string */
    private $moduleDir;
    /** @var string */
    private $version;

    /**
     * @param CommandRunner $runner
     * @param string $moduleDir
     * @param string $version version that must now be installed
     */
    public function __construct(CommandRunner $runner, string $moduleDir, string $version)
    {
        $this->runner = $runner;
        $this->moduleDir = $moduleDir;
        $this->version = $version;
    }

    /**
     * @inheritDoc
     */
    public function label(): string
    {
        return 'Check the result';
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        $composer = is_file($this->moduleDir . '/composer.json')
            ? json_decode((string) file_get_contents($this->moduleDir . '/composer.json'), true) : null;
        if (!is_array($composer) || ($composer['version'] ?? null) !== $this->version) {
            throw new UpgradeRejected('VERIFY_FAILED', 'The installed version is not the expected one.');
        }
        $status = $this->runner->magento(['module:status', 'GetDropFlow_FreeShippingBar']);
        if (stripos($status, 'enabled') === false || stripos($status, 'disabled') !== false) {
            throw new UpgradeRejected('VERIFY_FAILED', 'The module is not enabled after the upgrade.');
        }
    }

    /**
     * @inheritDoc
     */
    public function undo(): void
    {
        return;
    }
}
