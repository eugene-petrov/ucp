<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Console\Command;

use Aeqet\Ucp\Model\Security\JwkGenerator;
use Aeqet\Ucp\Model\Security\KeyManager;
use Aeqet\Ucp\Model\SigningKeyEntity;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * CLI command to generate UCP signing keys
 *
 * Generates ECDSA P-256 key pairs for webhook signature verification.
 * Public keys are published in the UCP manifest for verification by platforms.
 */
class GenerateSigningKeysCommand extends Command
{
    private const OPTION_KID = 'kid';
    private const OPTION_FORCE = 'force';
    private const OPTION_EXPIRES = 'expires';
    private const DATE_FORMAT = 'Y-m-d';
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @param KeyManager $keyManager
     * @param JwkGenerator $jwkGenerator
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        private readonly KeyManager $keyManager,
        private readonly JwkGenerator $jwkGenerator,
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
        $this->setName('ucp:keys:generate')
            ->setDescription('Generate ECDSA P-256 signing keys for UCP webhook authentication')
            ->addOption(
                self::OPTION_KID,
                'k',
                InputOption::VALUE_OPTIONAL,
                'Custom key ID (kid). Auto-generated if not provided.'
            )
            ->addOption(
                self::OPTION_FORCE,
                'f',
                InputOption::VALUE_NONE,
                'Skip confirmation prompt'
            )
            ->addOption(
                self::OPTION_EXPIRES,
                'e',
                InputOption::VALUE_OPTIONAL,
                'Key expiration date (YYYY-MM-DD format)'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $expiresAt = $this->validateAndParseExpiration($input, $output);
            if ($expiresAt === false) {
                return Command::FAILURE;
            }

            $this->displayExistingKeysInfo($output);

            if (!$this->confirmKeyGeneration($input, $output)) {
                return Command::SUCCESS;
            }

            return $this->generateAndDisplayKey($input, $output, $expiresAt);
        } catch (Exception $e) {
            return $this->handleError($e, $output);
        }
    }

    /**
     * Validate and parse expiration date from input
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string|null|false Parsed date, null if not provided, false if invalid
     */
    private function validateAndParseExpiration(InputInterface $input, OutputInterface $output): string|null|false
    {
        $expires = $input->getOption(self::OPTION_EXPIRES);

        if ($expires === null) {
            return null;
        }

        $expiresAt = $this->parseExpirationDate($expires);
        if ($expiresAt === null) {
            $output->writeln('<error>Invalid expiration date format. Use YYYY-MM-DD.</error>');
            return false;
        }

        return $expiresAt;
    }

    /**
     * Display information about existing active keys
     *
     * @param OutputInterface $output
     * @return void
     */
    private function displayExistingKeysInfo(OutputInterface $output): void
    {
        $activeKeyCount = $this->keyManager->getActiveKeyCount();

        if ($activeKeyCount === 0) {
            return;
        }

        $output->writeln(sprintf(
            '<comment>There are currently %d active signing key(s).</comment>',
            $activeKeyCount
        ));
        $output->writeln(
            '<comment>The new key will be added alongside existing keys (for rotation support).</comment>'
        );
        $output->writeln('');
    }

    /**
     * Prompt user for confirmation unless force flag is set
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool True if confirmed or forced, false if cancelled
     */
    private function confirmKeyGeneration(InputInterface $input, OutputInterface $output): bool
    {
        $force = (bool) $input->getOption(self::OPTION_FORCE);

        if ($force) {
            return true;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            '<question>Generate a new ECDSA P-256 signing key? [y/N]</question> ',
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Key generation cancelled.</info>');
            return false;
        }

        return true;
    }

    /**
     * Generate signing key and display result
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string|null $expiresAt
     * @return int Command result code
     */
    private function generateAndDisplayKey(
        InputInterface $input,
        OutputInterface $output,
        ?string $expiresAt
    ): int {
        $kid = $input->getOption(self::OPTION_KID);

        $output->writeln('<info>Generating ECDSA P-256 signing key...</info>');

        $signingKey = $this->keyManager->generateKey($kid, $expiresAt);

        $this->displayKeyInfo($output, $signingKey);
        $this->displayNextSteps($output);

        return Command::SUCCESS;
    }

    /**
     * Display generated key information
     *
     * @param OutputInterface $output
     * @param SigningKeyEntity $signingKey
     * @return void
     */
    private function displayKeyInfo(OutputInterface $output, SigningKeyEntity $signingKey): void
    {
        $output->writeln('<info>Signing key generated successfully!</info>');
        $output->writeln('');
        $output->writeln(sprintf('<comment>Key ID (kid):</comment> %s', $signingKey->getKid()));
        $output->writeln(sprintf('<comment>Created:</comment> %s', $signingKey->getCreatedAt()));

        if ($signingKey->getExpiresAt()) {
            $output->writeln(sprintf('<comment>Expires:</comment> %s', $signingKey->getExpiresAt()));
        }

        $output->writeln('');
        $output->writeln('<info>Public key (JWK format):</info>');
        $output->writeln($this->formatJwkForDisplay($signingKey->getPublicKeyJwk()));
    }

    /**
     * Display next steps after key generation
     *
     * @param OutputInterface $output
     * @return void
     */
    private function displayNextSteps(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln('<comment>Next steps:</comment>');
        $output->writeln('  1. Regenerate the UCP manifest: <info>bin/magento ucp:manifest:generate</info>');
        $output->writeln('  2. The public key will be included in the manifest at /.well-known/ucp');
        $output->writeln('  3. Platforms will use this key to verify your webhook signatures');
    }

    /**
     * Handle exception and display error message
     *
     * @param Exception $e
     * @param OutputInterface $output
     * @return int Command failure code
     */
    private function handleError(Exception $e, OutputInterface $output): int
    {
        $this->logger->error('Failed to generate signing key', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

        return Command::FAILURE;
    }

    /**
     * Parse expiration date string to MySQL datetime format
     *
     * @param string $dateString Date in YYYY-MM-DD format
     * @return string|null MySQL datetime format or null if invalid
     */
    private function parseExpirationDate(string $dateString): ?string
    {
        $date = DateTime::createFromFormat(self::DATE_FORMAT, $dateString);

        if ($date === false) {
            return null;
        }

        $date->setTime(23, 59, 59);

        return $date->format(self::DATETIME_FORMAT);
    }

    /**
     * Format JWK for pretty console output
     *
     * @param string $jwkJson JWK in JSON format
     * @return string Pretty-printed JWK
     */
    private function formatJwkForDisplay(string $jwkJson): string
    {
        try {
            $jwk = $this->jwkGenerator->jsonToJwk($jwkJson);
            return $this->jwkGenerator->jwkToPrettyJson($jwk);
        } catch (Exception $e) {
            return $jwkJson;
        }
    }
}
