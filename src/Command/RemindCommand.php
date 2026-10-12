<?php

namespace Wexample\SymfonyShopDs\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wexample\SymfonyShopDs\Service\CartReminderService;
use Wexample\SymfonyShopDs\WexampleSymfonyShopDsBundle;
use Wexample\SymfonyHelpers\Command\AbstractBundleCommand;
use Wexample\SymfonyHelpers\Service\BundleService;

/**
 * Writes now to the buyers of carts left on the way, as the scheduler does
 * every quarter of an hour.
 */
class RemindCommand extends AbstractBundleCommand
{
    public function __construct(
        BundleService $bundleService,
        private readonly CartReminderService $reminders,
    ) {
        parent::__construct($bundleService);
    }

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyShopDsBundle::class;
    }

    protected function configure(): void
    {
        $this->setDescription('Writes to the buyers of carts left on the way.');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $output->writeln(sprintf('%d buyer(s) written to.', $this->reminders->remind()));

        return self::SUCCESS;
    }
}
