<?php

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
            ->addOption('clone-new', 'cn', InputOption::VALUE_NONE, 'Do not use the repository cache. Clone the entire repository.');
        $this->setHelp('Download and install TYPO3 core.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->logger = ConsoleLoggerFactory::create($io);

        // Download repository and checkout branch
        $gitService = new GitService($this->logger, $input->getArgument('repository'), $input->getArgument('target-folder'), $input->getOption('no-interaction'));
        $gitService->cloneRepository($input->getArgument('repository'), (bool)$input->getOption('clone-new'));
        $gitService->checkoutBranch($input->getArgument('branch'));

        // Get Gerrit/my.typo3.org username
        $userData = $this->getUserData($io);
        $gitService->setGitConfig($userData);

        // Validate Gerrit/my.typo3.org username
        if (getenv('SLH_COMMIT_TEMPLATE')) {
            $commitTemplatePath = getenv('SLH_COMMIT_TEMPLATE');
        } else {
            $pathService = new PathService();
            $templatePath = $pathService->getConfigFolder() . '/gitmessage.txt';
            $commitTemplatePath = $io->ask('Set TYPO3 commit message template?', $templatePath);
        }

        // Create a commit message template if
        // the target file path does not exist
        if (!is_file($commitTemplatePath)) {
            $createTemplate = $io->confirm('The commit message template file does not exist, do you want me to create it?', $commitTemplatePath);
            if ($createTemplate) {
                $gitService->createCommitTemplate($commitTemplatePath);
            }
        }
        $gitService->setCommitTemplate($commitTemplatePath);

        // Enable Commit Hooks
        $force = (bool)(getenv('SLH_HOOK_CREATE') ?: false);
        $answer = $force || $io->confirm('Setup "Commit Message" and "Pre Commit" hook?');

        if ($answer) {
            try {
                $hookService = new HookService($this->logger);
                $hookService->create($input->getArgument('target-folder'));
            } catch (FileNotFoundException|IOException $e) {
                $io->error('Could not create Hooks: ' . $e->getMessage());
            }
        }

        // Create composer.json
        $this->prepareComposerProject($input->getArgument('target-folder'));

        // Run TYPO3 setup command
        $command = new CommandService($output, $this->pathService, $input->getArgument('target-folder'));
        $command->setup();
        $command->styleguideGenerate();

        $this->logger->notice('🧟 Happy days ... TYPO3 Composer CoreDev Setup done!');
        // @todo: sort methods - gather data first, then process all at once
        // @todo: Fix git issue when repo does already exist. Do not clone.

        return Command::SUCCESS;
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
        } catch (\JsonException|\Exception $e) {
            $this->logger->error('Could not prepare composer.json file: ' . $e->getMessage());
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function getUserData(SymfonyStyle $io): mixed
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
