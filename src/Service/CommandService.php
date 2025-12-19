<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

readonly class CommandService
{
    private string $targetFolder;

    public function __construct(private OutputInterface $output, private PathService $pathService, string $targetFolder)
    {
        $this->targetFolder = $targetFolder;
    }

    /**
     * Run TYPO3 setup command
     */
    public function setup(): void
    {
        $this->run([
            'php-cli',
            './vendor/bin/typo3', // @todo: should be configurable for composer/non-composer
            'setup',
            '--driver',
            'sqlite',
            '--admin-username',
            'admin', // @todo: display after setup
            '--admin-user-password',
            'Password.1', // @todo: display after setup
            '--project-name',
            'TYPO3 Core',
            '--server-type',
            'other',
            '--admin-email',
            'admin@example.com',
            '--force',
            '--create-site',
            'no',
        ]);
    }

    public function styleguideGenerate(): void
    {
        $this->run([
            'php-cli',
            './vendor/bin/typo3', // @todo: should be configurable for composer/non-composer
            'styleguide:generate',
            '--create',
        ]);
    }

    /**
     * @param array<mixed> $arguments
     */
    private function run(array $arguments): void
    {
        $this->pathService->pharExtractFileToConfigBin('bin/frankenphp');
        $phpBinary = $this->pathService->getConfigFolder() . '/bin/frankenphp';
        $process = new Process(
            [$phpBinary, ...$arguments,
            ],
            $this->targetFolder
        );
        $process->setTty(Process::isTtySupported());
        $process->setTimeout(null);
        $process->start();

        foreach ($process->getIterator() as $data) {
            $this->output->write($data);
        }
    }
}
