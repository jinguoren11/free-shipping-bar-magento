<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Cron;

use GetDropFlow\FreeShippingBar\Model\Upgrade\ServiceFactory;

/**
 * Download edition only. Every few minutes: if an upgrade was killed half way (the shop may be in maintenance mode
 * and the admin unreachable), put the old version back. Does nothing, and costs nothing, when no upgrade is dead.
 */
class RecoverUpgrade
{
    /** @var ServiceFactory */
    private $factory;

    /**
     * @param ServiceFactory $factory
     */
    public function __construct(ServiceFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Runs the recovery check.
     *
     * @return void
     */
    public function execute(): void
    {
        $this->factory->create()->recoverIfDead();
    }
}
