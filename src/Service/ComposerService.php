<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

use Composer\Console\Application;
use Ochorocho\SantasLittleHelper\Logger\ConsoleLogger;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ComposerService extends BaseService
{
    protected ConsoleLogger $logger;
    protected Application $composerApplication;
    public function __construct(private readonly OutputInterface $output, private readonly string $targetFolder)
    {
        $this->logger = new ConsoleLogger($this->output);

        $this->composerApplication = new Application();
        $this->composerApplication->setAutoExit(false);
        $this->composerApplication->setCatchExceptions(false);
    }

    public function init(): void
    {
        $composerInput = new StringInput('init --name typo3/contribution --description "TYPO3 Core Contribution" --type project -n -d ' . $this->targetFolder);
        $this->composerApplication->run($composerInput, $this->output);
    }

    public function setLocalCoreRepository(): void
    {
        $composerInput = new StringInput('config repositories.typo3-core-packages path "' . BaseService::CORE_REPO_CACHE . '/typo3/sysext/*" -d ' . $this->targetFolder);
        $this->composerApplication->run($composerInput, $this->output);

        // Allow plugins
        $composerInput = new StringInput('config allow-plugins.typo3/class-alias-loader true -d ' . $this->targetFolder);
        $this->composerApplication->run($composerInput, $this->output);

        $composerInput = new StringInput('config allow-plugins.typo3/cms-composer-installers true -d ' . $this->targetFolder);
        $this->composerApplication->run($composerInput, $this->output);
    }

    public function requireAllCorePackages(): void
    {
        $finder = new Finder();
        $jsonFiles = $finder->files()
            ->in($this->targetFolder . '/' . BaseService::CORE_REPO_CACHE . '/typo3/sysext/')
            ->depth(1)
            ->name('composer.json');

        $packages = [];
        foreach ($jsonFiles as $jsonFile) {
            $json = json_decode(file_get_contents($jsonFile->getRealPath()), true);
            $packages[] = $json['name'] . ':@dev';
        }

        $composerInput = new StringInput('require ' . implode(' ', $packages) . ' -d ' . $this->targetFolder);
        $this->composerApplication->run($composerInput, $this->output);
    }
}
