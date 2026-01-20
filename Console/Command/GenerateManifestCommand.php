<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Console\Command;

use Aeqet\Ucp\Api\ManifestGeneratorInterface;
use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateManifestCommand extends Command
{
    private const OPTION_OUTPUT = 'output';
    private const OPTION_PRETTY = 'pretty';
    private const DEFAULT_OUTPUT_PATH = '.well-known/ucp';

    /**
     * Constructor
     *
     * @param ManifestGeneratorInterface $manifestGenerator
     * @param Filesystem $filesystem
     * @param FileDriver $fileDriver
     * @param Json $json
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        private readonly ManifestGeneratorInterface $manifestGenerator,
        private readonly Filesystem $filesystem,
        private readonly FileDriver $fileDriver,
        private readonly Json $json,
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
        $this->setName('ucp:manifest:generate')
            ->setDescription('Generate UCP manifest file at /.well-known/ucp')
            ->addOption(
                self::OPTION_OUTPUT,
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output file path (default: pub/.well-known/ucp)'
            )
            ->addOption(
                self::OPTION_PRETTY,
                'p',
                InputOption::VALUE_NONE,
                'Pretty print JSON output'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $outputPath = $input->getOption(self::OPTION_OUTPUT);
        $prettyPrint = (bool) $input->getOption(self::OPTION_PRETTY);

        try {
            $output->writeln('<info>Generating UCP manifest...</info>');

            $manifestData = $this->manifestGenerator->generate();
            $jsonContent = $this->encodeManifest($manifestData, $prettyPrint);

            $filePath = $this->writeManifestFile($outputPath, $jsonContent);

            $output->writeln('<info>UCP manifest generated successfully!</info>');
            $output->writeln(sprintf('<comment>Output file: %s</comment>', $filePath));

            if ($output->isVerbose()) {
                $output->writeln('');
                $output->writeln('<comment>Manifest content:</comment>');
                $output->writeln($this->encodeManifest($manifestData, true));
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->logger->error('Failed to generate UCP manifest', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    /**
     * Encode manifest data to JSON
     *
     * @param array $data
     * @param bool $prettyPrint
     * @return string
     */
    private function encodeManifest(array $data, bool $prettyPrint): string
    {
        if ($prettyPrint) {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return $this->json->serialize($data);
    }

    /**
     * Write manifest file to disk
     *
     * @param string|null $outputPath
     * @param string $content
     * @return string
     */
    private function writeManifestFile(?string $outputPath, string $content): string
    {
        if ($outputPath !== null && $this->isAbsolutePath($outputPath)) {
            return $this->writeToAbsolutePath($outputPath, $content);
        }

        return $this->writeToPubDirectory($outputPath ?? self::DEFAULT_OUTPUT_PATH, $content);
    }

    /**
     * Check if path is absolute
     *
     * @param string $path
     * @return bool
     */
    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Write content to absolute path
     *
     * @param string $path
     * @param string $content
     * @return string
     */
    private function writeToAbsolutePath(string $path, string $content): string
    {
        $directory = $this->fileDriver->getParentDirectory($path);

        if (!$this->fileDriver->isExists($directory)) {
            $this->fileDriver->createDirectory($directory);
        }

        $this->fileDriver->filePutContents($path, $content);

        return $path;
    }

    /**
     * Write content to pub directory
     *
     * @param string $relativePath
     * @param string $content
     * @return string
     */
    private function writeToPubDirectory(string $relativePath, string $content): string
    {
        $pubDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::PUB);

        $directory = $this->fileDriver->getParentDirectory($relativePath);
        if ($directory && $directory !== '.') {
            $pubDirectory->create($directory);
        }

        $pubDirectory->writeFile($relativePath, $content);

        return $pubDirectory->getAbsolutePath($relativePath);
    }
}
