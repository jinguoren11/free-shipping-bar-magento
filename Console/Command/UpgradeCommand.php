<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Console\Command;

use GetDropFlow\FreeShippingBar\Model\Upgrade\ServiceFactory;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Background worker of the one-click upgrade (started by the settings page, or by hand on the command line).
 */
class UpgradeCommand extends Command
{
    /** @var ServiceFactory */
    private $factory;

    /**
     * @param ServiceFactory $factory
     */
    public function __construct(ServiceFactory $factory)
    {
        parent::__construct('getdropflow:fsb:upgrade');
        $this->factory = $factory;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Upgrades the Free Shipping Progress Bar module to the newest signed release.');
        $this->addOption(
            'recover',
            null,
            InputOption::VALUE_NONE,
            'Put the old version back and leave maintenance mode after a killed upgrade '
            . '(use this when maintenance mode blocks the admin).'
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $service = $this->factory->create();
        if ($input->getOption('recover')) {
            $output->writeln($service->recoverIfDead(true) ? 'Restored.' : 'Nothing to restore.');
            return Command::SUCCESS;
        }
        $service->recoverIfDead(); // a dead earlier upgrade is restored first, never built upon
        $found = $service->peek();
        $manifest = $found['manifest'];
        if ($manifest === null || !$found['newer']) {
            $output->writeln('No newer version known. Press "Check for updates" first.');
            return Command::FAILURE;
        }
        $outcome = $service->work($manifest, $this->factory->serverFacts());
        if ($outcome['reasons']) {
            $output->writeln('Cannot upgrade: ' . implode(', ', $outcome['reasons']));
            return Command::FAILURE;
        }
        $output->writeln($outcome['state']);

        return $outcome['state'] === UpgradeStatus::DONE ? Command::SUCCESS : Command::FAILURE;
    }
}
