<?php

namespace Ochorocho\SantasLittleHelper\Commands;

use Ochorocho\SantasLittleHelper\Service\GerritService;
use Ochorocho\SantasLittleHelper\Service\GitService;
use Ochorocho\SantasLittleHelper\Service\HookService;
use Ochorocho\SantasLittleHelper\Service\PathService;
use Ochorocho\SantasLittleHelper\Validator\UserValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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
        $gitService = new GitService($output, $input->getArgument('repository'));
        $gitService->cloneRepository($input->getArgument('repository'), (bool)$input->getOption('clone-new'));
        $gitService->checkoutBranch($input->getArgument('branch'));

        // Validate Gerrit/my.typo3.org username
        if (getenv('SLH_USERNAME')) {
            $userData = (new GerritService())->getGerritUserData(getenv('SLH_USERNAME'));
        } else {
            $validator = new UserValidator();
            $userData = $io->ask('What is your TYPO3/Gerrit Account Username? ', null, $validator->username());
        }

        $gitService->setGitConfig($userData);

        // Enable Commit Hooks
        $hookService = new HookService($io, $output);
        $hookService->enable();

        return Command::SUCCESS;
    }
}