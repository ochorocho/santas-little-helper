<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

use Composer\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Finder\Finder;

class ComposerService extends BaseService
{
    protected Application $composerApplication;
    public function __construct(protected ConsoleLogger $logger, private readonly string $targetFolder)
    {
        $this->composerApplication = new Application();
        $this->composerApplication->setAutoExit(false);
        $this->composerApplication->setCatchExceptions(false);

        parent::__construct($logger);
    }

    /**
     * @throws \Exception
     */
    public function init(): void
    {
        $composerInput = new StringInput('init --name typo3/contribution --description "TYPO3 Core Contribution" --type project -n -d ' . $this->targetFolder);
        $this->runComposerCommand($composerInput);
    }

    /**
     * @throws \Exception
     */
    public function setLocalCoreRepository(): void
    {
        // Add local composer repository
        $composerInput = new StringInput('config repositories.typo3-core-packages path "' . BaseService::CORE_REPO_CACHE . '/typo3/sysext/*" -d ' . $this->targetFolder);
        $this->runComposerCommand($composerInput);

        // Allow plugins
        $composerInput = new StringInput('config allow-plugins.typo3/class-alias-loader true -d ' . $this->targetFolder);
        $this->runComposerCommand($composerInput);

        $composerInput = new StringInput('config allow-plugins.typo3/cms-composer-installers true -d ' . $this->targetFolder);
        $this->runComposerCommand($composerInput);
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    public function requireAllCorePackages(): void
    {
        $finder = new Finder();
        $jsonFiles = $finder->files()
            ->in($this->targetFolder . '/' . BaseService::CORE_REPO_CACHE . '/typo3/sysext/')
            ->depth(1)
            ->name('composer.json');

        $packages = [];
        foreach ($jsonFiles as $jsonFile) {
            $json = json_decode(file_get_contents($jsonFile->getRealPath()), true, 512, JSON_THROW_ON_ERROR);
            $packages[] = $json['name'] . ':@dev';
        }

        $composerInput = new StringInput('require ' . implode(' ', $packages) . ' -d ' . $this->targetFolder);
        $this->runComposerCommand($composerInput);
    }

    /**
     * Run composer command and suppress output
     *
     * @throws \Exception
     */
    private function runComposerCommand(StringInput $input): int
    {
        $output = new BufferedOutput();
        $exitCode = $this->composerApplication->run($input, $output);

        if ($exitCode !== 0) {
            $this->logger->error($output->fetch());
        }

        return $exitCode;
    }
}
