<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

use Composer\Script\Event;
use Composer\Util\ProcessExecutor;
use Ochorocho\SantasLittleHelper\Logger\ConsoleLogger;
use Ochorocho\SantasLittleHelper\Validator\SetupValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class GitService extends BaseService
{
    private ConsoleLogger $logger;
    private PathService $pathService;
    private Filesystem $fileSystem;

    private string $git;

    public function __construct(readonly OutputInterface $output, private readonly string $repository, private readonly string $targetFolder)
    {
        $this->logger = new ConsoleLogger($output);
        $this->git = (new ExecutableFinder())->find('git');
        $this->pathService = new PathService();

        $this->fileSystem = new Filesystem();
        $this->fileSystem->mkdir([$this->targetFolder]);
    }

    public function setGitConfig(array $userData): void
    {
        $pushUrl = 'ssh://' . $userData['username'] . '@' . $this->reviewServer;
        $this->setGitConfigValue('remote.origin.pushurl', $pushUrl);
        $this->setGitConfigValue('user.name', $userData['display_name'] ?? $userData['name'] ?? $userData['username']);
        $this->setGitConfigValue('user.email', $userData['email']);
    }

    public function setCommitTemplate(string $path)
    {
        $template = realpath($path);

        if ($this->setGitConfigValue('commit.template', $template)) {
            $this->logger->error('<error>Could not enable Git Commit Template!</error>');
        } else {
            $this->logger->out('<info>Set "commit.template" to ' . $template . ' </info>');
        }
    }

    public function createCommitTemplate(string $path)
    {
        $content = <<<EOF
[BUGFIX|TASK|FEATURE|DOCS]

Resolves: #
Releases: main        
EOF;

        try {
            $this->fileSystem->dumpFile($path, $content);
        } catch (IOException $e) {
            $this->logger->error('<error>Failed to create the commit template ' . $path . '</error>');
        }
    }

    // @todo: check if this makes still sense
    public function applyPatch(string $ref): int
    {
        if (empty($ref)) {
            $this->logger->error('<warning>No patch ref given</warning>');
            return Command::FAILURE;
        }

        if ($this->fileSystem->exists(self::CORE_REPO_CACHE)) {
            $process = new Process(['git', 'fetch', 'https://review.typo3.org/Packages/TYPO3.CMS ' . $ref , '&&', 'git', 'cherry-pick', 'FETCH_HEAD'], self::CORE_REPO_CACHE);
            $this->logger->out('<info>Apply patch ' . $ref . '</info>');
            $process->run();

            if (!$process->isSuccessful()) {
                $this->logger->error('<warning>Could not apply patch ' . $ref . ' </warning>');
                return Command::FAILURE;
            }
        } else {
            $this->logger->error('Could not apply patch, repository does not exist.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Clone the TYPO3 git repository and create a copy
     * in the config directory to speed up future repository downloads.
     *
     * This means:
     *  - First clone will download the entire repository
     *  - All following clones will just copy and update the
     *    existing repository unless $ignoreCache is not set.
     *
     * @param string $url The repositor to clone from
     * @param bool $ignoreCache Ignore the copy created in the config directory
     * @return void
     */
    public function cloneRepository(string $url, bool $ignoreCache = false): void
    {
        $repoTargetPath = $ignoreCache ? $this->targetFolder . '/' . self::CORE_REPO_CACHE : $this->pathService->getConfigFolder() . '/' . self::CORE_REPO_CACHE;

        if (!$this->fileSystem->exists($repoTargetPath)) {
            $process = $this->cloneRepositoryToProjectFolder($url, $repoTargetPath);

            if (!$process->isSuccessful()) {
                $this->logger->warning('<warning>Could not download git repository ' . $url . ' </warning>');
            }
        }

        if(!$ignoreCache) {
            // Use the existing local repository and clone from there
            $this->pull($repoTargetPath);

            if(!is_file($this->targetFolder . '/' . self::CORE_REPO_CACHE)) {
                $this->logger->out('Using local clone for core setup');
                $process = $this->cloneRepositoryToProjectFolder($repoTargetPath, $this->targetFolder . '/' . self::CORE_REPO_CACHE);

                if ($process->isSuccessful()) {
                    $this->setGitConfigValue('remote.origin.url', $this->repository);
                }
            }
        }
    }

    public function checkoutBranch(string $branch): int
    {
        if (empty($branch)) {
            $this->logger->error(('<warning>No branch name given</warning>'));
            return Command::FAILURE;
        }

        $process = new Process([$this->git, 'checkout', $branch], $this->targetFolder . '/' . self::CORE_REPO_CACHE);
        $process->setTty(true);
        $process->setTimeout(null);
        $this->logger->command([$process->getCommandLine()]);
        $process->run();

        $this->logger->info('<info>Checking out branch "' . $branch . '"!</info>');
        if (!$process->isSuccessful()) {
            $this->logger->error('<warning>Could not checkout branch ' . $branch . ' </warning>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    public function pull(string $folder): void
    {
        var_dump($folder);
        $process = new Process([$this->git, 'pull'], $folder);
        $process->setTty(true);
        $process->setTimeout(null);
        $this->logger->command([$process->getCommandLine(), '']);
        $process->run();
    }

    private function setGitConfigValue(string $config, string $value): int
    {
        $process = new Process([$this->git, 'config', $config, $value], $this->targetFolder);
        $process->setTty(true);
        $process->setTimeout(null);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->logger->error('<error>Could not set "' . $config . '" to "' . $value . '"</error>');
            return Command::FAILURE;
        }

        $this->logger->out('<info>Set "' . $config . '" to "' . $value . '"</info>');
        return Command::SUCCESS;
    }

    /**
     * @param string $url
     * @param string $repoTargetPath
     * @return Process
     */
    public function cloneRepositoryToProjectFolder(string $url, string $repoTargetPath): Process
    {
        $process = new Process([$this->git, 'clone', $url, $repoTargetPath]);
        $process->setTty(true);
        $process->setTimeout(null);
        $this->logger->command([$process->getCommandLine(), '']);
        $this->logger->out('<info>Cloning TYPO3 repository. This may take a while depending on your internet connection!</info>');
        $process->run();
        return $process;
    }
}
