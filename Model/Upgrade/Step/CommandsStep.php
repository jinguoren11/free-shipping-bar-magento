<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade\Step;

use GetDropFlow\FreeShippingBar\Model\Upgrade\ChangesShop;
use GetDropFlow\FreeShippingBar\Model\Upgrade\CommandRunner;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeStep;

/**
 * Runs a list of bin/magento commands (setup:upgrade, compile, static files, cache flush). Nothing to undo here:
 * after a rollback the runner calls the same list again on the old code (see UpgradeService).
 */
class CommandsStep implements UpgradeStep, ChangesShop
{
    /** @var CommandRunner */
    private $runner;
    /** @var array */
    private $commands;
    /** @var string */
    private $label;

    /**
     * @param CommandRunner $runner
     * @param array $commands list of argument lists
     * @param string $label
     */
    public function __construct(CommandRunner $runner, array $commands, string $label)
    {
        $this->runner = $runner;
        $this->commands = $commands;
        $this->label = $label;
    }

    /**
     * @inheritDoc
     */
    public function label(): string
    {
        return $this->label;
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        foreach ($this->commands as $arguments) {
            $this->runner->magento($arguments);
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
