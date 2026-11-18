<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade\Step;

use GetDropFlow\FreeShippingBar\Model\Upgrade\CommandRunner;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeStep;

/**
 * Puts the shop into maintenance mode while the files are replaced. Leaving it is done by the runner's finish
 * callback (also after a rollback), never by undo(): after a failed restore the shop must stay closed.
 */
class MaintenanceStep implements UpgradeStep
{
    /** @var CommandRunner */
    private $runner;
    /** @var string */
    private $marker;

    /**
     * @param CommandRunner $runner
     * @param string $marker file that says "this upgrade switched maintenance mode on" (so it is switched off
     *     again, but a maintenance mode the owner had switched on before is left alone)
     */
    public function __construct(CommandRunner $runner, string $marker)
    {
        $this->runner = $runner;
        $this->marker = $marker;
    }

    /**
     * Leaves maintenance mode, but only if this upgrade switched it on.
     *
     * @param CommandRunner $runner
     * @param string $marker
     * @return void
     */
    public static function leave(CommandRunner $runner, string $marker): void
    {
        if (is_file($marker)) {
            $runner->magento(['maintenance:disable']);
            Files::remove($marker);
        }
    }

    /**
     * @inheritDoc
     */
    public function label(): string
    {
        return 'Switch the shop to maintenance mode';
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        $status = $this->runner->magento(['maintenance:status']);
        if (stripos($status, 'is active') !== false && stripos($status, 'not active') === false) {
            return; // already in maintenance mode: not ours to switch off later
        }
        $this->runner->magento(['maintenance:enable']);
        Files::write($this->marker, '1');
    }

    /**
     * @inheritDoc
     */
    public function undo(): void
    {
        return;
    }
}
