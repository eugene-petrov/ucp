<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Console\Command;

use Aeqet\Ucp\Model\Webhook\DeliveryProcessor;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RetryWebhooksCommand extends Command
{
    /**
     * @param DeliveryProcessor $processor
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        private readonly DeliveryProcessor $processor,
        private readonly LoggerInterface $logger,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('ucp:webhooks:retry')
            ->setDescription('Manually process pending UCP webhook deliveries');

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln('<info>Processing pending UCP webhook deliveries...</info>');
            $this->processor->processPending();
            $output->writeln('<info>Done.</info>');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->logger->error('UCP webhooks:retry command failed', ['exception' => $e->getMessage()]);
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
