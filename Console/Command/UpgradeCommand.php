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
        $this->addOption('recover', null, InputOption::VALUE_NONE, 'Put the old version back after a killed upgrade.');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $service = $this->factory->create();
        if ($input->getOption('recover')) {
            $output->writeln($service->recoverIfDead() ? 'Restored.' : 'Nothing to restore.');
            return Command::SUCCESS;
        }
        $found = $service->peek();
        $manifest = $found['manifest'];
        if ($manifest === null || !$found['newer']) {
            $output->writeln('No newer version known. Press "Check for updates" first.');
            return Command::FAILURE;
        }
        $reasons = $service->preflight($manifest, $this->factory->serverFacts());
        if ($reasons) {
            $output->writeln('Cannot upgrade: ' . implode(', ', $reasons));
            return Command::FAILURE;
        }
        $result = $service->execute($manifest);
        $output->writeln($result);

        return $result === UpgradeStatus::DONE ? Command::SUCCESS : Command::FAILURE;
    }
}
