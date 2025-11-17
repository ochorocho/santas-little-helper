<?php

namespace Ochorocho\SantasLittleHelper\Commands;

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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(name: 'core:setup', description: 'Set up TYPO3 core.')]
class CoreComposer extends Command
{
    protected PathService $pathService;

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

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Download repository and checkout branch
        $gitService = new GitService($output, $input->getArgument('repository'), $input->getArgument('target-folder'));
        $gitService->cloneRepository($input->getArgument('repository'), (bool)$input->getOption('clone-new'));
        $gitService->checkoutBranch($input->getArgument('branch'));

        $validator = new SetupValidator();

        // Validate Gerrit/my.typo3.org username
        if (getenv('SLH_USERNAME')) {
            $userData = (new GerritService())->getGerritUserData(getenv('SLH_USERNAME'));
        } else {
            $userData = $io->ask('What is your TYPO3/Gerrit Account Username? ', null, $validator->username());
        }

        $gitService->setGitConfig($userData);

        // Validate Gerrit/my.typo3.org username
        if (getenv('SLH_COMMIT_TEMPLATE')) {
            $commitTemplatePath = getenv('SLH_COMMIT_TEMPLATE');
        } else {
            $pathService = new PathService();
            $templatePath = $pathService->getConfigFolder() . '/gitmessage.txt';

            $commitTemplatePath = $io->ask('Set TYPO3 commit message template?', $templatePath);
        }

        if(!is_file($commitTemplatePath)) {
            $createTemplate = $io->confirm('The commit message template file does not exist, do you want me to create it?', $commitTemplatePath);
            if ($createTemplate) {
                $gitService->createCommitTemplate($commitTemplatePath);
            }
        }

        $gitService->setCommitTemplate($commitTemplatePath);

        // Enable Commit Hooks
        $hookService = new HookService($output, $input->getArgument('target-folder'));
        $force = (bool)(getenv('SLH_HOOK_CREATE') ?: false);
        foreach ($hookService->getHookQuestions() as $question) {
            if ($force) {
                $answer = true;
            } else {
                $answer = $io->confirm($question['message'], $question['default']);
            }

            if ($answer) {
                $method = $question['method'];
                $hookService->$method();
            }
        }

        // Require packages
        $composerService = new ComposerService($output, $input->getArgument('target-folder'));
        $composerService->init();
        $composerService->setLocalCoreRepository();
        $composerService->requireAllCorePackages();

        // Run TYPO3 setup
        $phpBinary = $this->pathService->getConfigFolder() . '/bin/frankenphp';
        $this->pathService->pharExtractFileToConfigBin('bin/frankenphp');
        // @todo: Verify if this is working for args and options!
        $process = new Process(
            [$phpBinary,
                'php-cli',
                './vendor/bin/typo3',
                'setup',
                '--driver',
                'sqlite',
                '--admin-username',
                'admin',
                '--admin-user-password',
                'Password.1',
                '--project-name',
                'TYPO3 Core',
                '--server-type',
                'other',
                '--admin-email',
                'admin@example.com',
                '--force',
                '--create-site',
                'no',
            ], $input->getArgument('target-folder'));
        $process->setTty(true);
        $output->writeln(['<comment>Running command:</comment>', $process->getCommandLine(), '']);
        $process->setTimeout(null);
        $process->start();

        foreach ($process->getIterator() as $data) {
            $output->write($data);
        }

        // @todo: Initialize styleguide
        // @todo: Fix git issue when repo does already exist. Do not clone.

        return Command::SUCCESS;
    }
}