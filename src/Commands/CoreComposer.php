<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Commands;

use Ochorocho\SantasLittleHelper\Factory\ConsoleLoggerFactory;
use Ochorocho\SantasLittleHelper\Service\CommandService;
use Ochorocho\SantasLittleHelper\Service\ComposerService;
use Ochorocho\SantasLittleHelper\Service\GerritService;
use Ochorocho\SantasLittleHelper\Service\GitService;
use Ochorocho\SantasLittleHelper\Service\HookService;
use Ochorocho\SantasLittleHelper\Service\PathService;
use Ochorocho\SantasLittleHelper\Validator\SetupValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(name: 'core:setup', description: 'Set up TYPO3 core.')]
class CoreComposer extends Command
{
    protected PathService $pathService;
    protected ConsoleLogger $logger;

    public function __construct(?string $name = null)
    {
        $this->pathService = new PathService();
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('target-folder', InputArgument::REQUIRED, 'The target folder to create the project')
            ->addArgument('repository', InputArgument::OPTIONAL, 'Repository', 'https://github.com/TYPO3/typo3.git')
            ->addArgument('branch', InputArgument::OPTIONAL, 'Branch name', 'main')
            ->addOption('clone-new', 'cn', InputOption::VALUE_NONE, 'Do not use the repository cache. Clone the entire repository.')
            ->addOption('cache-only', null, InputOption::VALUE_NONE, 'Only warm the repository cache, skip full setup');
        $this->setHelp('Download and install TYPO3 core.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->logger = ConsoleLoggerFactory::create($io);
        $targetFolder = $input->getArgument('target-folder');

        $gitService = new GitService($this->logger, $input->getArgument('repository'), $targetFolder);

        if (!$gitService->cloneRepository($input->getArgument('repository'), (bool)$input->getOption('clone-new'))) {
            $io->error('Failed to clone the TYPO3 repository.');
            return Command::FAILURE;
        }
        $gitService->checkoutBranch($input->getArgument('branch'));

        if ($input->getOption('cache-only')) {
            $this->logger->notice('Repository cache warmed successfully.');
            return Command::SUCCESS;
        }

        // Get Gerrit/my.typo3.org username
        $userData = $this->getUserData($io);
        $gitService->setGitConfig($userData);

        $this->configureCommitTemplate($io, $gitService);
        $this->configureHooks($io, $targetFolder);
        $this->prepareComposerProject($targetFolder);
        $this->runTypo3Setup($output, $targetFolder);

        $this->logger->notice('Happy days ... TYPO3 Composer CoreDev Setup done!');

        return Command::SUCCESS;
    }

    private function configureCommitTemplate(SymfonyStyle $io, GitService $gitService): void
    {
        if (getenv('SLH_COMMIT_TEMPLATE')) {
            $commitTemplatePath = getenv('SLH_COMMIT_TEMPLATE');
        } else {
            $templatePath = $this->pathService->getConfigFolder() . '/gitmessage.txt';
            $commitTemplatePath = $io->ask('Set TYPO3 commit message template?', $templatePath);
        }

        if (!is_file($commitTemplatePath)) {
            $createTemplate = $io->confirm('The commit message template file does not exist, do you want me to create it?', true);
            if ($createTemplate) {
                $gitService->createCommitTemplate($commitTemplatePath);
            }
        }
        $gitService->setCommitTemplate($commitTemplatePath);
    }

    private function configureHooks(SymfonyStyle $io, string $targetFolder): void
    {
        $force = (bool)(getenv('SLH_HOOK_CREATE') ?: false);
        $answer = $force || $io->confirm('Setup "Commit Message" and "Pre Commit" hook?');

        if ($answer) {
            try {
                $hookService = new HookService($this->logger);
                $hookService->create($targetFolder);
            } catch (FileNotFoundException|IOException $e) {
                $io->error('Could not create Hooks: ' . $e->getMessage());
            }
        }
    }

    private function runTypo3Setup(OutputInterface $output, string $targetFolder): void
    {
        $command = new CommandService($output, $this->pathService, $targetFolder);
        $command->setup();
        $command->styleguideGenerate();
    }

    /**
     * Initialize composer.json, required all packages from sysext/*
     */
    private function prepareComposerProject(string $target): void
    {
        try {
            $composerService = new ComposerService($this->logger, $target);
            $composerService->init();
            $composerService->setLocalCoreRepository();
            $composerService->requireAllCorePackages();
        } catch (\Exception $e) {
            $this->logger->error('Could not prepare composer.json file: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    private function getUserData(SymfonyStyle $io): array
    {
        $validator = new SetupValidator();
        if (getenv('SLH_USERNAME')) {
            $userData = (new GerritService())->getGerritUserData(getenv('SLH_USERNAME'));
        } else {
            $userData = $io->ask('What is your TYPO3/Gerrit Account Username? ', null, $validator->username());
        }
        return $userData;
    }
}
